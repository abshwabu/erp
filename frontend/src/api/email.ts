import apiClient from './client'

export interface EmailDiagnostics {
  driver: string
  smtp_host: string
  smtp_port: number
  from_address: string
  from_name: string
  is_connected: boolean
  latency_ms: number
  connection_error: string | null
  mailhog_ui_url: string
}

export interface SendTestEmailPayload {
  email: string
  name?: string
}

export interface SendTestEmailResponse {
  message: string
  data: {
    success: boolean
    recipient: string
    duration_ms: number
    message: string
    error?: string
  }
}

export const emailApi = {
  async getDiagnostics(): Promise<{ data: EmailDiagnostics }> {
    const response = await apiClient.get<{ data: EmailDiagnostics }>('/core/email/diagnostics')
    return response.data
  },

  async sendTest(payload: SendTestEmailPayload): Promise<SendTestEmailResponse> {
    const response = await apiClient.post<SendTestEmailResponse>('/core/email/send-test', payload)
    return response.data
  },
}
