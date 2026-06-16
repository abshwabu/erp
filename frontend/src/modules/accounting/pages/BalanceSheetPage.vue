<script setup lang="ts">
import { computed, ref } from 'vue'
import { AlertTriangle, Download } from '@lucide/vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import { sampleBalanceSheet } from '../data'
import { formatCurrency, formatDate } from '@/utils/format'
import type { BalanceSheetGroup } from '@/types/accounting'

const asOfDate = ref(sampleBalanceSheet.asOfDate)
const comparisonDate = ref(sampleBalanceSheet.comparisonDate || '')
const assetGroups = sampleBalanceSheet.assets
const liabilityGroup = sampleBalanceSheet.liabilities[0] as BalanceSheetGroup
const equityGroup = sampleBalanceSheet.equity[0] as BalanceSheetGroup
const expandedGroups = ref<Record<string, boolean>>({
  'assets-current-assets': true,
  'assets-non-current-assets': true,
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
  return sampleBalanceSheet.assetsTotal - (sampleBalanceSheet.liabilitiesTotal + sampleBalanceSheet.equityTotal)
})

const downloadCsv = () => {
  const lines = [
    ['Section', 'Group', 'Code', 'Name', 'Balance'].join(','),
  ]

  ;[
    ['Assets', sampleBalanceSheet.assets],
    ['Liabilities', sampleBalanceSheet.liabilities],
    ['Equity', sampleBalanceSheet.equity],
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
          <UiBadge variant="info">{{ formatCurrency(sampleBalanceSheet.assetsTotal) }}</UiBadge>
        </div>
        <div class="space-y-4">
          <div v-for="group in assetGroups" :key="group.label" class="rounded-2xl border border-slate-200">
            <button class="flex w-full items-center justify-between px-4 py-3 text-left" @click="toggleGroup('assets', group)">
              <span class="text-sm font-semibold uppercase tracking-wide text-slate-700">{{ group.label }}</span>
              <span class="text-sm font-semibold text-slate-900">{{ formatCurrency(group.subtotal) }}</span>
            </button>
            <div v-if="isOpen('assets', group)" class="border-t border-slate-200">
              <div v-for="row in group.rows" :key="row.accountId" class="flex items-center justify-between px-4 py-3 text-sm">
                <span class="text-slate-700">{{ row.code }} - {{ row.name }}</span>
                <span class="font-medium text-slate-900">{{ formatCurrency(row.balance) }}</span>
              </div>
              <div class="flex items-center justify-between bg-slate-50 px-4 py-3 text-sm font-semibold">
                <span class="text-slate-700">Subtotal</span>
                <span class="text-slate-900">{{ formatCurrency(group.subtotal) }}</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="text-lg font-semibold text-slate-900">Liabilities + Equity</h2>
          <UiBadge variant="info">{{ formatCurrency(sampleBalanceSheet.liabilitiesTotal + sampleBalanceSheet.equityTotal) }}</UiBadge>
        </div>
        <div class="space-y-4">
          <div class="rounded-2xl border border-slate-200">
            <button class="flex w-full items-center justify-between px-4 py-3 text-left" @click="toggleGroup('liabilities', liabilityGroup)">
              <span class="text-sm font-semibold uppercase tracking-wide text-slate-700">{{ liabilityGroup.label }}</span>
              <span class="text-sm font-semibold text-slate-900">{{ formatCurrency(liabilityGroup.subtotal) }}</span>
            </button>
            <div v-if="isOpen('liabilities', liabilityGroup)" class="border-t border-slate-200">
              <div v-for="row in liabilityGroup.rows" :key="row.accountId" class="flex items-center justify-between px-4 py-3 text-sm">
                <span class="text-slate-700">{{ row.code }} - {{ row.name }}</span>
                <span class="font-medium text-slate-900">{{ formatCurrency(row.balance) }}</span>
              </div>
              <div class="flex items-center justify-between bg-slate-50 px-4 py-3 text-sm font-semibold">
                <span class="text-slate-700">Subtotal</span>
                <span class="text-slate-900">{{ formatCurrency(liabilityGroup.subtotal) }}</span>
              </div>
            </div>
          </div>

          <div class="rounded-2xl border border-slate-200">
            <button class="flex w-full items-center justify-between px-4 py-3 text-left" @click="toggleGroup('equity', equityGroup)">
              <span class="text-sm font-semibold uppercase tracking-wide text-slate-700">{{ equityGroup.label }}</span>
              <span class="text-sm font-semibold text-slate-900">{{ formatCurrency(equityGroup.subtotal) }}</span>
            </button>
            <div v-if="isOpen('equity', equityGroup)" class="border-t border-slate-200">
              <div v-for="row in equityGroup.rows" :key="row.accountId" class="flex items-center justify-between px-4 py-3 text-sm">
                <span class="text-slate-700">{{ row.code }} - {{ row.name }}</span>
                <span class="font-medium text-slate-900">{{ formatCurrency(row.balance) }}</span>
              </div>
              <div class="flex items-center justify-between bg-slate-50 px-4 py-3 text-sm font-semibold">
                <span class="text-slate-700">Subtotal</span>
                <span class="text-slate-900">{{ formatCurrency(equityGroup.subtotal) }}</span>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>

    <div
      class="flex items-center justify-between rounded-3xl border px-5 py-4 shadow-sm"
      :class="balanceDifference === 0 ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50'"
    >
      <div class="flex items-center gap-3">
        <AlertTriangle class="h-5 w-5" :class="balanceDifference === 0 ? 'text-emerald-600' : 'text-rose-600'" />
        <div>
          <p class="text-sm font-semibold text-slate-900">Balance check</p>
          <p class="text-sm text-slate-600">Assets must equal Liabilities plus Equity.</p>
        </div>
      </div>
      <div class="text-right">
        <p class="text-sm text-slate-600">Difference</p>
        <p class="text-lg font-bold" :class="balanceDifference === 0 ? 'text-emerald-700' : 'text-rose-700'">
          {{ formatCurrency(balanceDifference) }}
        </p>
      </div>
    </div>
  </div>
</template>
