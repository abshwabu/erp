<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import { Filter, Plus, Search } from '@lucide/vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import { formatCurrency, formatDate } from '@/utils/format'
import JournalDetailModal from '../components/JournalDetailModal.vue'
import JournalEntryModal from '../components/JournalEntryModal.vue'
import { accountingApi } from '@/api/accounting'
import type { Account, AccountType, Journal } from '@/types/accounting'

const currentYear = new Date().getFullYear()
const journals = ref<Journal[]>([])
const accounts = ref<Account[]>([])
const statusFilter = ref<'all' | Journal['status']>('all')
const searchQuery = ref('')
const fromDate = ref(`${currentYear}-01-01`)
const toDate = ref(`${currentYear}-12-31`)
const isEntryModalOpen = ref(false)
const selectedJournal = ref<Journal | null>(null)
const isDetailModalOpen = ref(false)

const mapAccountFromBackend = (acc: any): Account => {
  const nameMapFromBackend: Record<string, string> = {
    'Asset': 'asset',
    'Liability': 'liability',
    'Equity': 'equity',
    'Revenue': 'revenue',
    'Expense': 'expense',
    'COGS': 'cost_of_sales',
  }
  const typeName = acc.account_type?.name || 'Asset'
  const frontendType = nameMapFromBackend[typeName] || 'asset'
  return {
    id: acc.id,
    parentId: acc.parent_id,
    code: acc.code,
    name: acc.name,
    type: frontendType as AccountType,
    description: acc.description || '',
    currencyCode: acc.currency_code || 'USD',
    isActive: Boolean(acc.is_active),
    isSystemAccount: Boolean(acc.is_system_account),
    currentPeriodBalance: (acc.current_period_balance || 0) / 100,
    children: acc.children ? acc.children.map(mapAccountFromBackend) : undefined
  }
}

const mapJournalFromBackend = (j: any): Journal => {
  const lines = (j.lines || []).map((l: any) => ({
    id: l.id,
    journalId: l.journal_id,
    accountId: l.account_id,
    account: l.account ? mapAccountFromBackend(l.account) : undefined,
    description: l.description || '',
    debit: (l.debit_cents || 0) / 100,
    credit: (l.credit_cents || 0) / 100,
    currencyCode: l.currency_code || 'USD',
  }))

  const totalDebit = lines.reduce((sum: number, line: any) => sum + line.debit, 0)
  const totalCredit = lines.reduce((sum: number, line: any) => sum + line.credit, 0)

  return {
    id: j.id,
    reference: j.reference,
    description: j.description,
    journalDate: j.journal_date,
    status: j.status,
    sourceType: j.source_type || 'manual',
    reversalOfId: j.reversal_of_id,
    postedAt: j.posted_at,
    postedBy: j.posted_by?.name || null,
    totalDebit,
    totalCredit,
    lines,
  }
}

const loadAccounts = async () => {
  try {
    const res = await accountingApi.getAccountsTree()
    accounts.value = res.data.map(mapAccountFromBackend)
  } catch (err) {
    console.error('Failed to load accounts:', err)
  }
}

const loadJournals = async () => {
  try {
    const res = await accountingApi.getJournals()
    const resData: any = res.data
    const rawData = Array.isArray(resData) ? resData : resData?.data || []
    journals.value = rawData.map(mapJournalFromBackend)
  } catch (err) {
    console.error('Failed to load journals:', err)
  }
}

onMounted(() => {
  loadAccounts()
  loadJournals()
})

const flattenAccounts = (nodes: Account[]): Account[] => nodes.flatMap((account) => [
  account,
  ...(account.children ? flattenAccounts(account.children) : []),
])

const nextReference = computed(() => {
  const highest = journals.value.reduce((max, journal) => {
    const suffix = Number(journal.reference.replace(/[^0-9]/g, ''))
    return Number.isFinite(suffix) && suffix > max ? suffix : max
  }, 0)

  return `JE-${currentYear}-${String(highest + 1).padStart(4, '0')}`
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

const handleSaved = async (payload: {
  status: 'draft' | 'posted'
  journal: {
    reference: string
    journalDate: string
    description: string
    status: 'draft' | 'posted'
    lines: Array<{ accountId: string; description: string; debit: number; credit: number; currencyCode: string }>
  }
}) => {
  const apiLines = payload.journal.lines.map((l) => ({
    account_id: l.accountId,
    description: l.description,
    debit_cents: Math.round(l.debit * 100),
    credit_cents: Math.round(l.credit * 100),
    currency_code: l.currencyCode || 'USD',
  }))

  const apiPayload = {
    reference: payload.journal.reference,
    description: payload.journal.description,
    journal_date: payload.journal.journalDate,
    lines: apiLines,
  }

  try {
    const res = await accountingApi.createJournal(apiPayload)
    const journalId = res.data.id
    if (payload.status === 'posted') {
      await accountingApi.postJournal(journalId)
    }
    await loadJournals()
    isEntryModalOpen.value = false
  } catch (err) {
    console.error('Failed to create journal entry:', err)
    alert('Failed to save journal entry.')
  }
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

