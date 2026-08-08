<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { accountingApi } from '@/api/accounting'
import { hrApi } from '@/api/hr'
import apiClient from '@/api/client'
import { BarChart2, Package, Calculator, Users } from '@lucide/vue'

const loading = ref(true)
const stockValuation = ref<number | null>(null)
const trialBalanceRows = ref(0)
const employeeCount = ref(0)
const accountCount = ref(0)

onMounted(async () => {
  try {
    const today = new Date().toISOString().slice(0, 10)
    const monthStart = new Date(new Date().getFullYear(), new Date().getMonth(), 1)
      .toISOString()
      .slice(0, 10)

    const results = await Promise.allSettled([
      apiClient.get('/inventory/stock/valuation'),
      accountingApi.getTrialBalance({ from_date: monthStart, to_date: today }),
      accountingApi.getAccounts(),
      hrApi.getEmployees(),
    ])

    if (results[0].status === 'fulfilled') {
      const data = results[0].value.data?.data ?? results[0].value.data
      stockValuation.value = data?.total_valuation ?? null
    }

    if (results[1].status === 'fulfilled') {
      const tb = results[1].value.data as any
      const rows = tb?.data ?? tb?.accounts ?? tb
      trialBalanceRows.value = Array.isArray(rows) ? rows.length : 0
    }

    if (results[2].status === 'fulfilled') {
      const accounts = results[2].value.data as any
      accountCount.value = Array.isArray(accounts) ? accounts.length : (accounts?.data?.length ?? 0)
    }

    if (results[3].status === 'fulfilled') {
      const employees = results[3].value.data as any
      employeeCount.value = Array.isArray(employees) ? employees.length : (employees?.data?.length ?? 0)
    }
  } finally {
    loading.value = false
  }
})

const formatMoney = (cents: number | null) => {
  if (cents == null) return '—'
  return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(cents / 100)
}

const cards = computed(() => [
  {
    title: 'Stock Valuation',
    value: formatMoney(stockValuation.value),
    icon: Package,
    to: '/inventory/stock',
    hint: 'Inventory on-hand value',
  },
  {
    title: 'Trial Balance Lines',
    value: String(trialBalanceRows.value || '—'),
    icon: Calculator,
    to: '/accounting/trial-balance',
    hint: 'Accounting period summary',
  },
  {
    title: 'Chart of Accounts',
    value: String(accountCount.value || '—'),
    icon: BarChart2,
    to: '/accounting/chart-of-accounts',
    hint: 'Active GL accounts',
  },
  {
    title: 'Employees',
    value: String(employeeCount.value || '—'),
    icon: Users,
    to: '/hr/employees',
    hint: 'Workforce headcount',
  },
])
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-slate-900">Reporting</h1>
      <p class="text-slate-500">Key metrics from Inventory, Accounting, and HR.</p>
    </div>

    <div v-if="loading" class="text-slate-500">Loading metrics...</div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
      <RouterLink
        v-for="card in cards"
        :key="card.title"
        :to="card.to"
        class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm hover:border-primary-300 hover:shadow-md transition-all"
      >
        <div class="flex items-start gap-4">
          <div class="p-3 bg-primary-50 rounded-lg text-primary-600">
            <component :is="card.icon" class="h-6 w-6" />
          </div>
          <div>
            <p class="text-sm text-slate-500">{{ card.title }}</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ card.value }}</p>
            <p class="text-xs text-slate-400 mt-2">{{ card.hint }}</p>
          </div>
        </div>
      </RouterLink>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
      <h2 class="font-semibold text-slate-900 mb-2">Quick links</h2>
      <div class="flex flex-wrap gap-3 text-sm">
        <RouterLink class="text-primary-600 hover:underline" to="/accounting/trial-balance">Trial Balance</RouterLink>
        <RouterLink class="text-primary-600 hover:underline" to="/accounting/profit-loss">Profit &amp; Loss</RouterLink>
        <RouterLink class="text-primary-600 hover:underline" to="/accounting/balance-sheet">Balance Sheet</RouterLink>
        <RouterLink class="text-primary-600 hover:underline" to="/inventory/stock">Stock Levels</RouterLink>
        <RouterLink class="text-primary-600 hover:underline" to="/inventory/low-stock">Low Stock</RouterLink>
      </div>
    </div>
  </div>
</template>
