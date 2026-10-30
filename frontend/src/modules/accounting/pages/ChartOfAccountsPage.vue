<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { ChevronDown, ChevronRight, Filter, Plus, Search, Pencil, Trash2 } from '@lucide/vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import AccountModal from '../components/AccountModal.vue'
import AccountingNav from '../components/AccountingNav.vue'
import { accountingApi } from '@/api/accounting'
import { formatCurrency } from '@/utils/format'
import type { Account, AccountType } from '@/types/accounting'

const { t } = useI18n()

type TypeFilter = 'all' | AccountType

interface AccountRow {
  account: Account
  depth: number
  displayBalance: number
  hasChildren: boolean
}

const accountTree = ref<Account[]>([])
const accountTypes = ref<any[]>([])
const filter = ref<TypeFilter>('all')
const searchQuery = ref('')
const expandedIds = ref<string[]>([])
const isAccountModalOpen = ref(false)
const selectedAccount = ref<Account | null>(null)

const nameMapFromBackend: Record<string, string> = {
  'Asset': 'asset',
  'Liability': 'liability',
  'Equity': 'equity',
  'Revenue': 'revenue',
  'Expense': 'expense',
  'COGS': 'cost_of_sales',
}

const nameMapToBackend: Record<string, string> = {
  asset: 'Asset',
  liability: 'Liability',
  equity: 'Equity',
  revenue: 'Revenue',
  expense: 'Expense',
  cost_of_sales: 'COGS',
}

const mapAccountFromBackend = (acc: any): Account => {
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
    currentPeriodBalance: (acc.current_period_balance || 0) / 100, // Cents to Dollars
    children: acc.children ? acc.children.map(mapAccountFromBackend) : undefined
  }
}

const loadAccounts = async () => {
  try {
    const res = await accountingApi.getAccountsTree()
    const mapped = res.data.map(mapAccountFromBackend)
    accountTree.value = mapped
    
    // Auto-expand root elements if expandedIds is empty
    if (expandedIds.value.length === 0) {
      expandedIds.value = mapped.map((account) => account.id)
    }
  } catch (err) {
    console.error('Failed to load accounts tree:', err)
  }
}

const loadAccountTypes = async () => {
  try {
    const res = await accountingApi.getAccountTypes()
    accountTypes.value = res.data
  } catch (err) {
    console.error('Failed to load account types:', err)
  }
}

const mapTypeToId = (type: string) => {
  const targetName = nameMapToBackend[type] || 'Asset'
  const found = accountTypes.value.find((t) => t.name === targetName)
  return found ? found.id : null
}

onMounted(() => {
  loadAccountTypes()
  loadAccounts()
})

const flattenAccounts = (accounts: Account[], depth = 0): AccountRow[] => {
  return accounts.flatMap((account) => [
    {
      account,
      depth,
      displayBalance: account.children?.length
        ? account.children.reduce((sum, child) => sum + child.currentPeriodBalance, 0)
        : account.currentPeriodBalance,
      hasChildren: Boolean(account.children?.length),
    },
    ...(account.children ? flattenAccounts(account.children, depth + 1) : []),
  ])
}

const matchesFilter = (account: Account, filter: TypeFilter) => {
  if (filter === 'all') {
    return true
  }

  if (filter === 'expense') {
    return account.type === 'expense' || account.type === 'cost_of_sales'
  }

  return account.type === filter
}

const matchesQuery = (account: Account, query: string) => {
  if (!query) {
    return true
  }

  const searchable = `${account.code} ${account.name} ${account.type} ${account.description || ''}`.toLowerCase()
  return searchable.includes(query.toLowerCase())
}

const filterTree = (accounts: Account[], filter: TypeFilter, query: string): Account[] => {
  const result: Account[] = []

  accounts.forEach((account) => {
    const children = account.children ? filterTree(account.children, filter, query) : []
    const isSelfMatch = matchesFilter(account, filter) && matchesQuery(account, query)

    if (isSelfMatch || children.length > 0) {
      result.push({
        ...account,
        children: children.length > 0 ? children : undefined,
      })
    }
  })

  return result
}

