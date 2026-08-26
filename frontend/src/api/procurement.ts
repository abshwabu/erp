import apiClient from './client'

export interface Supplier {
  id: string
  name: string
  email?: string | null
  phone?: string | null
}

export interface PurchaseOrderLine {
  id?: string
  product_id?: string | null
  variant_id?: string | null
  description: string
  quantity: number
  unit_cost_cents: number
  received_quantity?: number
  product?: {
    id: string
    name: string
    sku: string
    has_variants?: boolean
  }
  variant?: {
    id: string
    name: string
    sku: string
  }
}

export interface PurchaseOrder {
  id: string
  supplier_id: string
  number: string
  status: 'draft' | 'ordered' | 'received' | 'cancelled'
  order_date: string
  total_cents: number
  supplier?: Supplier
  lines?: PurchaseOrderLine[]
}

export const procurementApi = {
  getSuppliers() {
    return apiClient.get<{ data: Supplier[] }>('/procurement/suppliers')
  },

  createSupplier(data: Partial<Supplier>) {
    return apiClient.post<{ data: Supplier }>('/procurement/suppliers', data)
  },

  getPurchaseOrders() {
    return apiClient.get<{ data: PurchaseOrder[] }>('/procurement/purchase-orders')
  },

  createPurchaseOrder(data: {
    supplier_id: string
    order_date: string
    number?: string
    status?: string
    lines: Array<{
      product_id: string
      variant_id?: string | null
      description?: string
      quantity: number
      unit_cost_cents: number
    }>
  }) {
    return apiClient.post<{ data: PurchaseOrder }>('/procurement/purchase-orders', data)
  },

  receivePurchaseOrder(id: string, location_id?: string) {
    return apiClient.post<{ data: PurchaseOrder }>(`/procurement/purchase-orders/${id}/receive`, {
      location_id: location_id || null,
    })
  },
}
