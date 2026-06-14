import apiClient from './client'
import type { User } from '@/stores/auth'

export interface LoginResponse {
  access_token: string
  refresh_token: string
  token_type: string
  expires_in: number
  mfa_required?: boolean
  mfa_token?: string
  tenant?: {
    id: string
    name: string
    domain: string
  }
}

export interface MeResponse {
  data: User & {
    permissions: string[]
  }
}

export const authApi = {
  async register(data: any): Promise<LoginResponse> {
    const response = await apiClient.post<LoginResponse>('/auth/register', data)
    return response.data
  },

  async login(credentials: { email: string; password: string }): Promise<LoginResponse> {
    const response = await apiClient.post<LoginResponse>('/auth/login', credentials)
    return response.data
  },

  async refresh(refreshToken: string): Promise<{ access_token: string; expires_in: number }> {
    const response = await apiClient.post('/auth/refresh', { refresh_token: refreshToken })
    return response.data
  },

  async logout(): Promise<void> {
    await apiClient.post('/auth/logout')
  },

  async me(): Promise<MeResponse> {
    const response = await apiClient.get<MeResponse>('/auth/me')
    return response.data
  }
}
