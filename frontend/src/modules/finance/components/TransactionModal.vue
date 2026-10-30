<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  TrendingUp,
  TrendingDown,
  Calendar,
  DollarSign,
  Tag,
  CreditCard,
  User,
  FileText,
  Bookmark,
  ChevronDown,
  ChevronUp,
  Landmark,
  Scale,
  Sparkles,
} from '@lucide/vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import { financeApi } from '@/api/finance'
import { useToast } from '@/composables/useToast'
import type {
  FinanceCategory,
  FinanceTransaction,
  SelectableAccounts,
  TransactionType,
  PaymentMethod,
} from '@/types/finance'

interface Props {
  modelValue: boolean
  transaction?: FinanceTransaction | null
  defaultType?: TransactionType
  categories: FinanceCategory[]
  accounts?: SelectableAccounts | null
}

const props = withDefaults(defineProps<Props>(), {
  transaction: null,
  defaultType: 'expense',
  accounts: null,
})

const emit = defineEmits(['update:modelValue', 'saved'])
const { t } = useI18n()
const toast = useToast()

const isSubmitting = ref(false)
const showAccountingDetails = ref(false)

const form = reactive({
  type: 'expense' as TransactionType,
  amount: '' as string | number,
  transaction_date: new Date().toISOString().slice(0, 10),
  category_id: '' as string,
  payment_method: 'cash' as PaymentMethod,
  payee_payer: '',
  reference: '',
  description: '',
  status: 'completed' as 'completed' | 'pending',
  payment_account_id: '' as string,
  account_id: '' as string,
})

// Initialize form from props
watch(
  () => [props.modelValue, props.transaction, props.defaultType],
  () => {
    if (props.transaction) {
      form.type = props.transaction.type
      form.amount = props.transaction.amount
      form.transaction_date = props.transaction.transaction_date
        ? String(props.transaction.transaction_date).slice(0, 10)
        : new Date().toISOString().slice(0, 10)
      form.category_id = props.transaction.category_id || ''
      form.payment_method = props.transaction.payment_method || 'cash'
      form.payee_payer = props.transaction.payee_payer || ''
      form.reference = props.transaction.reference || ''
      form.description = props.transaction.description || ''
      form.status = (props.transaction.status as any) || 'completed'
      form.payment_account_id = props.transaction.payment_account_id || ''
      form.account_id = props.transaction.account_id || ''
    } else {
      form.type = props.defaultType || 'expense'
      form.amount = ''
      form.transaction_date = new Date().toISOString().slice(0, 10)
      form.category_id = ''
      form.payment_method = 'cash'
      form.payee_payer = ''
      form.reference = ''
      form.description = ''
      form.status = 'completed'
      form.payment_account_id = ''
      form.account_id = ''
    }
  },
  { immediate: true }
)

// Filter categories matching the selected type
const filteredCategories = computed(() => {
  return props.categories.filter(
    (c) => c.type === form.type || c.type === 'both'
  )
})

// Auto-fill account when category is selected
watch(
  () => form.category_id,
  (catId) => {
    if (!catId) return
    const cat = props.categories.find((c) => c.id === catId)
    if (cat?.account_id && !form.account_id) {
      form.account_id = cat.account_id
    }
  }
)

const paymentMethodOptions = computed(() => [
  { label: t('finance.common.paymentMethods.cash'), value: 'cash' },
  { label: t('finance.common.paymentMethods.bank_transfer'), value: 'bank_transfer' },
  { label: t('finance.common.paymentMethods.check'), value: 'check' },
  { label: t('finance.common.paymentMethods.credit_card'), value: 'credit_card' },
  { label: t('finance.common.paymentMethods.mobile_money'), value: 'mobile_money' },
  { label: t('finance.common.paymentMethods.other'), value: 'other' },
])

const paymentAccountOptions = computed(() => {
  const accounts = props.accounts?.payment_accounts || []
  return [
    { label: t('finance.transactionModal.defaultPaymentAccount'), value: '' },
    ...accounts.map((a) => ({
      label: `${a.code} - ${a.name}`,
      value: a.id,
    })),
  ]
})

const ledgerAccountOptions = computed(() => {
  const accounts =
    form.type === 'income'
      ? props.accounts?.revenue_accounts || []
      : props.accounts?.expense_accounts || []

  return [
    { label: t('finance.transactionModal.defaultCategoryAccount'), value: '' },
    ...accounts.map((a) => ({
      label: `${a.code} - ${a.name}`,
      value: a.id,
    })),
  ]
})

