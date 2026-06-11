import { defineStore } from 'pinia'
import { ref } from 'vue'

export type ToastType = 'success' | 'error' | 'warning' | 'info'

export interface Toast {
  id: string
  message: string
  type: ToastType
  duration?: number
}

export const useNotificationStore = defineStore('notification', () => {
  const toasts = ref<Toast[]>([])
  const notifications = ref<any[]>([]) // For the bell dropdown

  function addToast(message: string, type: ToastType = 'info', duration = 4000) {
    const id = Math.random().toString(36).substring(2, 9)
    toasts.value.push({ id, message, type, duration })

    if (duration > 0) {
      setTimeout(() => {
        removeToast(id)
      }, duration)
    }
    return id
  }

  function removeToast(id: string) {
    const index = toasts.value.findIndex((t) => t.id === id)
    if (index !== -1) {
      toasts.value.splice(index, 1)
    }
  }

  return {
    toasts,
    notifications,
    addToast,
    removeToast,
  }
})
