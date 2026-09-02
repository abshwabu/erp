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
  plan?: {
    id: string
    name: string
    slug: string
    allowed_modules: string[]
    limits?: Record<string, any>
    perks?: string[]
  } | null
  tenant?: {
    id: string
    name: string
    slug: string
    status: string
    trial_ends_at?: string | null
    days_left?: number
    is_trial?: boolean
    trial_expired?: boolean
    needs_plan?: boolean
  } | null
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const accessToken = useStorage<string | null>('access_token', null)
  const refreshToken = useStorage<string | null>('refresh_token', null)
  const permissions = ref<string[]>([])

  const isAuthenticated = computed(() => !!accessToken.value)

  const isTrialActive = computed(() => {
    return !!user.value?.tenant?.is_trial && !user.value?.tenant?.trial_expired
  })

  const trialDaysLeft = computed(() => {
    return user.value?.tenant?.days_left ?? 0
  })

  const needsPlanSelection = computed(() => {
    if (user.value?.email === 'superadmin@erp.local') return false
    return !!user.value?.tenant?.needs_plan
  })

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

  function hasModuleAccess(moduleName: string): boolean {
    if (!user.value?.plan?.allowed_modules) {
      return true // If no plan restrictions specified, allow
    }

    const allowed = user.value.plan.allowed_modules
    if (allowed.includes('*')) {
      return true
    }

    return allowed.map((m) => m.toLowerCase()).includes(moduleName.toLowerCase())
  }

  function setToken(access: string) {
    accessToken.value = access
  }

  function setTokens(access: string, refresh?: string) {
    accessToken.value = access
    if (refresh) {
      refreshToken.value = refresh
    }
  }

  async function impersonateTenant(access: string, tenant: { id: string; name: string; domain: string }) {
    const tenantStore = useTenantStore()
    accessToken.value = access
    tenantStore.setTenant({
      id: tenant.id,
      name: tenant.name,
      domain: tenant.domain,
    })
    await checkAuth()
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
    const tenantStore = useTenantStore()
    const response = await authApi.login({ email, password })
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

      const tenantStore = useTenantStore()
      if (response.data.tenant_id && !tenantStore.tenantId) {
        tenantStore.setTenant({
          id: response.data.tenant_id,
          name: 'Active Enterprise Workspace',
          domain: 'tenant',
        })
      }
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
    isTrialActive,
    trialDaysLeft,
    needsPlanSelection,
    userInitials,
    hasPermission,
    hasModuleAccess,
    setToken,
    setTokens,
    impersonateTenant,
    setUser,
    logout,
    login,
    register,
    refreshAuthToken,
    checkAuth,
  }
})
