import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useStorage } from '@vueuse/core'

export interface User {
  id: number
  name: string
  email: string
  role?: string
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

  function logout() {
    user.value = null
    accessToken.value = null
    refreshToken.value = null
    permissions.value = []
  }

  async function login(email: string, password: string) {
    // Implementation will call API
  }

  async function refreshAuthToken() {
    // Implementation will call API
  }

  async function checkAuth() {
    // Implementation will call API to verify token/user
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
    refreshAuthToken,
    checkAuth,
  }
})
