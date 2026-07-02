<script setup lang="ts">
import { computed, ref, onMounted, watch } from 'vue'
import { AlertTriangle, Download } from '@lucide/vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import { accountingApi } from '@/api/accounting'
import { formatCurrency, formatDate } from '@/utils/format'
import type { BalanceSheetGroup } from '@/types/accounting'

const currentYear = new Date().getFullYear()
const asOfDate = ref(`${currentYear}-12-31`)
const comparisonDate = ref(`${currentYear - 1}-12-31`)

const assets = ref<BalanceSheetGroup[]>([])
const liabilities = ref<BalanceSheetGroup[]>([])
const equity = ref<BalanceSheetGroup[]>([])
const reportTotals = ref({
  assetsTotal: 0,
  liabilitiesTotal: 0,
  equityTotal: 0,
})

const expandedGroups = ref<Record<string, boolean>>({
  'assets-current-assets': true,
  'liabilities-current-liabilities': true,
  'equity-equity': true,
})

const createGroupKey = (section: string, group: string) => `${section}-${group.toLowerCase().replace(/[^a-z0-9]+/g, '-')}`

const isOpen = (section: string, group: BalanceSheetGroup) => expandedGroups.value[createGroupKey(section, group.label)] ?? true

const toggleGroup = (section: string, group: BalanceSheetGroup) => {
  const key = createGroupKey(section, group.label)
  expandedGroups.value[key] = !isOpen(section, group)
}

const balanceDifference = computed(() => {
  return reportTotals.value.assetsTotal - (reportTotals.value.liabilitiesTotal + reportTotals.value.equityTotal)
})

const loadBalanceSheet = async () => {
  try {
    const res = await accountingApi.getBalanceSheet({
      as_of_date: asOfDate.value,
    })
    const data = res.data
    const sections = data.sections || {}
    const totals = data.totals || {}

    const assetRows = (sections.Asset || []).map((row: any) => ({
      accountId: row.id,
      code: row.code,
      name: row.name,
      balance: (row.amount || 0) / 100,
    }))
    const assetsTotal = (totals.assets || 0) / 100

    const liabilityRows = (sections.Liability || []).map((row: any) => ({
      accountId: row.id,
      code: row.code,
      name: row.name,
      balance: (row.amount || 0) / 100,
    }))
    const liabilitiesTotal = (totals.liabilities || 0) / 100

    const equityRows = (sections.Equity || []).map((row: any) => ({
      accountId: row.id,
      code: row.code,
      name: row.name,
      balance: (row.amount || 0) / 100,
    }))
    const equityTotal = (totals.equity || 0) / 100

    assets.value = [
      {
        label: 'Current Assets',
        subtotal: assetsTotal,
        rows: assetRows,
      }
    ]

    liabilities.value = [
      {
        label: 'Current Liabilities',
        subtotal: liabilitiesTotal,
        rows: liabilityRows,
      }
    ]

    equity.value = [
      {
        label: 'Equity',
        subtotal: equityTotal,
        rows: equityRows,
      }
    ]

    reportTotals.value = {
      assetsTotal,
      liabilitiesTotal,
      equityTotal,
    }
  } catch (err) {
    console.error('Failed to load balance sheet:', err)
  }
}

onMounted(() => {
  loadBalanceSheet()
})

watch(asOfDate, () => {
  loadBalanceSheet()
})

