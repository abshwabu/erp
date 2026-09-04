<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import { Printer, CheckCircle2, X } from '@lucide/vue'
import { useTenantStore } from '@/stores/tenant'
import { settingsApi, type TenantSettings } from '@/api/settings'

const tenantStore = useTenantStore()
const tenantSettings = ref<TenantSettings | null>(null)

const props = defineProps({
  modelValue: { type: Boolean, required: true },
  orderData: { type: Object, default: () => null }
})

const emit = defineEmits(['update:modelValue', 'close'])

onMounted(async () => {
  try {
    const res = await settingsApi.get()
    if (res.data?.data) {
      tenantSettings.value = res.data.data
    }
  } catch (err) {
    console.debug('ReceiptModal: settings lookup skipped', err)
  }
})

const companyName = computed(() => {
  return props.orderData?.company?.name
    || tenantSettings.value?.display_name
    || tenantStore.currentTenant?.name
    || 'Bina ERP'
})

const tinNumber = computed(() => {
  return props.orderData?.company?.tin
    || props.orderData?.company?.tax_id
    || tenantSettings.value?.tax_id
    || ''
})

const companyAddress = computed(() => {
  return props.orderData?.company?.address
    || tenantSettings.value?.company_address
    || ''
})

const companyPhone = computed(() => {
  return props.orderData?.company?.phone
    || tenantSettings.value?.company_phone
    || ''
})

function handleClose() {
  emit('update:modelValue', false)
  emit('close')
}

