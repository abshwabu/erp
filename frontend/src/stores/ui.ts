import { defineStore } from 'pinia'
import { ref } from 'vue'

export interface Breadcrumb {
  label: string
  to?: string | object
}

export const useUIStore = defineStore('ui', () => {
  const sidebarOpen = ref(true)
  const activeModule = ref('dashboard')
  const pageTitle = ref('Dashboard')
  const breadcrumbs = ref<Breadcrumb[]>([])

  function toggleSidebar() {
    sidebarOpen.value = !sidebarOpen.value
  }

  function setPageTitle(title: string) {
    pageTitle.value = title
    document.title = `${title} | ERP`
  }

  function setBreadcrumbs(items: Breadcrumb[]) {
    breadcrumbs.value = items
  }

  return {
    sidebarOpen,
    activeModule,
    pageTitle,
    breadcrumbs,
    toggleSidebar,
    setPageTitle,
    setBreadcrumbs,
  }
})
