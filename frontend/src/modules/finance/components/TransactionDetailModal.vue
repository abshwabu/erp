<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  TrendingUp,
  TrendingDown,
  Calendar,
  CreditCard,
  User,
  Scale,
  ExternalLink,
  CheckCircle2,
  Clock,
  Ban,
  Tag,
  Hash,
  FileText,
} from '@lucide/vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiButton from '@/components/ui/UiButton.vue'
import { formatCurrency } from '@/utils/format'
import type { FinanceTransaction } from '@/types/finance'

interface Props {
  modelValue: boolean
  transaction: FinanceTransaction | null
}

const props = defineProps<Props>()
const emit = defineEmits(['update:modelValue', 'edit'])
const router = useRouter()
const { t } = useI18n()

const isIncome = computed(() => props.transaction?.type === 'income')

const goToJournal = (journalId?: string) => {
  emit('update:modelValue', false)
  router.push('/accounting/journals')
}
</script>

<template>
  <UiModal
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
    size="lg"
    :title="t('finance.detailModal.title')"
  >
    <div v-if="transaction" class="space-y-5 text-slate-800">
      <!-- Header Banner with Amount -->
      <div
        class="p-4 rounded-2xl border flex flex-col sm:flex-row sm:items-center justify-between gap-3"
        :class="[
          isIncome
            ? 'bg-emerald-50/70 border-emerald-200'
            : 'bg-rose-50/70 border-rose-200',
        ]"
      >
        <div class="flex items-center gap-3">
          <div
            class="w-12 h-12 rounded-2xl flex items-center justify-center text-white shadow-sm shrink-0"
            :class="isIncome ? 'bg-emerald-600' : 'bg-rose-600'"
          >
            <TrendingUp v-if="isIncome" class="w-6 h-6" />
            <TrendingDown v-else class="w-6 h-6" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="text-xs font-mono font-bold text-slate-500">
                {{ transaction.transaction_number }}
              </span>
              <span
                class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider"
                :class="[
                  isIncome
                    ? 'bg-emerald-100 text-emerald-800'
                    : 'bg-rose-100 text-rose-800',
                ]"
              >
                {{ t(`finance.common.${transaction.type}`) || transaction.type }}
              </span>
            </div>
            <h3 class="text-lg font-black text-slate-900 mt-0.5">
              {{ transaction.payee_payer || (isIncome ? t('finance.detailModal.customerIncomeSource') : t('finance.detailModal.vendorPayee')) }}
            </h3>
          </div>
        </div>

        <div class="text-right">
          <p class="text-xs font-semibold text-slate-400">{{ t('finance.detailModal.totalAmount') }}</p>
          <p
            class="text-2xl font-black tracking-tight mt-0.5"
            :class="isIncome ? 'text-emerald-600' : 'text-rose-600'"
          >
            {{ isIncome ? '+' : '-' }}{{ formatCurrency(transaction.amount) }}
          </p>
        </div>
      </div>

      <!-- Attributes Grid -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-slate-50 p-3.5 rounded-2xl border border-slate-200/80 text-xs">
        <div>
          <span class="text-slate-400 block font-semibold mb-0.5">{{ t('finance.detailModal.date') }}</span>
          <span class="font-bold text-slate-800">{{ transaction.transaction_date }}</span>
        </div>
        <div>
          <span class="text-slate-400 block font-semibold mb-0.5">{{ t('finance.detailModal.category') }}</span>
          <span
            v-if="transaction.category"
            class="inline-flex items-center gap-1 font-bold text-slate-800"
          >
            <span
              class="w-2 h-2 rounded-full"
              :style="{ backgroundColor: transaction.category.color || '#3B82F6' }"
            />
            {{ transaction.category.name }}
          </span>
          <span v-else class="font-medium text-slate-400">{{ t('finance.common.uncategorized') }}</span>
        </div>
        <div>
          <span class="text-slate-400 block font-semibold mb-0.5">{{ t('finance.detailModal.paymentMethod') }}</span>
          <span class="font-bold text-slate-800 capitalize">
            {{ t(`finance.common.paymentMethods.${transaction.payment_method}`) || (transaction.payment_method || 'cash').replace('_', ' ') }}
          </span>
        </div>
        <div>
          <span class="text-slate-400 block font-semibold mb-0.5">{{ t('finance.detailModal.status') }}</span>
          <span
            class="inline-flex items-center gap-1 font-bold capitalize"
            :class="[
              transaction.status === 'completed'
                ? 'text-emerald-600'
                : transaction.status === 'pending'
                ? 'text-amber-600'
                : 'text-slate-500',
            ]"
          >
            <CheckCircle2 v-if="transaction.status === 'completed'" class="w-3.5 h-3.5" />
            <Clock v-else-if="transaction.status === 'pending'" class="w-3.5 h-3.5" />
            <Ban v-else class="w-3.5 h-3.5" />
            {{ t(`finance.common.statuses.${transaction.status}`) || transaction.status }}
          </span>
        </div>
      </div>

      <!-- Reference & Description -->
      <div v-if="transaction.reference || transaction.description || transaction.source_type" class="space-y-2 text-xs">
        <div class="flex flex-wrap items-center gap-4 text-slate-600">
          <div v-if="transaction.reference" class="flex items-center gap-2">
            <span class="font-bold text-slate-400">{{ t('finance.detailModal.reference') }}:</span>
            <span class="font-mono bg-slate-100 px-2 py-0.5 rounded-md font-semibold text-slate-800">
              {{ transaction.reference }}
            </span>
          </div>
          <div v-if="transaction.source_type" class="flex items-center gap-2">
            <span class="font-bold text-slate-400">{{ t('finance.detailModal.source') }}:</span>
            <span
              v-if="transaction.source_type === 'pos_transaction'"
              class="px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 font-bold text-[11px]"
            >
              {{ t('finance.detailModal.sourcePos') }}
            </span>
            <span
              v-else-if="transaction.source_type === 'sales_payment'"
              class="px-2 py-0.5 rounded-md bg-sky-50 text-sky-700 font-bold text-[11px]"
            >
              {{ t('finance.detailModal.sourceSales') }}
            </span>
            <span v-else class="capitalize text-slate-700 font-semibold">{{ transaction.source_type }}</span>
          </div>
        </div>
        <div v-if="transaction.description" class="p-3 bg-slate-50 rounded-xl border border-slate-200/70 text-slate-700 leading-relaxed">
          <span class="font-bold text-slate-400 block mb-0.5">{{ t('finance.detailModal.description') }}:</span>
          {{ transaction.description }}
        </div>
      </div>

      <!-- Accounting Module Integration Card -->
      <div class="border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-2xs">
        <div class="p-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <Scale class="w-4 h-4 text-blue-600" />
            <h4 class="text-xs font-bold text-slate-900">
              {{ t('finance.detailModal.ledgerIntegration') }}
            </h4>
          </div>
          <span
            v-if="transaction.journal"
            class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-100 text-blue-800"
          >
            {{ t('finance.detailModal.postedEntry') }}
          </span>
          <span v-else class="text-[10px] text-slate-400">{{ t('finance.detailModal.noJournalLinked') }}</span>
        </div>

        <div class="p-4 space-y-3">
          <div v-if="transaction.journal" class="space-y-3">
            <div class="flex flex-wrap items-center justify-between text-xs text-slate-600 gap-2">
              <div>
                <span class="text-slate-400">{{ t('finance.detailModal.journalRef') }}: </span>
                <span class="font-bold text-slate-900">{{ transaction.journal.reference }}</span>
              </div>
              <button
                type="button"
                @click="goToJournal(transaction.journal_id || undefined)"
                class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-700 font-bold hover:underline"
              >
                <span>{{ t('finance.detailModal.openInJournals') }}</span>
                <ExternalLink class="w-3.5 h-3.5" />
              </button>
            </div>

            <!-- Double-entry lines table -->
            <div
              v-if="transaction.journal.lines && transaction.journal.lines.length > 0"
              class="border border-slate-200 rounded-xl overflow-hidden"
            >
              <table class="w-full text-left text-xs divide-y divide-slate-200">
                <thead class="bg-slate-50 text-slate-500 font-bold">
                  <tr>
                    <th class="px-3 py-2">{{ t('finance.detailModal.ledgerAccount') }}</th>
                    <th class="px-3 py-2 text-right">{{ t('finance.detailModal.debit') }}</th>
                    <th class="px-3 py-2 text-right">{{ t('finance.detailModal.credit') }}</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr
                    v-for="line in transaction.journal.lines"
                    :key="line.id"
                    class="hover:bg-slate-50/60"
                  >
                    <td class="px-3 py-2 font-medium text-slate-800">
                      <span class="font-mono font-bold text-blue-600 mr-1.5">
                        {{ line.account?.code }}
                      </span>
                      {{ line.account?.name }}
                    </td>
                    <td class="px-3 py-2 text-right font-mono font-bold text-slate-900">
                      {{ line.debit_cents > 0 ? formatCurrency(line.debit_cents / 100) : '—' }}
                    </td>
                    <td class="px-3 py-2 text-right font-mono font-bold text-slate-900">
                      {{ line.credit_cents > 0 ? formatCurrency(line.credit_cents / 100) : '—' }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div v-else class="text-xs text-slate-400 italic">
            {{ t('finance.detailModal.notPostedNotice') }}
          </div>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="flex items-center justify-between w-full">
        <button
          type="button"
          @click="emit('update:modelValue', false)"
          class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 font-semibold text-xs sm:text-sm"
        >
          {{ t('finance.detailModal.close') }}
        </button>

        <UiButton
          type="button"
          variant="outline"
          @click="emit('edit', transaction)"
          class="text-xs sm:text-sm"
        >
          {{ t('finance.detailModal.editTransaction') }}
        </UiButton>
      </div>
    </template>
  </UiModal>
</template>
