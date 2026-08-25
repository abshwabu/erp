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

    const tenantId = tenantStore.tenantId || authStore.user?.tenant_id
    if (tenantId) {
      config.headers['X-Tenant-ID'] = tenantId
    }

    return config
  },
  (error) => Promise.reject(error)
)

let refreshPromise: Promise<void> | null = null

// Response interceptor
apiClient.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config
    const authStore = useAuthStore()

    // Handle 401 Unauthorized (exclude auth endpoints to avoid infinite loops)
    const isAuthRoute =
      originalRequest?.url?.includes('/auth/login') ||
      originalRequest?.url?.includes('/auth/register') ||
      originalRequest?.url?.includes('/auth/refresh')

    if (error.response?.status === 401 && !originalRequest._retry && !isAuthRoute) {
      originalRequest._retry = true

      if (!refreshPromise) {
        refreshPromise = authStore
          .refreshAuthToken()
          .finally(() => {
            refreshPromise = null
          })
      }

      try {
        await refreshPromise
        if (authStore.accessToken) {
          originalRequest.headers = originalRequest.headers || {}
          originalRequest.headers.Authorization = `Bearer ${authStore.accessToken}`
        }
        return apiClient(originalRequest)
      } catch (refreshError) {
        await authStore.logout()
        return Promise.reject(refreshError)
      }
    }

    // Handle 422 Validation Errors
    if (error.response?.status === 422) {
      const errors = error.response.data?.errors
      return Promise.reject({
        status: 422,
        errors,
        message: error.response.data?.message || 'Validation failed',
      })
    }

    return Promise.reject(error)
  }
)

export default apiClient