const filteredTree = computed(() => filterTree(accountTree.value, filter.value, searchQuery.value.trim()))

const visibleRows = computed(() => {
  const rows: AccountRow[] = []

  const visit = (accounts: Account[], depth = 0) => {
    accounts.forEach((account) => {
      const hasChildren = Boolean(account.children?.length)
      rows.push({
        account,
        depth,
        displayBalance: account.children?.length
          ? account.children.reduce((sum, child) => sum + child.currentPeriodBalance, 0)
          : account.currentPeriodBalance,
        hasChildren,
      })

      if (hasChildren && expandedIds.value.includes(account.id)) {
        visit(account.children || [], depth + 1)
      }
    })
  }

  visit(filteredTree.value)
  return rows
})

const flattenedAccounts = computed(() => flattenAccounts(accountTree.value))

const typeLabel = (type: AccountType) => {
  return t(`accounting.common.types.${type}`) || type
}

const filterTabs = computed(() => [
  { label: t('accounting.chartOfAccounts.tabs.all'), value: 'all' },
  { label: t('accounting.chartOfAccounts.tabs.assets'), value: 'asset' },
  { label: t('accounting.chartOfAccounts.tabs.liabilities'), value: 'liability' },
  { label: t('accounting.chartOfAccounts.tabs.equity'), value: 'equity' },
  { label: t('accounting.chartOfAccounts.tabs.revenue'), value: 'revenue' },
  { label: t('accounting.chartOfAccounts.tabs.expenses'), value: 'expense' },
])

const accountTypeOptions = computed(() => [
  { label: t('accounting.common.types.asset'), value: 'asset' as AccountType },
  { label: t('accounting.common.types.liability'), value: 'liability' as AccountType },
  { label: t('accounting.common.types.equity'), value: 'equity' as AccountType },
  { label: t('accounting.common.types.revenue'), value: 'revenue' as AccountType },
  { label: t('accounting.common.types.expense'), value: 'expense' as AccountType },
  { label: t('accounting.common.types.cost_of_sales'), value: 'cost_of_sales' as AccountType },
])

const toggleExpanded = (accountId: string) => {
  if (expandedIds.value.includes(accountId)) {
    expandedIds.value = expandedIds.value.filter((id) => id !== accountId)
    return
  }

  expandedIds.value = [...expandedIds.value, accountId]
}

const openCreateAccount = () => {
  selectedAccount.value = null
  isAccountModalOpen.value = true
}

const openEditAccount = (account: Account) => {
  selectedAccount.value = account
  isAccountModalOpen.value = true
}

const handleSaveAccount = async (payload: Partial<Account>) => {
  const isEditing = Boolean(payload.id)
  const apiPayload = {
    name: payload.name,
    code: payload.code,
    account_type_id: mapTypeToId(payload.type || 'asset'),
    parent_id: payload.parentId || null,
    description: payload.description || null,
    currency_code: payload.currencyCode || 'USD',
    is_active: payload.isActive ?? true,
  }

  try {
    if (isEditing && payload.id) {
      await accountingApi.updateAccount(payload.id, apiPayload)
    } else {
      await accountingApi.createAccount(apiPayload)
    }
    await loadAccounts()
  } catch (err) {
    console.error('Failed to save account:', err)
  }
}

const handleDeleteAccount = async (id: string) => {
  if (confirm(t('accounting.chartOfAccounts.modal.deleteConfirm'))) {
    try {
      await accountingApi.deleteAccount(id)
      await loadAccounts()
    } catch (err) {
      console.error('Failed to delete account:', err)
    }
  }
}

</script>

