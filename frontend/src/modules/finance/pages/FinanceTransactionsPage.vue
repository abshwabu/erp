<script setup lang="ts">
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import {
  Plus,
  Search,
  TrendingUp,
  TrendingDown,
  DollarSign,
  Tag,
  Download,
  Filter,
  X,
  RefreshCw,
  Scale,
  Calendar,
  CreditCard,
  CheckCircle2,
  Clock,
  Ban,
  Eye,
  Edit2,
  Trash2,
  FileSpreadsheet,
  FileText,
  ArrowRightLeft,
  ChevronRight,
  Layers,
} from '@lucide/vue'
import AccountingNav from '@/modules/accounting/components/AccountingNav.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiTable from '@/components/ui/UiTable.vue'
import UiPagination from '@/components/ui/UiPagination.vue'
import UiModal from '@/components/ui/UiModal.vue'
import TransactionModal from '../components/TransactionModal.vue'
import TransactionDetailModal from '../components/TransactionDetailModal.vue'
import CategoryModal from '../components/CategoryModal.vue'
import { financeApi } from '@/api/finance'
import { formatCurrency } from '@/utils/format'
import { useToast } from '@/composables/useToast'
import type {
  FinanceCategory,
  FinanceTransaction,
  FinanceSummary,
  SelectableAccounts,
  TransactionType,
} from '@/types/finance'

const { t } = useI18n()
const queryClient = useQueryClient()
const toast = useToast()

const paymentMethodLabel = (method?: string) => {
  if (!method) return t('finance.common.paymentMethods.cash')
  return t(`finance.common.paymentMethods.${method}`) || method.replace('_', ' ')
}

const statusLabel = (status?: string) => {
  if (!status) return ''
  return t(`finance.common.statuses.${status}`) || status
}

const typeLabel = (type?: string) => {
  if (!type) return ''
  return t(`finance.common.${type}`) || type
}

const page = ref(1)
const activeTypeTab = ref<'all' | 'income' | 'expense'>('all')

const filters = reactive({
  search: '',
  category_id: '',
  status: '',
  payment_method: '',
  start_date: '',
  end_date: '',
})

const debouncedSearch = ref('')
let debounceTimer: any = null

watch(
  () => filters.search,
  (val) => {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => {
      debouncedSearch.value = val || ''
      page.value = 1
    }, 250)
  }
)

watch(
  () => [activeTypeTab.value, filters.category_id, filters.status, filters.payment_method, filters.start_date, filters.end_date],
  () => {
    page.value = 1
  }
)

// Summary KPI Query
const { data: summaryData, refetch: refetchSummary } = useQuery<FinanceSummary>({
  queryKey: ['finance', 'summary', filters.start_date, filters.end_date],
  queryFn: async () => {
    const res = await financeApi.getSummary({
      start_date: filters.start_date || undefined,
      end_date: filters.end_date || undefined,
    })
    return res.data
  },
})

// Categories Query
const { data: categoriesData, refetch: refetchCategories } = useQuery<FinanceCategory[]>({
  queryKey: ['finance', 'categories'],
  queryFn: async () => {
    const res = await financeApi.getCategories()
    return Array.isArray(res.data) ? res.data : []
  },
  initialData: [],
})

// Selectable Accounts Query
const { data: accountsData } = useQuery<SelectableAccounts>({
  queryKey: ['finance', 'accounts'],
  queryFn: async () => {
    const res = await financeApi.getSelectableAccounts()
    return res.data
  },
})

// Transactions Query
const { data: transactionsData, isLoading, isFetching, refetch: refetchTransactions } = useQuery({
  queryKey: computed(() => [
    'finance',
    'transactions',
    page.value,
    activeTypeTab.value,
    debouncedSearch.value,
    filters.category_id,
    filters.status,
    filters.payment_method,
    filters.start_date,
    filters.end_date,
  ]),
  queryFn: async () => {
    const params: any = {
      page: page.value,
      per_page: 15,
      search: debouncedSearch.value || undefined,
      category_id: filters.category_id || undefined,
      status: filters.status || undefined,
      payment_method: filters.payment_method || undefined,
      start_date: filters.start_date || undefined,
      end_date: filters.end_date || undefined,
    }
    if (activeTypeTab.value !== 'all') {
      params.type = activeTypeTab.value
    }

    const res = await financeApi.getTransactions(params)
    return res.data
  },
})

