<script setup lang="ts">
import { computed, ref, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { Download, Printer, ChartColumn, ToggleLeft } from '@lucide/vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import AccountingNav from '../components/AccountingNav.vue'
import { accountingApi } from '@/api/accounting'
import { formatCurrency, formatDate } from '@/utils/format'

const { t } = useI18n()

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
        label: t('accounting.profitLoss.operatingExpenses'),
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
    [t('accounting.profitLoss.metric'), t('finance.transactionsPage.table.amount')].join(','),
    [t('accounting.profitLoss.revenue'), reportData.value.revenueSubtotal].join(','),
    [t('accounting.profitLoss.cogs'), reportData.value.cogsSubtotal].join(','),
    [t('accounting.profitLoss.grossProfit'), reportData.value.grossProfit].join(','),
    [t('accounting.profitLoss.operatingExpenses'), reportData.value.totalOperatingExpenses].join(','),
    [t('accounting.profitLoss.ebitda'), reportData.value.ebitda].join(','),
    [t('accounting.profitLoss.netProfit'), reportData.value.netProfit].join(','),
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
    <AccountingNav />

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-xl sm:text-2xl font-bold text-slate-900">{{ t('accounting.profitLoss.title') }}</h1>
        <p class="text-xs sm:text-sm text-slate-500">{{ t('accounting.profitLoss.subtitle') }}</p>
      </div>
      <div class="flex flex-wrap gap-2 w-full sm:w-auto">
        <UiButton variant="outline" class="flex-1 sm:flex-none justify-center" @click="printReport">
          <Printer class="mr-2 h-4 w-4 shrink-0" /> {{ t('accounting.profitLoss.exportPdf') }}
        </UiButton>
        <UiButton variant="outline" class="flex-1 sm:flex-none justify-center" @click="downloadCsv">
          <Download class="mr-2 h-4 w-4 shrink-0" /> {{ t('accounting.profitLoss.exportExcel') }}
        </UiButton>
      </div>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-4 sm:p-5 shadow-sm space-y-4">
      <div class="grid gap-3 grid-cols-1 sm:grid-cols-2">
        <UiInput v-model="fromDate" :label="t('accounting.profitLoss.from')" type="date" />
        <UiInput v-model="toDate" :label="t('accounting.profitLoss.to')" type="date" />
      </div>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-3 sm:px-4 sm:py-3">
        <div class="flex items-center gap-2.5 text-xs sm:text-sm text-slate-600">
          <ChartColumn class="h-4 w-4 shrink-0" />
          <span>{{ t('accounting.profitLoss.reportWindow', { window: `${formatDate(fromDate)} - ${formatDate(toDate)}` }) }}</span>
        </div>
        <button
          class="inline-flex items-center justify-center gap-2 rounded-full border border-slate-200 bg-white px-3.5 py-1.5 sm:px-4 sm:py-2 text-xs sm:text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors shrink-0"
          @click="monthlyMode = !monthlyMode"
        >
          <ToggleLeft class="h-4 w-4" />
          {{ monthlyMode ? t('accounting.profitLoss.monthlyColumnsOn') : t('accounting.profitLoss.singlePeriodView') }}
        </button>
      </div>
    </div>

    <div v-if="!monthlyMode" class="space-y-4 rounded-3xl border border-slate-200 bg-white p-4 sm:p-6 shadow-sm">
      <section class="space-y-3">
        <div class="flex items-center justify-between gap-2">
          <h2 class="text-base sm:text-lg font-semibold text-slate-900">{{ t('accounting.profitLoss.revenue') }}</h2>
          <p class="text-xs sm:text-sm text-slate-500 whitespace-nowrap">{{ t('accounting.profitLoss.subtotal', { amount: formatCurrency(reportData.revenueSubtotal) }) }}</p>
        </div>
        <div class="space-y-2">
          <div v-for="row in reportData.revenue" :key="row.label" class="flex items-center justify-between gap-3 rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3">
            <div class="text-xs sm:text-sm font-medium text-slate-900 min-w-0 break-words">{{ row.label }}</div>
            <div class="text-xs sm:text-sm font-semibold text-slate-900 shrink-0 whitespace-nowrap">{{ formatCurrency(row.amount) }}</div>
          </div>
        </div>
      </section>

      <section class="space-y-3">
        <div class="flex items-center justify-between gap-2">
          <h2 class="text-base sm:text-lg font-semibold text-slate-900">{{ t('accounting.profitLoss.cogs') }}</h2>
          <p class="text-xs sm:text-sm text-slate-500 whitespace-nowrap">{{ t('accounting.profitLoss.subtotal', { amount: formatCurrency(reportData.cogsSubtotal) }) }}</p>
        </div>
        <div class="space-y-2">
          <div v-for="row in reportData.cogs" :key="row.label" class="flex items-center justify-between gap-3 rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3">
            <div class="text-xs sm:text-sm font-medium text-slate-900 min-w-0 break-words">{{ row.label }}</div>
            <div class="text-xs sm:text-sm font-semibold text-slate-900 shrink-0 whitespace-nowrap">{{ formatCurrency(row.amount) }}</div>
          </div>
        </div>
      </section>

      <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3">
        <div class="flex items-center justify-between gap-2">
          <span class="text-xs sm:text-sm font-semibold uppercase tracking-wide text-emerald-800">{{ t('accounting.profitLoss.grossProfit') }}</span>
          <span class="text-base sm:text-lg font-bold text-emerald-900 shrink-0 whitespace-nowrap">{{ formatCurrency(reportData.grossProfit) }}</span>
        </div>
      </div>

      <section class="space-y-4">
        <div class="flex items-center justify-between gap-2">
          <h2 class="text-base sm:text-lg font-semibold text-slate-900">{{ t('accounting.profitLoss.operatingExpenses') }}</h2>
          <p class="text-xs sm:text-sm text-slate-500 whitespace-nowrap">{{ t('accounting.profitLoss.total', { amount: formatCurrency(reportData.totalOperatingExpenses) }) }}</p>
        </div>
        <div v-for="category in reportData.operatingExpenses" :key="category.label" class="space-y-2">
          <div class="flex items-center justify-between gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
            <div class="text-xs sm:text-sm font-semibold uppercase tracking-wide text-slate-700 min-w-0 break-words">{{ category.label }}</div>
            <div class="text-xs sm:text-sm font-semibold text-slate-900 shrink-0 whitespace-nowrap">{{ formatCurrency(category.subtotal) }}</div>
          </div>
          <div v-for="row in category.rows" :key="row.label" class="flex items-center justify-between gap-2 rounded-2xl border border-slate-100 bg-white px-4 sm:px-6 py-2.5 sm:py-3">
            <div class="text-xs sm:text-sm text-slate-700 min-w-0 break-words">{{ row.label }}</div>
            <div class="text-xs sm:text-sm font-medium text-slate-900 shrink-0 whitespace-nowrap">{{ formatCurrency(row.amount) }}</div>
          </div>
        </div>
      </section>

      <div class="grid gap-3 sm:gap-4 grid-cols-1 sm:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
          <div class="flex items-center justify-between gap-2">
            <span class="text-xs sm:text-sm font-semibold uppercase tracking-wide text-slate-700">{{ t('accounting.profitLoss.ebitda') }}</span>
            <span class="text-base sm:text-lg font-bold text-slate-900 shrink-0 whitespace-nowrap">{{ formatCurrency(reportData.ebitda) }}</span>
          </div>
        </div>
        <div class="rounded-2xl border border-slate-900 bg-slate-900 px-4 py-3 text-white">
          <div class="flex items-center justify-between gap-2">
            <span class="text-xs sm:text-sm font-semibold uppercase tracking-wide">{{ t('accounting.profitLoss.netProfit') }}</span>
            <span class="text-xl sm:text-2xl font-bold shrink-0 whitespace-nowrap">{{ formatCurrency(reportData.netProfit) }}</span>
          </div>
          <p class="mt-1 text-xs text-slate-300">{{ t('accounting.profitLoss.netMargin', { margin: reportData.netMargin }) }}</p>
        </div>
      </div>
    </div>

    <div v-else class="overflow-hidden rounded-3xl border border-slate-200 bg-white p-4 sm:p-6 shadow-sm">
      <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
          <h2 class="text-base sm:text-lg font-semibold text-slate-900">{{ t('accounting.profitLoss.monthlyColumnsTitle') }}</h2>
          <p class="text-xs sm:text-sm text-slate-500">{{ t('accounting.profitLoss.monthlyColumnsSubtitle') }}</p>
        </div>
        <UiBadge variant="info" class="self-start sm:self-auto">{{ t('accounting.profitLoss.periodsCount', { count: selectedColumns.length }) }}</UiBadge>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full min-w-[650px] divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50">
            <tr>
              <th class="sticky left-0 bg-slate-50 z-10 px-4 py-3 text-left font-semibold uppercase tracking-wide text-slate-500 whitespace-nowrap">{{ t('accounting.profitLoss.metric') }}</th>
              <th v-for="month in selectedColumns" :key="month" class="px-4 py-3 text-right font-semibold uppercase tracking-wide text-slate-500 whitespace-nowrap">
                {{ month }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            <tr>
              <td class="sticky left-0 bg-white z-10 px-4 py-3 font-semibold text-slate-900 whitespace-nowrap">{{ t('accounting.profitLoss.revenue') }}</td>
              <td v-for="value in monthlyTotals.revenue" :key="value" class="px-4 py-3 text-right text-slate-700 whitespace-nowrap">{{ formatCurrency(value) }}</td>
            </tr>
            <tr>
              <td class="sticky left-0 bg-white z-10 px-4 py-3 font-semibold text-slate-900 whitespace-nowrap">{{ t('accounting.profitLoss.cogs') }}</td>
              <td v-for="value in monthlyTotals.cogs" :key="value" class="px-4 py-3 text-right text-slate-700 whitespace-nowrap">{{ formatCurrency(value) }}</td>
            </tr>
            <tr class="bg-emerald-50">
              <td class="sticky left-0 bg-emerald-50 z-10 px-4 py-3 font-semibold text-emerald-900 whitespace-nowrap">{{ t('accounting.profitLoss.grossProfit') }}</td>
              <td v-for="value in monthlyTotals.grossProfit" :key="value" class="px-4 py-3 text-right font-semibold text-emerald-900 whitespace-nowrap">{{ formatCurrency(value) }}</td>
            </tr>
            <tr>
              <td class="sticky left-0 bg-white z-10 px-4 py-3 font-semibold text-slate-900 whitespace-nowrap">{{ t('accounting.profitLoss.operatingExpenses') }}</td>
              <td v-for="value in monthlyTotals.opex" :key="value" class="px-4 py-3 text-right text-slate-700 whitespace-nowrap">{{ formatCurrency(value) }}</td>
            </tr>
            <tr class="bg-slate-900 text-white">
              <td class="sticky left-0 bg-slate-900 z-10 px-4 py-3 font-semibold whitespace-nowrap">{{ t('accounting.profitLoss.ebitda') }}</td>
              <td v-for="value in monthlyTotals.ebitda" :key="value" class="px-4 py-3 text-right font-semibold whitespace-nowrap">{{ formatCurrency(value) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
