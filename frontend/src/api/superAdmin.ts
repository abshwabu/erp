import apiClient from './client'

export interface PlatformPlan {
  id: string
  name: string
  slug: string
  badge?: string
  tagline?: string
  description?: string
  price_monthly: number
  price_annually: number
  is_active: boolean
  tenants_count?: number
  allowed_modules: string[]
  perks: string[]
  limits?: {
    users_limit: number
    storage_gb: number
    invoices_limit: number
    multi_warehouse: boolean
    advanced_accounting: boolean
    custom_domain: boolean
    webhooks_integrations?: boolean
    support_sla?: string
    [key: string]: any
  }
  features?: Array<{
    id: string
    feature_key: string
    feature_value: any
  }>
}

export interface PlatformTenant {
  id: string
  name: string
  slug: string
  custom_domain?: string | null
  status: 'active' | 'trial' | 'suspended' | 'maintenance'
  plan?: {
    id: string
    name: string
    price_monthly: number
  } | null
  settings?: Record<string, any> | null
  users_count?: number
  created_at: string
  updated_at?: string
}

export interface PlatformMetrics {
  total_tenants: number
  active_tenants: number
  trial_tenants: number
  suspended_tenants: number
  mrr_cents: number
  arr_cents: number
  currency?: string
  currency_symbol?: string
  platform_version: string
  health: string
  plan_distribution: Array<{
    id: string
    name: string
    tenants_count: number
    price_monthly: number
  }>
}

export const superAdminApi = {
  getMetrics() {
    return apiClient.get<{ data: PlatformMetrics }>('/super-admin/metrics')
  },
  getTenants(params?: { search?: string; status?: string; plan_id?: string }) {
    return apiClient.get<{ data: PlatformTenant[] }>('/super-admin/tenants', { params })
  },
  getTenant(id: string) {
    return apiClient.get<{ data: PlatformTenant }>(`/super-admin/tenants/${id}`)
  },
  createTenant(data: {
    name: string
    slug?: string
    custom_domain?: string
    plan_id?: string
    status?: string
    admin_name: string
    admin_email: string
    admin_password: string
    currency?: string
    timezone?: string
  }) {
    return apiClient.post<{ data: PlatformTenant }>('/super-admin/tenants', data)
  },
  updateTenant(
    id: string,
    data: {
      name?: string
      slug?: string
      custom_domain?: string
      plan_id?: string
      status?: string
      settings?: Record<string, any>
    }
  ) {
    return apiClient.put<{ data: PlatformTenant }>(`/super-admin/tenants/${id}`, data)
  },
  updateTenantStatus(id: string, status: string) {
    return apiClient.patch<{ data: { id: string; status: string; message: string } }>(
      `/super-admin/tenants/${id}/status`,
      { status }
    )
  },
  impersonateTenant(id: string) {
    return apiClient.post<{
      data: {
        tenant_id: string
        tenant_name: string
        tenant_slug: string
        access_token: string
        token_type: string
        impersonating: boolean
      }
    }>(`/super-admin/tenants/${id}/impersonate`)
  },
  deleteTenant(id: string) {
    return apiClient.delete(`/super-admin/tenants/${id}`)
  },
  getPlans() {
    return apiClient.get<{ data: PlatformPlan[] }>('/super-admin/plans')
  },
}
