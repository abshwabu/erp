import { useNotificationStore } from '@/stores/notification'

export function useToast() {
  const store = useNotificationStore()

  return {
    success: (message: string) => store.addToast(message, 'success'),
    error: (message: string) => store.addToast(message, 'error'),
    warning: (message: string) => store.addToast(message, 'warning'),
    info: (message: string) => store.addToast(message, 'info'),
  }
}
