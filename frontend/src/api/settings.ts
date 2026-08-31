import apiClient from './client'

export interface TenantSettings {
  display_name: string
  company_email?: string
  company_phone?: string
  company_address?: string
  tax_id?: string
  website?: string
  logo_url?: string
  timezone: string
  currency: string
  currency_symbol: string
  date_format: string
  fiscal_year_start: string
  default_tax_rate: number
  invoice_prefix: string
  quote_prefix: string
  po_prefix: string
  default_payment_terms: string
  auto_inventory_sync: boolean
}

export const settingsApi = {
  get() {
    return apiClient.get<{ data: TenantSettings }>('/core/settings')
  },
  update(data: Partial<TenantSettings>) {
    return apiClient.post<{ data: TenantSettings }>('/core/settings', data)
  },
}
