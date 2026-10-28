<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useQueryClient } from '@tanstack/vue-query'
import { posApi } from '@/api/pos'
import { usePosStore } from '../stores/posStore'
import { useToast } from '@/composables/useToast'
import ReceiptModal from './ReceiptModal.vue'
import LanguageSwitcher from '@/components/shared/LanguageSwitcher.vue'
import { ArrowLeft, CheckCircle2, DollarSign, Landmark, Wallet, User, Search, Loader2, XCircle, ChevronDown } from '@lucide/vue'

const { t } = useI18n()
const posStore = usePosStore()
const queryClient = useQueryClient()
const toast = useToast()
const emit = defineEmits(['back', 'completed'])

const showReceipt = ref(false)
const lastOrderSummary = ref<any>(null)
const isProcessing = ref(false)
const paymentError = ref('')

async function handleTinSearch() {
  await posStore.lookupCustomerByTin()
}

const paymentMethods = computed(() => [
  { id: 'cash', name: t('pos.cash'), icon: DollarSign },
  { id: 'bank', name: t('pos.bank'), icon: Landmark },
  { id: 'wallet', name: t('pos.wallet'), icon: Wallet },
])

const selectedMethod = ref('cash')

const ethiopianBanks = [
  'Commercial Bank of Ethiopia',
  'Awash Bank',
  'Bank of Abyssinia',
  'Dashen Bank',
  'Cooperative Bank of Oromia',
  'Wegagen Bank',
  'Hibret Bank',
  'Nib International Bank',
  'Lion International Bank',
  'Zemen Bank',
  'Oromia Bank',
  'Bunna International Bank',
  'Berhan Bank',
  'Abay Bank',
  'Addis International Bank',
  'Enat Bank',
  'Global Bank Ethiopia',
  'Hijra Bank',
  'ZamZam Bank',
  'Siinqee Bank',
  'Amhara Bank',
  'Tsedey Bank',
  'Gadaa Bank',
  'Sidama Bank',
  'Shabelle Bank',
]

const selectedBank = ref('Commercial Bank of Ethiopia')

const ethiopianWallets = [
  'Telebirr',
  'M-Pesa',
  'CBE Birr',
  'Awash Birr',
  'e-Birr',
  'Coopay-Ebirr',
  'HelloCash',
]

const selectedWallet = ref('Telebirr')

const transactionReference = ref('')

const subtotal = computed(() => {
  return posStore.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0)
})

const grandTotal = computed(() => {
  return subtotal.value * 1.05 // Total with 5% tax
})

const amountTendered = ref<number | null>(null)

// Auto-fill exact cash tendered so checkout button is immediately clickable
watch(grandTotal, (val) => {
  if (amountTendered.value === null || amountTendered.value <= 0) {
    amountTendered.value = val
  }
}, { immediate: true })

const quickAmounts = computed(() => {
  const total = grandTotal.value
  const roundedUp10 = Math.ceil(total / 10) * 10
  const roundedUp20 = Math.ceil(total / 20) * 20
  const roundedUp50 = Math.ceil(total / 50) * 50

  const presets = [total, roundedUp10, roundedUp20, roundedUp50].filter(
    (v, i, a) => a.indexOf(v) === i && v >= total
  )
  return presets
})

const changeDue = computed(() => {
  if (!amountTendered.value || amountTendered.value < grandTotal.value) return 0
  return amountTendered.value - grandTotal.value
})

const isValid = computed(() => {
  if (selectedMethod.value === 'cash') {
    return (amountTendered.value || 0) >= grandTotal.value
  }
  if (selectedMethod.value === 'bank') {
    return !!selectedBank.value
  }
  if (selectedMethod.value === 'wallet') {
    return !!selectedWallet.value
  }
  return true
})

function setQuickAmount(amt: number) {
  amountTendered.value = amt
}

