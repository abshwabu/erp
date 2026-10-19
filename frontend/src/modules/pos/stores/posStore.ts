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

  // Optional customer details for checkout / receipts
  const customer = ref<{
    tin: string
    name: string
    id: string | null
    source: string | null
  }>({
    tin: '',
    name: '',
    id: null,
    source: null,
  })
  const isLookingUpTin = ref(false)
  const tinLookupStatus = ref<'idle' | 'success' | 'not_found' | 'error'>('idle')
  const tinLookupMessage = ref('')

  const setCustomer = (data: { tin?: string; name?: string; id?: string | null; source?: string | null }) => {
    if (data.tin !== undefined) customer.value.tin = data.tin
    if (data.name !== undefined) customer.value.name = data.name
    if (data.id !== undefined) customer.value.id = data.id
    if (data.source !== undefined) customer.value.source = data.source
  }

  // Custom POS Alert / Modal state
  const alertState = ref<{
    open: boolean
    title: string
    message: string
    type: 'warning' | 'error' | 'info' | 'success'
    confirmText: string
    cancelText?: string
    onConfirm?: () => void
    onCancel?: () => void
  }>({
    open: false,
    title: '',
    message: '',
    type: 'warning',
    confirmText: 'Understood',
  })

  const showAlert = (options: {
    title?: string
    message: string
    type?: 'warning' | 'error' | 'info' | 'success'
    confirmText?: string
    onConfirm?: () => void
  } | string) => {
    if (typeof options === 'string') {
      alertState.value = {
        open: true,
        title: 'Notice',
        message: options,
        type: 'warning',
        confirmText: 'Got It',
      }
    } else {
      alertState.value = {
        open: true,
        title: options.title || (options.type === 'error' ? 'Notice' : 'Item Alert'),
        message: options.message,
        type: options.type || 'warning',
        confirmText: options.confirmText || 'Got It',
        onConfirm: options.onConfirm,
      }
    }
  }

  const showConfirm = (options: {
    title: string
    message: string
    confirmText?: string
    cancelText?: string
    type?: 'warning' | 'error' | 'info'
    onConfirm: () => void
    onCancel?: () => void
  }) => {
    alertState.value = {
      open: true,
      title: options.title,
      message: options.message,
      type: options.type || 'warning',
      confirmText: options.confirmText || 'Confirm',
      cancelText: options.cancelText || 'Cancel',
      onConfirm: options.onConfirm,
      onCancel: options.onCancel,
    }
  }

  const closeAlert = () => {
    alertState.value.open = false
  }

  const confirmAlert = () => {
    if (alertState.value.onConfirm) {
      try {
        alertState.value.onConfirm()
      } catch (e) {
        console.error('POS Alert onConfirm error', e)
      }
    }
    closeAlert()
  }

  const cancelAlert = () => {
    if (alertState.value.onCancel) {
      try {
        alertState.value.onCancel()
      } catch (e) {
        console.error('POS Alert onCancel error', e)
      }
    }
    closeAlert()
  }

  const clearCustomer = () => {
    customer.value = { tin: '', name: '', id: null, source: null }
    tinLookupStatus.value = 'idle'
    tinLookupMessage.value = ''
  }

  const lookupCustomerByTin = async (rawTin?: string) => {
    const tin = (rawTin !== undefined ? rawTin : customer.value.tin).trim()
    if (!tin) {
      tinLookupStatus.value = 'idle'
      tinLookupMessage.value = 'Please enter a TIN to look up.'
      return null
    }

    isLookingUpTin.value = true
    tinLookupStatus.value = 'idle'
    tinLookupMessage.value = ''

    try {
      const res = await posApi.lookupTin(tin)
      const data = res.data?.data
      if (data?.found && data.name) {
        customer.value.tin = data.tin || tin
        customer.value.name = data.name
        customer.value.id = data.customer_id || null
        customer.value.source = data.source || 'found'
        tinLookupStatus.value = 'success'
        tinLookupMessage.value = data.source === 'etrade_api'
          ? `Verified via eTrade: ${data.name}`
          : `Found in records: ${data.name}`
      } else {
        tinLookupStatus.value = 'not_found'
        tinLookupMessage.value = data?.message || 'TIN not found in eTrade / records. You can enter the customer name manually.'
      }
      return data
    } catch (err: any) {
      tinLookupStatus.value = 'error'
      tinLookupMessage.value = err?.response?.data?.message || 'Could not verify TIN with eTrade. You can enter the customer name manually.'
      return null
    } finally {
      isLookingUpTin.value = false
    }
  }

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
    selectedShopId.value = shopId
    needsShopSelection.value = false
    session.value = null
    clearCart()
    return ensureSession()
  }

  const ensureSession = async () => {
    // Eagerly establish shop list and default active shop
    const shops = await loadShops()
    if (!selectedShopId.value && shops.length > 0 && shops[0]) {
      selectedShopId.value = shops[0].id
    }

    const currentShopId = selectedShopId.value || shops[0]?.id || undefined

    if (session.value?.status === 'open' && (!currentShopId || session.value.shop_id === currentShopId)) {
      if (!session.value.shop_id && currentShopId) {
        session.value.shop_id = currentShopId
      }
      return session.value
    }

    sessionLoading.value = true
    try {
      const current = await posApi.getCurrentSession(currentShopId)
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
    syncOfflineQueue,
    customer,
    isLookingUpTin,
    tinLookupStatus,
    tinLookupMessage,
    setCustomer,
    clearCustomer,
    lookupCustomerByTin,
    alertState,
    showAlert,
    showConfirm,
    closeAlert,
    confirmAlert,
    cancelAlert,
  }
})
