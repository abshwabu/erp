import apiClient from './client'

export interface WarehouseLocation {
  id: string
  warehouse_id: string
  code: string
  name: string
  type: string
  is_active: boolean
  warehouse?: {
    id: string
    name: string
    code: string
  }
}

export interface StockMovementResult {
  id: string
  product_id: string
  variant_id?: string | null
  from_location_id?: string | null
  to_location_id?: string | null
  quantity: number
  type: string
  unit_cost?: number
  reference_type?: string | null
  created_at?: string
}

export interface ReceivePayload {
  product_id: string
  location_id: string
  quantity: number
  unit_cost?: number
  variant_id?: string
  lot_number?: string
  serial_number?: string
}

export interface TransferPayload {
  product_id: string
  from_location_id: string
  to_location_id: string
  quantity: number
  variant_id?: string
}

export interface CreateLocationPayload {
  name: string
  code: string
  type?: string
  warehouse_id?: string
}

export const warehouseApi = {
  getLocations() {
    return apiClient.get<{ data: WarehouseLocation[] }>('/warehouse/locations').then(res => {
      const data = Array.isArray(res.data) ? res.data : (res.data?.data || [])
      return { ...res, data }
    })
  },

  createLocation(payload: CreateLocationPayload) {
    return apiClient.post<{ data: WarehouseLocation }>('/warehouse/locations', payload)
  },

  receive(payload: ReceivePayload) {
    return apiClient.post<{ data: StockMovementResult }>('/warehouse/receive', payload)
  },

  transfer(payload: TransferPayload) {
    return apiClient.post<{ data: StockMovementResult }>('/warehouse/transfer', payload)
  },
}
