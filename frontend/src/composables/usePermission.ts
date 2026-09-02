import { useAuthStore } from '@/stores/auth'

export function usePermission() {
  const authStore = useAuthStore()

  const hasPermission = (permission: string) => {
    return authStore.hasPermission(permission)
  }

  const hasAnyPermission = (permissions: string[]) => {
    return permissions.some((p) => authStore.hasPermission(p))
  }

  const hasAllPermissions = (permissions: string[]) => {
    return permissions.every((p) => authStore.hasPermission(p))
  }

  const hasModuleAccess = (moduleName: string) => {
    return authStore.hasModuleAccess(moduleName)
  }

  return {
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
    hasModuleAccess,
  }
}
