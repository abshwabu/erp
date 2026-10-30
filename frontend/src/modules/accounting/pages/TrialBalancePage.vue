<script setup lang="ts">
import { computed, ref, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { Download } from '@lucide/vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import AccountingNav from '../components/AccountingNav.vue'
import { accountingApi } from '@/api/accounting'
import { formatCurrency, formatDate } from '@/utils/format'
import type { AccountType, TrialBalanceRow, TrialBalanceSection } from '@/types/accounting'

const { t } = useI18n()
const currentYear = new Date().getFullYear()

// Generate fiscal periods matching the database seed
const accountingFiscalPeriods = Array.from({ length: 12 }, (_, i) => {
  const month = i + 1
  const monthStr = String(month).padStart(2, '0')
  return {
    id: `${currentYear}-${monthStr}`,
    year: currentYear,
    month,
    startDate: `${currentYear}-${monthStr}-01`,
    endDate: new Date(currentYear, month, 0).toISOString().slice(0, 10),
    status: month < new Date().getMonth() + 1 ? 'closed' : 'open',
  }
})

const fromDate = ref(`${currentYear}-01-01`)
const toDate = ref(`${currentYear}-12-31`)
const comparePreviousPeriod = ref(false)
const mode = ref<'custom' | 'fiscal'>('custom')
const selectedPeriod = ref(`${currentYear}-01`)

const visibleSections = ref<TrialBalanceSection[]>([])
const totals = ref({
  openingBalance: 0,
  debits: 0,
  credits: 0,
  closingBalance: 0,
  priorPeriodBalance: 0
})

const loadTrialBalance = async () => {
  try {
    const params = {
      from_date: fromDate.value,
      to_date: toDate.value,
    }
    const res = await accountingApi.getTrialBalance(params)
    const rawRows = res.data || []

    const sectionsMap: Record<string, { label: string; type: AccountType; rows: TrialBalanceRow[] }> = {
      Asset: { label: t('accounting.common.types.asset'), type: 'asset', rows: [] },
      Liability: { label: t('accounting.common.types.liability'), type: 'liability', rows: [] },
      Equity: { label: t('accounting.common.types.equity'), type: 'equity', rows: [] },
      Revenue: { label: t('accounting.common.types.revenue'), type: 'revenue', rows: [] },
      COGS: { label: t('accounting.common.types.cost_of_sales'), type: 'cost_of_sales', rows: [] },
      Expense: { label: t('accounting.common.types.expense'), type: 'expense', rows: [] },
    }

    rawRows.forEach((row: any) => {
      const typeKey = row.type_name || 'Asset'
      if (!sectionsMap[typeKey]) {
        sectionsMap[typeKey] = { label: typeKey, type: 'asset', rows: [] }
      }
      
      sectionsMap[typeKey].rows.push({
        accountId: row.id,
        code: row.code,
        name: row.name,
        type: (row.type_name?.toLowerCase() || 'asset') as AccountType,
        openingBalance: (row.opening_balance || 0) / 100,
        debits: (row.debits || 0) / 100,
        credits: (row.credits || 0) / 100,
        closingBalance: (row.closing_balance || 0) / 100,
      })
    })

    const mappedSections: any[] = []
    Object.keys(sectionsMap).forEach((key) => {
      const sect = sectionsMap[key]
      if (!sect || sect.rows.length === 0) return

      const subtotal = {
        openingBalance: sect.rows.reduce((sum: number, r: any) => sum + r.openingBalance, 0),
        debits: sect.rows.reduce((sum: number, r: any) => sum + r.debits, 0),
        credits: sect.rows.reduce((sum: number, r: any) => sum + r.credits, 0),
        closingBalance: sect.rows.reduce((sum: number, r: any) => sum + r.closingBalance, 0),
      }

      mappedSections.push({
        ...sect,
        subtotal,
      })
    })

    visibleSections.value = mappedSections

    totals.value = {
      openingBalance: visibleSections.value.reduce((sum, s) => sum + s.subtotal.openingBalance, 0),
      debits: visibleSections.value.reduce((sum, s) => sum + s.subtotal.debits, 0),
      credits: visibleSections.value.reduce((sum, s) => sum + s.subtotal.credits, 0),
      closingBalance: visibleSections.value.reduce((sum, s) => sum + s.subtotal.closingBalance, 0),
      priorPeriodBalance: 0
    }
  } catch (err) {
    console.error('Failed to load trial balance:', err)
  }
}

onMounted(() => {
  loadTrialBalance()
})

watch(
  () => selectedPeriod.value,
  (val) => {
    if (mode.value === 'fiscal' && val) {
      const found = accountingFiscalPeriods.find((p) => p.id === val)
      if (found) {
        fromDate.value = found.startDate
        toDate.value = found.endDate
      }
    }
  }
)

watch(
  () => mode.value,
  (val) => {
    if (val === 'fiscal' && selectedPeriod.value) {
      const found = accountingFiscalPeriods.find((p) => p.id === selectedPeriod.value)
      if (found) {
        fromDate.value = found.startDate
        toDate.value = found.endDate
      }
    }
  }
)

watch(
  [fromDate, toDate],
  () => {
    loadTrialBalance()
  }
)

const downloadCsv = () => {
  const header = comparePreviousPeriod.value
    ? [
        t('accounting.trialBalance.table.section'),
        t('accounting.trialBalance.table.code'),
        t('accounting.trialBalance.table.name'),
        t('accounting.trialBalance.table.openingBalance'),
        t('accounting.trialBalance.table.debits'),
        t('accounting.trialBalance.table.credits'),
        t('accounting.trialBalance.table.closingBalance'),
        t('accounting.trialBalance.table.priorPeriod'),
      ]
    : [
        t('accounting.trialBalance.table.section'),
        t('accounting.trialBalance.table.code'),
        t('accounting.trialBalance.table.name'),
        t('accounting.trialBalance.table.openingBalance'),
        t('accounting.trialBalance.table.debits'),
        t('accounting.trialBalance.table.credits'),
        t('accounting.trialBalance.table.closingBalance'),
      ]

  const lines = [header.join(',')]

  visibleSections.value.forEach((section) => {
    section.rows.forEach((row) => {
      lines.push([
        section.label,
        row.code,
        `"${row.name}"`,
        row.openingBalance,
        row.debits,
        row.credits,
        row.closingBalance,
        comparePreviousPeriod.value ? row.priorPeriodBalance ?? '' : '',
      ].join(','))
    })
  })

  const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const anchor = document.createElement('a')
  anchor.href = url
  anchor.download = 'trial-balance.csv'
  anchor.click()
  URL.revokeObjectURL(url)
}

const periodLabel = computed(() => {
  if (mode.value === 'fiscal') {
    const period = accountingFiscalPeriods.find((item) => item.id === selectedPeriod.value)
    return period ? `${period.year}-${String(period.month).padStart(2, '0')}` : 'Fiscal Period'
  }

  return `${formatDate(fromDate.value)} - ${formatDate(toDate.value)}`
})
</script>

<template>
  <div class="space-y-6">
    <AccountingNav />

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-xl sm:text-2xl font-bold text-slate-900">{{ t('accounting.trialBalance.title') }}</h1>
        <p class="text-xs sm:text-sm text-slate-500">{{ t('accounting.trialBalance.subtitle') }}</p>
      </div>
      <UiButton variant="outline" class="w-full sm:w-auto justify-center" @click="downloadCsv">
        <Download class="mr-2 h-4 w-4 shrink-0" /> {{ t('accounting.trialBalance.exportExcel') }}
      </UiButton>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-4 sm:p-5 shadow-sm space-y-4">
      <div class="grid gap-3 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
        <UiSelect
          v-model="mode"
          :label="t('accounting.trialBalance.periodMode')"
          :options="[
            { label: t('accounting.trialBalance.customDates'), value: 'custom' },
            { label: t('accounting.trialBalance.fiscalPeriod'), value: 'fiscal' },
          ]"
        />
        <UiInput v-if="mode === 'custom'" v-model="fromDate" :label="t('accounting.trialBalance.from')" type="date" />
        <UiInput v-if="mode === 'custom'" v-model="toDate" :label="t('accounting.trialBalance.to')" type="date" />
        <UiSelect
          v-if="mode === 'fiscal'"
          v-model="selectedPeriod"
          :label="t('accounting.trialBalance.fiscalPeriod')"
          :options="accountingFiscalPeriods.map((period) => ({ label: `${period.year}-${String(period.month).padStart(2, '0')}`, value: period.id }))"
        />
        <label class="flex items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3 sm:col-span-2 lg:col-span-1">
          <input v-model="comparePreviousPeriod" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500 shrink-0" />
          <span class="min-w-0">
            <span class="block text-sm font-medium text-slate-900">{{ t('accounting.trialBalance.comparePrevious') }}</span>
            <span class="block text-xs text-slate-500">{{ t('accounting.trialBalance.compareDesc') }}</span>
          </span>
        </label>
      </div>
      <p class="text-xs sm:text-sm text-slate-500">{{ t('accounting.trialBalance.reportingWindow', { window: periodLabel }) }}</p>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
      <div class="overflow-x-auto">
        <table class="min-w-full min-w-[760px] divide-y divide-slate-200">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 whitespace-nowrap">{{ t('accounting.trialBalance.table.code') }}</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 min-w-[180px]">{{ t('accounting.trialBalance.table.name') }}</th>
              <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500 whitespace-nowrap">{{ t('accounting.trialBalance.table.openingBalance') }}</th>
              <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500 whitespace-nowrap">{{ t('accounting.trialBalance.table.debits') }}</th>
              <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500 whitespace-nowrap">{{ t('accounting.trialBalance.table.credits') }}</th>
              <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500 whitespace-nowrap">{{ t('accounting.trialBalance.table.closingBalance') }}</th>
              <th v-if="comparePreviousPeriod" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500 whitespace-nowrap">{{ t('accounting.trialBalance.table.priorPeriod') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 text-sm">
            <template v-for="section in visibleSections" :key="section.type">
              <tr class="bg-slate-100">
                <td :colspan="comparePreviousPeriod ? 7 : 6" class="px-4 py-2.5 text-xs sm:text-sm font-semibold uppercase tracking-wide text-slate-700">
                  {{ section.label }}
                </td>
              </tr>
              <tr v-for="row in section.rows" :key="row.accountId" class="hover:bg-slate-50">
                <td class="px-4 py-3 font-medium text-slate-900 whitespace-nowrap">{{ row.code }}</td>
                <td class="px-4 py-3 text-slate-700">{{ row.name }}</td>
                <td class="px-4 py-3 text-right text-slate-700 whitespace-nowrap">{{ formatCurrency(row.openingBalance) }}</td>
                <td class="px-4 py-3 text-right text-slate-700 whitespace-nowrap">{{ formatCurrency(row.debits) }}</td>
                <td class="px-4 py-3 text-right text-slate-700 whitespace-nowrap">{{ formatCurrency(row.credits) }}</td>
                <td class="px-4 py-3 text-right font-semibold text-slate-900 whitespace-nowrap">{{ formatCurrency(row.closingBalance) }}</td>
                <td v-if="comparePreviousPeriod" class="px-4 py-3 text-right text-slate-700 whitespace-nowrap">{{ formatCurrency(row.priorPeriodBalance || 0) }}</td>
              </tr>
              <tr class="bg-slate-50/70 font-semibold">
                <td class="px-4 py-3" colspan="2"></td>
                <td class="px-4 py-3 text-right text-xs uppercase tracking-wider text-slate-500">{{ t('accounting.trialBalance.table.subtotal') }}</td>
                <td class="px-4 py-3 text-right text-slate-900 whitespace-nowrap">{{ formatCurrency(section.subtotal.debits) }}</td>
                <td class="px-4 py-3 text-right text-slate-900 whitespace-nowrap">{{ formatCurrency(section.subtotal.credits) }}</td>
                <td class="px-4 py-3 text-right text-slate-900 whitespace-nowrap">{{ formatCurrency(section.subtotal.closingBalance) }}</td>
                <td v-if="comparePreviousPeriod" class="px-4 py-3 text-right text-slate-900 whitespace-nowrap">{{ formatCurrency(section.subtotal.priorPeriodBalance || 0) }}</td>
              </tr>
            </template>
          </tbody>
          <tfoot class="bg-slate-900 text-white">
            <tr>
              <td colspan="2" class="px-4 py-3.5 text-xs sm:text-sm font-semibold uppercase tracking-wider">{{ t('accounting.trialBalance.table.grandTotal') }}</td>
              <td class="px-4 py-3.5 text-right text-xs sm:text-sm font-semibold whitespace-nowrap">{{ formatCurrency(totals.openingBalance) }}</td>
              <td class="px-4 py-3.5 text-right text-xs sm:text-sm font-semibold whitespace-nowrap">{{ formatCurrency(totals.debits) }}</td>
              <td class="px-4 py-3.5 text-right text-xs sm:text-sm font-semibold whitespace-nowrap">{{ formatCurrency(totals.credits) }}</td>
              <td class="px-4 py-3.5 text-right text-xs sm:text-sm font-semibold whitespace-nowrap">{{ formatCurrency(totals.closingBalance) }}</td>
              <td v-if="comparePreviousPeriod" class="px-4 py-3.5 text-right text-xs sm:text-sm font-semibold whitespace-nowrap">{{ formatCurrency(totals.priorPeriodBalance || 0) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
</template>
