import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import { useTenantStore } from '@/stores/tenant'
import { offlineStorage } from '@/services/offlineStorage'
import { offlineSyncService } from '@/services/offlineSync'

const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

function deriveMutationTitle(config: any): string {
  const url = config.url || ''
  const method = (config.method || 'POST').toUpperCase()

  if (url.includes('/pos/orders') || url.includes('/pos/sale')) return 'POS Order / Checkout'
  if (url.includes('/sales/invoices') || url.includes('/invoices')) return 'Customer Invoice'
  if (url.includes('/inventory/products') || url.includes('/products')) return 'Product Inventory Record'
  if (url.includes('/crm/contacts') || url.includes('/contacts')) return 'CRM Contact'
  if (url.includes('/crm/leads') || url.includes('/leads')) return 'Sales Lead'
  if (url.includes('/hr/employees')) return 'Employee Record'

  return `${method} ${url.replace(/^.*\/api\//, '')}`
}

// Request interceptor
apiClient.interceptors.request.use(
  async (config) => {
    const authStore = useAuthStore()
    const tenantStore = useTenantStore()

    if (authStore.accessToken) {
      config.headers.Authorization = `Bearer ${authStore.accessToken}`
    }

    const tenantId = tenantStore.tenantId || authStore.user?.tenant_id
    if (tenantId) {
      config.headers['X-Tenant-ID'] = tenantId
    }

    return config
  },
  (error) => Promise.reject(error)
)

let refreshPromise: Promise<void> | null = null

// Response interceptor
apiClient.interceptors.response.use(
  (response) => {
    // Asynchronously cache successful GET responses for offline access
    if (response.config.method?.toLowerCase() === 'get' && response.config.url) {
      const cacheKey = response.config.url
      offlineStorage.setCachedQuery(cacheKey, response.data)
    }

    return response
  },
  async (error) => {
    const originalRequest = error.config
    const authStore = useAuthStore()

    const isNetworkError = !error.response || error.code === 'ERR_NETWORK' || error.message?.includes('Network Error')
    const isOffline = typeof window !== 'undefined' && !window.navigator.onLine

    // ── 1. Offline & Network Error Handling ───────────────────────────────────
    if (isNetworkError || isOffline) {
      const method = (originalRequest?.method || 'get').toLowerCase()
      const url = originalRequest?.url || ''
      const isAuthRoute =
        url.includes('/auth/login') ||
        url.includes('/auth/register') ||
        url.includes('/auth/refresh')

      // A) Offline GET: Serve cached query data from IndexedDB
      if (method === 'get') {
        const cached = await offlineStorage.getCachedQuery(url)
        if (cached !== null) {
          console.log(`[OfflineMode] Serving cached data for ${url}`)
          return Promise.resolve({
            data: cached,
            status: 200,
            statusText: 'OK (Offline Cache)',
            headers: { 'x-offline-cache': 'true' },
            config: originalRequest,
          })
        }
      }

      // B) Offline Mutation (POST/PUT/PATCH/DELETE): Enqueue in Outbox & return optimistic response
      if (['post', 'put', 'patch', 'delete'].includes(method) && !isAuthRoute) {
        let payload = originalRequest.data
        if (typeof payload === 'string') {
          try {
            payload = JSON.parse(payload)
          } catch {
            // keep as is
          }
        }

        const outboxItem = await offlineStorage.enqueueOutbox({
          url,
          method: method.toUpperCase() as any,
          data: payload,
          headers: originalRequest.headers ? { ...originalRequest.headers } : undefined,
          title: deriveMutationTitle(originalRequest),
        })

        // Update reactive pending count
        offlineSyncService.refreshPendingCount()

        console.log(`[OfflineMode] Mutation queued in Outbox (${outboxItem.id}): ${method.toUpperCase()} ${url}`)

        // Construct realistic optimistic data for store/view
        const optimisticId = outboxItem.id
        const optimisticResult = {
          id: optimisticId,
          ...(payload || {}),
          _offline: true,
          _offline_queued: true,
          created_at: new Date().toISOString(),
          updated_at: new Date().toISOString(),
        }

        return Promise.resolve({
          data: {
            data: optimisticResult,
            message: 'Transaction saved locally in offline mode. Will automatically sync when reconnected.',
            _offline_queued: true,
          },
          status: 200,
          statusText: 'OK (Offline Queued)',
          headers: { 'x-offline-queued': 'true' },
          config: originalRequest,
        })
      }
    }

    // ── 2. Handle 401 Unauthorized ───────────────────────────────────────────
    const isAuthRoute =
      originalRequest?.url?.includes('/auth/login') ||
      originalRequest?.url?.includes('/auth/register') ||
      originalRequest?.url?.includes('/auth/refresh')

    if (error.response?.status === 401 && !originalRequest._retry && !isAuthRoute) {
      originalRequest._retry = true

      if (!refreshPromise) {
        refreshPromise = authStore
          .refreshAuthToken()
          .finally(() => {
            refreshPromise = null
          })
      }

      try {
        await refreshPromise
        if (authStore.accessToken) {
          originalRequest.headers = originalRequest.headers || {}
          originalRequest.headers.Authorization = `Bearer ${authStore.accessToken}`
        }
        return apiClient(originalRequest)
      } catch (refreshError) {
        await authStore.logout()
        return Promise.reject(refreshError)
      }
    }

    // ── 3. Handle 422 Validation Errors ──────────────────────────────────────
    if (error.response?.status === 422) {
      const errors = error.response.data?.errors
      return Promise.reject({
        status: 422,
        errors,
        message: error.response.data?.message || 'Validation failed',
      })
    }

    return Promise.reject(error)
  }
)

export default apiClient
