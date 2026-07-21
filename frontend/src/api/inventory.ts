import apiClient from './client'
import type { 
  Product, 
  ProductFilters, 
  StockLevel, 
  StockSummary, 
  StockMovement, 
  StockAdjustment,
  InventoryFilters,
  PaginatedResponse 
} from '@/types/inventory'

export const inventoryApi = {
  // Products
  getProducts(filters: ProductFilters = {}, page = 1) {
    return apiClient.get<PaginatedResponse<Product>>('/inventory/products', {
      params: { ...filters, page }
    })
  },

  getProduct(id: number) {
    return apiClient.get<Product>(`/inventory/products/${id}`)
  },

  createProduct(data: Partial<Product>) {
    return apiClient.post<Product>('/inventory/products', data)
  },

  updateProduct(id: number, data: Partial<Product>) {
    return apiClient.put<Product>(`/inventory/products/${id}`, data)
  },

  deleteProduct(id: number) {
    return apiClient.delete(`/inventory/products/${id}`)
  },

  // Stock
  getProductStock(id: number) {
    return apiClient.get<StockLevel[]>(`/inventory/products/${id}/stock`)
  },

  getStockSummary(filters: any = {}, page = 1) {
    return apiClient.get<PaginatedResponse<StockSummary>>('/inventory/stock', {
      params: { ...filters, page }
    })
  },

  getLocations() {
    return apiClient.get('/inventory/locations').then(res => {
      const data = Array.isArray(res.data) ? res.data : (res.data?.data || [])
      return { ...res, data }
    })
  },

  createStockAdjustment(data: any) {
    const payload = {
      product_id: data.product_id || data.productId,
      location_id: data.location_id || data.locationId,
      quantity: data.type === 'remove' ? -Math.abs(data.quantity) : Math.abs(data.quantity),
      reason: data.reason,
      notes: data.notes
    }
    return apiClient.post<StockMovement>('/inventory/stock/adjustments', payload)
  },

  getStockMovements(filters: InventoryFilters = {}, page = 1) {
    return apiClient.get<PaginatedResponse<StockMovement>>('/inventory/stock/movements', {
      params: { ...filters, page }
    })
  },

  getLowStockProducts() {
    return apiClient.get<StockSummary[]>('/inventory/stock/low')
  },

  importProducts(file: File) {
    const formData = new FormData()
    formData.append('file', file)
    return apiClient.post<{ jobId: string }>('/inventory/products/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  },

  // Categories
  getCategories() {
    return apiClient.get('/inventory/categories').then(res => {
      const data = Array.isArray(res.data) ? res.data : (res.data?.data || [])
      return { ...res, data }
    })
  },

  createCategory(data: { name: string; slug?: string; description?: string; parent_id?: string }) {
    return apiClient.post('/inventory/categories', data)
  },

  updateCategory(id: string, data: Partial<{ name: string; description?: string }>) {
    return apiClient.put(`/inventory/categories/${id}`, data)
  },

  deleteCategory(id: string) {
    return apiClient.delete(`/inventory/categories/${id}`)
  }
}