function handlePrint() {
  const receiptNum = props.orderData?.receiptNumber || 'REC-884920'
  const dateStr = props.orderData?.date || new Date().toLocaleString()
  const compName = companyName.value
  const compTin = tinNumber.value
  const compAddress = companyAddress.value
  const compPhone = companyPhone.value
  const items = props.orderData?.items || []
  const subtotal = (props.orderData?.subtotal || 0).toFixed(2)
  const total = (props.orderData?.total || 0).toFixed(2)
  const tendered = (props.orderData?.tendered || 0).toFixed(2)
  const change = (props.orderData?.change || 0).toFixed(2)
  const method = props.orderData?.method || 'Cash'

  const itemsHtml = items.length > 0 ? items.map((item: any) => `
    <tr style="border-bottom: 1px border-dashed #eee;">
      <td style="padding: 4px 0; text-align: left; word-break: break-word;">${item.name}</td>
      <td style="padding: 4px 0; text-align: center;">${item.quantity} x $${Number(item.price).toFixed(2)}</td>
      <td style="padding: 4px 0; text-align: right; font-weight: bold;">$${(item.quantity * item.price).toFixed(2)}</td>
    </tr>
  `).join('') : `
    <tr>
      <td colspan="3" style="padding: 8px 0; text-align: center; color: #666;">No items</td>
    </tr>
  `

  const receiptHtml = `
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Receipt - ${receiptNum}</title>
      <style>
        @page {
          size: 80mm auto;
          margin: 0;
        }
        body {
          width: 76mm;
          margin: 0 auto;
          padding: 10px;
          font-family: 'Courier New', Courier, monospace;
          font-size: 11px;
          color: #000000;
          background: #ffffff;
          line-height: 1.3;
        }
        .header {
          text-align: center;
          border-bottom: 1px dashed #000;
          padding-bottom: 8px;
          margin-bottom: 8px;
        }
        .company-title {
          font-size: 15px;
          font-weight: bold;
          text-transform: uppercase;
          font-family: Arial, sans-serif;
          margin: 0 0 3px 0;
          letter-spacing: 0.5px;
        }
        .company-tin {
          font-size: 11px;
          font-weight: bold;
          font-family: 'Courier New', monospace;
          margin: 2px 0;
          color: #000;
        }
        .company-contact {
          font-size: 9px;
          color: #444;
          margin: 1px 0;
          font-family: Arial, sans-serif;
        }
        .subtitle {
          font-size: 10px;
          color: #444;
          margin: 4px 0 0 0;
          text-transform: uppercase;
          letter-spacing: 0.5px;
        }
        .meta-row {
          display: flex;
          justify-content: space-between;
          font-size: 10px;
          margin-top: 6px;
          border-top: 1px solid #eee;
          padding-top: 4px;
        }
        table {
          width: 100%;
          border-collapse: collapse;
          margin: 8px 0;
          font-size: 11px;
        }
        th {
          text-align: left;
          font-size: 9px;
          text-transform: uppercase;
          border-bottom: 1px solid #000;
          padding-bottom: 4px;
        }
        .summary {
          border-top: 1px dashed #000;
          border-bottom: 1px dashed #000;
          padding: 6px 0;
          margin-top: 8px;
        }
        .row {
          display: flex;
          justify-content: space-between;
          margin-bottom: 3px;
        }
        .total-row {
          display: flex;
          justify-content: space-between;
          font-size: 13px;
          font-weight: bold;
          border-top: 1px solid #000;
          padding-top: 4px;
          margin-top: 4px;
        }
        .footer {
          text-align: center;
          margin-top: 14px;
          padding-top: 8px;
          border-top: 1px dashed #bbb;
        }
        .footer-brand {
          font-family: monospace;
          font-size: 10px;
          font-weight: bold;
          color: #222;
          margin-top: 6px;
          border-top: 1px solid #eee;
          padding-top: 4px;
        }
      </style>
    </head>
    <body>
      <div class="header">
        <h1 class="company-title">${compName}</h1>
        ${compTin ? `<div class="company-tin"><strong>TIN:</strong> ${compTin}</div>` : ''}
        ${compAddress ? `<div class="company-contact">${compAddress}</div>` : ''}
        ${compPhone ? `<div class="company-contact">Tel: ${compPhone}</div>` : ''}
        <p class="subtitle">Official Sales Receipt / Tax Invoice</p>
        <div class="meta-row">
          <span>Receipt No: <strong>${receiptNum}</strong></span>
          <span>Date: ${dateStr}</span>
        </div>
      </div>

      <table>
        <thead>
          <tr>
            <th style="width: 45%;">Item</th>
            <th style="width: 30%; text-align: center;">Qty x Price</th>
            <th style="width: 25%; text-align: right;">Total</th>
          </tr>
        </thead>
        <tbody>
          ${itemsHtml}
        </tbody>
      </table>

      <div class="summary">
        <div class="row">
          <span>Subtotal:</span>
          <span>$${subtotal}</span>
        </div>
        <div class="total-row">
          <span>TOTAL VALUE:</span>
          <span>$${total}</span>
        </div>
        <div class="row" style="margin-top: 4px;">
          <span>Paid via ${method}:</span>
          <span>$${tendered}</span>
        </div>
        <div class="row" style="font-weight: bold;">
          <span>Change Due:</span>
          <span>$${change}</span>
        </div>
      </div>

      <div class="footer">
        <div style="font-size: 11px; font-weight: bold;">Thank you for shopping with us!</div>
        <div class="footer-brand">Powered by Bina ERP</div>
      </div>
    </body>
    </html>
  `

  const iframe = document.createElement('iframe')
  iframe.style.position = 'fixed'
  iframe.style.right = '0'
  iframe.style.bottom = '0'
  iframe.style.width = '0'
  iframe.style.height = '0'
  iframe.style.border = '0'
  document.body.appendChild(iframe)

  const doc = iframe.contentWindow?.document || iframe.contentDocument
  if (doc) {
    doc.open()
    doc.write(receiptHtml)
    doc.close()

    setTimeout(() => {
      iframe.contentWindow?.focus()
      iframe.contentWindow?.print()
      setTimeout(() => {
        if (document.body.contains(iframe)) {
          document.body.removeChild(iframe)
        }
      }, 1000)
    }, 250)
  }
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

      <!-- Receipt On-Screen Preview -->
      <div class="p-5 text-slate-800 text-xs space-y-4 font-mono">
        <!-- Receipt Header -->
        <div class="text-center border-b border-dashed border-slate-300 pb-3">
          <h4 class="font-extrabold text-sm text-slate-900 font-sans uppercase tracking-wider mb-0.5">
            {{ companyName }}
          </h4>
          <div v-if="tinNumber" class="text-[11px] font-bold text-slate-900 font-mono tracking-wide">
            TIN: {{ tinNumber }}
          </div>
          <div v-if="companyAddress || companyPhone" class="text-[10px] text-slate-500 font-sans mt-0.5">
            <span v-if="companyAddress">{{ companyAddress }}</span>
            <span v-if="companyAddress && companyPhone"> · </span>
            <span v-if="companyPhone">Tel: {{ companyPhone }}</span>
          </div>
          <p class="text-[10px] text-slate-400 font-sans uppercase tracking-wider mt-1.5">Official Sales Receipt / Tax Invoice</p>
          
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
          <div class="border-t border-slate-200 pt-2 text-[10px] font-mono text-slate-500 font-bold">
            Powered by Bina ERP
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="p-4 bg-slate-50 border-t border-slate-100 flex gap-2">
        <button
          @click="handlePrint"
          class="flex-1 py-2.5 px-3 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl transition-all flex items-center justify-center gap-1.5 shadow-sm active:scale-98"
        >
          <Printer class="w-4 h-4" />
          Print Receipt
        </button>

        <button
          @click="handleClose"
          class="flex-1 py-2.5 px-3 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs rounded-xl transition-all shadow-sm active:scale-98"
        >
          New Sale
        </button>
      </div>
    </div>
  </div>
</template>
