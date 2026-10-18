import apiClient from './client'

export interface PosTerminal {
  id: string
  name: string
  location_id: string
  shop_id?: string | null
  is_active: boolean
}

export interface PosSession {
  id: string
  terminal_id: string
  shop_id?: string | null
  cashier_id: string
  status: string
  opening_cash_cents: number
  terminal?: PosTerminal
}

export interface CheckoutPayload {
  session_id: string
  customer_id?: string | null
  customer_tin?: string | null
  customer_name?: string | null
  location_id?: string | null
  items: Array<{
    product_id: string
    quantity: number
    unit_price_cents?: number
    discount_cents?: number
    variant_id?: string | null
  }>
  payments: Array<{
    method: string
    amount_cents: number
    reference?: string | null
    change_cents?: number
  }>
  discounts?: {
    percent?: number
    amount_cents?: number
  }
}

export interface TinLookupResult {
  found: boolean
  source: string | null
  tin: string
  name: string | null
  customer_id?: string | null
  phone?: string | null
  email?: string | null
  message?: string
}

export const posApi = {
  getTerminals(shopId?: string) {
    return apiClient.get<{ data: PosTerminal[] }>('/pos/terminals', {
      params: shopId ? { shop_id: shopId } : undefined,
    })
  },

  getCurrentSession(shopId?: string) {
    return apiClient.get<{ data: PosSession | null }>('/pos/sessions/current', {
      params: shopId ? { shop_id: shopId } : undefined,
    })
  },

  openSession(payload: { terminal_id: string; shop_id?: string; opening_cash_cents?: number }) {
    return apiClient.post<{ data: PosSession }>('/pos/sessions/open', payload)
  },

  closeSession(sessionId: string, closing_cash_cents: number) {
    return apiClient.post(`/pos/sessions/${sessionId}/close`, { closing_cash_cents })
  },

  checkout(payload: CheckoutPayload) {
    return apiClient.post<{ data: any }>('/pos/checkout', payload)
  },

  lookupTin(tin: string) {
    return apiClient.get<{ data: TinLookupResult }>('/pos/customers/lookup-tin', {
      params: { tin },
    })
  },

  getReceipt(receiptNumber: string) {
    return apiClient.get(`/pos/receipts/${receiptNumber}`)
  },
}
