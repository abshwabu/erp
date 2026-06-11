import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useStorage } from '@vueuse/core'

export interface Tenant {
  id: string
  name: string
  domain: string
  logo?: string
  accentColor?: string
}

export const useTenantStore = defineStore('tenant', () => {
  const currentTenant = ref<Tenant | null>(null)
  const tenantId = useStorage<string | null>('tenant_id', null)

  function setTenant(tenant: Tenant) {
    currentTenant.value = tenant
    tenantId.value = tenant.id
    if (tenant.accentColor) {
      document.documentElement.style.setProperty('--tenant-accent', tenant.accentColor)
    }
  }

  function clearTenant() {
    currentTenant.value = null
    tenantId.value = null
  }

  return {
    currentTenant,
    tenantId,
    setTenant,
    clearTenant,
  }
})
