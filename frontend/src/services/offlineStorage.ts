/**
 * Offline IndexedDB Storage Service
 * Manages local client database for offline caching and outbox mutation queue.
 */

export interface OutboxItem {
  id: string
  url: string
  method: 'POST' | 'PUT' | 'PATCH' | 'DELETE'
  data: any
  headers?: Record<string, string>
  createdAt: number
  retryCount: number
  status: 'pending' | 'syncing' | 'failed'
  error?: string
  title?: string
  entityType?: string
}

export interface CachedQuery {
  url: string
  data: any
  cachedAt: number
}

const DB_NAME = 'erp_offline_db'
const DB_VERSION = 2

class OfflineStorage {
  private db: IDBDatabase | null = null
  private initPromise: Promise<IDBDatabase> | null = null

  public async getDb(): Promise<IDBDatabase> {
    if (this.db) return this.db
    if (this.initPromise) return this.initPromise

    this.initPromise = new Promise<IDBDatabase>((resolve, reject) => {
      const request = indexedDB.open(DB_NAME, DB_VERSION)

      request.onupgradeneeded = (event) => {
        const db = (event.target as IDBOpenDBRequest).result

        // 1. Cached GET responses
        if (!db.objectStoreNames.contains('queries')) {
          db.createObjectStore('queries', { keyPath: 'url' })
        }

        // 2. Outbox pending mutations (POST/PUT/PATCH/DELETE)
        if (!db.objectStoreNames.contains('outbox')) {
          const outboxStore = db.createObjectStore('outbox', { keyPath: 'id' })
          outboxStore.createIndex('createdAt', 'createdAt', { unique: false })
          outboxStore.createIndex('status', 'status', { unique: false })
        }

        // 3. Offline Collections (Products, Contacts, Invoices, POS Orders)
        if (!db.objectStoreNames.contains('collections')) {
          const collStore = db.createObjectStore('collections', { keyPath: 'id' })
          collStore.createIndex('type', 'type', { unique: false })
        }
      }

      request.onsuccess = () => {
        this.db = request.result
        resolve(this.db)
      }

      request.onerror = () => {
        reject(request.error)
      }
    })

    return this.initPromise
  }

  // ── Query Cache (GET Requests) ──────────────────────────────────────────────

  public async setCachedQuery(url: string, data: any): Promise<void> {
    try {
      const db = await this.getDb()
      const tx = db.transaction('queries', 'readwrite')
      const store = tx.objectStore('queries')
      store.put({
        url,
        data,
        cachedAt: Date.now(),
      })
    } catch (e) {
      console.warn('[OfflineStorage] Failed to cache query:', url, e)
    }
  }

  public async getCachedQuery(url: string): Promise<any | null> {
    try {
      const db = await this.getDb()
      const tx = db.transaction('queries', 'readonly')
      const store = tx.objectStore('queries')

      return await new Promise<any>((resolve) => {
        const req = store.get(url)
        req.onsuccess = () => {
          resolve(req.result ? req.result.data : null)
        }
        req.onerror = () => resolve(null)
      })
    } catch (e) {
      return null
    }
  }

  // ── Outbox (Offline Write Mutations) ────────────────────────────────────────

  public async enqueueOutbox(item: Omit<OutboxItem, 'id' | 'createdAt' | 'retryCount' | 'status'>): Promise<OutboxItem> {
    const db = await this.getDb()
    const outboxItem: OutboxItem = {
      ...item,
      id: 'offline_' + Date.now() + '_' + Math.random().toString(36).substring(2, 9),
      createdAt: Date.now(),
      retryCount: 0,
      status: 'pending',
    }

    const tx = db.transaction('outbox', 'readwrite')
    const store = tx.objectStore('outbox')
    store.put(outboxItem)

    return new Promise((resolve, reject) => {
      tx.oncomplete = () => resolve(outboxItem)
      tx.onerror = () => reject(tx.error)
    })
  }

  public async getPendingOutbox(): Promise<OutboxItem[]> {
    try {
      const db = await this.getDb()
      const tx = db.transaction('outbox', 'readonly')
      const store = tx.objectStore('outbox')
      const index = store.index('createdAt')

      return await new Promise<OutboxItem[]>((resolve) => {
        const req = index.getAll()
        req.onsuccess = () => {
          const items = req.result || []
          resolve(items.filter((i) => i.status === 'pending' || i.status === 'failed'))
        }
        req.onerror = () => resolve([])
      })
    } catch (e) {
      return []
    }
  }

  public async updateOutboxItem(item: OutboxItem): Promise<void> {
    const db = await this.getDb()
    const tx = db.transaction('outbox', 'readwrite')
    const store = tx.objectStore('outbox')
    store.put(item)
  }

  public async removeOutboxItem(id: string): Promise<void> {
    const db = await this.getDb()
    const tx = db.transaction('outbox', 'readwrite')
    const store = tx.objectStore('outbox')
    store.delete(id)
  }

  public async clearOutbox(): Promise<void> {
    const db = await this.getDb()
    const tx = db.transaction('outbox', 'readwrite')
    const store = tx.objectStore('outbox')
    store.clear()
  }

  public async getOutboxCount(): Promise<number> {
    try {
      const db = await this.getDb()
      const tx = db.transaction('outbox', 'readonly')
      const store = tx.objectStore('outbox')

      return await new Promise<number>((resolve) => {
        const req = store.count()
        req.onsuccess = () => resolve(req.result || 0)
        req.onerror = () => resolve(0)
      })
    } catch (e) {
      return 0
    }
  }

  // ── Offline Entity Collections (e.g. Products, Customers) ───────────────────

  public async saveCollectionItems(type: string, items: any[]): Promise<void> {
    try {
      const db = await this.getDb()
      const tx = db.transaction('collections', 'readwrite')
      const store = tx.objectStore('collections')

      for (const item of items) {
        if (item && item.id) {
          store.put({
            id: `${type}_${item.id}`,
            type,
            data: item,
            savedAt: Date.now(),
          })
        }
      }
    } catch (e) {
      console.warn('[OfflineStorage] Error saving collection:', type, e)
    }
  }

  public async getCollectionItems(type: string): Promise<any[]> {
    try {
      const db = await this.getDb()
      const tx = db.transaction('collections', 'readonly')
      const store = tx.objectStore('collections')
      const index = store.index('type')

      return await new Promise<any[]>((resolve) => {
        const req = index.getAll(IDBKeyRange.only(type))
        req.onsuccess = () => {
          const records = req.result || []
          resolve(records.map((r) => r.data))
        }
        req.onerror = () => resolve([])
      })
    } catch (e) {
      return []
    }
  }
}

export const offlineStorage = new OfflineStorage()
