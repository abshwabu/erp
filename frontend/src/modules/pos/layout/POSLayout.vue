<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import ProductGrid from '../components/ProductGrid.vue'
import POSCart from '../components/POSCart.vue'
import PaymentPanel from '../components/PaymentPanel.vue'
import POSAlertModal from '../components/POSAlertModal.vue'
import { usePosStore } from '../stores/posStore'
import { ShoppingCart, Grid, Store, Check, X, Loader2 } from '@lucide/vue'
import UiButton from '@/components/ui/UiButton.vue'
import LanguageSwitcher from '@/components/shared/LanguageSwitcher.vue'

const posStore = usePosStore()
const showPayment = ref(false)
const activeTab = ref<'catalog' | 'cart'>('catalog')
const sessionError = ref('')
const selectingShop = ref(false)
const switchingShopId = ref<string | null>(null)
const isSwitchingShop = computed(() => !!switchingShopId.value)

const totalCartItems = computed(() => {
  return posStore.cart.reduce((sum, item) => sum + item.quantity, 0)
})

const cartTotal = computed(() => {
  return posStore.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0)
})

const shopName = computed(() => posStore.selectedShop()?.name || posStore.session?.shop?.name || '')

async function bootstrapSession() {
  sessionError.value = ''
  try {
    await posStore.ensureSession()
  } catch (err: any) {
    if (posStore.needsShopSelection) {
      selectingShop.value = true
      sessionError.value = ''
      return
    }
    sessionError.value = err?.message || err?.response?.data?.message || 'Failed to open POS session'
  }
}

async function chooseShop(shopId: string) {
  if (posStore.selectedShopId === shopId && posStore.session?.status === 'open') {
    selectingShop.value = false
    return
  }

  sessionError.value = ''
  switchingShopId.value = shopId
  try {
    await posStore.selectShop(shopId)
    selectingShop.value = false
  } catch (err: any) {
    sessionError.value = err?.message || 'Failed to open shop session'
    if (posStore.needsShopSelection) selectingShop.value = true
  } finally {
    switchingShopId.value = null
  }
}

