<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { Filter, Plus, Search } from '@lucide/vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import { formatCurrency, formatDate } from '@/utils/format'
import JournalDetailModal from '../components/JournalDetailModal.vue'
import JournalEntryModal from '../components/JournalEntryModal.vue'
import AccountingNav from '../components/AccountingNav.vue'
import { accountingApi } from '@/api/accounting'
import type { Account, AccountType, Journal } from '@/types/accounting'

const { t } = useI18n()

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
const statusTabs = computed(() => [
  { label: t('accounting.journals.tabs.all'), value: 'all' },
  { label: t('accounting.journals.tabs.draft'), value: 'draft' },
  { label: t('accounting.journals.tabs.posted'), value: 'posted' },
  { label: t('accounting.journals.tabs.reversed'), value: 'reversed' },
])
</script>

<template>
  <div class="space-y-6">
    <AccountingNav />

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-xl sm:text-2xl font-bold text-slate-900">{{ t('accounting.journals.title') }}</h1>
        <p class="text-xs sm:text-sm text-slate-500">{{ t('accounting.journals.subtitle') }}</p>
      </div>
      <UiButton class="w-full sm:w-auto justify-center" @click="createJournal">
        <Plus class="mr-2 h-4 w-4 shrink-0" /> {{ t('accounting.journals.newEntry') }}
      </UiButton>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-4 sm:p-5 shadow-sm space-y-4">
      <div class="flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0 scrollbar-none">
        <Filter class="h-4 w-4 text-slate-400 shrink-0 hidden sm:block" />
        <button
          v-for="tab in statusTabs"
          :key="tab.value"
          class="whitespace-nowrap rounded-full border px-3.5 py-1.5 sm:px-4 sm:py-2 text-xs sm:text-sm font-medium transition-colors shrink-0"
          :class="statusFilter === tab.value ? 'border-primary-600 bg-primary-50 text-primary-700 font-semibold' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
          @click="statusFilter = tab.value as 'all' | Journal['status']"
        >
          {{ tab.label }}
        </button>
      </div>
      <div class="grid gap-3 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
        <div class="relative sm:col-span-2 lg:col-span-2">
          <UiInput v-model="searchQuery" :placeholder="t('accounting.journals.searchPlaceholder')" />
          <Search class="pointer-events-none absolute right-3 top-3 h-4 w-4 text-slate-400" />
        </div>
        <UiInput v-model="fromDate" :label="t('accounting.journals.from')" type="date" />
        <UiInput v-model="toDate" :label="t('accounting.journals.to')" type="date" />
      </div>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
      <div class="overflow-x-auto">
        <table class="min-w-full min-w-[700px] divide-y divide-slate-200">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 whitespace-nowrap">{{ t('accounting.journals.table.date') }}</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 whitespace-nowrap">{{ t('accounting.journals.table.reference') }}</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 min-w-[200px]">{{ t('accounting.journals.table.description') }}</th>
              <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500 whitespace-nowrap">{{ t('accounting.journals.table.totalAmount') }}</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 whitespace-nowrap">{{ t('accounting.journals.table.status') }}</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 whitespace-nowrap">{{ t('accounting.journals.table.source') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            <tr
              v-for="journal in filteredJournals"
              :key="journal.id"
              class="cursor-pointer hover:bg-slate-50 transition-colors"
              @click="openJournal(journal)"
            >
              <td class="px-4 py-3 text-slate-700 whitespace-nowrap text-sm">{{ formatDate(journal.journalDate) }}</td>
              <td class="px-4 py-3 font-medium text-slate-900 whitespace-nowrap text-sm">{{ journal.reference }}</td>
              <td class="px-4 py-3 text-slate-600 text-sm">
                <div class="line-clamp-2">{{ journal.description }}</div>
              </td>
              <td class="px-4 py-3 text-right font-semibold text-slate-900 whitespace-nowrap text-sm">{{ formatCurrency(journal.totalDebit) }}</td>
              <td class="px-4 py-3 whitespace-nowrap">
                <UiBadge :variant="statusVariant(journal.status)">{{ t(`accounting.common.statuses.${journal.status}`) || journal.status }}</UiBadge>
              </td>
              <td class="px-4 py-3 text-slate-600 whitespace-nowrap text-sm capitalize">{{ journal.sourceType }}</td>
            </tr>
          </tbody>
        </table>
      </div>
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

