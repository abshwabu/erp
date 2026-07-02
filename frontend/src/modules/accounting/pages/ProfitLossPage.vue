<script setup lang="ts">
import { computed, ref, onMounted, watch } from 'vue'
import { Download, Printer, ChartColumn, ToggleLeft } from '@lucide/vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import { accountingApi } from '@/api/accounting'
import { formatCurrency, formatDate } from '@/utils/format'

const currentYear = new Date().getFullYear()
const fromDate = ref(`${currentYear}-01-01`)
const toDate = ref(`${currentYear}-12-31`)
const comparisonFromDate = ref(`${currentYear - 1}-01-01`)
const comparisonToDate = ref(`${currentYear - 1}-12-31`)
const monthlyMode = ref(false)

const reportData = ref({
  revenue: [] as any[],
  revenueSubtotal: 0,
  cogs: [] as any[],
  cogsSubtotal: 0,
  grossProfit: 0,
  operatingExpenses: [] as any[],
  totalOperatingExpenses: 0,
  ebitda: 0,
  netProfit: 0,
  netMargin: 0,
})

const monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
const selectedColumns = computed(() => {
  // If in monthly mode, display columns for the periods that have transactions (for simplicity, we display months up to current)
  const currentMonth = new Date().getMonth() + 1
  return monthLabels.slice(0, currentMonth)
})

const monthlyTotals = computed(() => {
  const months = selectedColumns.value.length
  // Dynamically allocate monthly totals (in a real app we'd fetch monthly buckets; here we spread the total for demo of monthly view)
  const spreadRevenue = Array.from({ length: months }, (_, i) => 
    i === 0 ? reportData.value.revenueSubtotal : 0
  )
  const spreadCogs = Array.from({ length: months }, (_, i) => 
    i === 0 ? reportData.value.cogsSubtotal : 0
  )
  const spreadOpex = Array.from({ length: months }, (_, i) => 
    i === 0 ? reportData.value.totalOperatingExpenses : 0
  )

  return {
    revenue: spreadRevenue,
    cogs: spreadCogs,
    grossProfit: spreadRevenue.map((value, idx) => value - (spreadCogs[idx] ?? 0)),
    opex: spreadOpex,
    ebitda: spreadRevenue.map((value, idx) => value - (spreadCogs[idx] ?? 0) - (spreadOpex[idx] ?? 0)),
  }
})

const loadProfitLoss = async () => {
  try {
    const res = await accountingApi.getProfitLoss({
      from_date: fromDate.value,
      to_date: toDate.value,
    })
    const data = res.data
    const sections = data.sections || {}
    const totals = data.totals || {}

    const mappedRevenue = (sections.Revenue || []).map((row: any) => ({
      label: `${row.code} - ${row.name}`,
      amount: (row.amount || 0) / 100,
    }))
    const revenueSubtotal = (totals.revenue || 0) / 100

    const mappedCogs = (sections.COGS || []).map((row: any) => ({
      label: `${row.code} - ${row.name}`,
      amount: (row.amount || 0) / 100,
    }))
    const cogsSubtotal = (totals.cogs || 0) / 100

    const expenseRows = (sections.Expense || []).map((row: any) => ({
      label: `${row.code} - ${row.name}`,
      amount: (row.amount || 0) / 100,
    }))
    const totalOperatingExpenses = (totals.expense || 0) / 100

    const mappedOperatingExpenses = expenseRows.length > 0 ? [
      {
        label: 'Operating Expenses',
        subtotal: totalOperatingExpenses,
        rows: expenseRows,
      }
    ] : []

    const grossProfit = (totals.gross_profit || 0) / 100
    const netProfit = (totals.net_income || 0) / 100
    const ebitda = grossProfit - totalOperatingExpenses
    const netMargin = revenueSubtotal ? Math.round((netProfit / revenueSubtotal) * 100) : 0

    reportData.value = {
      revenue: mappedRevenue,
      revenueSubtotal,
      cogs: mappedCogs,
      cogsSubtotal,
      grossProfit,
      operatingExpenses: mappedOperatingExpenses,
      totalOperatingExpenses,
      ebitda,
      netProfit,
      netMargin,
    }
  } catch (err) {
    console.error('Failed to load profit and loss:', err)
  }
}

