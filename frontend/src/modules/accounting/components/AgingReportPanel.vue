<script setup lang="ts">
import { computed, ref } from 'vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiButton from '@/components/ui/UiButton.vue'
import { formatCurrency, formatDate } from '@/utils/format'

interface AgingInvoice {
  id: string
  reference: string
  dueDate: string
  invoiceDate: string
  amount: number
  bucket: '0-30' | '31-60' | '61-90' | '90+'
  daysOverdue: number
}

interface AgingRow {
  id: string
  invoiceCount: number
  outstanding: number
  bucket0_30: number
  bucket31_60: number
  bucket61_90: number
  bucket90_plus: number
  percentage: number
  invoices: AgingInvoice[]
  customerName?: string
  supplierName?: string
}

interface Props {
  title: string
  subtitle: string
  partyLabel: string
  rows: AgingRow[]
  resolveName: (row: AgingRow) => string
}

const props = defineProps<Props>()

const selectedIds = ref<string[]>([])
const expandedId = ref<string | null>(null)
const statusMessage = ref('')

const summary = computed(() => {
  const totals = props.rows.reduce(
    (accumulator, row) => {
      accumulator.outstanding += row.outstanding
      accumulator.bucket0_30 += row.bucket0_30
      accumulator.bucket31_60 += row.bucket31_60
      accumulator.bucket61_90 += row.bucket61_90
      accumulator.bucket90_plus += row.bucket90_plus
      return accumulator
    },
    { outstanding: 0, bucket0_30: 0, bucket31_60: 0, bucket61_90: 0, bucket90_plus: 0 }
  )

  const total = totals.outstanding || 1

  return [
    { label: 'Total Outstanding', value: totals.outstanding, percent: 100 },
    { label: '0-30 Days', value: totals.bucket0_30, percent: Math.round((totals.bucket0_30 / total) * 100) },
    { label: '31-60 Days', value: totals.bucket31_60, percent: Math.round((totals.bucket31_60 / total) * 100) },
    { label: '61-90 Days', value: totals.bucket61_90, percent: Math.round((totals.bucket61_90 / total) * 100) },
    { label: '90+ Days', value: totals.bucket90_plus, percent: Math.round((totals.bucket90_plus / total) * 100) },
  ]
})

const chartSegments = computed(() => {
  const [bucket0 = 0, bucket1 = 0, bucket2 = 0, bucket3 = 0] = summary.value.slice(1).map((entry) => entry.value)
  const grandTotal = bucket0 + bucket1 + bucket2 + bucket3 || 1

  return [
    { label: '0-30', value: bucket0, width: (bucket0 / grandTotal) * 100, color: 'bg-emerald-500' },
    { label: '31-60', value: bucket1, width: (bucket1 / grandTotal) * 100, color: 'bg-amber-500' },
    { label: '61-90', value: bucket2, width: (bucket2 / grandTotal) * 100, color: 'bg-orange-500' },
    { label: '90+', value: bucket3, width: (bucket3 / grandTotal) * 100, color: 'bg-rose-500' },
  ]
})

const toggleRow = (rowId: string) => {
  expandedId.value = expandedId.value === rowId ? null : rowId
}

const toggleSelection = (rowId: string) => {
  if (selectedIds.value.includes(rowId)) {
    selectedIds.value = selectedIds.value.filter((id) => id !== rowId)
    return
  }

  selectedIds.value = [...selectedIds.value, rowId]
}

const selectOverdue = () => {
  selectedIds.value = props.rows.filter((row) => row.outstanding > 0).map((row) => row.id)
}

