<script setup lang="ts">
import { computed, ref } from 'vue'
import { Filter, Plus, Search } from '@lucide/vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import { formatCurrency, formatDate } from '@/utils/format'
import JournalDetailModal from '../components/JournalDetailModal.vue'
import JournalEntryModal from '../components/JournalEntryModal.vue'
import { sampleAccounts, sampleJournals } from '../data'
import type { Account, Journal } from '@/types/accounting'

const cloneTree = (accounts: Account[]): Account[] => accounts.map((account) => ({
  ...account,
  children: account.children ? cloneTree(account.children) : undefined,
}))

const flattenAccounts = (accounts: Account[]): Account[] => accounts.flatMap((account) => [
  account,
  ...(account.children ? flattenAccounts(account.children) : []),
])

const journals = ref<Journal[]>(sampleJournals.map((journal) => ({
  ...journal,
  lines: journal.lines.map((line) => ({ ...line })),
})))
const accounts = ref<Account[]>(cloneTree(sampleAccounts))
const statusFilter = ref<'all' | Journal['status']>('all')
const searchQuery = ref('')
const fromDate = ref('2026-03-01')
const toDate = ref('2026-03-31')
const isEntryModalOpen = ref(false)
const selectedJournal = ref<Journal | null>(null)
const isDetailModalOpen = ref(false)

const accountIndex = computed(() => {
  return new Map(flattenAccounts(accounts.value).map((account) => [account.id, account]))
})

const nextReference = computed(() => {
  const highest = journals.value.reduce((max, journal) => {
    const suffix = Number(journal.reference.replace(/[^0-9]/g, ''))
    return Number.isFinite(suffix) && suffix > max ? suffix : max
  }, 0)

  return `JE-2026-${String(highest + 1).padStart(4, '0')}`
})

const filteredJournals = computed(() => {
  return journals.value.filter((journal) => {
    const matchesStatus = statusFilter.value === 'all' || journal.status === statusFilter.value
    const matchesFromDate = !fromDate.value || journal.journalDate >= fromDate.value
    const matchesToDate = !toDate.value || journal.journalDate <= toDate.value
    const searchable = `${journal.reference} ${journal.description} ${journal.sourceType}`.toLowerCase()
    const matchesQuery = !searchQuery.value || searchable.includes(searchQuery.value.toLowerCase())

    return matchesStatus && matchesFromDate && matchesToDate && matchesQuery
  })
})

const statusVariant = (status: Journal['status']) => {
  switch (status) {
    case 'posted':
      return 'success'
    case 'reversed':
      return 'danger'
    default:
      return 'warning'
  }
}

const openJournal = (journal: Journal) => {
  selectedJournal.value = journal
  isDetailModalOpen.value = true
}

const createJournal = () => {
  isEntryModalOpen.value = true
}

const handleSaved = (payload: {
  status: 'draft' | 'posted'
  journal: {
    reference: string
    journalDate: string
    description: string
    status: 'draft' | 'posted'
    lines: Array<{ accountId: string; description: string; debit: number; credit: number; currencyCode: string }>
  }
}) => {
  const resolvedLines = payload.journal.lines.map((line, index) => {
    const account = accountIndex.value.get(line.accountId)
    return {
      id: `jrnl-line-${Date.now()}-${index}`,
      journalId: `jrnl-${Date.now()}`,
      accountId: line.accountId,
      account,
      description: line.description,
      debit: line.debit,
      credit: line.credit,
      currencyCode: line.currencyCode,
    }
  })

  const totalDebit = resolvedLines.reduce((sum, line) => sum + line.debit, 0)
  const totalCredit = resolvedLines.reduce((sum, line) => sum + line.credit, 0)

  journals.value = [
    {
      id: `jrnl-${Date.now()}`,
      reference: payload.journal.reference,
      description: payload.journal.description,
      journalDate: payload.journal.journalDate,
      status: payload.status,
      sourceType: 'manual',
      totalDebit,
      totalCredit,
      lines: resolvedLines,
      postedAt: payload.status === 'posted' ? new Date().toISOString() : null,
    },
    ...journals.value,
  ]
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Journal Entries</h1>
        <p class="text-sm text-slate-500">Track manual and system-generated postings across the ledger.</p>
      </div>
      <UiButton @click="createJournal">
        <Plus class="mr-2 h-4 w-4" /> New Journal Entry
      </UiButton>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
      <div class="flex flex-wrap items-center gap-2">
        <Filter class="h-4 w-4 text-slate-400" />
        <button
          v-for="tab in [
            { label: 'All', value: 'all' },
            { label: 'Draft', value: 'draft' },
            { label: 'Posted', value: 'posted' },
            { label: 'Reversed', value: 'reversed' },
          ]"
          :key="tab.label"
          class="rounded-full border px-4 py-2 text-sm font-medium transition-colors"
          :class="statusFilter === tab.value ? 'border-primary-600 bg-primary-50 text-primary-700' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
          @click="statusFilter = tab.value as 'all' | Journal['status']"
        >
          {{ tab.label }}
        </button>
      </div>
      <div class="mt-4 grid gap-4 lg:grid-cols-4">
        <div class="relative lg:col-span-2">
          <UiInput v-model="searchQuery" placeholder="Search reference, description, or source..." />
          <Search class="pointer-events-none absolute right-3 top-3 h-4 w-4 text-slate-400" />
        </div>
        <UiInput v-model="fromDate" label="From" type="date" />
        <UiInput v-model="toDate" label="To" type="date" />
      </div>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Date</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Reference</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Description</th>
            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Total Amount</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Source</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
          <tr
            v-for="journal in filteredJournals"
            :key="journal.id"
            class="cursor-pointer hover:bg-slate-50"
            @click="openJournal(journal)"
          >
            <td class="px-4 py-3 text-slate-700">{{ formatDate(journal.journalDate) }}</td>
            <td class="px-4 py-3 font-medium text-slate-900">{{ journal.reference }}</td>
            <td class="px-4 py-3 text-slate-600">{{ journal.description }}</td>
            <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ formatCurrency(journal.totalDebit) }}</td>
            <td class="px-4 py-3">
              <UiBadge :variant="statusVariant(journal.status)">{{ journal.status }}</UiBadge>
            </td>
            <td class="px-4 py-3 text-slate-600">{{ journal.sourceType }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <JournalEntryModal
      v-model="isEntryModalOpen"
      :accounts="accounts"
      :journal="{ reference: nextReference, journalDate: new Date().toISOString().slice(0, 10), description: '', status: 'draft', lines: [] }"
      @saved="handleSaved"
    />

    <JournalDetailModal
      v-model="isDetailModalOpen"
      :journal="selectedJournal"
    />
  </div>
</template>

