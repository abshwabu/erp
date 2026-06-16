<script setup lang="ts">
import { computed, ref } from 'vue'
import { Download, Printer, ChartColumn, ToggleLeft } from '@lucide/vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import { sampleProfitLoss } from '../data'
import { formatCurrency, formatDate } from '@/utils/format'

const fromDate = ref(sampleProfitLoss.fromDate)
const toDate = ref(sampleProfitLoss.toDate)
const comparisonFromDate = ref(sampleProfitLoss.comparisonFromDate || '')
const comparisonToDate = ref(sampleProfitLoss.comparisonToDate || '')
const monthlyMode = ref(sampleProfitLoss.monthlyMode)

const monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
const selectedColumns = computed(() => monthLabels.slice(0, sampleProfitLoss.revenue[0]?.monthlyAmounts?.length || 0))

const monthlyTotals = computed(() => {
  const months = selectedColumns.value.length
  const revenue = Array.from({ length: months }, (_, index) =>
    sampleProfitLoss.revenue.reduce((sum, row) => sum + (row.monthlyAmounts?.[index] || 0), 0)
  )
  const cogs = Array.from({ length: months }, (_, index) =>
    sampleProfitLoss.cogs.reduce((sum, row) => sum + (row.monthlyAmounts?.[index] || 0), 0)
  )
  const opex = Array.from({ length: months }, (_, index) =>
    sampleProfitLoss.operatingExpenses.reduce((sum, category) => sum + category.rows.reduce((inner, row) => inner + (row.monthlyAmounts?.[index] || 0), 0), 0)
  )

  return {
    revenue,
    cogs,
    grossProfit: revenue.map((value, index) => value - (cogs[index] ?? 0)),
    opex,
    ebitda: revenue.map((value, index) => value - (cogs[index] ?? 0) - (opex[index] ?? 0)),
  }
})

const downloadCsv = () => {
  const lines = [
    ['Metric', 'Amount', 'Prior Period'].join(','),
    ['Revenue', sampleProfitLoss.revenueSubtotal, sampleProfitLoss.revenue.reduce((sum, row) => sum + (row.priorAmount || 0), 0)].join(','),
    ['Cost of Sales', sampleProfitLoss.cogsSubtotal, sampleProfitLoss.cogs.reduce((sum, row) => sum + (row.priorAmount || 0), 0)].join(','),
    ['Gross Profit', sampleProfitLoss.grossProfit, sampleProfitLoss.revenue.reduce((sum, row) => sum + (row.priorAmount || 0), 0) - sampleProfitLoss.cogs.reduce((sum, row) => sum + (row.priorAmount || 0), 0)].join(','),
    ['Total Operating Expenses', sampleProfitLoss.totalOperatingExpenses, sampleProfitLoss.operatingExpenses.reduce((sum, category) => sum + (category.priorSubtotal || 0), 0)].join(','),
    ['EBITDA', sampleProfitLoss.ebitda, sampleProfitLoss.ebitda].join(','),
    ['Net Profit', sampleProfitLoss.netProfit, sampleProfitLoss.netProfit].join(','),
  ]

  const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const anchor = document.createElement('a')
  anchor.href = url
  anchor.download = 'profit-loss.csv'
  anchor.click()
  URL.revokeObjectURL(url)
}

const printReport = () => {
  window.print()
}