async function processPayment() {
  if (!isValid.value || isProcessing.value) return
  isProcessing.value = true
  paymentError.value = ''

  try {
    const session = await posStore.ensureSession()
    if (!session?.id) {
      throw new Error('No open POS session')
    }

    const cartItems = [...posStore.cart]
    const tenderedCents = Math.round((amountTendered.value ?? grandTotal.value) * 100)
    const totalCents = Math.round(grandTotal.value * 100)
    const changeCents = Math.max(0, tenderedCents - totalCents)

    const paymentMethod = selectedMethod.value
    const refStr = transactionReference.value.trim()
    const paymentRef = paymentMethod === 'bank'
      ? (refStr ? `${selectedBank.value} - ${refStr}` : selectedBank.value)
      : paymentMethod === 'wallet'
        ? (refStr ? `${selectedWallet.value} - ${refStr}` : selectedWallet.value)
        : (refStr || null)

    const methodDisplay = paymentMethod === 'bank'
      ? `Bank (${selectedBank.value})`
      : paymentMethod === 'wallet'
        ? `Wallet (${selectedWallet.value})`
        : 'Cash'

    const response = await posApi.checkout({
      session_id: session.id,
      customer_id: posStore.customer.id || null,
      customer_tin: posStore.customer.tin?.trim() || null,
      customer_name: posStore.customer.name?.trim() || null,
      location_id: posStore.selectedShop()?.stock_location_id
        ?? session.terminal?.location_id
        ?? null,
      items: cartItems.map(item => ({
        product_id: String(item.productId || item.product_id || item.id),
        variant_id: item.variantId || item.variant_id || null,
        quantity: item.quantity,
        unit_price_cents: Math.round((item.price || 0) * 100),
      })),
      payments: [{
        method: paymentMethod,
        amount_cents: paymentMethod === 'cash' ? tenderedCents : totalCents,
        change_cents: paymentMethod === 'cash' ? changeCents : 0,
        reference: paymentRef,
      }],
    })

    const transaction = response.data.data

    cartItems.forEach(item => {
      posStore.deductStock(item.productId || item.product_id || item.id, item.quantity, item.variantId || item.variant_id)
    })

    queryClient.invalidateQueries({ queryKey: ['inventory'] })
    queryClient.invalidateQueries({ queryKey: ['shops'] })

    try {
      if (typeof BroadcastChannel !== 'undefined') {
        const channel = new BroadcastChannel('pos-sync-channel')
        channel.postMessage({ type: 'POS_SALE_COMPLETED', timestamp: Date.now() })
        channel.close()
      }
    } catch (e) {
      console.debug('Failed to broadcast POS sale', e)
    }

    lastOrderSummary.value = {
      items: cartItems,
      subtotal: (transaction.subtotal_cents ?? Math.round(subtotal.value * 100)) / 100,
      total: (transaction.total_cents ?? totalCents) / 100,
      method: methodDisplay,
      tendered: paymentMethod === 'cash' ? (amountTendered.value || grandTotal.value) : grandTotal.value,
      change: paymentMethod === 'cash' ? changeDue.value : 0,
      date: new Date().toLocaleString(),
      receiptNumber: transaction.receipt_number,
      company: transaction.company || null,
      customerTin: transaction.customer_tin || posStore.customer.tin || null,
      customerName: transaction.customer_name || posStore.customer.name || null,
      reference: paymentRef || null,
    }

    showReceipt.value = true
    posStore.clearCart()
    posStore.clearCustomer()
    transactionReference.value = ''
  } catch (err: any) {
    const message = err?.errors?.items?.[0]
      || err?.response?.data?.message
      || err?.message
      || 'Checkout failed'
    paymentError.value = message
    toast.error(message)
  } finally {
    isProcessing.value = false
  }
}

function handleReceiptClose() {
  showReceipt.value = false
  emit('completed')
}
</script>

