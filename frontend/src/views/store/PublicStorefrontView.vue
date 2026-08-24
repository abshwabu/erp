<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/api/client'
import {
  ShoppingBag,
  ShoppingCart,
  X,
  Truck,
  ShieldCheck,
  RefreshCw,
  Star,
  Plus,
  Minus,
  CheckCircle2,
  AlertCircle,
  Building2,
  ArrowRight,
} from '@lucide/vue'

interface Product {
  id: string
  name: string
  sku: string
  selling_price: string | number
  description?: string
  category?: { id: string; name: string }
}

interface CartItem {
  product: Product
  quantity: number
}

const route = useRoute()
const slug = computed(() => route.params.slug as string)

const storefront = ref<any>(null)
const products = ref<Product[]>([])
const loading = ref(true)
const error = ref('')

const cart = ref<CartItem[]>([])
const isCartOpen = ref(false)
const isCheckoutOpen = ref(false)
const isPlacingOrder = ref(false)
const orderSuccess = ref<any>(null)

// Checkout form
const customerName = ref('')
const customerEmail = ref('')
const shippingAddress = ref('')
const checkoutError = ref('')

async function fetchStore() {
  loading.value = true
  error.value = ''
  try {
    const tenantQuery = route.query.tenant ? `?tenant=${route.query.tenant}` : ''
    const previewQuery = route.query.preview ? '&preview=1' : ''
    const res = await api.get(`/store/${slug.value}${tenantQuery}${previewQuery}`)
    storefront.value = res.data?.data?.storefront || res.data?.storefront
    products.value = res.data?.data?.products || res.data?.products || []
  } catch (e: any) {
    error.value = e?.response?.data?.message || 'Store not found or unavailable.'
  } finally {
    loading.value = false
  }
}

function addToCart(product: Product) {
  const existing = cart.value.find(i => i.product.id === product.id)
  if (existing) {
    existing.quantity++
  } else {
    cart.value.push({ product, quantity: 1 })
  }
  isCartOpen.value = true
}

function updateQuantity(item: CartItem, delta: number) {
  item.quantity += delta
  if (item.quantity <= 0) {
    cart.value = cart.value.filter(i => i.product.id !== item.product.id)
  }
}

const cartTotalCents = computed(() => {
  return cart.value.reduce((acc, item) => {
    const priceCents = Math.round(Number(item.product.selling_price) * 100)
    return acc + priceCents * item.quantity
  }, 0)
})

const cartCount = computed(() => {
  return cart.value.reduce((acc, item) => acc + item.quantity, 0)
})

function formatPrice(amount: string | number) {
  const num = Number(amount)
  return '$' + (isNaN(num) ? '0.00' : num.toFixed(2))
}

function formatCents(cents: number) {
  return '$' + (cents / 100).toFixed(2)
}

async function handleCheckout() {
  if (!customerName.value || !customerEmail.value) {
    checkoutError.value = 'Please provide your name and email.'
    return
  }

  checkoutError.value = ''
  isPlacingOrder.value = true

  try {
    const payload = {
      customer_name: customerName.value,
      customer_email: customerEmail.value,
      shipping_address: shippingAddress.value,
      items: cart.value.map(i => ({
        product_id: i.product.id,
        name: i.product.name,
        quantity: i.quantity,
        price_cents: Math.round(Number(i.product.selling_price) * 100),
      })),
    }

    const tenantQuery = route.query.tenant ? `?tenant=${route.query.tenant}` : ''
    const res = await api.post(`/store/${slug.value}/checkout${tenantQuery}`, payload)
    orderSuccess.value = res.data?.data || res.data
    cart.value = []
    isCheckoutOpen.value = false
  } catch (e: any) {
    checkoutError.value = e?.response?.data?.message || 'Checkout failed. Please try again.'
  } finally {
    isPlacingOrder.value = false
  }
}

onMounted(fetchStore)
</script>

