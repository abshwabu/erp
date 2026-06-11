import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import { useTenantStore } from '@/stores/tenant'

const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

// Request interceptor
apiClient.interceptors.request.use(
  (config) => {
    const authStore = useAuthStore()
    const tenantStore = useTenantStore()

    if (authStore.accessToken) {
      config.headers.Authorization = `Bearer ${authStore.accessToken}`
    }

    if (tenantStore.tenantId) {
      config.headers['X-Tenant-ID'] = tenantStore.tenantId
    }

    return config
  },
  (error) => Promise.reject(error)
)

// Response interceptor
apiClient.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config
    const authStore = useAuthStore()

    // Handle 401 Unauthorized
    if (error.response?.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true
      try {
        await authStore.refreshAuthToken()
        return apiClient(originalRequest)
      } catch (refreshError) {
        authStore.logout()
        return Promise.reject(refreshError)
      }
    }

    // Handle 422 Validation Errors
    if (error.response?.status === 422) {
      const errors = error.response.data.errors
      // Convert Laravel style errors to a flat record if needed
      return Promise.reject({
        status: 422,
        errors,
        message: error.response.data.message || 'Validation failed'
      })
    }

    return Promise.reject(error)
  }
)

export default apiClient
