<script setup lang="ts">
import { ref } from 'vue'
import { usePosStore } from '../stores/posStore'
import ReceiptModal from './ReceiptModal.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'

const posStore = usePosStore()
const showReceipt = ref(false)

const paymentMethods = [
  { name: 'Cash', icon: '💰' },
  { name: 'Card', icon: '💳' },
  { name: 'Mobile Money', icon: '📱' },
]

const selectedMethod = ref(paymentMethods[0])
const amount = ref(0)

const processPayment = () => {
  showReceipt.value = true
  posStore.clearCart()
}
</script>

<template>
  <div class="h-full bg-white p-6 flex flex-col border-l border-slate-200">
    <h2 class="text-lg font-semibold text-slate-900 mb-6">Payment Method</h2>
    
    <div class="grid grid-cols-3 gap-3 mb-8">
      <button v-for="method in paymentMethods" :key="method.name" 
        @click="selectedMethod = method"
        :class="['p-4 rounded-lg border text-center transition-all', selectedMethod?.name === method.name ? 'border-primary-500 bg-primary-50 text-primary-700' : 'border-slate-200 hover:border-slate-300']">
        <div class="text-2xl mb-1">{{ method.icon }}</div>
        <div class="font-medium text-sm">{{ method.name }}</div>
      </button>
    </div>

    <UiInput v-model="amount" type="number" label="Amount Tendered" placeholder="0.00" class="mb-6" />

    <UiButton @click="processPayment" class="w-full" size="lg">Process Payment</UiButton>
    
    <ReceiptModal v-model="showReceipt" />
  </div>
</template>
