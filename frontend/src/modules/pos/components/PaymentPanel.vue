<script setup lang="ts">
import { ref, computed } from 'vue'
import { usePosStore } from '../stores/posStore'
import ReceiptModal from './ReceiptModal.vue'
import { ArrowLeft, CheckCircle2, DollarSign, CreditCard, Smartphone, Wallet } from '@lucide/vue'

const posStore = usePosStore()
const emit = defineEmits(['back', 'completed'])

const showReceipt = ref(false)
const lastOrderSummary = ref<any>(null)

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

function processPayment() {
  if (!isValid.value) return

  lastOrderSummary.value = {
    items: [...posStore.cart],
    subtotal: subtotal.value,
    total: grandTotal.value,
    method: selectedMethod.value,
    tendered: amountTendered.value || grandTotal.value,
    change: changeDue.value,
    date: new Date().toLocaleString()
  }

  showReceipt.value = true
  posStore.clearCart()
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
        :disabled="!isValid"
        class="w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-500 disabled:bg-slate-300 disabled:cursor-not-allowed text-white font-bold text-sm rounded-xl shadow-md shadow-emerald-600/20 transition-all flex items-center justify-center gap-2 active:scale-98"
      >
        <CheckCircle2 class="w-4 h-4" />
        Complete Payment (${{ grandTotal.toFixed(2) }})
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