onMounted(bootstrapSession)

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
  <div class="h-full w-full flex-1 min-h-0 flex flex-col md:flex-row overflow-hidden bg-slate-100 rounded-xl border border-slate-200/80 shadow-sm relative">
    <!-- Shop Selection Modal -->
    <div
      v-if="selectingShop"
      class="absolute inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 animate-fade-in"
    >
      <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4 border border-slate-200">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
              <Store class="h-5 w-5" />
            </div>
            <div>
              <h2 class="text-base font-bold text-slate-900">{{ $t('pos.chooseShop') }}</h2>
              <p class="text-xs text-slate-500">{{ $t('pos.chooseShopSubtitle') }}</p>
            </div>
          </div>
          <button
            v-if="!posStore.needsShopSelection"
            type="button"
            @click="selectingShop = false"
            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer"
            title="Close"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <div class="space-y-2 max-h-80 overflow-y-auto pr-1">
          <button
            v-for="shop in posStore.availableShops"
            :key="shop.id"
            type="button"
            :disabled="isSwitchingShop"
            class="w-full text-left px-4 py-3 rounded-xl border transition-all flex items-center justify-between group cursor-pointer disabled:opacity-60"
            :class="[
              posStore.selectedShopId === shop.id
                ? 'border-blue-500 bg-blue-50/70 ring-2 ring-blue-500/20'
                : 'border-slate-200 hover:border-blue-300 hover:bg-slate-50'
            ]"
            @click="chooseShop(shop.id)"
          >
            <div class="min-w-0 flex-1 pr-3">
              <div class="font-bold text-sm text-slate-900 flex items-center gap-2">
                <span class="truncate">{{ shop.name }}</span>
                <span
                  v-if="posStore.selectedShopId === shop.id"
                  class="text-[10px] font-extrabold uppercase px-1.5 py-0.5 rounded bg-blue-600 text-white shrink-0"
                >
                  Active
                </span>
              </div>
              <div class="text-xs text-slate-500 font-mono mt-0.5 truncate">
                {{ shop.code }} · {{ shop.stock_location?.name || $t('pos.stockLocation') }}
              </div>
            </div>

            <div v-if="switchingShopId === shop.id" class="shrink-0 text-blue-600">
              <Loader2 class="w-5 h-5 animate-spin" />
            </div>
            <div v-else-if="posStore.selectedShopId === shop.id" class="shrink-0 text-blue-600">
              <Check class="w-5 h-5" />
            </div>
          </button>
        </div>
      </div>
    </div>

    <div
      v-if="sessionError"
      class="absolute inset-x-0 top-0 z-40 bg-amber-50 border-b border-amber-200 text-amber-800 text-sm px-4 py-2"
    >
      {{ sessionError }}
    </div>

    <!-- Mobile Top Navigation Bar -->
    <div class="md:hidden flex items-center justify-between bg-slate-900 text-white px-3.5 py-2.5 shrink-0 z-20">
      <div class="flex items-center gap-2">
        <button
          v-if="posStore.availableShops.length > 1"
          type="button"
          @click="selectingShop = true; posStore.needsShopSelection = true"
          class="font-bold text-sm tracking-tight flex items-center gap-1.5 hover:text-blue-300 transition-colors"
          title="Tap to switch shop"
        >
          <span>{{ shopName || $t('pos.terminal') }}</span>
          <span class="text-[10px] text-blue-400 font-normal underline decoration-dotted">{{ $t('pos.changeShop') }}</span>
        </button>
        <span v-else class="font-bold text-sm tracking-tight">{{ shopName || $t('pos.terminal') }}</span>

        <span class="text-xs bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 px-2 py-0.5 rounded-full font-mono">
          {{ posStore.session ? $t('pos.sessionOpen') : $t('pos.starting') }}
        </span>
      </div>

      <div class="flex items-center gap-1.5">
        <LanguageSwitcher variant="dark" :compact="true" />

        <div class="flex items-center gap-1 bg-slate-800 p-1 rounded-lg border border-slate-700">
          <button
            @click="activeTab = 'catalog'"
            class="flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-semibold transition-all cursor-pointer"
            :class="[activeTab === 'catalog' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-400 hover:text-white']"
          >
            <Grid class="w-3.5 h-3.5" />
            {{ $t('pos.productsTab') }}
          </button>

          <button
            @click="activeTab = 'cart'"
            class="flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-semibold transition-all relative cursor-pointer"
            :class="[activeTab === 'cart' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-400 hover:text-white']"
          >
            <ShoppingCart class="w-3.5 h-3.5" />
            {{ $t('pos.orderTab') }}
            <span
              v-if="totalCartItems > 0"
              class="ml-1 bg-amber-400 text-slate-950 font-bold px-1.5 py-0.2 rounded-full text-[10px]"
            >
              {{ totalCartItems }}
            </span>
          </button>
        </div>
      </div>
    </div>

    <!-- Product Catalog Column -->
    <div
      class="flex-1 h-full min-h-0 overflow-hidden border-r border-slate-200/80 bg-slate-50 transition-all duration-200 flex flex-col"
      :class="[activeTab === 'catalog' ? 'flex' : 'hidden md:flex']"
    >
      <ProductGrid @switch-shop="selectingShop = true; posStore.needsShopSelection = true" />
    </div>

    <!-- Order Cart & Payment Column -->
    <div
      class="flex-1 md:flex-initial w-full md:w-[360px] lg:w-[400px] xl:w-[440px] h-full min-h-0 bg-white border-l border-slate-200/80 md:shrink-0 flex flex-col transition-all duration-200"
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
        <span class="text-xs text-slate-400 block font-medium">{{ $t('pos.cartTotal') }} ({{ totalCartItems }})</span>
        <span class="text-lg font-bold text-emerald-400">${{ cartTotal.toFixed(2) }}</span>
      </div>

      <button
        @click="activeTab = 'cart'"
        class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-md transition-all active:scale-95"
      >
        <ShoppingCart class="w-4 h-4" />
        {{ $t('pos.viewOrder') }}
      </button>
    </div>

    <!-- Custom POS Alert Modal -->
    <POSAlertModal />
  </div>
</template>