const isEditMode = computed(() => !!props.transaction?.id)

const handleSubmit = async () => {
  const numAmount = Number(form.amount)
  if (!numAmount || numAmount <= 0) {
    toast.error('Please enter a valid amount greater than zero')
    return
  }

  if (!form.transaction_date) {
    toast.error('Please enter a transaction date')
    return
  }

  try {
    isSubmitting.value = true

    const payload = {
      type: form.type,
      amount: numAmount,
      transaction_date: form.transaction_date,
      category_id: form.category_id || null,
      payment_method: form.payment_method,
      payee_payer: form.payee_payer || null,
      reference: form.reference || null,
      description: form.description || null,
      status: form.status,
      payment_account_id: form.payment_account_id || null,
      account_id: form.account_id || null,
    }

    if (isEditMode.value) {
      await financeApi.updateTransaction(props.transaction!.id, payload)
      toast.success('Transaction updated and accounting ledger synced')
    } else {
      await financeApi.createTransaction(payload)
      toast.success(
        form.type === 'income'
          ? 'Income recorded & posted to accounting ledger!'
          : 'Expense recorded & posted to accounting ledger!'
      )
    }

    emit('saved')
    emit('update:modelValue', false)
  } catch (err: any) {
    console.error('Failed to save transaction', err)
    toast.error(err?.response?.data?.message || 'Failed to save transaction')
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <UiModal
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
    size="lg"
    :title="isEditMode ? t('finance.transactionModal.editTransaction') : form.type === 'income' ? t('finance.transactionModal.recordIncome') : t('finance.transactionModal.recordExpense')"
  >
    <form @submit.prevent="handleSubmit" class="space-y-5 text-slate-800">
      <!-- 1. Type Switcher (Income vs Expense) -->
      <div v-if="!isEditMode" class="grid grid-cols-2 gap-3 p-1.5 bg-slate-100 rounded-2xl">
        <button
          type="button"
          @click="form.type = 'income'"
          class="flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl font-bold text-xs sm:text-sm transition-all"
          :class="[
            form.type === 'income'
              ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/20'
              : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/60',
          ]"
        >
          <TrendingUp class="w-4 h-4" />
          <span>{{ t('finance.transactionModal.incomeCashIn') }}</span>
        </button>

        <button
          type="button"
          @click="form.type = 'expense'"
          class="flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl font-bold text-xs sm:text-sm transition-all"
          :class="[
            form.type === 'expense'
              ? 'bg-rose-600 text-white shadow-md shadow-rose-600/20'
              : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/60',
          ]"
        >
          <TrendingDown class="w-4 h-4" />
          <span>{{ t('finance.transactionModal.expenseCashOut') }}</span>
        </button>
      </div>

      <!-- 2. Amount and Date in Prominent Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Amount -->
        <div>
          <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">
            {{ t('finance.transactionModal.amount') }} <span class="text-rose-500">*</span>
          </label>
          <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 font-bold text-lg">
              $
            </span>
            <input
              type="number"
              step="0.01"
              min="0.01"
              v-model="form.amount"
              required
              placeholder="0.00"
              class="w-full pl-9 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl font-bold text-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-2xs"
            />
          </div>
        </div>

        <!-- Date -->
        <div>
          <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">
            {{ t('finance.transactionModal.date') }} <span class="text-rose-500">*</span>
          </label>
          <input
            type="date"
            v-model="form.transaction_date"
            required
            class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-2xs"
          />
        </div>
      </div>

      <!-- 3. Category & Payee / Payer -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Category -->
        <div>
          <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">
            {{ t('finance.transactionModal.category') }}
          </label>
          <select
            v-model="form.category_id"
            class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-2xs"
          >
            <option value="">{{ t('finance.transactionModal.selectCategory') }}</option>
            <option
              v-for="cat in filteredCategories"
              :key="cat.id"
              :value="cat.id"
            >
              {{ cat.name }}
            </option>
          </select>
        </div>

        <!-- Payee or Payer -->
        <div>
          <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">
            {{ form.type === 'income' ? t('finance.transactionModal.receivedFrom') : t('finance.transactionModal.paidTo') }}
          </label>
          <input
            type="text"
            v-model="form.payee_payer"
            :placeholder="form.type === 'income' ? t('finance.transactionModal.payerPlaceholder') : t('finance.transactionModal.payeePlaceholder')"
            class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-2xs"
          />
        </div>
      </div>

      <!-- 4. Payment Method & Reference -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Payment Method -->
        <div>
          <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">
            {{ t('finance.transactionModal.paymentMethod') }}
          </label>
          <select
            v-model="form.payment_method"
            class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-2xs"
          >
            <option
              v-for="opt in paymentMethodOptions"
              :key="opt.value"
              :value="opt.value"
            >
              {{ opt.label }}
            </option>
          </select>
        </div>

        <!-- Reference / Check # -->
        <div>
          <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">
            {{ t('finance.transactionModal.reference') }}
          </label>
          <input
            type="text"
            v-model="form.reference"
            :placeholder="t('finance.transactionModal.referencePlaceholder')"
            class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-2xs"
          />
        </div>
      </div>

      <!-- 5. Description / Memo -->
      <div>
        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">
          {{ t('finance.transactionModal.description') }}
        </label>
        <textarea
          v-model="form.description"
          rows="2"
          :placeholder="t('finance.transactionModal.descriptionPlaceholder')"
          class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-2xs resize-none"
        ></textarea>
      </div>

      <!-- 6. Collapsible Accounting Ledger Section -->
      <div class="border border-slate-200 rounded-2xl overflow-hidden bg-slate-50/60">
        <button
          type="button"
          @click="showAccountingDetails = !showAccountingDetails"
          class="w-full flex items-center justify-between p-3.5 text-left hover:bg-slate-100/60 transition-colors"
        >
          <div class="flex items-center gap-2">
            <Scale class="w-4 h-4 text-primary-600" />
            <span class="text-xs font-bold text-slate-800">
              {{ t('finance.transactionModal.ledgerSettings') }}
            </span>
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-primary-100 text-primary-800">
              {{ t('finance.transactionModal.autoSynced') }}
            </span>
          </div>
          <ChevronDown
            class="w-4 h-4 text-slate-400 transition-transform duration-200"
            :class="showAccountingDetails ? 'rotate-180' : ''"
          />
        </button>

        <div v-if="showAccountingDetails" class="p-3.5 pt-0 space-y-3.5 border-t border-slate-200/60">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">
            <!-- Payment Account (Cash/Bank) -->
            <div>
              <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">
                {{ t('finance.transactionModal.paymentAccount') }}
              </label>
              <select
                v-model="form.payment_account_id"
                class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-2xs"
              >
                <option
                  v-for="acc in paymentAccountOptions"
                  :key="acc.value"
                  :value="acc.value"
                >
                  {{ acc.label }}
                </option>
              </select>
            </div>

            <!-- Ledger Revenue or Expense Account -->
            <div>
              <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">
                {{ form.type === 'income' ? t('finance.transactionModal.revenueAccount') : t('finance.transactionModal.expenseAccount') }}
              </label>
              <select
                v-model="form.account_id"
                class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-2xs"
              >
                <option
                  v-for="acc in ledgerAccountOptions"
                  :key="acc.value"
                  :value="acc.value"
                >
                  {{ acc.label }}
                </option>
              </select>
            </div>
          </div>

          <!-- Double-entry preview banner -->
          <div class="p-2.5 bg-blue-50/80 border border-blue-200/70 rounded-xl text-xs text-blue-900 flex items-start gap-2">
            <Sparkles class="w-4 h-4 text-blue-600 shrink-0 mt-0.5" />
            <div class="leading-relaxed">
              <span class="font-bold">{{ t('finance.transactionModal.ledgerPostingBanner') }}</span>
              <span v-if="form.type === 'income'">
                {{ t('finance.transactionModal.incomePostingDesc') }}
              </span>
              <span v-else>
                {{ t('finance.transactionModal.expensePostingDesc') }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
        <button
          type="button"
          @click="emit('update:modelValue', false)"
          class="px-4 py-2.5 rounded-xl text-slate-600 hover:bg-slate-100 font-semibold text-xs sm:text-sm transition-colors"
        >
          {{ t('finance.transactionModal.cancel') }}
        </button>

        <UiButton
          type="submit"
          :variant="form.type === 'income' ? 'primary' : 'danger'"
          :loading="isSubmitting"
          class="shadow-md text-xs sm:text-sm"
        >
          <span v-if="form.type === 'income'">
            {{ isEditMode ? t('finance.transactionModal.updateIncome') : t('finance.transactionModal.recordIncome') }}
          </span>
          <span v-else>
            {{ isEditMode ? t('finance.transactionModal.updateExpense') : t('finance.transactionModal.recordExpense') }}
          </span>
        </UiButton>
      </div>
    </form>
  </UiModal>
</template>
