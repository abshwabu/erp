import apiClient from './client'

export type ShopStockMode = 'own' | 'shared_warehouse'

export interface ShopKeeper {
  id: string
  name: string
  email: string
  role: string
}

export interface Shop {
  id: string
  name: string
  code: string
  is_active: boolean
  stock_mode: ShopStockMode
  warehouse_id: string
  stock_location_id: string
  warehouse?: { id: string; name: string; code: string } | null
  stock_location?: { id: string; name: string; code: string } | null
  address?: Record<string, unknown> | null
  phone?: string | null
  notes?: string | null
  keepers?: ShopKeeper[]
  terminals?: Array<{ id: string; name: string; location_id: string; is_active: boolean }>
  created_at?: string
  updated_at?: string
}

export interface ShopStockRow {
  product_id: string
  name: string
  sku: string
  selling_price: number
  cost_price: number
  quantity_on_hand: number
  quantity_committed: number
  available_quantity: number
  location_id: string
  location_name?: string
}

export interface CreateShopPayload {
  name: string
  code: string
  stock_mode: ShopStockMode
  stock_location_id?: string
  phone?: string
  notes?: string
  is_active?: boolean
  address?: Record<string, unknown>
}

export const shopsApi = {
  getShops() {
    return apiClient.get<{ data: Shop[] }>('/shops').then((res) => {
      const data = Array.isArray(res.data?.data) ? res.data.data : []
      return { ...res, data }
    })
  },

  getMyShops() {
    return apiClient.get<{ data: Shop[] }>('/shops/mine').then((res) => {
      const data = Array.isArray(res.data?.data) ? res.data.data : []
      return { ...res, data }
    })
  },

  getShop(id: string) {
    return apiClient.get<{ data: Shop }>(`/shops/${id}`)
  },

  createShop(payload: CreateShopPayload) {
    return apiClient.post<{ data: Shop }>('/shops', payload)
  },

  updateShop(id: string, payload: Partial<CreateShopPayload>) {
    return apiClient.put<{ data: Shop }>(`/shops/${id}`, payload)
  },

  getKeepers(id: string) {
    return apiClient.get<{ data: ShopKeeper[] }>(`/shops/${id}/keepers`).then((res) => {
      const data = Array.isArray(res.data?.data) ? res.data.data : []
      return { ...res, data }
    })
  },

  syncKeepers(id: string, keepers: Array<{ user_id: string; role?: string }>) {
    return apiClient.put<{ data: Shop }>(`/shops/${id}/keepers`, { keepers })
  },

  getAssignableUsers() {
    return apiClient.get<{ data: Array<{ id: string; name: string; email: string }> }>('/shops/assignable-users').then((res) => {
      const data = Array.isArray(res.data?.data) ? res.data.data : []
      return { ...res, data }
    })
  },

  getStock(id: string) {
    return apiClient.get<{ data: ShopStockRow[] }>(`/shops/${id}/stock`).then((res) => {
      const data = Array.isArray(res.data?.data) ? res.data.data : []
      return { ...res, data }
    })
  },

  adjustStock(id: string, payload: {
    product_id: string
    quantity: number
    type: 'add' | 'remove'
    reason?: string
    notes?: string
    variant_id?: string
  }) {
    return apiClient.post(`/shops/${id}/stock/adjust`, payload)
  },
}