const downloadCsv = () => {
  const lines = [
    ['Section', 'Group', 'Code', 'Name', 'Balance'].join(','),
  ]

  ;[
    ['Assets', assets.value],
    ['Liabilities', liabilities.value],
    ['Equity', equity.value],
  ].forEach(([section, groups]) => {
    ;(groups as BalanceSheetGroup[]).forEach((group) => {
      group.rows.forEach((row) => {
        lines.push([section, group.label, row.code, `"${row.name}"`, row.balance].join(','))
      })
    })
  })

  const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const anchor = document.createElement('a')
  anchor.href = url
  anchor.download = 'balance-sheet.csv'
  anchor.click()
  URL.revokeObjectURL(url)
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Balance Sheet</h1>
        <p class="text-sm text-slate-500">A two-column statement of financial position as of the selected date.</p>
      </div>
      <UiButton variant="outline" @click="downloadCsv">
        <Download class="mr-2 h-4 w-4" /> Export to Excel
      </UiButton>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
      <div class="grid gap-4 lg:grid-cols-2">
        <UiInput v-model="asOfDate" label="As of Date" type="date" />
        <UiInput v-model="comparisonDate" label="Comparison Date" type="date" />
      </div>
      <p class="mt-4 text-sm text-slate-500">
        Current as of {{ formatDate(asOfDate) }}. Comparison against {{ comparisonDate ? formatDate(comparisonDate) : 'prior date' }}.
      </p>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
      <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="text-lg font-semibold text-slate-900">Assets</h2>
          <UiBadge variant="info">{{ formatCurrency(reportTotals.assetsTotal) }}</UiBadge>
        </div>
        <div class="space-y-4">
          <div v-for="group in assets" :key="group.label" class="rounded-2xl border border-slate-200">
            <button class="flex w-full items-center justify-between px-4 py-3 text-left" @click="toggleGroup('assets', group)">
              <span class="text-sm font-semibold uppercase tracking-wide text-slate-700">{{ group.label }}</span>
              <span class="text-sm font-semibold text-slate-900">{{ formatCurrency(group.subtotal) }}</span>
            </button>
            <div v-if="isOpen('assets', group)" class="border-t border-slate-200">
              <div v-for="row in group.rows" :key="row.accountId" class="flex items-center justify-between px-4 py-3 text-sm">
                <span class="text-slate-700">{{ row.code }} - {{ row.name }}</span>
                <span class="font-medium text-slate-900">{{ formatCurrency(row.balance) }}</span>
              </div>
              <div class="flex items-center justify-between bg-slate-50 px-4 py-3 text-sm font-semibold border-t">
                <span class="text-slate-950">Subtotal {{ group.label }}</span>
                <span class="text-slate-900">{{ formatCurrency(group.subtotal) }}</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      <div class="space-y-6">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
          <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900">Liabilities</h2>
            <UiBadge variant="info">{{ formatCurrency(reportTotals.liabilitiesTotal) }}</UiBadge>
          </div>
          <div class="space-y-4">
            <div v-for="group in liabilities" :key="group.label" class="rounded-2xl border border-slate-200">
              <button class="flex w-full items-center justify-between px-4 py-3 text-left" @click="toggleGroup('liabilities', group)">
                <span class="text-sm font-semibold uppercase tracking-wide text-slate-700">{{ group.label }}</span>
                <span class="text-sm font-semibold text-slate-900">{{ formatCurrency(group.subtotal) }}</span>
              </button>
              <div v-if="isOpen('liabilities', group)" class="border-t border-slate-200">
                <div v-for="row in group.rows" :key="row.accountId" class="flex items-center justify-between px-4 py-3 text-sm">
                  <span class="text-slate-700">{{ row.code }} - {{ row.name }}</span>
                  <span class="font-medium text-slate-900">{{ formatCurrency(row.balance) }}</span>
                </div>
                <div class="flex items-center justify-between bg-slate-50 px-4 py-3 text-sm font-semibold border-t">
                  <span class="text-slate-950">Subtotal {{ group.label }}</span>
                  <span class="text-slate-900">{{ formatCurrency(group.subtotal) }}</span>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
          <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900">Equity</h2>
            <UiBadge variant="info">{{ formatCurrency(reportTotals.equityTotal) }}</UiBadge>
          </div>
          <div class="space-y-4">
            <div v-for="group in equity" :key="group.label" class="rounded-2xl border border-slate-200">
              <button class="flex w-full items-center justify-between px-4 py-3 text-left" @click="toggleGroup('equity', group)">
                <span class="text-sm font-semibold uppercase tracking-wide text-slate-700">{{ group.label }}</span>
                <span class="text-sm font-semibold text-slate-900">{{ formatCurrency(group.subtotal) }}</span>
              </button>
              <div v-if="isOpen('equity', group)" class="border-t border-slate-200">
                <div v-for="row in group.rows" :key="row.accountId" class="flex items-center justify-between px-4 py-3 text-sm">
                  <span class="text-slate-700">{{ row.code }} - {{ row.name }}</span>
                  <span class="font-medium text-slate-900">{{ formatCurrency(row.balance) }}</span>
                </div>
                <div class="flex items-center justify-between bg-slate-50 px-4 py-3 text-sm font-semibold border-t">
                  <span class="text-slate-950">Subtotal {{ group.label }}</span>
                  <span class="text-slate-900">{{ formatCurrency(group.subtotal) }}</span>
                </div>
              </div>
            </div>
          </div>
        </section>

        <div
          v-if="Math.abs(balanceDifference) > 0.01"
          class="rounded-3xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800"
        >
          <div class="flex items-center gap-2">
            <AlertTriangle class="h-5 w-5 text-rose-600" />
            <span class="font-semibold text-rose-900">Ledger is out of balance!</span>
          </div>
          <p class="mt-1">
            Total Assets must equal total Liabilities + Equity. The discrepancy is
            {{ formatCurrency(balanceDifference) }}.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
