import apiClient from './client'

export interface Role {
  id: number
  name: string
  guard_name: string
  permissions: string[]
}

export interface AppUser {
  id: string
  name: string
  email: string
  is_active: boolean
  mfa_enabled?: boolean
  last_login_at?: string | null
  roles: string[]
  permissions?: string[]
  created_at: string
  updated_at?: string
}

export const usersApi = {
  list(params?: any) {
    return apiClient.get<{ data: AppUser[] }>('/users', { params })
  },
  get(id: string) {
    return apiClient.get<{ data: AppUser }>(`/users/${id}`)
  },
  create(data: { name: string; email: string; password: string; roles?: string[]; is_active?: boolean }) {
    return apiClient.post<{ data: AppUser }>('/users', data)
  },
  update(id: string, data: { name?: string; email?: string; password?: string; roles?: string[]; is_active?: boolean }) {
    return apiClient.put<{ data: AppUser }>(`/users/${id}`, data)
  },
  toggleStatus(id: string) {
    return apiClient.patch<{ data: { id: string; is_active: boolean; message: string } }>(`/users/${id}/toggle-status`)
  },
  delete(id: string) {
    return apiClient.delete(`/users/${id}`)
  },
}

export const rolesApi = {
  list() {
    return apiClient.get<{ data: Role[] }>('/roles')
  },
  permissions() {
    return apiClient.get<{ data: string[] }>('/permissions')
  },
  create(name: string) {
    return apiClient.post<{ data: Role }>('/roles', { name })
  },
  update(id: number, name: string) {
    return apiClient.put<{ data: Role }>(`/roles/${id}`, { name })
  },
  destroy(id: number) {
    return apiClient.delete(`/roles/${id}`)
  },
  syncPermissions(id: number, permissions: string[]) {
    return apiClient.post(`/roles/${id}/permissions`, { permissions })
  },
}
