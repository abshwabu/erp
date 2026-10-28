import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { VueQueryPlugin, type VueQueryPluginOptions } from '@tanstack/vue-query'

import App from './App.vue'
import router from './router'
import i18n from './i18n'
import { offlineSyncService } from './services/offlineSync'

const app = createApp(App)

// Initialize offline storage & background synchronization service
offlineSyncService.init()

// Pinia
app.use(createPinia())

// i18n Localization
app.use(i18n)

// Vue Router
app.use(router)

// Vue Query Configuration
const vueQueryOptions: VueQueryPluginOptions = {
  queryClientConfig: {
    defaultOptions: {
      queries: {
        staleTime: 5 * 60 * 1000, // 5 minutes
        refetchOnWindowFocus: false,
        retry: (failureCount, error: any) => {
          if (error?.response?.status >= 400 && error?.response?.status < 500) {
            return false // No retry on 4xx errors
          }
          return failureCount < 1 // Retry once on other errors (like network/5xx)
        },
      },
    },
  },
}
app.use(VueQueryPlugin, vueQueryOptions)

// Auto-activate new Service Worker updates and auto-refresh page
if ('serviceWorker' in navigator) {
  let refreshing = false
  navigator.serviceWorker.addEventListener('controllerchange', () => {
    if (!refreshing) {
      refreshing = true
      window.location.reload()
    }
  })

  window.addEventListener('load', () => {
    navigator.serviceWorker.getRegistration().then(reg => {
      if (reg) {
        reg.update().catch(() => {})
      }
    }).catch(() => {})
  })
}

app.mount('#app')
