import apiClient from './client'

export interface BomLine {
  id?: string
  bom_id?: string
  material_id: string
  quantity: number
  unit?: string
  notes?: string | null
  material?: {
    id: string
    name: string
    sku: string
    cost_price?: number
  }
}

export interface BillOfMaterial {
  id: string
  product_id: string
  name: string
  description?: string | null
  output_quantity: number
  status: 'draft' | 'active' | 'archived'
  created_at?: string
  product?: {
    id: string
    name: string
    sku: string
    cost_price?: number
    selling_price?: number
  }
  lines?: BomLine[]
}

export interface WorkOrder {
  id: string
  number: string
  bom_id: string
  quantity: number
  status: 'draft' | 'in_progress' | 'completed' | 'cancelled'
  priority: 'low' | 'normal' | 'high' | 'urgent'
  planned_start?: string | null
  planned_end?: string | null
  started_at?: string | null
  completed_at?: string | null
  notes?: string | null
  created_at?: string
  bom?: BillOfMaterial
}

export const manufacturingApi = {
  getBoms() {
    return apiClient.get<{ data: BillOfMaterial[] }>('/manufacturing/boms')
  },

  getBom(id: string) {
    return apiClient.get<{ data: BillOfMaterial }>(`/manufacturing/boms/${id}`)
  },

  createBom(data: {
    product_id: string
    name: string
    description?: string
    output_quantity: number
    status?: 'draft' | 'active'
    lines: Array<{
      material_id: string
      quantity: number
      unit?: string
      notes?: string
    }>
  }) {
    return apiClient.post<{ data: BillOfMaterial }>('/manufacturing/boms', data)
  },

  activateBom(id: string) {
    return apiClient.post<{ data: BillOfMaterial }>(`/manufacturing/boms/${id}/activate`)
  },

  archiveBom(id: string) {
    return apiClient.post<{ data: BillOfMaterial }>(`/manufacturing/boms/${id}/archive`)
  },

  deleteBom(id: string) {
    return apiClient.delete(`/manufacturing/boms/${id}`)
  },

  getWorkOrders() {
    return apiClient.get<{ data: WorkOrder[] }>('/manufacturing/work-orders')
  },

  getWorkOrder(id: string) {
    return apiClient.get<{ data: WorkOrder }>(`/manufacturing/work-orders/${id}`)
  },

  createWorkOrder(data: {
    bom_id: string
    quantity: number
    priority?: 'low' | 'normal' | 'high' | 'urgent'
    planned_start?: string
    planned_end?: string
    notes?: string
  }) {
    return apiClient.post<{ data: WorkOrder }>('/manufacturing/work-orders', data)
  },

  startWorkOrder(id: string) {
    return apiClient.post<{ data: WorkOrder }>(`/manufacturing/work-orders/${id}/start`)
  },

  completeWorkOrder(id: string, location_id?: string) {
    return apiClient.post<{ data: WorkOrder }>(`/manufacturing/work-orders/${id}/complete`, {
      location_id: location_id || null,
    })
  },

  cancelWorkOrder(id: string) {
    return apiClient.post<{ data: WorkOrder }>(`/manufacturing/work-orders/${id}/cancel`)
  },

  deleteWorkOrder(id: string) {
    return apiClient.delete(`/manufacturing/work-orders/${id}`)
  },
}
