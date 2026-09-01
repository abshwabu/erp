import apiClient from './client'

export interface IntegrationLog {
  id: string
  integration_id: string
  event: string
  direction: 'inbound' | 'outbound'
  status_code: number | null
  payload: Record<string, any> | null
  response: Record<string, any> | null
  created_at: string
}

export interface Integration {
  id: string
  provider: string
  name: string
  status: 'connected' | 'disconnected' | 'error'
  api_key?: string | null
  webhook_url?: string | null
  settings?: Record<string, any> | null
  last_tested_at?: string | null
  logs_count?: number
  logs?: IntegrationLog[]
  created_at?: string
  updated_at?: string
}

export interface ConnectorCatalogItem {
  provider: string
  name: string
  category: string
  description: string
  icon: string
  badge?: string | null
  fields: Array<{
    key: string
    label: string
    type: 'text' | 'password' | 'url' | 'email'
    required: boolean
  }>
  supported_events: string[]
}

export const integrationsApi = {
  list() {
    return apiClient.get<{ data: Integration[] }>('/integrations')
  },
  getCatalog() {
    return apiClient.get<{ data: ConnectorCatalogItem[] }>('/integrations/catalog')
  },
  get(id: string) {
    return apiClient.get<{ data: Integration }>(`/integrations/${id}`)
  },
  create(data: {
    provider: string
    name: string
    api_key?: string
    webhook_url?: string
    settings?: Record<string, any>
  }) {
    return apiClient.post<{ data: Integration }>('/integrations', data)
  },
  update(
    id: string,
    data: {
      name?: string
      status?: 'connected' | 'disconnected' | 'error'
      api_key?: string
      webhook_url?: string
      settings?: Record<string, any>
    }
  ) {
    return apiClient.put<{ data: Integration }>(`/integrations/${id}`, data)
  },
  testConnection(id: string) {
    return apiClient.post<{ data: { status: string; message: string; last_tested_at: string; log: IntegrationLog } }>(
      `/integrations/${id}/test`
    )
  },
  sendTestEvent(id: string, data: { event: string; payload?: Record<string, any> }) {
    return apiClient.post<{ data: { message: string; log: IntegrationLog } }>(`/integrations/${id}/send-test`, data)
  },
  getLogs(id: string, params?: { per_page?: number; page?: number }) {
    return apiClient.get<{ data: { data: IntegrationLog[]; current_page: number; total: number } }>(
      `/integrations/${id}/logs`,
      { params }
    )
  },
  delete(id: string) {
    return apiClient.delete(`/integrations/${id}`)
  },
}