const sendReminders = () => {
  statusMessage.value = `Queued reminders for ${selectedIds.value.length} ${props.partyLabel.toLowerCase()}s.`
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">{{ title }}</h1>
        <p class="text-sm text-slate-500">{{ subtitle }}</p>
      </div>
      <div class="flex flex-wrap gap-3">
        <UiButton variant="outline" @click="selectOverdue">Select Overdue</UiButton>
        <UiButton :disabled="selectedIds.length === 0" @click="sendReminders">Send Reminders</UiButton>
      </div>
    </div>

    <div class="grid gap-4 md:grid-cols-5">
      <div
        v-for="item in summary"
        :key="item.label"
        class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"
      >
        <p class="text-sm font-medium text-slate-500">{{ item.label }}</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ formatCurrency(item.value) }}</p>
        <p class="mt-1 text-sm text-slate-500">{{ item.percent }}% of total</p>
      </div>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="mb-4 flex items-center justify-between">
        <div>
          <h2 class="text-lg font-semibold text-slate-900">Aging Distribution</h2>
          <p class="text-sm text-slate-500">Stacked mix of outstanding balances across buckets.</p>
        </div>
        <div class="text-sm text-slate-500">{{ partyLabel }}s with open balances</div>
      </div>
      <div class="flex h-5 overflow-hidden rounded-full bg-slate-100">
        <div
          v-for="segment in chartSegments"
          :key="segment.label"
          class="h-full"
          :class="segment.color"
          :style="{ width: `${segment.width}%` }"
        />
      </div>
      <div class="mt-4 flex flex-wrap gap-4 text-sm text-slate-600">
        <span v-for="segment in chartSegments" :key="segment.label" class="inline-flex items-center gap-2">
          <span class="h-3 w-3 rounded-full" :class="segment.color" />
          {{ segment.label }} {{ formatCurrency(segment.value) }}
        </span>
      </div>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
      <div v-if="rows.length === 0" class="px-6 py-16 text-center">
        <p class="text-base font-medium text-slate-900">No outstanding balances</p>
        <p class="mt-2 text-sm text-slate-500 max-w-md mx-auto">
          Aging will appear here once invoices or bills with open balances exist. Until Sales/AP data is available, this report stays empty.
        </p>
      </div>
      <table v-else class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ partyLabel }}</th>
            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Invoices</th>
            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">0-30</th>
            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">31-60</th>
            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">61-90</th>
            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">90+</th>
            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Total</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
          <template v-for="row in rows" :key="row.id">
            <tr class="hover:bg-slate-50">
              <td class="px-4 py-3">
                <input
                  type="checkbox"
                  class="rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                  :checked="selectedIds.includes(row.id)"
                  @change="toggleSelection(row.id)"
                />
              </td>
              <td class="px-4 py-3">
                <button class="text-left" @click="toggleRow(row.id)">
                  <div class="font-medium text-slate-900">{{ resolveName(row) }}</div>
                  <div class="text-xs text-slate-500">Click to expand invoices</div>
                </button>
              </td>
              <td class="px-4 py-3 text-right text-slate-700">{{ row.invoiceCount }}</td>
              <td class="px-4 py-3 text-right text-slate-700">{{ formatCurrency(row.bucket0_30) }}</td>
              <td class="px-4 py-3 text-right text-slate-700">{{ formatCurrency(row.bucket31_60) }}</td>
              <td class="px-4 py-3 text-right text-slate-700">{{ formatCurrency(row.bucket61_90) }}</td>
              <td class="px-4 py-3 text-right text-slate-700">{{ formatCurrency(row.bucket90_plus) }}</td>
              <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ formatCurrency(row.outstanding) }}</td>
            </tr>
            <tr v-if="expandedId === row.id" class="bg-slate-50/80">
              <td colspan="8" class="px-4 py-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                  <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-900">Overdue invoices</h3>
                    <UiBadge variant="info">{{ row.invoices.length }} items</UiBadge>
                  </div>
                  <div class="overflow-hidden rounded-xl border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                      <thead class="bg-slate-50">
                        <tr>
                          <th class="px-3 py-2 text-left font-medium text-slate-500">Reference</th>
                          <th class="px-3 py-2 text-left font-medium text-slate-500">Invoice Date</th>
                          <th class="px-3 py-2 text-left font-medium text-slate-500">Due Date</th>
                          <th class="px-3 py-2 text-right font-medium text-slate-500">Days Overdue</th>
                          <th class="px-3 py-2 text-right font-medium text-slate-500">Amount</th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-slate-200">
                        <tr v-for="invoice in row.invoices" :key="invoice.id">
                          <td class="px-3 py-2 font-medium text-slate-900">{{ invoice.reference }}</td>
                          <td class="px-3 py-2 text-slate-600">{{ formatDate(invoice.invoiceDate) }}</td>
                          <td class="px-3 py-2 text-slate-600">{{ formatDate(invoice.dueDate) }}</td>
                          <td class="px-3 py-2 text-right text-slate-700">{{ invoice.daysOverdue }}</td>
                          <td class="px-3 py-2 text-right font-semibold text-slate-900">{{ formatCurrency(invoice.amount) }}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>

    <p v-if="statusMessage" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
      {{ statusMessage }}
    </p>
  </div>
</template>