const netMargin = computed(() => {
  return sampleProfitLoss.revenueSubtotal ? Math.round((sampleProfitLoss.netProfit / sampleProfitLoss.revenueSubtotal) * 100) : 0
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Profit and Loss</h1>
        <p class="text-sm text-slate-500">A structured income statement for the selected reporting period.</p>
      </div>
      <div class="flex flex-wrap gap-3">
        <UiButton variant="outline" @click="printReport">
          <Printer class="mr-2 h-4 w-4" /> Export to PDF
        </UiButton>
        <UiButton variant="outline" @click="downloadCsv">
          <Download class="mr-2 h-4 w-4" /> Export to Excel
        </UiButton>
      </div>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
      <div class="grid gap-4 lg:grid-cols-4">
        <UiInput v-model="fromDate" label="From" type="date" />
        <UiInput v-model="toDate" label="To" type="date" />
        <UiInput v-model="comparisonFromDate" label="Comparison From" type="date" />
        <UiInput v-model="comparisonToDate" label="Comparison To" type="date" />
      </div>
      <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
        <div class="flex items-center gap-3 text-sm text-slate-600">
          <ChartColumn class="h-4 w-4" />
          <span>Report window: {{ formatDate(fromDate) }} - {{ formatDate(toDate) }}</span>
        </div>
        <button
          class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700"
          @click="monthlyMode = !monthlyMode"
        >
          <ToggleLeft class="h-4 w-4" />
          {{ monthlyMode ? 'Monthly columns on' : 'Single period view' }}
        </button>
      </div>
    </div>

    <div v-if="!monthlyMode" class="space-y-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <section class="space-y-3">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-slate-900">REVENUE</h2>
          <p class="text-sm text-slate-500">Subtotal {{ formatCurrency(sampleProfitLoss.revenueSubtotal) }}</p>
        </div>
        <div class="space-y-2">
          <div v-for="row in sampleProfitLoss.revenue" :key="row.label" class="flex items-center justify-between rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3">
            <div class="text-sm font-medium text-slate-900">{{ row.label }}</div>
            <div class="text-sm font-semibold text-slate-900">{{ formatCurrency(row.amount) }}</div>
          </div>
        </div>
      </section>

      <section class="space-y-3">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-slate-900">COST OF GOODS SOLD</h2>
          <p class="text-sm text-slate-500">Subtotal {{ formatCurrency(sampleProfitLoss.cogsSubtotal) }}</p>
        </div>
        <div class="space-y-2">
          <div v-for="row in sampleProfitLoss.cogs" :key="row.label" class="flex items-center justify-between rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3">
            <div class="text-sm font-medium text-slate-900">{{ row.label }}</div>
            <div class="text-sm font-semibold text-slate-900">{{ formatCurrency(row.amount) }}</div>
          </div>
        </div>
      </section>

      <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3">
        <div class="flex items-center justify-between">
          <span class="text-sm font-semibold uppercase tracking-wide text-emerald-800">Gross Profit</span>
          <span class="text-lg font-bold text-emerald-900">{{ formatCurrency(sampleProfitLoss.grossProfit) }}</span>
        </div>
      </div>

      <section class="space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-slate-900">OPERATING EXPENSES</h2>
          <p class="text-sm text-slate-500">Total {{ formatCurrency(sampleProfitLoss.totalOperatingExpenses) }}</p>
        </div>
        <div v-for="category in sampleProfitLoss.operatingExpenses" :key="category.label" class="space-y-2">
          <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
            <div class="text-sm font-semibold uppercase tracking-wide text-slate-700">{{ category.label }}</div>
            <div class="text-sm font-semibold text-slate-900">{{ formatCurrency(category.subtotal) }}</div>
          </div>
          <div v-for="row in category.rows" :key="row.label" class="flex items-center justify-between rounded-2xl border border-slate-100 bg-white px-6 py-3">
            <div class="text-sm text-slate-700">{{ row.label }}</div>
            <div class="text-sm font-medium text-slate-900">{{ formatCurrency(row.amount) }}</div>
          </div>
        </div>
      </section>

      <div class="grid gap-4 md:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
          <div class="flex items-center justify-between">
            <span class="text-sm font-semibold uppercase tracking-wide text-slate-700">EBITDA</span>
            <span class="text-lg font-bold text-slate-900">{{ formatCurrency(sampleProfitLoss.ebitda) }}</span>
          </div>
        </div>
        <div class="rounded-2xl border border-slate-900 bg-slate-900 px-4 py-3 text-white">
          <div class="flex items-center justify-between">
            <span class="text-sm font-semibold uppercase tracking-wide">Net Profit</span>
            <span class="text-2xl font-bold">{{ formatCurrency(sampleProfitLoss.netProfit) }}</span>
          </div>
          <p class="mt-1 text-xs text-slate-300">Net margin {{ netMargin }}%</p>
        </div>
      </div>
    </div>

    <div v-else class="overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="mb-4 flex items-center justify-between">
        <div>
          <h2 class="text-lg font-semibold text-slate-900">Monthly Columns</h2>
          <p class="text-sm text-slate-500">Month-by-month trend view for revenue, expenses, and profit.</p>
        </div>
        <UiBadge variant="info">12 periods</UiBadge>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide text-slate-500">Metric</th>
              <th v-for="month in selectedColumns" :key="month" class="px-4 py-3 text-right font-semibold uppercase tracking-wide text-slate-500">
                {{ month }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            <tr>
              <td class="px-4 py-3 font-semibold text-slate-900">Revenue</td>
              <td v-for="value in monthlyTotals.revenue" :key="value" class="px-4 py-3 text-right text-slate-700">{{ formatCurrency(value) }}</td>
            </tr>
            <tr>
              <td class="px-4 py-3 font-semibold text-slate-900">COGS</td>
              <td v-for="value in monthlyTotals.cogs" :key="value" class="px-4 py-3 text-right text-slate-700">{{ formatCurrency(value) }}</td>
            </tr>
            <tr class="bg-emerald-50">
              <td class="px-4 py-3 font-semibold text-emerald-900">Gross Profit</td>
              <td v-for="value in monthlyTotals.grossProfit" :key="value" class="px-4 py-3 text-right font-semibold text-emerald-900">{{ formatCurrency(value) }}</td>
            </tr>
            <tr>
              <td class="px-4 py-3 font-semibold text-slate-900">Operating Expenses</td>
              <td v-for="value in monthlyTotals.opex" :key="value" class="px-4 py-3 text-right text-slate-700">{{ formatCurrency(value) }}</td>
            </tr>
            <tr class="bg-slate-900 text-white">
              <td class="px-4 py-3 font-semibold">EBITDA</td>
              <td v-for="value in monthlyTotals.ebitda" :key="value" class="px-4 py-3 text-right font-semibold">{{ formatCurrency(value) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
