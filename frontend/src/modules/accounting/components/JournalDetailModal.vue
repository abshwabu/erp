<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiModal from '@/components/ui/UiModal.vue'
import { formatCurrency, formatDate } from '@/utils/format'
import type { Journal } from '@/types/accounting'

interface Props {
  modelValue: boolean
  journal: Journal | null
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (event: 'update:modelValue', value: boolean): void
}>()

const { t } = useI18n()

const close = () => emit('update:modelValue', false)

const statusVariant = computed<'default' | 'success' | 'warning' | 'danger' | 'info'>(() => {
  if (!props.journal) {
    return 'default'
  }

  switch (props.journal?.status) {
    case 'posted':
      return 'success'
    case 'reversed':
      return 'danger'
    default:
      return 'warning'
  }
})
</script>

<template>
  <UiModal :model-value="modelValue" :title="t('accounting.journals.detailModal.title')" size="full" @update:modelValue="emit('update:modelValue', $event)">
    <div v-if="journal" class="space-y-6">
      <div class="grid gap-3 grid-cols-2 sm:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 sm:p-4">
          <p class="text-xs uppercase tracking-wide text-slate-500">{{ t('accounting.journals.detailModal.reference') }}</p>
          <p class="mt-1 text-base sm:text-lg font-semibold text-slate-900 break-words">{{ journal.reference }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 sm:p-4">
          <p class="text-xs uppercase tracking-wide text-slate-500">{{ t('accounting.journals.detailModal.date') }}</p>
          <p class="mt-1 text-base sm:text-lg font-semibold text-slate-900">{{ formatDate(journal.journalDate) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 sm:p-4">
          <p class="text-xs uppercase tracking-wide text-slate-500">{{ t('accounting.journals.detailModal.source') }}</p>
          <p class="mt-1 text-base sm:text-lg font-semibold text-slate-900 capitalize">{{ journal.sourceType }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 sm:p-4">
          <p class="text-xs uppercase tracking-wide text-slate-500">{{ t('accounting.journals.detailModal.status') }}</p>
          <div class="mt-1">
            <UiBadge :variant="statusVariant">{{ t(`accounting.common.statuses.${journal.status}`) || journal.status }}</UiBadge>
          </div>
        </div>
      </div>

      <div class="rounded-2xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full min-w-[550px] divide-y divide-slate-200 text-xs sm:text-sm">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide text-slate-500 whitespace-nowrap">{{ t('accounting.journals.detailModal.account') }}</th>
                <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide text-slate-500 min-w-[160px]">{{ t('accounting.journals.detailModal.description') }}</th>
                <th class="px-4 py-3 text-right font-semibold uppercase tracking-wide text-slate-500 whitespace-nowrap">{{ t('accounting.journals.detailModal.debit') }}</th>
                <th class="px-4 py-3 text-right font-semibold uppercase tracking-wide text-slate-500 whitespace-nowrap">{{ t('accounting.journals.detailModal.credit') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
              <tr v-for="line in journal.lines" :key="line.id" class="align-top">
                <td class="px-4 py-3 whitespace-nowrap">
                  <div class="font-medium text-slate-900">{{ line.account?.code }} - {{ line.account?.name }}</div>
                  <div class="text-xs text-slate-500">{{ t(`accounting.common.types.${line.account?.type}`) || line.account?.type }}</div>
                </td>
                <td class="px-4 py-3 text-slate-600">{{ line.description || journal.description }}</td>
                <td class="px-4 py-3 text-right font-medium text-slate-900 whitespace-nowrap">{{ formatCurrency(line.debit) }}</td>
                <td class="px-4 py-3 text-right font-medium text-slate-900 whitespace-nowrap">{{ formatCurrency(line.credit) }}</td>
              </tr>
            </tbody>
            <tfoot class="bg-slate-50 font-semibold">
              <tr>
                <td colspan="2" class="px-4 py-3 text-right text-slate-700">{{ t('accounting.journals.detailModal.total') }}</td>
                <td class="px-4 py-3 text-right text-slate-900 whitespace-nowrap">{{ formatCurrency(journal.totalDebit) }}</td>
                <td class="px-4 py-3 text-right text-slate-900 whitespace-nowrap">{{ formatCurrency(journal.totalCredit) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="flex w-full justify-end">
        <UiButton variant="outline" @click="close">{{ t('accounting.journals.detailModal.close') }}</UiButton>
      </div>
    </template>
  </UiModal>
</template>
