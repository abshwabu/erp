import apiClient from './client'
import type {
  Product,
  ProductFilters,
  StockLevelRow,
  StockMovement,
  StockFilters,
  MovementFilters,
  LowStockItem,
  StockLocation,
  ProductCategory,
  PaginatedResponse,
  PaginationMeta,
  CreateProductPayload,
} from '@/types/inventory'

function normalizeMeta(meta: any): PaginationMeta {
  return {
    current_page: Number(meta?.current_page ?? meta?.currentPage ?? 1),
    last_page: Number(meta?.last_page ?? meta?.lastPage ?? 1),
    per_page: Number(meta?.per_page ?? meta?.perPage ?? 15),
    total: Number(meta?.total ?? 0),
    from: meta?.from ?? null,
    to: meta?.to ?? null,
  }
}

function asPaginated<T>(payload: any): PaginatedResponse<T> {
  const data = Array.isArray(payload?.data) ? payload.data : []
  return {
    data,
    meta: normalizeMeta(payload?.meta),
    links: payload?.links,
  }
}

export const inventoryApi = {
  getProducts(filters: ProductFilters = {}, page = 1) {
    return apiClient
      .get('/inventory/products', {
        params: {
          page,
          search: filters.search || undefined,
          category_id: filters.category_id || undefined,
          status: filters.status || undefined,
          type: filters.type || undefined,
        },
      })
      .then((res) => ({ ...res, data: asPaginated<Product>(res.data) }))
  },

  getProduct(id: string) {
    return apiClient.get<{ data: Product }>(`/inventory/products/${id}`)
  },

  createProduct(data: CreateProductPayload) {
    return apiClient.post<{ data: Product }>('/inventory/products', data)
  },

  updateProduct(id: string, data: Partial<CreateProductPayload>) {
    return apiClient.put<{ data: Product }>(`/inventory/products/${id}`, data)
  },

  deleteProduct(id: string) {
    return apiClient.delete(`/inventory/products/${id}`)
  },

  getProductStock(id: string) {
    return apiClient.get<{ data: StockLevelRow[] }>(`/inventory/stock/${id}`).then((res) => {
      const data = Array.isArray(res.data?.data) ? res.data.data : []
      return { ...res, data }
    })
  },

  getStockSummary(filters: StockFilters = {}, page = 1) {
    return apiClient
      .get('/inventory/stock', {
        params: {
          page,
          search: filters.search || undefined,
          location_id: filters.location_id || undefined,
          low_stock: filters.low_stock ? 1 : undefined,
          category_id: filters.category_id || undefined,
        },
      })
      .then((res) => ({ ...res, data: asPaginated<Product>(res.data) }))
  },

  getLocations() {
    return apiClient.get<{ data: StockLocation[] }>('/inventory/locations').then((res) => {
      const data = Array.isArray(res.data?.data) ? res.data.data : (Array.isArray(res.data) ? res.data : [])
      return { ...res, data: data as StockLocation[] }
    })
  },

  createStockAdjustment(data: {
    product_id: string
    location_id: string
    quantity: number
    type: 'add' | 'remove' | 'set'
    reason?: string
    notes?: string
    variant_id?: string
  }) {
    return apiClient.post('/inventory/stock/adjustments', data)
  },

  getStockMovements(filters: MovementFilters = {}, page = 1) {
    return apiClient
      .get('/inventory/stock/movements', {
        params: {
          page,
          search: filters.search || undefined,
          product_id: filters.product_id || undefined,
          location_id: filters.location_id || undefined,
          type: filters.type || undefined,
          start_date: filters.start_date || undefined,
          end_date: filters.end_date || undefined,
        },
      })
      .then((res) => ({ ...res, data: asPaginated<StockMovement>(res.data) }))
  },

  getLowStockProducts() {
    return apiClient.get<{ data: LowStockItem[] }>('/inventory/stock/low').then((res) => {
      const data = Array.isArray(res.data?.data) ? res.data.data : []
      return { ...res, data }
    })
  },

  importProducts(file: File) {
    const formData = new FormData()
    formData.append('file', file)
    return apiClient.post<{ jobId?: string; message?: string }>('/inventory/products/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },

  getCategories() {
    return apiClient.get<{ data: ProductCategory[] }>('/inventory/categories').then((res) => {
      const data = Array.isArray(res.data?.data) ? res.data.data : (Array.isArray(res.data) ? res.data : [])
      return { ...res, data: data as ProductCategory[] }
    })
  },

  createCategory(data: { name: string; slug?: string; description?: string; parent_id?: string }) {
    return apiClient.post<{ data: ProductCategory }>('/inventory/categories', data)
  },

  updateCategory(id: string, data: Partial<{ name: string; description?: string }>) {
    return apiClient.put(`/inventory/categories/${id}`, data)
  },

  uploadMedia(file: File) {
    const formData = new FormData()
    formData.append('file', file)
    return apiClient.post<{ data: { path: string; url: string; name: string } }>('/inventory/media/upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },

  uploadProductImage(productId: string, file: File, isPrimary = false) {
    const formData = new FormData()
    formData.append('file', file)
    if (isPrimary) formData.append('is_primary', '1')
    return apiClient.post<{ data: any }>(`/inventory/products/${productId}/images`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },

  deleteProductImage(productId: string, imageId: string) {
    return apiClient.delete(`/inventory/products/${productId}/images/${imageId}`)
  },

  deleteCategory(id: string) {
    return apiClient.delete(`/inventory/categories/${id}`)
  },
}