<template>
  <div v-if="loading" class="min-h-screen bg-slate-50 flex items-center justify-center p-4">
    <div class="text-center space-y-2">
      <div class="w-10 h-10 border-4 border-primary-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
      <p class="text-sm font-medium text-slate-600">Loading storefront…</p>
    </div>
  </div>

  <div v-else-if="error" class="min-h-screen bg-slate-50 flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl p-6 sm:p-8 text-center space-y-4 shadow-xl border border-slate-200">
      <AlertCircle class="w-12 h-12 text-red-500 mx-auto" />
      <h2 class="text-lg sm:text-xl font-bold text-slate-900">Store Not Available</h2>
      <p class="text-xs sm:text-sm text-slate-500">{{ error }}</p>
    </div>
  </div>

  <div v-else class="min-h-screen bg-white text-slate-900 flex flex-col selection:bg-primary-500 selection:text-white font-sans overflow-x-hidden">
    <!-- Announcement Bar -->
    <div
      v-if="storefront.theme_config?.show_banner"
      class="bg-indigo-600 text-white text-[11px] sm:text-xs text-center py-2 px-3 font-medium tracking-wide"
    >
      {{ storefront.theme_config?.banner_text || 'Welcome to our online store!' }}
    </div>

    <!-- Store Header -->
    <header class="sticky top-0 z-30 bg-white/95 backdrop-blur-md border-b border-slate-100 px-4 sm:px-8 py-3.5 flex items-center justify-between">
      <div class="flex items-center space-x-2.5 min-w-0 pr-2">
        <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-primary-600 flex items-center justify-center text-white shadow-sm shrink-0">
          <Building2 class="w-4 h-4 sm:w-5 sm:h-5" />
        </div>
        <span class="text-base sm:text-lg font-extrabold tracking-tight text-slate-900 truncate">
          {{ storefront.name }}
        </span>
      </div>

      <!-- Navigation & Cart Trigger -->
      <div class="flex items-center space-x-4 sm:space-x-6 shrink-0">
        <a href="#products" class="text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors hidden xs:inline-block">Catalog</a>
        <button
          @click="isCartOpen = true"
          class="relative p-2 rounded-full hover:bg-slate-100 text-slate-700 transition-colors"
          aria-label="Shopping Cart"
        >
          <ShoppingCart class="w-5 h-5" />
          <span
            v-if="cartCount > 0"
            class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-primary-600 text-white text-[10px] font-bold flex items-center justify-center animate-scale"
          >
            {{ cartCount }}
          </span>
        </button>
      </div>
    </header>

    <!-- Main Dynamic Blocks -->
    <main class="flex-1">
      <div v-for="page in storefront.pages" :key="page.id">
        <div v-for="sec in page.sections" :key="sec.id">
          <!-- 1. Hero -->
          <section
            v-if="sec.type === 'hero'"
            :style="{ backgroundColor: sec.props.background_color || '#0f172a', color: sec.props.text_color || '#ffffff' }"
            class="py-12 sm:py-20 px-4 sm:px-8 text-center space-y-4 sm:space-y-5"
          >
            <h1 class="text-2xl sm:text-4xl md:text-5xl font-black tracking-tight max-w-2xl mx-auto leading-tight px-2">
              {{ sec.props.headline }}
            </h1>
            <p class="text-xs sm:text-base md:text-lg max-w-xl mx-auto opacity-90 leading-relaxed px-4">
              {{ sec.props.subheadline }}
            </p>
            <div class="pt-2">
              <a
                :href="sec.props.button_link || '#products'"
                class="inline-flex items-center justify-center px-6 sm:px-8 py-2.5 sm:py-3 rounded-full font-bold text-xs sm:text-sm bg-primary-600 text-white shadow-xl shadow-primary-600/30 hover:bg-primary-500 hover:scale-105 transition-all"
              >
                {{ sec.props.button_text || 'Shop Now' }}
              </a>
            </div>
          </section>

          <!-- 2. Features -->
          <section v-else-if="sec.type === 'features'" class="py-10 sm:py-16 px-4 sm:px-8 bg-slate-50 border-y border-slate-100">
            <h3 v-if="sec.props.title" class="text-lg sm:text-2xl font-bold text-center text-slate-900 mb-6 sm:mb-10">{{ sec.props.title }}</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-8 max-w-5xl mx-auto">
              <div v-for="(feat, i) in sec.props.items" :key="i" class="p-5 sm:p-6 bg-white rounded-2xl shadow-sm border border-slate-100 text-center space-y-2.5 sm:space-y-3">
                <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center mx-auto">
                  <Truck v-if="feat.icon === 'truck'" class="w-5 h-5 sm:w-6 sm:h-6" />
                  <ShieldCheck v-else-if="feat.icon === 'shield'" class="w-5 h-5 sm:w-6 sm:h-6" />
                  <RefreshCw v-else class="w-5 h-5 sm:w-6 sm:h-6" />
                </div>
                <h4 class="font-bold text-sm sm:text-base text-slate-900">{{ feat.title }}</h4>
                <p class="text-xs text-slate-500 leading-relaxed">{{ feat.description }}</p>
              </div>
            </div>
          </section>

          <!-- 3. Product Catalog Grid -->
          <section v-else-if="sec.type === 'product_grid'" id="products" class="py-12 sm:py-20 px-4 sm:px-8 max-w-6xl mx-auto">
            <div class="text-center mb-8 sm:mb-12 space-y-1 sm:space-y-2">
              <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">{{ sec.props.title }}</h2>
              <p v-if="sec.props.subtitle" class="text-xs sm:text-sm text-slate-500">{{ sec.props.subtitle }}</p>
            </div>

            <div v-if="products.length === 0" class="text-center py-12 sm:py-16 bg-slate-50 rounded-2xl border border-slate-100 px-4">
              <ShoppingBag class="w-10 h-10 sm:w-12 sm:h-12 text-slate-300 mx-auto mb-3" />
              <p class="text-slate-600 text-xs sm:text-sm font-medium">No items available in store catalog right now.</p>
            </div>

            <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-6">
              <div
                v-for="prod in products"
                :key="prod.id"
                class="bg-white border border-slate-200 rounded-2xl p-3 sm:p-4 flex flex-col justify-between hover:shadow-xl hover:border-slate-300 transition-all group"
              >
                <div class="space-y-2 sm:space-y-3">
                  <div class="aspect-square bg-slate-100 rounded-xl flex items-center justify-center text-slate-300 group-hover:scale-[1.02] transition-transform">
                    <ShoppingBag class="w-8 h-8 sm:w-10 sm:h-10" />
                  </div>
                  <div>
                    <span class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider text-primary-600 block">{{ prod.category?.name || 'Item' }}</span>
                    <h3 class="font-bold text-xs sm:text-sm text-slate-900 line-clamp-2">{{ prod.name }}</h3>
                  </div>
                </div>

                <div class="flex items-center justify-between pt-3 sm:pt-4 border-t border-slate-100 mt-3 sm:mt-4">
                  <span class="text-sm sm:text-base font-extrabold text-slate-900">{{ formatPrice(prod.selling_price) }}</span>
                  <button
                    @click="addToCart(prod)"
                    class="px-2.5 sm:px-3.5 py-1.5 rounded-lg text-[11px] sm:text-xs font-semibold bg-slate-900 text-white hover:bg-primary-600 active:scale-95 transition-all flex items-center space-x-1"
                  >
                    <Plus class="w-3 h-3 sm:w-3.5 sm:h-3.5" />
                    <span>Add</span>
                  </button>
                </div>
              </div>
            </div>
          </section>

          <!-- 4. Promo Banner -->
          <section
            v-else-if="sec.type === 'promo_banner'"
            :style="{ backgroundColor: sec.props.background_color || '#6366f1' }"
            class="py-10 sm:py-14 px-4 sm:px-8 text-white text-center space-y-2 sm:space-y-3"
          >
            <span class="inline-block px-2.5 sm:px-3 py-0.5 sm:py-1 text-[9px] sm:text-[10px] font-bold rounded-full bg-white/20 uppercase tracking-widest">
              {{ sec.props.badge || 'SPECIAL' }}
            </span>
            <h3 class="text-xl sm:text-3xl font-extrabold px-2">{{ sec.props.headline }}</h3>
            <p class="text-xs sm:text-sm max-w-lg mx-auto opacity-90 leading-relaxed px-4">{{ sec.props.description }}</p>
            <div v-if="sec.props.code" class="pt-2">
              <span class="px-3 sm:px-4 py-1 sm:py-1.5 font-mono text-xs sm:text-sm font-bold rounded-lg bg-white text-slate-900 shadow">
                Use Code: {{ sec.props.code }}
              </span>
            </div>
          </section>

          <!-- 5. Testimonials -->
          <section v-else-if="sec.type === 'testimonials'" class="py-10 sm:py-16 px-4 sm:px-8 bg-slate-50">
            <div class="max-w-5xl mx-auto space-y-6 sm:space-y-8">
              <h3 class="text-xl sm:text-2xl font-bold text-center text-slate-900">{{ sec.props.title }}</h3>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                <div v-for="(t, idx) in sec.props.items" :key="idx" class="p-5 sm:p-6 bg-white rounded-2xl border border-slate-200 shadow-sm space-y-2.5 sm:space-y-3">
                  <div class="flex text-amber-400">
                    <Star v-for="s in (t.rating || 5)" :key="s" class="w-3.5 h-3.5 fill-current" />
                  </div>
                  <p class="text-xs sm:text-sm text-slate-700 italic leading-relaxed">"{{ t.quote }}"</p>
                  <p class="text-xs font-bold text-slate-900">— {{ t.author }}</p>
                </div>
              </div>
            </div>
          </section>

          <!-- 6. Footer -->
          <footer v-else-if="sec.type === 'footer'" class="py-8 sm:py-12 px-4 sm:px-6 bg-slate-950 text-slate-400 text-center text-xs space-y-2 sm:space-y-3">
            <p class="font-extrabold text-white text-sm sm:text-base">{{ sec.props.store_name }}</p>
            <p class="max-w-md mx-auto text-xs">{{ sec.props.tagline }}</p>
            <p class="pt-2 sm:pt-4 text-[10px] text-slate-600">&copy; 2026 {{ sec.props.store_name }}. All rights reserved.</p>
          </footer>
        </div>
      </div>
    </main>

    <!-- Shopping Cart Drawer (Responsive) -->
    <div
      v-if="isCartOpen"
      class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex justify-end"
    >
      <div class="w-full sm:max-w-md bg-white h-full shadow-2xl flex flex-col p-5 sm:p-6 animate-slide-left">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
          <h3 class="text-base sm:text-lg font-bold text-slate-900 flex items-center space-x-2">
            <ShoppingCart class="w-5 h-5 text-primary-600" />
            <span>Your Cart ({{ cartCount }})</span>
          </h3>
          <button @click="isCartOpen = false" class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-700">
            <X class="w-5 h-5" />
          </button>
        </div>

        <div class="flex-1 overflow-y-auto py-4 space-y-3">
          <div v-if="cart.length === 0" class="text-center py-16 text-slate-400 space-y-2">
            <ShoppingCart class="w-12 h-12 mx-auto text-slate-300" />
            <p class="text-xs sm:text-sm">Your shopping bag is empty.</p>
          </div>

          <div
            v-for="item in cart"
            :key="item.product.id"
            class="flex items-center justify-between p-3 sm:p-3.5 rounded-xl border border-slate-200 bg-slate-50/50"
          >
            <div class="space-y-0.5 truncate pr-2">
              <h4 class="font-bold text-xs text-slate-900 truncate">{{ item.product.name }}</h4>
              <p class="text-xs text-slate-500 font-semibold">{{ formatPrice(item.product.selling_price) }}</p>
            </div>

            <div class="flex items-center space-x-2 shrink-0">
              <button
                @click="updateQuantity(item, -1)"
                class="w-7 h-7 rounded-lg bg-white border border-slate-300 flex items-center justify-center text-slate-600 hover:bg-slate-100 active:scale-95"
              >
                <Minus class="w-3.5 h-3.5" />
              </button>
              <span class="text-xs font-bold w-4 text-center">{{ item.quantity }}</span>
              <button
                @click="updateQuantity(item, 1)"
                class="w-7 h-7 rounded-lg bg-white border border-slate-300 flex items-center justify-center text-slate-600 hover:bg-slate-100 active:scale-95"
              >
                <Plus class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>
        </div>

        <!-- Cart Footer -->
        <div v-if="cart.length > 0" class="border-t border-slate-100 pt-4 space-y-4">
          <div class="flex items-center justify-between text-sm">
            <span class="text-slate-500 font-medium">Subtotal</span>
            <span class="text-base sm:text-lg font-black text-slate-900">{{ formatCents(cartTotalCents) }}</span>
          </div>

          <button
            @click="isCheckoutOpen = true; isCartOpen = false"
            class="w-full py-3 rounded-xl bg-primary-600 hover:bg-primary-500 text-white font-bold text-sm shadow-lg shadow-primary-600/30 active:scale-[0.98] transition-all"
          >
            Proceed to Checkout
          </button>
        </div>
      </div>
    </div>

    <!-- Checkout Modal (Responsive) -->
    <div
      v-if="isCheckoutOpen"
      class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4 overflow-y-auto"
    >
      <div class="bg-white rounded-2xl max-w-lg w-full p-5 sm:p-6 space-y-4 shadow-2xl border border-slate-200 my-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="text-base font-bold text-slate-900">Express Checkout</h3>
          <button @click="isCheckoutOpen = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-700">✕</button>
        </div>

        <div v-if="checkoutError" class="p-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-xs">
          {{ checkoutError }}
        </div>

        <form @submit.prevent="handleCheckout" class="space-y-3 text-xs sm:text-sm">
          <div class="space-y-1">
            <label class="font-semibold text-slate-700">Full Name *</label>
            <input v-model="customerName" required placeholder="Jane Doe" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs sm:text-sm focus:ring-2 focus:ring-primary-500/20" />
          </div>

          <div class="space-y-1">
            <label class="font-semibold text-slate-700">Email Address *</label>
            <input v-model="customerEmail" type="email" required placeholder="jane@example.com" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs sm:text-sm focus:ring-2 focus:ring-primary-500/20" />
          </div>

          <div class="space-y-1">
            <label class="font-semibold text-slate-700">Shipping Address</label>
            <textarea v-model="shippingAddress" rows="2" placeholder="123 Main St, New York, NY" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs sm:text-sm focus:ring-2 focus:ring-primary-500/20"></textarea>
          </div>

          <div class="p-3 bg-slate-50 rounded-xl flex items-center justify-between text-xs font-semibold">
            <span class="text-slate-600">Total Due:</span>
            <span class="text-base font-bold text-slate-900">{{ formatCents(cartTotalCents) }}</span>
          </div>

          <button
            type="submit"
            :disabled="isPlacingOrder"
            class="w-full py-3 rounded-xl bg-primary-600 hover:bg-primary-500 text-white font-bold text-sm shadow-lg shadow-primary-600/30 active:scale-[0.98] transition-all disabled:opacity-50"
          >
            {{ isPlacingOrder ? 'Processing...' : 'Place Order & Complete' }}
          </button>
        </form>
      </div>
    </div>

    <!-- Order Success Modal -->
    <div
      v-if="orderSuccess"
      class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4"
    >
      <div class="bg-white rounded-2xl max-w-md w-full p-6 sm:p-8 text-center space-y-4 shadow-2xl my-auto">
        <CheckCircle2 class="w-12 h-12 sm:w-14 sm:h-14 text-emerald-500 mx-auto" />
        <h3 class="text-lg sm:text-xl font-bold text-slate-900">Order Confirmed!</h3>
        <p class="text-xs text-slate-500">{{ orderSuccess.message }}</p>
        <div class="p-3 bg-slate-50 rounded-xl text-xs font-mono text-slate-700">
          Order Number: <span class="font-bold text-slate-900">{{ orderSuccess.order_number }}</span>
        </div>
        <button
          @click="orderSuccess = null"
          class="w-full py-2.5 rounded-xl bg-slate-900 text-white font-bold text-xs hover:bg-slate-800"
        >
          Continue Shopping
        </button>
      </div>
    </div>
  </div>
</template>
