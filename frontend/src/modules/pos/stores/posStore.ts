import { defineStore } from 'pinia'
import { ref } from 'vue'

export const usePosStore = defineStore('pos', () => {
  const catalog = ref<any[]>([])
  const cart = ref<any[]>([])
  const heldTransactions = ref<any[]>([])
  const isOnline = ref(true)
  const session = ref<any | null>(null)
  const offlineQueue = ref<any[]>([])

  const addToCart = (product: any, quantity = 1) => {
    const existing = cart.value.find(item => String(item.id) === String(product.id))
    if (existing) {
      existing.quantity += quantity
    } else {
      cart.value.push({ ...product, quantity })
    }
  }

  const removeFromCart = (productId: number | string) => {
    cart.value = cart.value.filter(item => String(item.id) !== String(productId))
  }

  const clearCart = () => {
    cart.value = []
  }

  const updateQuantity = (productId: number | string, delta: number) => {
    const item = cart.value.find(i => String(i.id) === String(productId))
    if (item) {
      item.quantity += delta
      if (item.quantity <= 0) {
        removeFromCart(productId)
      }
    }
  }

  const deductStock = (productId: number | string, quantity: number) => {
    const item = catalog.value.find(p => String(p.id) === String(productId))
    if (item) {
      item.stock = Math.max(0, (item.stock || 0) - quantity)
    }
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
    updateQuantity,
    deductStock,
    clearCart,
    syncOfflineQueue
  }
})
