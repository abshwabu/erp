import { defineStore } from 'pinia'
import { ref } from 'vue'
import { posApi, type PosSession } from '@/api/pos'

export const usePosStore = defineStore('pos', () => {
  const catalog = ref<any[]>([])
  const cart = ref<any[]>([])
  const heldTransactions = ref<any[]>([])
  const isOnline = ref(true)
  const session = ref<PosSession | null>(null)
  const sessionLoading = ref(false)
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

  const ensureSession = async () => {
    if (session.value?.status === 'open') return session.value

    sessionLoading.value = true
    try {
      const current = await posApi.getCurrentSession()
      if (current.data.data) {
        session.value = current.data.data
        return session.value
      }

      const terminals = await posApi.getTerminals()
      const terminal = terminals.data.data?.[0]
      if (!terminal) {
        throw new Error('No POS terminal available. Create a stock location first.')
      }

      const opened = await posApi.openSession({
        terminal_id: terminal.id,
        opening_cash_cents: 0,
      })
      session.value = opened.data.data
      return session.value
    } finally {
      sessionLoading.value = false
    }
  }

  const syncOfflineQueue = async () => {
    if (!isOnline.value || offlineQueue.value.length === 0) return
  }

  return {
    catalog,
    cart,
    heldTransactions,
    isOnline,
    session,
    sessionLoading,
    offlineQueue,
    addToCart,
    removeFromCart,
    updateQuantity,
    deductStock,
    clearCart,
    ensureSession,
    syncOfflineQueue
  }
})
