<script setup lang="ts">
import { computed, ref } from 'vue'
import { ChevronDown, ChevronRight, Filter, Plus, Search, Upload, Pencil, Trash2 } from '@lucide/vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import AccountModal from '../components/AccountModal.vue'
import { sampleAccounts } from '../data'
import { formatCurrency } from '@/utils/format'
import type { Account, AccountType } from '@/types/accounting'

type TypeFilter = 'all' | AccountType

interface AccountRow {
  account: Account
  depth: number
  displayBalance: number
  hasChildren: boolean
}

const cloneTree = (accounts: Account[]): Account[] => accounts.map((account) => ({
  ...account,
  children: account.children ? cloneTree(account.children) : undefined,
}))

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

const accountTree = ref<Account[]>(cloneTree(sampleAccounts))
const filter = ref<TypeFilter>('all')
const searchQuery = ref('')
const expandedIds = ref<string[]>(accountTree.value.map((account) => account.id))
const isAccountModalOpen = ref(false)
const selectedAccount = ref<Account | null>(null)
const importFileName = ref('')
const importInput = ref<HTMLInputElement | null>(null)

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
  switch (type) {
    case 'asset':
      return 'Assets'
    case 'liability':
      return 'Liabilities'
    case 'equity':
      return 'Equity'
    case 'revenue':
      return 'Revenue'
    case 'expense':
      return 'Expenses'
    case 'cost_of_sales':
      return 'Cost of Sales'
  }
}

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

const insertAccount = (nodes: Account[], parentId: string | null, nextAccount: Account): Account[] => {
  if (!parentId) {
    return [...nodes, nextAccount]
  }

  return nodes.map((account) => {
    if (account.id === parentId) {
      return {
        ...account,
        children: [...(account.children || []), nextAccount],
      }
    }

    return {
      ...account,
      children: account.children ? insertAccount(account.children, parentId, nextAccount) : account.children,
    }
  })
}

const updateAccount = (nodes: Account[], updated: Account): Account[] => {
  return nodes.map((account) => {
    if (account.id === updated.id) {
      return {
        ...account,
        ...updated,
      }
    }

    return {
      ...account,
      children: account.children ? updateAccount(account.children, updated) : account.children,
    }
  })
}

const handleSaveAccount = (payload: Partial<Account>) => {
  const existing = payload.id ? flattenedAccounts.value.find((account) => account.account.id === payload.id)?.account : null
  const nextAccount: Account = {
    id: payload.id || `acc-${Date.now()}`,
    parentId: payload.parentId ?? null,
    code: payload.code || '',
    name: payload.name || '',
    type: (payload.type as AccountType) || 'asset',
    description: payload.description,
    currencyCode: payload.currencyCode || 'USD',
    isActive: payload.isActive ?? true,
    isSystemAccount: existing?.isSystemAccount ?? false,
    currentPeriodBalance: existing?.currentPeriodBalance ?? 0,
    children: existing?.children,
  }

  if (existing) {
    accountTree.value = updateAccount(accountTree.value, nextAccount)
  } else {
    accountTree.value = insertAccount(accountTree.value, nextAccount.parentId || null, nextAccount)
    if (nextAccount.parentId && !expandedIds.value.includes(nextAccount.parentId)) {
      expandedIds.value = [...expandedIds.value, nextAccount.parentId]
    }
  }
}

const handleImport = (event: Event) => {
  const input = event.target as HTMLInputElement
  importFileName.value = input.files?.[0]?.name || ''
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Chart of Accounts</h1>
        <p class="text-sm text-slate-500">Browse the full account hierarchy and manage account definitions.</p>
      </div>
      <div class="flex flex-wrap gap-3">
        <UiButton variant="outline" @click="importInput?.click()">
          <Upload class="mr-2 h-4 w-4" /> Import CSV
        </UiButton>
        <UiButton @click="openCreateAccount">
          <Plus class="mr-2 h-4 w-4" /> New Account
        </UiButton>
      </div>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
        <div class="relative flex-1">
          <UiInput v-model="searchQuery" placeholder="Search by code, name, or description..." />
          <Search class="pointer-events-none absolute right-3 top-3 h-4 w-4 text-slate-400" />
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <Filter class="h-4 w-4 text-slate-400" />
          <button
            v-for="tab in [
              { label: 'All', value: 'all' },
              { label: 'Assets', value: 'asset' },
              { label: 'Liabilities', value: 'liability' },
              { label: 'Equity', value: 'equity' },
              { label: 'Revenue', value: 'revenue' },
              { label: 'Expenses', value: 'expense' },
            ]"
            :key="tab.label"
            class="rounded-full border px-4 py-2 text-sm font-medium transition-colors"
            :class="filter === tab.value ? 'border-primary-600 bg-primary-50 text-primary-700' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
            @click="filter = tab.value as TypeFilter"
          >
            {{ tab.label }}
          </button>
        </div>
      </div>
      <p v-if="importFileName" class="mt-3 text-sm text-slate-500">Selected file: {{ importFileName }}</p>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Code</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Name</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Type</th>
            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Balance</th>
            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
          <tr v-for="row in visibleRows" :key="row.account.id" class="hover:bg-slate-50">
            <td class="px-4 py-3">
              <div class="flex items-center gap-2" :style="{ paddingLeft: `${row.depth * 24}px` }">
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
              <div v-if="row.account.description" class="text-xs text-slate-500">{{ row.account.description }}</div>
            </td>
            <td class="px-4 py-3">
              <UiBadge variant="info">{{ typeLabel(row.account.type) }}</UiBadge>
            </td>
            <td class="px-4 py-3 text-right font-semibold text-slate-900">
              {{ formatCurrency(row.displayBalance) }}
            </td>
            <td class="px-4 py-3">
              <div class="flex justify-end gap-2">
                <UiButton variant="ghost" size="sm" @click="openEditAccount(row.account)">
                  <Pencil class="h-4 w-4" />
                </UiButton>
                <UiButton variant="ghost" size="sm">
                  <Trash2 class="h-4 w-4" />
                </UiButton>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <AccountModal
      v-model="isAccountModalOpen"
      :account="selectedAccount"
      :account-types="[
        { label: 'Assets', value: 'asset' },
        { label: 'Liabilities', value: 'liability' },
        { label: 'Equity', value: 'equity' },
        { label: 'Revenue', value: 'revenue' },
        { label: 'Expenses', value: 'expense' },
        { label: 'Cost of Sales', value: 'cost_of_sales' },
      ]"
      :parent-options="flattenedAccounts.map((entry) => entry.account)"
      @save="handleSaveAccount"
    />

    <input ref="importInput" type="file" accept=".csv" class="hidden" @change="handleImport" />
  </div>
</template>
