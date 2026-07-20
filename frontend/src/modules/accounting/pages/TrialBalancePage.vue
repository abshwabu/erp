<script setup lang="ts">
import { computed, ref, onMounted, watch } from 'vue'
import { Download } from '@lucide/vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import { accountingApi } from '@/api/accounting'
import { formatCurrency, formatDate } from '@/utils/format'
import type { AccountType, TrialBalanceRow, TrialBalanceSection } from '@/types/accounting'

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
      Asset: { label: 'Assets', type: 'asset', rows: [] },
      Liability: { label: 'Liabilities', type: 'liability', rows: [] },
      Equity: { label: 'Equity', type: 'equity', rows: [] },
      Revenue: { label: 'Revenue', type: 'revenue', rows: [] },
      COGS: { label: 'Cost of Goods Sold', type: 'cost_of_sales', rows: [] },
      Expense: { label: 'Expenses', type: 'expense', rows: [] },
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
    ? ['Section', 'Code', 'Name', 'Opening Balance', 'Debits', 'Credits', 'Closing Balance', 'Prior Period']
    : ['Section', 'Code', 'Name', 'Opening Balance', 'Debits', 'Credits', 'Closing Balance']

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
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Trial Balance</h1>
        <p class="text-sm text-slate-500">Grouped ledger balances for the selected reporting period.</p>
      </div>
      <UiButton variant="outline" @click="downloadCsv">
        <Download class="mr-2 h-4 w-4" /> Export to Excel
      </UiButton>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
      <div class="grid gap-4 lg:grid-cols-4">
        <UiSelect
          v-model="mode"
          label="Period Mode"
          :options="[
            { label: 'Custom Dates', value: 'custom' },
            { label: 'Fiscal Period', value: 'fiscal' },
          ]"
        />
        <UiInput v-if="mode === 'custom'" v-model="fromDate" label="From" type="date" />
        <UiInput v-if="mode === 'custom'" v-model="toDate" label="To" type="date" />
        <UiSelect
          v-if="mode === 'fiscal'"
          v-model="selectedPeriod"
          label="Fiscal Period"
          :options="accountingFiscalPeriods.map((period) => ({ label: `${period.year}-${String(period.month).padStart(2, '0')}`, value: period.id }))"
        />
        <label class="flex items-end gap-3 rounded-2xl border border-slate-200 px-4 py-3">
          <input v-model="comparePreviousPeriod" type="checkbox" class="mt-1 rounded border-slate-300 text-primary-600 focus:ring-primary-500" />
          <span>
            <span class="block text-sm font-medium text-slate-900">Compare previous period</span>
            <span class="block text-xs text-slate-500">Shows a prior-period balance column.</span>
          </span>
        </label>
      </div>
      <p class="mt-4 text-sm text-slate-500">Reporting window: {{ periodLabel }}</p>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Code</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Name</th>
            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Opening Balance</th>
            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Debits</th>
            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Credits</th>
            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Closing Balance</th>
            <th v-if="comparePreviousPeriod" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Prior Period</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
          <template v-for="section in visibleSections" :key="section.type">
            <tr class="bg-slate-100">
              <td :colspan="comparePreviousPeriod ? 7 : 6" class="px-4 py-3 text-sm font-semibold uppercase tracking-wide text-slate-700">
                {{ section.label }}
              </td>
            </tr>
            <tr v-for="row in section.rows" :key="row.accountId" class="hover:bg-slate-50">
              <td class="px-4 py-3 font-medium text-slate-900">{{ row.code }}</td>
              <td class="px-4 py-3 text-slate-700">{{ row.name }}</td>
              <td class="px-4 py-3 text-right text-slate-700">{{ formatCurrency(row.openingBalance) }}</td>
              <td class="px-4 py-3 text-right text-slate-700">{{ formatCurrency(row.debits) }}</td>
              <td class="px-4 py-3 text-right text-slate-700">{{ formatCurrency(row.credits) }}</td>
              <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ formatCurrency(row.closingBalance) }}</td>
              <td v-if="comparePreviousPeriod" class="px-4 py-3 text-right text-slate-700">{{ formatCurrency(row.priorPeriodBalance || 0) }}</td>
            </tr>
            <tr class="bg-slate-50">
              <td class="px-4 py-3" colspan="2"></td>
              <td class="px-4 py-3 text-right text-sm font-semibold text-slate-700">Subtotal</td>
              <td class="px-4 py-3 text-right text-sm font-semibold text-slate-900">{{ formatCurrency(section.subtotal.openingBalance) }}</td>
              <td class="px-4 py-3 text-right text-sm font-semibold text-slate-900">{{ formatCurrency(section.subtotal.debits) }}</td>
              <td class="px-4 py-3 text-right text-sm font-semibold text-slate-900">{{ formatCurrency(section.subtotal.credits) }}</td>
              <td class="px-4 py-3 text-right text-sm font-semibold text-slate-900">{{ formatCurrency(section.subtotal.closingBalance) }}</td>
              <td v-if="comparePreviousPeriod" class="px-4 py-3 text-right text-sm font-semibold text-slate-900">{{ formatCurrency(section.subtotal.priorPeriodBalance || 0) }}</td>
            </tr>
          </template>
        </tbody>
        <tfoot class="bg-slate-900 text-white">
          <tr>
            <td colspan="2" class="px-4 py-3 text-sm font-semibold">Grand Total</td>
            <td class="px-4 py-3 text-right text-sm font-semibold">{{ formatCurrency(totals.openingBalance) }}</td>
            <td class="px-4 py-3 text-right text-sm font-semibold">{{ formatCurrency(totals.debits) }}</td>
            <td class="px-4 py-3 text-right text-sm font-semibold">{{ formatCurrency(totals.credits) }}</td>
            <td class="px-4 py-3 text-right text-sm font-semibold">{{ formatCurrency(totals.closingBalance) }}</td>
            <td v-if="comparePreviousPeriod" class="px-4 py-3 text-right text-sm font-semibold">{{ formatCurrency(totals.priorPeriodBalance || 0) }}</td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</template>
