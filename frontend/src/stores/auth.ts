import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useStorage } from '@vueuse/core'
import { authApi } from '@/api/auth'
import { useTenantStore } from '@/stores/tenant'

export interface User {
  id: number
  tenant_id?: string
  name: string
  email: string
  roles?: string[]
  avatar?: string
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const accessToken = useStorage<string | null>('access_token', null)
  const refreshToken = useStorage<string | null>('refresh_token', null)
  const permissions = ref<string[]>([])

  const isAuthenticated = computed(() => !!accessToken.value)

  const userInitials = computed(() => {
    if (!user.value?.name) return ''
    return user.value.name
      .split(' ')
      .map((n) => n[0])
      .join('')
      .toUpperCase()
  })

  function hasPermission(permission: string) {
    return permissions.value.includes(permission)
  }

  function setTokens(access: string, refresh: string) {
    accessToken.value = access
    refreshToken.value = refresh
  }

  function setUser(userData: User, userPermissions: string[]) {
    user.value = userData
    permissions.value = userPermissions
  }

  async function logout() {
    try {
      if (accessToken.value) {
        await authApi.logout()
      }
    } catch (error) {
      console.error('Logout API call failed', error)
    } finally {
      user.value = null
      accessToken.value = null
      refreshToken.value = null
      permissions.value = []
    }
  }

  async function login(email: string, password: string) {
    const response = await authApi.login({ email, password })
    setTokens(response.access_token, response.refresh_token)
    await checkAuth()
    return response
  }

  async function register(data: any) {
    const tenantStore = useTenantStore()
    const response = await authApi.register(data)
    setTokens(response.access_token, response.refresh_token)

    if (response.tenant) {
      tenantStore.setTenant({
        id: response.tenant.id,
        name: response.tenant.name,
        domain: response.tenant.domain,
      })
    }

    await checkAuth()
    return response
  }


  async function refreshAuthToken() {
    if (!refreshToken.value) {
      throw new Error('No refresh token available')
    }

    try {
      const response = await authApi.refresh(refreshToken.value)
      accessToken.value = response.access_token
    } catch (error) {
      await logout()
      throw error
    }
  }

  async function checkAuth() {
    if (!accessToken.value) return

    try {
      const response = await authApi.me()
      setUser(response.data, response.data.permissions)
    } catch (error) {
      await logout()
      throw error
    }
  }

  return {
    user,
    accessToken,
    refreshToken,
    permissions,
    isAuthenticated,
    userInitials,
    hasPermission,
    setTokens,
    setUser,
    logout,
    login,
    register,
    refreshAuthToken,
    checkAuth,
  }
})
