import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const usePosStore = defineStore('pos', () => {
  const catalog = ref<any[]>([])
  const cart = ref<any[]>([])
  const heldTransactions = ref<any[]>([])
  const isOnline = ref(true)
  const session = ref<any | null>(null)
  const offlineQueue = ref<any[]>([])

  const addToCart = (product: any, quantity = 1) => {
    const existing = cart.value.find(item => item.id === product.id)
    if (existing) {
      existing.quantity += quantity
    } else {
      cart.value.push({ ...product, quantity })
    }
  }

  const removeFromCart = (productId: number) => {
    cart.value = cart.value.filter(item => item.id !== productId)
  }

  const clearCart = () => {
    cart.value = []
  }

  const syncOfflineQueue = async () => {
    if (!isOnline.value || offlineQueue.value.length === 0) return
    // Logic to push queue to backend
  }

  return {
    catalog,
    cart,
    heldTransactions,
    isOnline,
    session,
    offlineQueue,
    addToCart,
    removeFromCart,
    clearCart,
    syncOfflineQueue
  }
})
