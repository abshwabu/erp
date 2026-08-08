<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import ProductGrid from '../components/ProductGrid.vue'
import POSCart from '../components/POSCart.vue'
import PaymentPanel from '../components/PaymentPanel.vue'
import { usePosStore } from '../stores/posStore'
import { ShoppingCart, Grid } from '@lucide/vue'

const posStore = usePosStore()
const showPayment = ref(false)
const activeTab = ref<'catalog' | 'cart'>('catalog')
const sessionError = ref('')

const totalCartItems = computed(() => {
  return posStore.cart.reduce((sum, item) => sum + item.quantity, 0)
})

const cartTotal = computed(() => {
  return posStore.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0)
})

onMounted(async () => {
  try {
    await posStore.ensureSession()
  } catch (err: any) {
    sessionError.value = err?.message || err?.response?.data?.message || 'Failed to open POS session'
  }
})

function handleCheckout() {
  showPayment.value = true
  activeTab.value = 'cart'
}

function handleBackToCart() {
  showPayment.value = false
}

function handlePaymentComplete() {
  showPayment.value = false
  activeTab.value = 'catalog'
}
</script>

<template>
  <div class="h-[calc(100vh-5rem)] w-full flex flex-col md:flex-row overflow-hidden bg-slate-100 rounded-xl border border-slate-200/80 shadow-sm relative">
    <div
      v-if="sessionError"
      class="absolute inset-x-0 top-0 z-40 bg-amber-50 border-b border-amber-200 text-amber-800 text-sm px-4 py-2"
    >
      {{ sessionError }}
    </div>

    <div class="md:hidden flex items-center justify-between bg-slate-900 text-white px-4 py-2.5 shrink-0 z-20">
      <div class="flex items-center gap-2">
        <span class="font-bold text-sm tracking-tight">POS Terminal</span>
        <span class="text-xs bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 px-2 py-0.5 rounded-full font-mono">
          {{ posStore.session ? 'Session Open' : 'Starting…' }}
        </span>
      </div>

      <div class="flex items-center gap-1 bg-slate-800 p-1 rounded-lg border border-slate-700">
        <button
          @click="activeTab = 'catalog'"
          class="flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-semibold transition-all"
          :class="[activeTab === 'catalog' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-400 hover:text-white']"
        >
          <Grid class="w-3.5 h-3.5" />
          Products
        </button>

        <button
          @click="activeTab = 'cart'"
          class="flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-semibold transition-all relative"
          :class="[activeTab === 'cart' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-400 hover:text-white']"
        >
          <ShoppingCart class="w-3.5 h-3.5" />
          Order
          <span
            v-if="totalCartItems > 0"
            class="ml-1 bg-amber-400 text-slate-950 font-bold px-1.5 py-0.2 rounded-full text-[10px]"
          >
            {{ totalCartItems }}
          </span>
        </button>
      </div>
    </div>

    <div
      class="flex-1 h-full overflow-hidden border-r border-slate-200/80 bg-slate-50 transition-all duration-200"
      :class="[activeTab === 'catalog' ? 'flex' : 'hidden md:flex']"
    >
      <ProductGrid />
    </div>

    <div
      class="w-full md:w-[380px] lg:w-[420px] xl:w-[460px] h-full bg-white border-l border-slate-200/80 shrink-0 flex flex-col transition-all duration-200"
      :class="[activeTab === 'cart' ? 'flex' : 'hidden md:flex']"
    >
      <PaymentPanel
        v-if="showPayment"
        @back="handleBackToCart"
        @completed="handlePaymentComplete"
      />
      <POSCart
        v-else
        @checkout="handleCheckout"
        @switch-to-catalog="activeTab = 'catalog'"
      />
    </div>

    <div
      v-if="activeTab === 'catalog' && totalCartItems > 0 && !showPayment"
      class="md:hidden absolute bottom-3 left-3 right-3 bg-slate-900 text-white p-3 rounded-2xl shadow-xl flex items-center justify-between z-30 border border-slate-700 animate-slide-up"
    >
      <div>
        <span class="text-xs text-slate-400 block font-medium">Cart Total ({{ totalCartItems }} items)</span>
        <span class="text-lg font-bold text-emerald-400">${{ cartTotal.toFixed(2) }}</span>
      </div>

      <button
        @click="activeTab = 'cart'"
        class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-md transition-all active:scale-95"
      >
        <ShoppingCart class="w-4 h-4" />
        View Order
      </button>
    </div>
  </div>
</template>
