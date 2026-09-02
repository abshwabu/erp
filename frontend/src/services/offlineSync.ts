import { ref, computed } from 'vue'
import axios from 'axios'
import { offlineStorage, type OutboxItem } from './offlineStorage'

const isOnline = ref<boolean>(typeof window !== 'undefined' ? window.navigator.onLine : true)
const isSyncing = ref<boolean>(false)
const pendingCount = ref<number>(0)
const lastSyncTime = ref<number | null>(null)
const syncErrors = ref<Array<{ id: string; title: string; error: string }>>([])

// Dedicated raw axios instance for sync execution to bypass offline interceptor loop
const rawSyncClient = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api',
  timeout: 30000,
})

class OfflineSyncService {
  private initialized = false

  public init() {
    if (this.initialized || typeof window === 'undefined') return
    this.initialized = true

    // Listen to browser network changes
    window.addEventListener('online', () => {
      isOnline.value = true
      console.log('[OfflineSync] Network connected! Triggering automatic sync...')
      this.syncAll()
    })

    window.addEventListener('offline', () => {
      isOnline.value = false
      console.warn('[OfflineSync] Device is now OFFLINE. Offline mode enabled.')
    })

    // Initial count
    this.refreshPendingCount()

    // Periodic heartbeat sync every 25 seconds if online and items exist
    setInterval(() => {
      if (isOnline.value && pendingCount.value > 0 && !isSyncing.value) {
        this.syncAll()
      }
    }, 25000)
  }

  public async refreshPendingCount(): Promise<number> {
    try {
      const count = await offlineStorage.getOutboxCount()
      pendingCount.value = count
      return count
    } catch {
      return 0
    }
  }

  public async syncAll(): Promise<{ successCount: number; failCount: number }> {
    if (isSyncing.value) {
      return { successCount: 0, failCount: 0 }
    }

    if (!isOnline.value) {
      console.log('[OfflineSync] Cannot sync while offline.')
      return { successCount: 0, failCount: 0 }
    }

    isSyncing.value = true
    syncErrors.value = []
    let successCount = 0
    let failCount = 0

    try {
      const items = await offlineStorage.getPendingOutbox()
      if (items.length === 0) {
        pendingCount.value = 0
        return { successCount: 0, failCount: 0 }
      }

      console.log(`[OfflineSync] Syncing ${items.length} pending offline transactions...`)

      for (const item of items) {
        const success = await this.syncItem(item)
        if (success) {
          successCount++
        } else {
          failCount++
        }
      }

      lastSyncTime.value = Date.now()
      await this.refreshPendingCount()

      // Broadcast sync event for reactive views to refresh their queries
      window.dispatchEvent(
        new CustomEvent('erp:offline-synced', {
          detail: { successCount, failCount },
        })
      )
    } finally {
      isSyncing.value = false
    }

    return { successCount, failCount }
  }

  private getSafeStorageItem(key: string): string | null {
    if (typeof window === 'undefined') return null
    const raw = localStorage.getItem(key)
    if (!raw) return null
    try {
      const parsed = JSON.parse(raw)
      return typeof parsed === 'string' ? parsed : String(parsed)
    } catch {
      return raw
    }
  }

  private async syncItem(item: OutboxItem): Promise<boolean> {
    try {
      item.status = 'syncing'
      await offlineStorage.updateOutboxItem(item)

      // Retrieve current tokens safely from localStorage
      const token = this.getSafeStorageItem('access_token')
      const tenantId = this.getSafeStorageItem('tenant_id')

      const headers: Record<string, string> = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Offline-Synced': 'true',
        'X-Idempotency-Key': item.id,
      }

      // Safely copy non-auth headers from the original request
      if (item.headers && typeof item.headers === 'object') {
        for (const [k, v] of Object.entries(item.headers)) {
          if (
            typeof v === 'string' &&
            !['authorization', 'x-tenant-id', 'host', 'content-length'].includes(k.toLowerCase())
          ) {
            headers[k] = v
          }
        }
      }

      if (token) {
        headers['Authorization'] = `Bearer ${token}`
      }
      if (tenantId) {
        headers['X-Tenant-ID'] = tenantId
      }

      // Execute request
      await rawSyncClient.request({
        url: item.url,
        method: item.method,
        data: item.data,
        headers,
      })

      // On success, permanently remove from outbox
      await offlineStorage.removeOutboxItem(item.id)
      console.log(`[OfflineSync] Successfully synced: ${item.method} ${item.url} (${item.id})`)
      return true
    } catch (err: any) {
      console.error(`[OfflineSync] Failed to sync ${item.id}:`, err?.message)
      item.retryCount = (item.retryCount || 0) + 1
      item.status = 'failed'
      item.error = err?.response?.data?.message || err?.message || 'Network error'
      await offlineStorage.updateOutboxItem(item)

      syncErrors.value.push({
        id: item.id,
        title: item.title || `${item.method} ${item.url}`,
        error: item.error || 'Sync failure',
      })

      // If server explicitly returned 422 or 400 validation error, we remove it to avoid blocking the queue
      if (err?.response?.status && err.response.status >= 400 && err.response.status < 500 && err.response.status !== 401) {
        console.warn(`[OfflineSync] Discarding invalid transaction ${item.id}: ${item.error}`)
        await offlineStorage.removeOutboxItem(item.id)
      }

      return false
    }
  }
}

export const offlineSyncService = new OfflineSyncService()

export function useOfflineSync() {
  return {
    isOnline: computed(() => isOnline.value),
    isSyncing: computed(() => isSyncing.value),
    pendingCount: computed(() => pendingCount.value),
    lastSyncTime: computed(() => lastSyncTime.value),
    syncErrors: computed(() => syncErrors.value),
    syncAll: () => offlineSyncService.syncAll(),
    refreshPendingCount: () => offlineSyncService.refreshPendingCount(),
  }
}
