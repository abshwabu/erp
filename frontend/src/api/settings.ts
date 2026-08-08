import apiClient from './client'

export const settingsApi = {
  get() {
    return apiClient.get('/core/settings')
  },
  update(data: { display_name?: string; timezone?: string; currency?: string }) {
    return apiClient.post('/core/settings', data)
  },
}
