import apiClient from './client'

export interface Role {
  id: number
  name: string
  guard_name: string
  permissions: string[]
}

export const rolesApi = {
  list() {
    return apiClient.get<{ data: Role[] }>('/roles')
  },

  permissions() {
    return apiClient.get<{ data: string[] }>('/permissions')
  },

  create(name: string) {
    return apiClient.post('/roles', { name })
  },

  update(id: number, name: string) {
    return apiClient.put(`/roles/${id}`, { name })
  },

  destroy(id: number) {
    return apiClient.delete(`/roles/${id}`)
  },

  syncPermissions(id: number, permissions: string[]) {
    return apiClient.post(`/roles/${id}/permissions`, { permissions })
  },
}