<template>
  <div class="h-full min-h-0 bg-white flex flex-col overflow-hidden">
    <!-- Payment Header -->
    <div class="px-3.5 py-3 sm:p-4 border-b border-slate-200/80 flex items-center justify-between shrink-0 bg-slate-50/50">
      <button
        @click="emit('back')"
        class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors cursor-pointer"
      >
        <ArrowLeft class="w-4 h-4" />
        {{ $t('common.back') }}
      </button>

      <div class="flex items-center gap-2">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ $t('pos.terminal') }}</span>
        <LanguageSwitcher variant="light" :compact="true" />
      </div>
    </div>

    <!-- Payment Body -->
    <div class="flex-1 min-h-0 overflow-y-auto p-3.5 sm:p-4 space-y-3.5 sm:space-y-4 custom-scrollbar">
      <div
        v-if="paymentError"
        class="rounded-xl border border-red-200 bg-red-50 text-red-700 text-sm px-3 py-2"
      >
        {{ paymentError }}
      </div>

      <!-- Total Banner -->
      <div class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white rounded-2xl p-4 sm:p-5 shadow-lg shadow-blue-600/15 text-center">
        <span class="text-[11px] sm:text-xs font-medium text-blue-200 uppercase tracking-wider block mb-0.5 sm:mb-1">{{ $t('pos.amountDue') }}</span>
        <span class="text-2xl sm:text-3xl font-extrabold font-mono">${{ grandTotal.toFixed(2) }}</span>
      </div>

      <!-- Customer Details (Optional) -->
      <div class="rounded-2xl border border-slate-200/90 bg-slate-50/70 p-3 sm:p-3.5 space-y-2.5 sm:space-y-3">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-1.5 text-xs font-semibold text-slate-800">
            <User class="w-4 h-4 text-slate-500" />
            <span>{{ $t('pos.customerInfo') }}</span>
            <span class="text-[10px] font-normal text-slate-500 bg-white border border-slate-200/80 px-2 py-0.5 rounded-full shadow-2xs">
              {{ $t('common.optional') }}
            </span>
          </div>

          <button
            v-if="posStore.customer.tin || posStore.customer.name"
            type="button"
            @click="posStore.clearCustomer"
            class="text-[11px] font-medium text-slate-400 hover:text-red-500 transition-colors flex items-center gap-1"
          >
            <XCircle class="w-3.5 h-3.5" />
            {{ $t('common.clear') }}
          </button>
        </div>

        <!-- Customer TIN with eTrade Lookup -->
        <div class="space-y-1">
          <label class="block text-[11px] font-medium text-slate-600">
            {{ $t('pos.customerTin') }}
          </label>
          <div class="relative flex items-center">
            <input
              v-model="posStore.customer.tin"
              @keydown.enter.prevent="handleTinSearch"
              type="text"
              maxlength="30"
              :placeholder="$t('pos.enterTin')"
              class="w-full pl-3 pr-20 py-2 bg-white border border-slate-200 rounded-xl text-xs font-mono text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all placeholder:text-slate-400"
            />
            <button
              type="button"
              @click="handleTinSearch"
              :disabled="posStore.isLookingUpTin || !posStore.customer.tin?.trim()"
              class="absolute right-1 px-2.5 py-1.5 bg-blue-600 hover:bg-blue-500 disabled:bg-slate-200 disabled:text-slate-400 disabled:cursor-not-allowed text-white text-[11px] font-semibold rounded-lg transition-all flex items-center gap-1 shadow-xs cursor-pointer active:scale-95"
              title="Lookup customer name from eTrade or database records"
            >
              <Loader2 v-if="posStore.isLookingUpTin" class="w-3 h-3 animate-spin" />
              <Search v-else class="w-3 h-3" />
              <span>{{ $t('pos.lookupTin') }}</span>
            </button>
          </div>

          <!-- Lookup feedback badge / message -->
          <div
            v-if="posStore.tinLookupMessage"
            class="text-[11px] px-2.5 py-1.5 rounded-lg flex items-start gap-1.5 mt-1 transition-all"
            :class="[
              posStore.tinLookupStatus === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' :
              posStore.tinLookupStatus === 'not_found' ? 'bg-amber-50 text-amber-800 border border-amber-200' :
              posStore.tinLookupStatus === 'error' ? 'bg-rose-50 text-rose-800 border border-rose-200' :
              'bg-slate-100 text-slate-600'
            ]"
          >
            <CheckCircle2 v-if="posStore.tinLookupStatus === 'success'" class="w-3.5 h-3.5 text-emerald-600 shrink-0 mt-0.5" />
            <span class="leading-tight">{{ posStore.tinLookupMessage }}</span>
          </div>
        </div>

        <!-- Customer Name (Optional, auto-filled or manual) -->
        <div class="space-y-1">
          <div class="flex items-center justify-between text-[11px]">
            <label class="font-medium text-slate-600">
              {{ $t('pos.customerName') }}
            </label>
            <span v-if="posStore.customer.source === 'etrade_api'" class="text-[10px] text-emerald-600 font-semibold">
              via eTrade
            </span>
          </div>
          <input
            v-model="posStore.customer.name"
            type="text"
            maxlength="255"
            :placeholder="$t('pos.customerName') + ' (' + $t('common.optional') + ')'"
            class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all placeholder:text-slate-400"
          />
        </div>
      </div>

      <!-- Payment Methods Grid -->
      <div>
        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">{{ $t('pos.paymentMethod') }}</label>
        <div class="grid grid-cols-3 gap-2">
          <button
            v-for="method in paymentMethods"
            :key="method.id"
            type="button"
            @click="selectedMethod = method.id"
            class="p-3 rounded-xl border flex flex-col items-center justify-center gap-1.5 text-xs font-semibold transition-all cursor-pointer select-none"
            :class="[
              selectedMethod === method.id
                ? 'border-blue-600 bg-blue-50 text-blue-700 shadow-sm ring-1 ring-blue-500/20'
                : 'border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50'
            ]"
          >
            <component :is="method.icon" class="w-5 h-5" />
            {{ method.name }}
          </button>
        </div>
      </div>

      <!-- Bank Selection Section -->
      <div v-if="selectedMethod === 'bank'" class="space-y-3 rounded-2xl border border-slate-200/90 bg-slate-50/70 p-3 sm:p-3.5 animate-in fade-in duration-150">
        <div class="space-y-1.5">
          <div class="flex items-center justify-between">
            <label class="block text-xs font-semibold text-slate-700">
              {{ $t('pos.selectBank') }}
            </label>
            <span class="text-[10px] font-bold text-blue-700 bg-blue-50 border border-blue-200/80 px-2 py-0.5 rounded-full">
              {{ $t('pos.defaultCbe') }}
            </span>
          </div>

          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
              <Landmark class="w-4 h-4 text-blue-600" />
            </div>
            <select
              v-model="selectedBank"
              class="w-full pl-9 pr-8 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all appearance-none cursor-pointer"
            >
              <option v-for="bank in ethiopianBanks" :key="bank" :value="bank">
                {{ bank }}
              </option>
            </select>
            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
              <ChevronDown class="w-4 h-4" />
            </div>
          </div>
        </div>

        <!-- Optional Transfer Reference -->
        <div class="space-y-1 pt-2 border-t border-slate-200/70">
          <label class="block text-[11px] font-medium text-slate-600">
            {{ $t('pos.transferRef') }}
          </label>
          <input
            v-model="transactionReference"
            type="text"
            maxlength="100"
            :placeholder="$t('pos.transferRefPlaceholder')"
            class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-mono text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all placeholder:text-slate-400"
          />
        </div>
      </div>

      <!-- Wallet Selection Section -->
      <div v-if="selectedMethod === 'wallet'" class="space-y-3 rounded-2xl border border-slate-200/90 bg-slate-50/70 p-3 sm:p-3.5 animate-in fade-in duration-150">
        <div class="space-y-1.5">
          <div class="flex items-center justify-between">
            <label class="block text-xs font-semibold text-slate-700">
              {{ $t('pos.selectWallet') }}
            </label>
            <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200/80 px-2 py-0.5 rounded-full">
              {{ $t('pos.defaultTelebirr') }}
            </span>
          </div>

          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
              <Wallet class="w-4 h-4 text-emerald-600" />
            </div>
            <select
              v-model="selectedWallet"
              class="w-full pl-9 pr-8 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all appearance-none cursor-pointer"
            >
              <option v-for="wallet in ethiopianWallets" :key="wallet" :value="wallet">
                {{ wallet }}
              </option>
            </select>
            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
              <ChevronDown class="w-4 h-4" />
            </div>
          </div>
        </div>

        <!-- Optional Transaction ID -->
        <div class="space-y-1 pt-2 border-t border-slate-200/70">
          <label class="block text-[11px] font-medium text-slate-600">
            {{ $t('pos.walletTxnId') }}
          </label>
          <input
            v-model="transactionReference"
            type="text"
            maxlength="100"
            :placeholder="$t('pos.walletTxnPlaceholder')"
            class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-mono text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all placeholder:text-slate-400"
          />
        </div>
      </div>

      <!-- Cash Amount Tendered & Presets -->
      <div v-if="selectedMethod === 'cash'" class="space-y-3">
        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $t('pos.tenderedAmount') }} ($)</label>
        
        <!-- Preset Quick Cash Buttons -->
        <div class="flex flex-wrap gap-2">
          <button
            v-for="amt in quickAmounts"
            :key="amt"
            @click="setQuickAmount(amt)"
            class="px-3 py-1.5 rounded-lg text-xs font-bold border border-slate-200 bg-slate-50 hover:bg-blue-50 hover:border-blue-300 hover:text-blue-600 transition-all font-mono"
            :class="{ 'bg-blue-600 text-white border-blue-600': amountTendered === amt }"
          >
            ${{ amt.toFixed(2) }}
          </button>
        </div>

        <input
          v-model.number="amountTendered"
          type="number"
          step="0.01"
          placeholder="0.00"
          class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-mono font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
        />

        <!-- Change Calculation Widget -->
        <div class="p-3 bg-slate-50 border border-slate-200/80 rounded-xl flex items-center justify-between text-xs">
          <span class="text-slate-500 font-medium">{{ $t('pos.change') }}:</span>
          <span
            class="font-mono font-bold text-sm"
            :class="[changeDue >= 0 ? 'text-emerald-600' : 'text-slate-400']"
          >
            ${{ changeDue.toFixed(2) }}
          </span>
        </div>
      </div>
    </div>

    <!-- Complete Payment Footer Button -->
    <div class="border-t border-slate-200/80 p-3 sm:p-4 bg-slate-50/95 shrink-0 pb-[max(0.85rem,env(safe-area-inset-bottom))]">
      <button
        @click="processPayment"
        :disabled="!isValid || isProcessing"
        class="w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-500 disabled:bg-slate-300 disabled:cursor-not-allowed text-white font-bold text-xs sm:text-sm rounded-xl shadow-md shadow-emerald-600/25 transition-all flex items-center justify-center gap-2 active:scale-98 cursor-pointer select-none"
      >
        <CheckCircle2 class="w-4 h-4" />
        <span v-if="isProcessing">{{ $t('pos.completing') }}</span>
        <span v-else>{{ $t('pos.completeOrder') }} (${{ grandTotal.toFixed(2) }})</span>
      </button>
    </div>

    <!-- Receipt Modal -->
    <ReceiptModal
      v-model="showReceipt"
      :order-data="lastOrderSummary"
      @close="handleReceiptClose"
    />
  </div>
</template>