const transactionsList = computed<FinanceTransaction[]>(() => {
  const d = transactionsData.value
  if (Array.isArray(d?.data)) return d.data
  if (Array.isArray(d)) return d
  return []
})

const meta = computed(() => {
  const d = transactionsData.value
  return {
    current_page: d?.current_page || 1,
    last_page: d?.last_page || 1,
    total: d?.total || transactionsList.value.length,
    from: d?.from || 0,
    to: d?.to || 0,
  }
})

// Modal states
const isTransactionModalOpen = ref(false)
const isDetailModalOpen = ref(false)
const isCategoryModalOpen = ref(false)
const isExportModalOpen = ref(false)
const isDeleteModalOpen = ref(false)

const modalDefaultType = ref<TransactionType>('expense')
const activeTransaction = ref<FinanceTransaction | null>(null)
const transactionToDelete = ref<FinanceTransaction | null>(null)

const openCreateTransaction = (type: TransactionType) => {
  activeTransaction.value = null
  modalDefaultType.value = type
  isTransactionModalOpen.value = true
}

const openEditTransaction = (item: FinanceTransaction) => {
  activeTransaction.value = item
  modalDefaultType.value = item.type
  isDetailModalOpen.value = false
  isTransactionModalOpen.value = true
}

const openViewTransaction = (item: FinanceTransaction) => {
  activeTransaction.value = item
  isDetailModalOpen.value = true
}

const openDeleteConfirm = (item: FinanceTransaction) => {
  transactionToDelete.value = item
  isDeleteModalOpen.value = true
}

const confirmDelete = async () => {
  if (!transactionToDelete.value) return
  try {
    await financeApi.deleteTransaction(transactionToDelete.value.id)
    toast.success('Transaction deleted and accounting journal reversed')
    isDeleteModalOpen.value = false
    transactionToDelete.value = null
    refetchAll()
  } catch (err: any) {
    console.error('Delete failed', err)
    toast.error(err?.response?.data?.message || 'Failed to delete transaction')
  }
}

const refetchAll = () => {
  refetchTransactions()
  refetchSummary()
  refetchCategories()
}

// Reset filters
const resetFilters = () => {
  filters.search = ''
  debouncedSearch.value = ''
  filters.category_id = ''
  filters.status = ''
  filters.payment_method = ''
  filters.start_date = ''
  filters.end_date = ''
  activeTypeTab.value = 'all'
  page.value = 1
}

const hasActiveFilters = computed(() => {
  return (
    !!filters.search ||
    !!debouncedSearch.value ||
    !!filters.category_id ||
    !!filters.status ||
    !!filters.payment_method ||
    !!filters.start_date ||
    !!filters.end_date ||
    activeTypeTab.value !== 'all'
  )
})

// Export handler
const isExporting = ref<'xlsx' | 'csv' | 'pdf' | null>(null)
const handleExport = async (format: 'xlsx' | 'csv' | 'pdf') => {
  try {
    isExporting.value = format
    const payload: any = {
      format,
      type: activeTypeTab.value !== 'all' ? activeTypeTab.value : undefined,
      category_id: filters.category_id || undefined,
      status: filters.status || undefined,
      start_date: filters.start_date || undefined,
      end_date: filters.end_date || undefined,
    }

    const res = await financeApi.exportTransactions(payload)
    const mimeMap = {
      xlsx: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      csv: 'text/csv;charset=utf-8;',
      pdf: 'application/pdf',
    }
    const blob = new Blob([res.data], { type: mimeMap[format] })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `finance_transactions_${new Date().toISOString().slice(0, 10)}.${format}`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)

    toast.success(`Export downloaded successfully (.${format})`)
    isExportModalOpen.value = false
  } catch (err: any) {
    console.error('Export failed', err)
    toast.error('Failed to export transactions')
  } finally {
    isExporting.value = null
  }
}

// Sync POS & Invoices handler
const isSyncing = ref(false)
const handleSyncSales = async () => {
  try {
    isSyncing.value = true
    const res = await financeApi.syncSales()
    toast.success(res.data?.message || 'Sales and invoices synced successfully')
    refetchAll()
  } catch (err: any) {
    console.error('Sync failed', err)
    toast.error(err?.response?.data?.message || 'Failed to sync sales')
  } finally {
    isSyncing.value = false
  }
}
</script>

