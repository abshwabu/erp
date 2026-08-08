import { defineStore } from 'pinia'
import { ref } from 'vue'
import { posApi, type PosSession } from '@/api/pos'
import { shopsApi, type Shop } from '@/api/shops'

export const usePosStore = defineStore('pos', () => {
  const catalog = ref<any[]>([])
  const cart = ref<any[]>([])
  const heldTransactions = ref<any[]>([])
  const isOnline = ref(true)
  const session = ref<PosSession | null>(null)
  const sessionLoading = ref(false)
  const offlineQueue = ref<any[]>([])
  const availableShops = ref<Shop[]>([])
  const selectedShopId = ref<string | null>(null)
  const needsShopSelection = ref(false)

  const selectedShop = () =>
    availableShops.value.find((s) => s.id === selectedShopId.value) || null

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

  const loadShops = async () => {
    try {
      const res = await shopsApi.getMyShops()
      availableShops.value = res.data
    } catch {
      availableShops.value = []
    }
    return availableShops.value
  }

  const selectShop = async (shopId: string) => {
    if (
      session.value?.status === 'open' &&
      session.value.shop_id &&
      session.value.shop_id !== shopId
    ) {
      try {
        await posApi.closeSession(session.value.id, session.value.opening_cash_cents ?? 0)
      } catch {
        // continue — open may still succeed if terminal allows
      }
      session.value = null
    }

    selectedShopId.value = shopId
    needsShopSelection.value = false
    clearCart()
    return ensureSession()
  }

  const ensureSession = async () => {
    if (session.value?.status === 'open') return session.value

    sessionLoading.value = true
    try {
      const current = await posApi.getCurrentSession()
      if (current.data.data) {
        session.value = current.data.data
        if (session.value.shop_id) {
          selectedShopId.value = session.value.shop_id
        }
        needsShopSelection.value = false
        return session.value
      }

      const shops = await loadShops()

      if (shops.length === 0) {
        // Legacy mode only when tenant has no shop-bound terminals
        const terminals = await posApi.getTerminals()
        const terminal = terminals.data.data?.[0]
        if (!terminal) {
          throw new Error(
            'No shop assigned. Ask an admin to create a shop and assign you as a keeper.'
          )
        }
        const opened = await posApi.openSession({
          terminal_id: terminal.id,
          opening_cash_cents: 0,
        })
        session.value = opened.data.data
        return session.value
      }

      if (!selectedShopId.value) {
        if (shops.length === 1) {
          selectedShopId.value = shops[0].id
        } else {
          needsShopSelection.value = true
          throw new Error('Select a shop to continue.')
        }
      }

      const shopId = selectedShopId.value!
      const terminals = await posApi.getTerminals(shopId)
      let terminal = terminals.data.data?.[0]
      if (!terminal) {
        throw new Error('No POS terminal for this shop.')
      }

      const opened = await posApi.openSession({
        terminal_id: terminal.id,
        shop_id: shopId,
        opening_cash_cents: 0,
      })
      session.value = opened.data.data
      needsShopSelection.value = false
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
    availableShops,
    selectedShopId,
    needsShopSelection,
    selectedShop,
    addToCart,
    removeFromCart,
    updateQuantity,
    deductStock,
    clearCart,
    loadShops,
    selectShop,
    ensureSession,
    syncOfflineQueue
  }
})
