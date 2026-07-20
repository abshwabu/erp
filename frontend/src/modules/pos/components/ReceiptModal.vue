<script setup lang="ts">
import { computed } from 'vue'
import { Printer, CheckCircle2, X } from '@lucide/vue'
import { useTenantStore } from '@/stores/tenant'

const tenantStore = useTenantStore()

const props = defineProps({
  modelValue: { type: Boolean, required: true },
  orderData: { type: Object, default: () => null }
})

const emit = defineEmits(['update:modelValue', 'close'])

const companyName = computed(() => {
  return tenantStore.currentTenant?.name || 'KESB Enterprise Solutions'
})

function handleClose() {
  emit('update:modelValue', false)
  emit('close')
}

function handlePrint() {
  window.print()
}
</script>

<template>
  <div v-if="modelValue" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 modal-container">
    <div class="bg-white rounded-2xl max-w-sm w-full shadow-2xl overflow-hidden border border-slate-200 animate-scale-up modal-card">
      
      <!-- Modal Header (Screen Only) -->
      <div class="bg-emerald-600 text-white p-4 text-center relative no-print">
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

      <!-- Printable Thermal Receipt Container -->
      <div id="printable-receipt-content" class="p-5 text-slate-800 text-xs space-y-4 font-mono">
        <!-- Receipt Header -->
        <div class="text-center border-b border-dashed border-slate-300 pb-3">
          <h4 class="font-extrabold text-sm text-slate-900 font-sans uppercase tracking-wider mb-0.5">
            {{ companyName }}
          </h4>
          <p class="text-[11px] text-slate-500 font-sans">Official Sales Receipt</p>
          
          <div class="mt-2 text-[10px] text-slate-600 space-y-0.5 font-mono">
            <div class="flex justify-between">
              <span class="text-slate-400">Receipt No:</span>
              <span class="font-bold text-slate-900">{{ orderData?.receiptNumber || 'REC-884920' }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-400">Date:</span>
              <span>{{ orderData?.date || new Date().toLocaleString() }}</span>
            </div>
          </div>
        </div>

        <!-- Itemized List Table -->
        <div v-if="orderData?.items" class="space-y-2">
          <div class="flex justify-between font-bold text-[10px] text-slate-400 uppercase tracking-wider border-b border-slate-100 pb-1">
            <span>Item</span>
            <span>Qty x Price</span>
            <span>Total</span>
          </div>

          <div v-for="(item, idx) in orderData.items" :key="idx" class="flex justify-between items-start text-[11px] py-0.5">
            <div class="min-w-0 pr-2 flex-1">
              <span class="font-semibold text-slate-900 block truncate">{{ item.name }}</span>
            </div>
            <div class="text-[10px] text-slate-500 w-20 text-center font-mono">
              {{ item.quantity }} x ${{ item.price.toFixed(2) }}
            </div>
            <div class="font-bold text-slate-900 font-mono text-right min-w-[50px]">
              ${{ (item.quantity * item.price).toFixed(2) }}
            </div>
          </div>
        </div>

        <!-- Totals & Payment Summary -->
        <div class="border-t border-b border-dashed border-slate-300 py-2.5 space-y-1 text-[11px] text-slate-600">
          <div class="flex justify-between">
            <span>Subtotal:</span>
            <span class="font-mono">${{ (orderData?.subtotal || 0).toFixed(2) }}</span>
          </div>
          <div class="flex justify-between font-bold text-slate-900 text-sm pt-1 border-t border-slate-100">
            <span>TOTAL VALUE:</span>
            <span class="font-mono text-blue-600">${{ (orderData?.total || 0).toFixed(2) }}</span>
          </div>
          <div class="flex justify-between text-[10px] text-slate-500 pt-1">
            <span>Paid via {{ orderData?.method || 'Cash' }}:</span>
            <span class="font-mono">${{ (orderData?.tendered || 0).toFixed(2) }}</span>
          </div>
          <div v-if="orderData?.change !== undefined" class="flex justify-between text-[11px] text-emerald-600 font-bold">
            <span>Change Due:</span>
            <span class="font-mono">${{ (orderData?.change || 0).toFixed(2) }}</span>
          </div>
        </div>

        <!-- Footer / Branding Notice -->
        <div class="text-center pt-2 font-sans space-y-1">
          <p class="text-[11px] font-semibold text-slate-700">Thank you for shopping with us!</p>
          <div class="border-t border-slate-200 pt-2 text-[10px] font-mono text-slate-400 font-medium">
            Powered by KESB Tech
          </div>
        </div>
      </div>

      <!-- Action Buttons (Screen Only) -->
      <div class="p-4 bg-slate-50 border-t border-slate-100 flex gap-2 no-print">
        <button
          @click="handlePrint"
          class="flex-1 py-2.5 px-3 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl transition-all flex items-center justify-center gap-1.5 shadow-sm"
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

<style>
@media print {
  /* Hide all app elements except #printable-receipt-content */
  body * {
    visibility: hidden !important;
  }
  
  .no-print {
    display: none !important;
  }

  .modal-container, .modal-card {
    background: transparent !important;
    box-shadow: none !important;
    border: none !important;
    padding: 0 !important;
    margin: 0 !important;
    position: static !important;
    display: block !important;
  }

  #printable-receipt-content, #printable-receipt-content * {
    visibility: visible !important;
  }

  #printable-receipt-content {
    position: absolute !important;
    left: 0 !important;
    top: 0 !important;
    width: 80mm !important;
    margin: 0 !important;
    padding: 10px !important;
    font-size: 11px !important;
    font-family: monospace !important;
    color: #000000 !important;
    background: #ffffff !important;
  }
}
</style>
