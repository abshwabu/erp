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

  createStockAdjustment(data: StockAdjustment) {
    return apiClient.post<StockMovement>('/inventory/stock/adjustments', data)
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
    return apiClient.get('/inventory/categories')
  }
}
