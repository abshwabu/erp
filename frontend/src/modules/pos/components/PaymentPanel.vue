<script setup lang="ts">
import { ref, computed } from 'vue'
import { useQueryClient } from '@tanstack/vue-query'
import { posApi } from '@/api/pos'
import { usePosStore } from '../stores/posStore'
import { useToast } from '@/composables/useToast'
import ReceiptModal from './ReceiptModal.vue'
import { ArrowLeft, CheckCircle2, DollarSign, CreditCard, Smartphone } from '@lucide/vue'

const posStore = usePosStore()
const queryClient = useQueryClient()
const toast = useToast()
const emit = defineEmits(['back', 'completed'])

const showReceipt = ref(false)
const lastOrderSummary = ref<any>(null)
const isProcessing = ref(false)
const paymentError = ref('')

const paymentMethods = [
  { id: 'cash', name: 'Cash', icon: DollarSign },
  { id: 'card', name: 'Card', icon: CreditCard },
  { id: 'mobile', name: 'Mobile Money', icon: Smartphone }
]

const selectedMethod = ref('cash')

const subtotal = computed(() => {
  return posStore.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0)
})

const grandTotal = computed(() => {
  return subtotal.value * 1.05 // Total with 5% tax
})

const amountTendered = ref<number | null>(null)

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

    const response = await posApi.checkout({
      session_id: session.id,
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
        method: selectedMethod.value,
        amount_cents: selectedMethod.value === 'cash' ? tenderedCents : totalCents,
        change_cents: selectedMethod.value === 'cash' ? changeCents : 0,
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
      method: selectedMethod.value,
      tendered: (amountTendered.value || grandTotal.value),
      change: changeDue.value,
      date: new Date().toLocaleString(),
      receiptNumber: transaction.receipt_number,
      company: transaction.company || null,
    }

    showReceipt.value = true
    posStore.clearCart()
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
  <div class="h-full bg-white flex flex-col justify-between overflow-hidden">
    <!-- Payment Header -->
    <div class="p-4 border-b border-slate-200/80 flex items-center justify-between shrink-0 bg-slate-50/50">
      <button
        @click="emit('back')"
        class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors"
      >
        <ArrowLeft class="w-4 h-4" />
        Back to Order
      </button>

      <span class="text-xs font-bold uppercase tracking-wider text-slate-400">POS Checkout</span>
    </div>

    <!-- Payment Body -->
    <div class="flex-1 overflow-y-auto p-4 space-y-5">
      <div
        v-if="paymentError"
        class="rounded-xl border border-red-200 bg-red-50 text-red-700 text-sm px-3 py-2"
      >
        {{ paymentError }}
      </div>

      <!-- Total Banner -->
      <div class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white rounded-2xl p-5 shadow-lg shadow-blue-600/15 text-center">
        <span class="text-xs font-medium text-blue-200 uppercase tracking-wider block mb-1">Total Amount Due</span>
        <span class="text-3xl font-extrabold font-mono">${{ grandTotal.toFixed(2) }}</span>
      </div>

      <!-- Payment Methods Grid -->
      <div>
        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Payment Method</label>
        <div class="grid grid-cols-3 gap-2">
          <button
            v-for="method in paymentMethods"
            :key="method.id"
            @click="selectedMethod = method.id"
            class="p-3 rounded-xl border flex flex-col items-center justify-center gap-1.5 text-xs font-semibold transition-all"
            :class="[
              selectedMethod === method.id
                ? 'border-blue-600 bg-blue-50 text-blue-700 shadow-sm'
                : 'border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50'
            ]"
          >
            <component :is="method.icon" class="w-5 h-5" />
            {{ method.name }}
          </button>
        </div>
      </div>

      <!-- Cash Amount Tendered & Presets -->
      <div v-if="selectedMethod === 'cash'" class="space-y-3">
        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Amount Tendered ($)</label>
        
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
          placeholder="Enter tendered amount..."
          class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-mono font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
        />

        <!-- Change Calculation Widget -->
        <div class="p-3 bg-slate-50 border border-slate-200/80 rounded-xl flex items-center justify-between text-xs">
          <span class="text-slate-500 font-medium">Change Due:</span>
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
    <div class="border-t border-slate-200/80 p-4 bg-slate-50/80 shrink-0">
      <button
        @click="processPayment"
        :disabled="!isValid || isProcessing"
        class="w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-500 disabled:bg-slate-300 disabled:cursor-not-allowed text-white font-bold text-sm rounded-xl shadow-md shadow-emerald-600/20 transition-all flex items-center justify-center gap-2 active:scale-98"
      >
        <CheckCircle2 class="w-4 h-4" />
        <span v-if="isProcessing">Processing...</span>
        <span v-else>Complete Payment (${{ grandTotal.toFixed(2) }})</span>
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
