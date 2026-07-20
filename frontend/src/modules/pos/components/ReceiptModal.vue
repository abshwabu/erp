<script setup lang="ts">
import { Printer, CheckCircle2, X } from '@lucide/vue'

const props = defineProps({
  modelValue: { type: Boolean, required: true },
  orderData: { type: Object, default: () => null }
})

const emit = defineEmits(['update:modelValue', 'close'])

function handleClose() {
  emit('update:modelValue', false)
  emit('close')
}

function handlePrint() {
  window.print()
}
</script>

<template>
  <div v-if="modelValue" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-sm w-full shadow-2xl overflow-hidden border border-slate-200 animate-scale-up">
      <!-- Modal Header -->
      <div class="bg-emerald-600 text-white p-4 text-center relative">
        <button
          @click="handleClose"
          class="absolute top-3 right-3 text-emerald-100 hover:text-white p-1 rounded-lg hover:bg-emerald-700 transition-colors"
        >
          <X class="w-4 h-4" />
        </button>

        <CheckCircle2 class="w-10 h-10 mx-auto mb-1 text-emerald-200" />
        <h3 class="font-extrabold text-base tracking-tight">Payment Successful</h3>
        <p class="text-xs text-emerald-100">Transaction Completed</p>
      </div>

      <!-- Receipt Body (Print Target) -->
      <div class="p-5 text-slate-800 text-xs space-y-4 font-mono">
        <div class="text-center border-b border-dashed border-slate-200 pb-3">
          <h4 class="font-bold text-sm text-slate-900 font-sans uppercase tracking-wider">ERP Store Outlet</h4>
          <p class="text-[11px] text-slate-500 font-sans">Main Warehouse POS Terminal</p>
          <p class="text-[10px] text-slate-400 mt-1">{{ orderData?.date || new Date().toLocaleString() }}</p>
        </div>

        <!-- Items Table -->
        <div v-if="orderData?.items" class="space-y-2">
          <div v-for="(item, idx) in orderData.items" :key="idx" class="flex justify-between items-start text-[11px]">
            <div class="min-w-0 pr-2">
              <span class="font-semibold text-slate-900 block truncate">{{ item.name }}</span>
              <span class="text-[10px] text-slate-400">{{ item.quantity }} x ${{ item.price.toFixed(2) }}</span>
            </div>
            <span class="font-bold text-slate-900">${{ (item.quantity * item.price).toFixed(2) }}</span>
          </div>
        </div>

        <!-- Summary Totals -->
        <div class="border-t border-b border-dashed border-slate-200 py-3 space-y-1 text-slate-600">
          <div class="flex justify-between">
            <span>Subtotal:</span>
            <span>${{ (orderData?.subtotal || 0).toFixed(2) }}</span>
          </div>
          <div class="flex justify-between font-bold text-slate-900 text-sm pt-1">
            <span>Total:</span>
            <span class="text-blue-600">${{ (orderData?.total || 0).toFixed(2) }}</span>
          </div>
          <div class="flex justify-between text-[11px] text-slate-500 pt-1">
            <span>Tendered ({{ orderData?.method || 'Cash' }}):</span>
            <span>${{ (orderData?.tendered || 0).toFixed(2) }}</span>
          </div>
          <div v-if="orderData?.change" class="flex justify-between text-[11px] text-emerald-600 font-bold">
            <span>Change Due:</span>
            <span>${{ (orderData?.change || 0).toFixed(2) }}</span>
          </div>
        </div>

        <div class="text-center text-[10px] text-slate-400 pt-1 font-sans">
          Thank you for your business!
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="p-4 bg-slate-50 border-t border-slate-100 flex gap-2">
        <button
          @click="handlePrint"
          class="flex-1 py-2.5 px-3 bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold text-xs rounded-xl transition-all flex items-center justify-center gap-1.5"
        >
          <Printer class="w-4 h-4" />
          Print Receipt
        </button>

        <button
          @click="handleClose"
          class="flex-1 py-2.5 px-3 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs rounded-xl transition-all shadow-sm"
        >
          New Sale
        </button>
      </div>
    </div>
  </div>
</template>