<template>
  <div class="space-y-6">
    <AccountingNav />

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-xl sm:text-2xl font-bold text-slate-900">{{ t('accounting.chartOfAccounts.title') }}</h1>
        <p class="text-xs sm:text-sm text-slate-500">{{ t('accounting.chartOfAccounts.subtitle') }}</p>
      </div>
      <div class="flex items-center gap-2">
        <UiButton class="w-full sm:w-auto justify-center" @click="openCreateAccount">
          <Plus class="mr-2 h-4 w-4 shrink-0" /> {{ t('accounting.chartOfAccounts.newAccount') }}
        </UiButton>
      </div>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-4 sm:p-5 shadow-sm space-y-4">
      <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
        <div class="relative flex-1">
          <UiInput v-model="searchQuery" :placeholder="t('accounting.chartOfAccounts.searchPlaceholder')" />
          <Search class="pointer-events-none absolute right-3 top-3 h-4 w-4 text-slate-400" />
        </div>
        <div class="flex items-center gap-2 overflow-x-auto pb-1 lg:pb-0 scrollbar-none shrink-0">
          <Filter class="h-4 w-4 text-slate-400 shrink-0 hidden sm:block" />
          <button
            v-for="tab in filterTabs"
            :key="tab.value"
            class="whitespace-nowrap rounded-full border px-3.5 py-1.5 sm:px-4 sm:py-2 text-xs sm:text-sm font-medium transition-colors shrink-0"
            :class="filter === tab.value ? 'border-primary-600 bg-primary-50 text-primary-700 font-semibold' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
            @click="filter = tab.value as TypeFilter"
          >
            {{ tab.label }}
          </button>
        </div>
      </div>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
      <div class="overflow-x-auto">
        <table class="min-w-full min-w-[650px] divide-y divide-slate-200">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 whitespace-nowrap">{{ t('accounting.chartOfAccounts.table.code') }}</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 min-w-[200px]">{{ t('accounting.chartOfAccounts.table.name') }}</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 whitespace-nowrap">{{ t('accounting.chartOfAccounts.table.type') }}</th>
              <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500 whitespace-nowrap">{{ t('accounting.chartOfAccounts.table.balance') }}</th>
              <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500 whitespace-nowrap">{{ t('accounting.chartOfAccounts.table.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            <tr v-for="row in visibleRows" :key="row.account.id" class="hover:bg-slate-50">
              <td class="px-4 py-3 whitespace-nowrap">
                <div class="flex items-center gap-2" :style="{ paddingLeft: `${row.depth * 20}px` }">
                  <button
                    v-if="row.hasChildren"
                    type="button"
                    class="rounded-full border border-slate-200 p-1 text-slate-500 hover:bg-slate-100"
                    @click="toggleExpanded(row.account.id)"
                  >
                    <ChevronDown v-if="expandedIds.includes(row.account.id)" class="h-3.5 w-3.5" />
                    <ChevronRight v-else class="h-3.5 w-3.5" />
                  </button>
                  <span class="font-medium text-slate-900">{{ row.account.code }}</span>
                </div>
              </td>
              <td class="px-4 py-3">
                <div class="font-medium text-slate-900">{{ row.account.name }}</div>
                <div v-if="row.account.description" class="text-xs text-slate-500 line-clamp-1 sm:line-clamp-none">{{ row.account.description }}</div>
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
                <UiBadge variant="info">{{ typeLabel(row.account.type) }}</UiBadge>
              </td>
              <td class="px-4 py-3 text-right font-semibold text-slate-900 whitespace-nowrap">
                {{ formatCurrency(row.displayBalance) }}
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
                <div class="flex justify-end gap-1 sm:gap-2">
                  <UiButton variant="ghost" size="sm" @click="openEditAccount(row.account)">
                    <Pencil class="h-4 w-4" />
                  </UiButton>
                  <UiButton variant="ghost" size="sm" @click="handleDeleteAccount(row.account.id)">
                    <Trash2 class="h-4 w-4" />
                  </UiButton>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <AccountModal
      v-model="isAccountModalOpen"
      :account="selectedAccount"
      :account-types="accountTypeOptions"
      :parent-options="flattenedAccounts.map((entry) => entry.account)"
      @save="handleSaveAccount"
    />

  </div>
</template>
