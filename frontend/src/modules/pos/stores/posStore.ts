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

  const addToCart = (product: any, quantity = 1, variant: any = null) => {
    const pId = String(product.productId || product.product_id || product.id)
    const vId = variant ? String(variant.id) : (product.variantId || product.variant_id || null)
    const cartKey = vId ? `${pId}-${vId}` : pId

    const existing = cart.value.find(item => item.cartKey === cartKey || (String(item.id) === pId && item.variantId === vId))
    if (existing) {
      existing.quantity += quantity
    } else {
      const price = variant && typeof variant.selling_price === 'number'
        ? variant.selling_price / 100
        : (typeof product.price === 'number' ? product.price : (Number(product.selling_price) / 100 || 0))

      const name = variant ? `${product.name} - ${variant.name.replace(product.name, '').replace(/^[\s-]+/, '') || variant.name}` : product.name
      const sku = variant?.sku || product.sku
      const stock = variant ? (variant.stock ?? variant.available_quantity ?? product.stock) : product.stock

      cart.value.push({
        ...product,
        id: pId,
        cartKey,
        productId: pId,
        variantId: vId,
        variant_id: vId,
        variantName: variant?.name || null,
        name,
        sku,
        price,
        stock: Number(stock) || 0,
        quantity,
      })
    }
  }

  const removeFromCart = (cartKeyOrId: number | string) => {
    cart.value = cart.value.filter(item => item.cartKey !== String(cartKeyOrId) && String(item.id) !== String(cartKeyOrId))
  }

  const clearCart = () => {
    cart.value = []
  }

  const updateQuantity = (cartKeyOrId: number | string, delta: number) => {
    const item = cart.value.find(i => i.cartKey === String(cartKeyOrId) || String(i.id) === String(cartKeyOrId))
    if (item) {
      item.quantity += delta
      if (item.quantity <= 0) {
        removeFromCart(cartKeyOrId)
      }
    }
  }

  const deductStock = (productId: number | string, quantity: number, variantId?: string | null) => {
    const item = catalog.value.find(p => String(p.id) === String(productId))
    if (item) {
      item.stock = Math.max(0, (item.stock || 0) - quantity)
      if (variantId && Array.isArray(item.variants)) {
        const v = item.variants.find((variant: any) => String(variant.id) === String(variantId))
        if (v) {
          v.stock = Math.max(0, (v.stock || 0) - quantity)
        }
      }
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
    // Eagerly establish shop list and default active shop
    const shops = await loadShops()
    if (!selectedShopId.value && shops.length > 0 && shops[0]) {
      selectedShopId.value = shops[0].id
    }

    if (session.value?.status === 'open') {
      if (!session.value.shop_id && selectedShopId.value) {
        session.value.shop_id = selectedShopId.value
      }
      return session.value
    }

    sessionLoading.value = true
    try {
      const current = await posApi.getCurrentSession()
      if (current.data.data) {
        session.value = current.data.data
        if (session.value.shop_id) {
          selectedShopId.value = session.value.shop_id
        } else if (shops.length > 0 && shops[0]) {
          selectedShopId.value = shops[0].id
          session.value.shop_id = shops[0].id
        }
        needsShopSelection.value = false
        return session.value
      }

      if (shops.length === 0) {
        const terminals = await posApi.getTerminals()
        const terminal = terminals.data.data?.[0]
        if (!terminal) {
          throw new Error(
            'No POS terminal or shop available. Please ensure a shop and terminal are configured.'
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
        if (shops.length === 1 && shops[0]) {
          selectedShopId.value = shops[0].id
        } else if (shops.length > 1) {
          // Default to the first shop or prompt selection
          selectedShopId.value = shops[0]?.id || null
        }
      }

      const shopId = selectedShopId.value || shops[0]?.id
      if (!shopId) {
        needsShopSelection.value = true
        throw new Error('Please select a shop to continue.')
      }

      const terminals = await posApi.getTerminals(shopId)
      let terminal = terminals.data.data?.[0]
      if (!terminal) {
        const allTerminals = await posApi.getTerminals()
        terminal = allTerminals.data.data?.[0]
      }

      if (!terminal) {
        throw new Error('No POS terminal found for this shop.')
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