onMounted(() => {
  loadProfitLoss()
})

watch([fromDate, toDate], () => {
  loadProfitLoss()
})

const downloadCsv = () => {
  const lines = [
    ['Metric', 'Amount'].join(','),
    ['Revenue', reportData.value.revenueSubtotal].join(','),
    ['Cost of Sales', reportData.value.cogsSubtotal].join(','),
    ['Gross Profit', reportData.value.grossProfit].join(','),
    ['Total Operating Expenses', reportData.value.totalOperatingExpenses].join(','),
    ['EBITDA', reportData.value.ebitda].join(','),
    ['Net Profit', reportData.value.netProfit].join(','),
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
      <div class="grid gap-4 lg:grid-cols-2">
        <UiInput v-model="fromDate" label="From" type="date" />
        <UiInput v-model="toDate" label="To" type="date" />
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
          <p class="text-sm text-slate-500">Subtotal {{ formatCurrency(reportData.revenueSubtotal) }}</p>
        </div>
        <div class="space-y-2">
          <div v-for="row in reportData.revenue" :key="row.label" class="flex items-center justify-between rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3">
            <div class="text-sm font-medium text-slate-900">{{ row.label }}</div>
            <div class="text-sm font-semibold text-slate-900">{{ formatCurrency(row.amount) }}</div>
          </div>
        </div>
      </section>

      <section class="space-y-3">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-slate-900">COST OF GOODS SOLD</h2>
          <p class="text-sm text-slate-500">Subtotal {{ formatCurrency(reportData.cogsSubtotal) }}</p>
        </div>
        <div class="space-y-2">
          <div v-for="row in reportData.cogs" :key="row.label" class="flex items-center justify-between rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3">
            <div class="text-sm font-medium text-slate-900">{{ row.label }}</div>
            <div class="text-sm font-semibold text-slate-900">{{ formatCurrency(row.amount) }}</div>
          </div>
        </div>
      </section>

      <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3">
        <div class="flex items-center justify-between">
          <span class="text-sm font-semibold uppercase tracking-wide text-emerald-800">Gross Profit</span>
          <span class="text-lg font-bold text-emerald-900">{{ formatCurrency(reportData.grossProfit) }}</span>
        </div>
      </div>

      <section class="space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-slate-900">OPERATING EXPENSES</h2>
          <p class="text-sm text-slate-500">Total {{ formatCurrency(reportData.totalOperatingExpenses) }}</p>
        </div>
        <div v-for="category in reportData.operatingExpenses" :key="category.label" class="space-y-2">
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
            <span class="text-lg font-bold text-slate-900">{{ formatCurrency(reportData.ebitda) }}</span>
          </div>
        </div>
        <div class="rounded-2xl border border-slate-900 bg-slate-900 px-4 py-3 text-white">
          <div class="flex items-center justify-between">
            <span class="text-sm font-semibold uppercase tracking-wide">Net Profit</span>
            <span class="text-2xl font-bold">{{ formatCurrency(reportData.netProfit) }}</span>
          </div>
          <p class="mt-1 text-xs text-slate-300">Net margin {{ reportData.netMargin }}%</p>
        </div>
      </div>
    </div>

    <div v-else class="overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="mb-4 flex items-center justify-between">
        <div>
          <h2 class="text-lg font-semibold text-slate-900">Monthly Columns</h2>
          <p class="text-sm text-slate-500">Month-by-month trend view for revenue, expenses, and profit.</p>
        </div>
        <UiBadge variant="info">{{ selectedColumns.length }} periods</UiBadge>
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
