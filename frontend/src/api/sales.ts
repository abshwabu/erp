import apiClient from './client'

export interface Customer {
  id: string
  name: string
  email?: string | null
  phone?: string | null
  invoices_count?: number
  total_invoiced_cents?: number
  total_paid_cents?: number
  outstanding_cents?: number
  invoices?: Invoice[]
  created_at?: string
  updated_at?: string
}

export interface InvoiceLine {
  id?: string
  description: string
  quantity: number
  unit_price_cents: number
  line_total_cents?: number
}

export interface InvoicePayment {
  id: string
  invoice_id: string
  amount_cents: number
  method: string
  paid_at: string
  reference?: string | null
}

export type InvoiceStatus = 'draft' | 'sent' | 'paid' | 'void'

export interface Invoice {
  id: string
  customer_id: string
  number: string
  status: InvoiceStatus
  issue_date: string
  due_date: string
  subtotal_cents: number
  tax_cents: number
  total_cents: number
  amount_paid_cents: number
  notes?: string | null
  customer?: Customer
  lines?: InvoiceLine[]
  payments?: InvoicePayment[]
}

export interface CreateCustomerPayload {
  name: string
  email?: string | null
  phone?: string | null
}

export interface CreateInvoicePayload {
  customer_id: string
  number?: string
  status?: 'draft' | 'sent'
  issue_date: string
  due_date: string
  tax_cents?: number
  notes?: string | null
  lines: Array<{
    description: string
    quantity: number
    unit_price_cents: number
  }>
}

export interface RecordPaymentPayload {
  amount_cents: number
  method: string
  paid_at?: string
  reference?: string | null
}

export const salesApi = {
  getCustomers() {
    return apiClient.get<{ data: Customer[] }>('/sales/customers')
  },

  createCustomer(payload: CreateCustomerPayload) {
    return apiClient.post<{ data: Customer }>('/sales/customers', payload)
  },

  updateCustomer(id: string, payload: Partial<CreateCustomerPayload>) {
    return apiClient.put<{ data: Customer }>(`/sales/customers/${id}`, payload)
  },

  deleteCustomer(id: string) {
    return apiClient.delete(`/sales/customers/${id}`)
  },

  getCustomer(id: string) {
    return apiClient.get<{ data: Customer }>(`/sales/customers/${id}`)
  },

  getInvoices(params?: { status?: string; customer_id?: string }) {
    return apiClient.get<{ data: Invoice[] }>('/sales/invoices', { params })
  },

  createInvoice(payload: CreateInvoicePayload) {
    return apiClient.post<{ data: Invoice }>('/sales/invoices', payload)
  },

  getInvoice(id: string) {
    return apiClient.get<{ data: Invoice }>(`/sales/invoices/${id}`)
  },

  markSent(id: string) {
    return apiClient.post<{ data: Invoice }>(`/sales/invoices/${id}/mark-sent`)
  },

  recordPayment(id: string, payload: RecordPaymentPayload) {
    return apiClient.post<{ data: Invoice }>(`/sales/invoices/${id}/payments`, payload)
  },
}