<template>
  <div class="space-y-6 pb-12 font-sans max-w-7xl mx-auto px-4 sm:px-6">
    <!-- Top Navigation Bar for Accounting Module -->
    <AccountingNav />

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="flex items-center space-x-2">
          <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
            {{ t('finance.transactionsPage.title') }}
          </h1>
          <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-100">
            {{ t('finance.transactionsPage.recordsCount', { count: meta.total }) }}
          </span>
        </div>
        <p class="text-slate-500 text-xs sm:text-sm mt-1">
          {{ t('finance.transactionsPage.subtitle') }}
        </p>
      </div>

      <!-- Action Buttons -->
      <div class="flex flex-wrap items-center gap-2">
        <button
          type="button"
          :disabled="isSyncing"
          @click="handleSyncSales"
          class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs sm:text-sm font-semibold shadow-2xs transition-all disabled:opacity-50"
          :title="t('finance.transactionsPage.syncTooltip')"
        >
          <RefreshCw class="h-4 w-4 text-emerald-600" :class="{ 'animate-spin': isSyncing }" />
          <span>{{ isSyncing ? t('finance.transactionsPage.syncingBtn') : t('finance.transactionsPage.syncSalesBtn') }}</span>
        </button>

        <button
          type="button"
          @click="isCategoryModalOpen = true"
          class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs sm:text-sm font-semibold shadow-2xs transition-all"
        >
          <Tag class="h-4 w-4 text-slate-400" />
          <span>{{ t('finance.transactionsPage.categoriesBtn') }}</span>
        </button>

        <button
          type="button"
          @click="isExportModalOpen = true"
          class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs sm:text-sm font-semibold shadow-2xs transition-all"
        >
          <Download class="h-4 w-4 text-blue-600" />
          <span>{{ t('finance.transactionsPage.exportBtn') }}</span>
        </button>

        <!-- Record Income Button -->
        <button
          type="button"
          @click="openCreateTransaction('income')"
          class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs sm:text-sm font-bold shadow-md shadow-emerald-600/30 transition-all transform hover:-translate-y-0.5 active:translate-y-0"
        >
          <Plus class="h-4 w-4" />
          <span>{{ t('finance.transactionsPage.addIncomeBtn') }}</span>
        </button>

        <!-- Record Expense Button -->
        <button
          type="button"
          @click="openCreateTransaction('expense')"
          class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs sm:text-sm font-bold shadow-md shadow-rose-600/30 transition-all transform hover:-translate-y-0.5 active:translate-y-0"
        >
          <Plus class="h-4 w-4" />
          <span>{{ t('finance.transactionsPage.addExpenseBtn') }}</span>
        </button>
      </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
      <!-- Total Income -->
      <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center justify-between">
        <div>
          <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ t('finance.transactionsPage.kpis.totalIncome') }}</p>
          <h3 class="text-xl sm:text-2xl font-black text-emerald-600 mt-1">
            {{ formatCurrency(summaryData?.total_income || 0) }}
          </h3>
          <p class="text-[11px] text-slate-400 mt-0.5">
            {{ t('finance.transactionsPage.kpis.receivedPayments', { count: summaryData?.income_count || 0 }) }}
          </p>
        </div>
        <div class="p-2.5 bg-emerald-50 text-emerald-600 rounded-xl">
          <TrendingUp class="w-5 h-5" />
        </div>
      </div>

      <!-- Total Expenses -->
      <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center justify-between">
        <div>
          <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ t('finance.transactionsPage.kpis.totalExpenses') }}</p>
          <h3 class="text-xl sm:text-2xl font-black text-rose-600 mt-1">
            {{ formatCurrency(summaryData?.total_expense || 0) }}
          </h3>
          <p class="text-[11px] text-slate-400 mt-0.5">
            {{ t('finance.transactionsPage.kpis.expenditures', { count: summaryData?.expense_count || 0 }) }}
          </p>
        </div>
        <div class="p-2.5 bg-rose-50 text-rose-600 rounded-xl">
          <TrendingDown class="w-5 h-5" />
        </div>
      </div>

      <!-- Net Cash Flow / Profit -->
      <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center justify-between">
        <div>
          <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ t('finance.transactionsPage.kpis.netBalance') }}</p>
          <h3
            class="text-xl sm:text-2xl font-black mt-1"
            :class="(summaryData?.net || 0) >= 0 ? 'text-blue-600' : 'text-amber-600'"
          >
            {{ formatCurrency(summaryData?.net || 0) }}
          </h3>
          <p class="text-[11px] text-slate-400 mt-0.5">
            {{ (summaryData?.net || 0) >= 0 ? t('finance.transactionsPage.kpis.cashFlowSurplus') : t('finance.transactionsPage.kpis.cashFlowDeficit') }}
          </p>
        </div>
        <div class="p-2.5 bg-blue-50 text-blue-600 rounded-xl">
          <Scale class="w-5 h-5" />
        </div>
      </div>

      <!-- Accounting Ledger Sync -->
      <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center justify-between">
        <div>
          <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ t('finance.transactionsPage.kpis.accountingLedger') }}</p>
          <h3 class="text-xl sm:text-2xl font-black text-slate-900 mt-1">
            {{ t('finance.transactionsPage.kpis.syncedPercent') }}
          </h3>
          <p class="text-[11px] text-slate-400 mt-0.5">
            {{ t('finance.transactionsPage.kpis.realtimeDoubleEntry') }}
          </p>
        </div>
        <div class="p-2.5 bg-primary-50 text-primary-600 rounded-xl">
          <CheckCircle2 class="w-5 h-5" />
        </div>
      </div>
    </div>

    <!-- Type Switcher & Filter Toolbar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs space-y-3">
      <!-- Type Tabs -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
        <div class="inline-flex p-1 bg-slate-100 rounded-xl">
          <button
            type="button"
            @click="activeTypeTab = 'all'"
            class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all"
            :class="activeTypeTab === 'all' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
          >
            {{ t('finance.common.allTransactions') }}
          </button>
          <button
            type="button"
            @click="activeTypeTab = 'income'"
            class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5"
            :class="activeTypeTab === 'income' ? 'bg-emerald-600 text-white shadow-2xs' : 'text-emerald-700 hover:bg-emerald-50'"
          >
            <TrendingUp class="w-3.5 h-3.5" />
            <span>{{ t('finance.common.incomeOnly') }}</span>
          </button>
          <button
            type="button"
            @click="activeTypeTab = 'expense'"
            class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5"
            :class="activeTypeTab === 'expense' ? 'bg-rose-600 text-white shadow-2xs' : 'text-rose-700 hover:bg-rose-50'"
          >
            <TrendingDown class="w-3.5 h-3.5" />
            <span>{{ t('finance.common.expensesOnly') }}</span>
          </button>
        </div>

        <button
          v-if="hasActiveFilters"
          type="button"
          @click="resetFilters"
          class="inline-flex items-center gap-1 text-xs text-rose-600 hover:text-rose-700 font-semibold"
        >
          <X class="w-3.5 h-3.5" />
          <span>{{ t('finance.common.resetFilters') }}</span>
        </button>
      </div>

      <!-- Filter Controls Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
        <!-- Search -->
        <div class="relative">
          <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
            <Search class="h-4 w-4" />
          </span>
          <input
            type="text"
            v-model="filters.search"
            :placeholder="t('finance.common.searchPlaceholder')"
            class="w-full pl-9 pr-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <!-- Category -->
        <div>
          <select
            v-model="filters.category_id"
            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">{{ t('finance.common.allCategories') }}</option>
            <option
              v-for="cat in categoriesData"
              :key="cat.id"
              :value="cat.id"
            >
              {{ cat.name }} ({{ typeLabel(cat.type) }})
            </option>
          </select>
        </div>

        <!-- Payment Method -->
        <div>
          <select
            v-model="filters.payment_method"
            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">{{ t('finance.common.allPaymentMethods') }}</option>
            <option value="cash">{{ t('finance.common.paymentMethods.cash') }}</option>
            <option value="bank_transfer">{{ t('finance.common.paymentMethods.bank_transfer') }}</option>
            <option value="check">{{ t('finance.common.paymentMethods.check') }}</option>
            <option value="credit_card">{{ t('finance.common.paymentMethods.credit_card') }}</option>
            <option value="mobile_money">{{ t('finance.common.paymentMethods.mobile_money') }}</option>
          </select>
        </div>

        <!-- Status -->
        <div>
          <select
            v-model="filters.status"
            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">{{ t('finance.common.allStatuses') }}</option>
            <option value="completed">{{ t('finance.common.statuses.completed') }}</option>
            <option value="pending">{{ t('finance.common.statuses.pending') }}</option>
            <option value="cancelled">{{ t('finance.common.statuses.cancelled') }}</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Transactions Data Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs divide-y divide-slate-200">
          <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider">
            <tr>
              <th class="px-4 py-3">{{ t('finance.transactionsPage.table.txnNumber') }}</th>
              <th class="px-4 py-3">{{ t('finance.transactionsPage.table.date') }}</th>
              <th class="px-4 py-3">{{ t('finance.transactionsPage.table.payeePayerMemo') }}</th>
              <th class="px-4 py-3">{{ t('finance.transactionsPage.table.category') }}</th>
              <th class="px-4 py-3">{{ t('finance.transactionsPage.table.method') }}</th>
              <th class="px-4 py-3 text-right">{{ t('finance.transactionsPage.table.amount') }}</th>
              <th class="px-4 py-3 text-center">{{ t('finance.transactionsPage.table.ledgerEntry') }}</th>
              <th class="px-4 py-3 text-right">{{ t('finance.transactionsPage.table.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <template v-if="isLoading">
              <tr v-for="i in 5" :key="i" class="animate-pulse">
                <td colspan="8" class="px-4 py-4">
                  <div class="h-4 bg-slate-100 rounded-md w-full"></div>
                </td>
              </tr>
            </template>

            <template v-else-if="transactionsList.length > 0">
              <tr
                v-for="item in transactionsList"
                :key="item.id"
                class="hover:bg-slate-50/70 transition-colors"
              >
                <!-- Txn Number -->
                <td class="px-4 py-3.5 whitespace-nowrap">
                  <span class="font-mono font-bold text-slate-900 block">
                    {{ item.transaction_number }}
                  </span>
                  <div class="flex flex-wrap items-center gap-1 mt-0.5">
                    <span
                      class="inline-block px-1.5 py-0.2 rounded text-[10px] font-extrabold uppercase"
                      :class="[
                        item.type === 'income'
                          ? 'bg-emerald-50 text-emerald-700'
                          : 'bg-rose-50 text-rose-700',
                      ]"
                    >
                      {{ typeLabel(item.type) }}
                    </span>
                    <span
                      v-if="item.source_type === 'pos_transaction' || (item.transaction_number && item.transaction_number.startsWith('POS-'))"
                      class="inline-block px-1.5 py-0.2 rounded text-[9px] font-bold uppercase bg-indigo-50 text-indigo-700 border border-indigo-100"
                    >
                      {{ t('finance.common.posSale') }}
                    </span>
                    <span
                      v-else-if="item.source_type === 'sales_payment' || (item.transaction_number && item.transaction_number.startsWith('PAY-'))"
                      class="inline-block px-1.5 py-0.2 rounded text-[9px] font-bold uppercase bg-sky-50 text-sky-700 border border-sky-100"
                    >
                      {{ t('finance.common.invoicePayment') }}
                    </span>
                  </div>
                </td>

                <!-- Date -->
                <td class="px-4 py-3.5 whitespace-nowrap font-medium text-slate-600">
                  {{ item.transaction_date }}
                </td>

                <!-- Payee / Payer & Memo -->
                <td class="px-4 py-3.5 min-w-[200px]">
                  <p class="font-bold text-slate-900 truncate">
                    {{ item.payee_payer || (item.type === 'income' ? t('finance.common.directRevenue') : t('finance.common.generalExpenditure')) }}
                  </p>
                  <p v-if="item.description" class="text-slate-400 text-[11px] truncate mt-0.5">
                    {{ item.description }}
                  </p>
                  <p v-if="item.reference" class="text-slate-400 text-[10px] font-mono mt-0.5">
                    {{ t('finance.transactionsPage.table.ref') }}: {{ item.reference }}
                  </p>
                </td>

                <!-- Category -->
                <td class="px-4 py-3.5 whitespace-nowrap">
                  <span
                    v-if="item.category"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold text-slate-800 bg-slate-100"
                  >
                    <span
                      class="w-2 h-2 rounded-full"
                      :style="{ backgroundColor: item.category.color || '#3B82F6' }"
                    />
                    {{ item.category.name }}
                  </span>
                  <span v-else class="text-slate-400 italic">{{ t('finance.common.uncategorized') }}</span>
                </td>

                <!-- Method -->
                <td class="px-4 py-3.5 whitespace-nowrap text-slate-600 capitalize">
                  {{ paymentMethodLabel(item.payment_method) }}
                </td>

                <!-- Amount -->
                <td class="px-4 py-3.5 whitespace-nowrap text-right font-mono font-black text-sm">
                  <span :class="item.type === 'income' ? 'text-emerald-600' : 'text-rose-600'">
                    {{ item.type === 'income' ? '+' : '-' }}{{ formatCurrency(item.amount) }}
                  </span>
                </td>

                <!-- Accounting Ledger Badge -->
                <td class="px-4 py-3.5 whitespace-nowrap text-center">
                  <button
                    type="button"
                    v-if="item.journal_id"
                    @click="openViewTransaction(item)"
                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200/60 hover:bg-blue-100 transition-colors"
                    title="View Double-Entry Ledger Entry"
                  >
                    <Scale class="w-3 h-3 text-blue-600" />
                    <span>{{ t('finance.common.postedDrCr') }}</span>
                  </button>
                  <span
                    v-else
                    class="text-[11px] text-slate-400 italic"
                  >
                    {{ t('finance.common.pendingLedger') }}
                  </span>
                </td>

                <!-- Actions -->
                <td class="px-4 py-3.5 whitespace-nowrap text-right">
                  <div class="flex items-center justify-end gap-1">
                    <button
                      type="button"
                      @click="openViewTransaction(item)"
                      class="p-1.5 text-slate-400 hover:text-blue-600 rounded-lg hover:bg-slate-100 transition-colors"
                      title="View Details & Ledger Lines"
                    >
                      <Eye class="w-4 h-4" />
                    </button>
                    <button
                      type="button"
                      @click="openEditTransaction(item)"
                      class="p-1.5 text-slate-400 hover:text-slate-800 rounded-lg hover:bg-slate-100 transition-colors"
                      title="Edit"
                    >
                      <Edit2 class="w-4 h-4" />
                    </button>
                    <button
                      type="button"
                      @click="openDeleteConfirm(item)"
                      class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-slate-100 transition-colors"
                      title="Delete"
                    >
                      <Trash2 class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
            </template>

            <!-- Empty State -->
            <tr v-else>
              <td colspan="8" class="text-center py-12 text-slate-400">
                <div class="max-w-xs mx-auto space-y-3">
                  <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto text-slate-400">
                    <ArrowRightLeft class="w-6 h-6" />
                  </div>
                  <h4 class="text-sm font-bold text-slate-700">{{ t('finance.transactionsPage.empty.title') }}</h4>
                  <p class="text-xs text-slate-400 leading-relaxed">
                    {{ t('finance.transactionsPage.empty.description') }}
                  </p>
                  <div class="flex items-center justify-center gap-2 pt-1">
                    <button
                      type="button"
                      @click="openCreateTransaction('income')"
                      class="px-3 py-1.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-lg text-xs font-bold"
                    >
                      {{ t('finance.transactionsPage.empty.addIncome') }}
                    </button>
                    <button
                      type="button"
                      @click="openCreateTransaction('expense')"
                      class="px-3 py-1.5 bg-rose-50 text-rose-700 hover:bg-rose-100 rounded-lg text-xs font-bold"
                    >
                      {{ t('finance.transactionsPage.empty.addExpense') }}
                    </button>
                  </div>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="meta.total > 0" class="p-3 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-slate-500">
        <p>
          {{ t('finance.transactionsPage.pagination', { from: meta.from, to: meta.to, total: meta.total }) }}
        </p>
        <UiPagination
          :current-page="page"
          :total-pages="meta.last_page"
          :has-next-page="page < meta.last_page"
          :has-prev-page="page > 1"
          @update:current-page="page = $event"
        />
      </div>
    </div>

    <!-- Modals -->
    <!-- 1. Add / Edit Transaction Modal -->
    <TransactionModal
      v-model="isTransactionModalOpen"
      :transaction="activeTransaction"
      :default-type="modalDefaultType"
      :categories="categoriesData"
      :accounts="accountsData"
      @saved="refetchAll"
    />

    <!-- 2. Transaction Details Modal with Accounting Integration -->
    <TransactionDetailModal
      v-model="isDetailModalOpen"
      :transaction="activeTransaction"
      @edit="openEditTransaction"
    />

    <!-- 3. Category Management Modal -->
    <CategoryModal
      v-model="isCategoryModalOpen"
      :categories="categoriesData"
      :accounts="accountsData"
      @saved="refetchCategories"
    />

    <!-- 4. Export Modal -->
    <UiModal v-model="isExportModalOpen" :title="t('finance.transactionsPage.exportModal.title')" size="md">
      <div class="space-y-4 text-slate-800">
        <p class="text-xs text-slate-500 leading-relaxed">
          {{ t('finance.transactionsPage.exportModal.description') }}
        </p>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <button
            type="button"
            :disabled="!!isExporting"
            @click="handleExport('xlsx')"
            class="flex flex-col items-center justify-center p-4 rounded-2xl border-2 border-slate-200 hover:border-emerald-500 hover:bg-emerald-50/50 transition-all text-center"
          >
            <FileSpreadsheet class="w-8 h-8 text-emerald-600 mb-2" />
            <span class="font-bold text-xs text-slate-900">{{ t('finance.transactionsPage.exportModal.excel') }}</span>
            <span class="text-[10px] text-slate-400 mt-0.5">{{ t('finance.transactionsPage.exportModal.excelSubtitle') }}</span>
          </button>

          <button
            type="button"
            :disabled="!!isExporting"
            @click="handleExport('csv')"
            class="flex flex-col items-center justify-center p-4 rounded-2xl border-2 border-slate-200 hover:border-blue-500 hover:bg-blue-50/50 transition-all text-center"
          >
            <FileText class="w-8 h-8 text-blue-600 mb-2" />
            <span class="font-bold text-xs text-slate-900">{{ t('finance.transactionsPage.exportModal.csv') }}</span>
            <span class="text-[10px] text-slate-400 mt-0.5">{{ t('finance.transactionsPage.exportModal.csvSubtitle') }}</span>
          </button>

          <button
            type="button"
            :disabled="!!isExporting"
            @click="handleExport('pdf')"
            class="flex flex-col items-center justify-center p-4 rounded-2xl border-2 border-slate-200 hover:border-rose-500 hover:bg-rose-50/50 transition-all text-center"
          >
            <FileText class="w-8 h-8 text-rose-600 mb-2" />
            <span class="font-bold text-xs text-slate-900">{{ t('finance.transactionsPage.exportModal.pdf') }}</span>
            <span class="text-[10px] text-slate-400 mt-0.5">{{ t('finance.transactionsPage.exportModal.pdfSubtitle') }}</span>
          </button>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end w-full">
          <UiButton
            type="button"
            variant="secondary"
            @click="isExportModalOpen = false"
            class="text-xs"
          >
            {{ t('finance.transactionsPage.exportModal.cancel') }}
          </UiButton>
        </div>
      </template>
    </UiModal>

    <!-- 5. Delete Confirmation Modal -->
    <UiModal v-model="isDeleteModalOpen" :title="t('finance.transactionsPage.deleteModal.title')" size="sm">
      <div v-if="transactionToDelete" class="space-y-4">
        <div class="flex items-start gap-3.5 p-3.5 bg-rose-50 border border-rose-200 rounded-2xl">
          <div class="p-2 bg-rose-100 text-rose-600 rounded-xl shrink-0 mt-0.5">
            <Trash2 class="w-5 h-5" />
          </div>
          <div class="space-y-1">
            <h4 class="text-sm font-bold text-rose-950">{{ t('finance.transactionsPage.deleteModal.confirmHeader') }}</h4>
            <p class="text-xs text-rose-800 leading-relaxed">
              {{ t('finance.transactionsPage.deleteModal.confirmText', { txn: transactionToDelete.transaction_number, amount: formatCurrency(transactionToDelete.amount) }) }}
            </p>
          </div>
        </div>

        <p class="text-xs text-slate-500 leading-relaxed">
          {{ t('finance.transactionsPage.deleteModal.warning') }}
        </p>

        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
          <UiButton variant="outline" size="sm" type="button" @click="isDeleteModalOpen = false">
            {{ t('finance.transactionsPage.deleteModal.cancel') }}
          </UiButton>
          <UiButton variant="danger" size="sm" type="button" @click="confirmDelete">
            {{ t('finance.transactionsPage.deleteModal.confirmBtn') }}
          </UiButton>
        </div>
      </div>
    </UiModal>
  </div>
</template>
