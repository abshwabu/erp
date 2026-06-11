import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { VueQueryPlugin, type VueQueryPluginOptions } from '@tanstack/vue-query'

import App from './App.vue'
import router from './router'

const app = createApp(App)

// Pinia
app.use(createPinia())

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

app.mount('#app')
