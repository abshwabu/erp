<script setup lang="ts">
import { computed } from 'vue'
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
  <UiModal :model-value="modelValue" title="Journal Details" size="full" @update:modelValue="emit('update:modelValue', $event)">
    <div v-if="journal" class="space-y-6">
      <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
          <p class="text-xs uppercase tracking-wide text-slate-500">Reference</p>
          <p class="mt-1 text-lg font-semibold text-slate-900">{{ journal.reference }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
          <p class="text-xs uppercase tracking-wide text-slate-500">Date</p>
          <p class="mt-1 text-lg font-semibold text-slate-900">{{ formatDate(journal.journalDate) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
          <p class="text-xs uppercase tracking-wide text-slate-500">Source</p>
          <p class="mt-1 text-lg font-semibold text-slate-900">{{ journal.sourceType }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
          <p class="text-xs uppercase tracking-wide text-slate-500">Status</p>
          <UiBadge :variant="statusVariant">{{ journal.status }}</UiBadge>
        </div>
      </div>

      <div class="rounded-2xl border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Account</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Description</th>
              <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Debit</th>
              <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Credit</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 bg-white">
            <tr v-for="line in journal.lines" :key="line.id" class="align-top">
              <td class="px-4 py-3">
                <div class="font-medium text-slate-900">{{ line.account?.code }} - {{ line.account?.name }}</div>
                <div class="text-xs text-slate-500">{{ line.account?.type }}</div>
              </td>
              <td class="px-4 py-3 text-slate-600">{{ line.description || journal.description }}</td>
              <td class="px-4 py-3 text-right font-medium text-slate-900">{{ formatCurrency(line.debit) }}</td>
              <td class="px-4 py-3 text-right font-medium text-slate-900">{{ formatCurrency(line.credit) }}</td>
            </tr>
          </tbody>
          <tfoot class="bg-slate-50">
            <tr>
              <td colspan="2" class="px-4 py-3 text-right text-sm font-semibold text-slate-700">Total</td>
              <td class="px-4 py-3 text-right text-sm font-semibold text-slate-900">{{ formatCurrency(journal.totalDebit) }}</td>
              <td class="px-4 py-3 text-right text-sm font-semibold text-slate-900">{{ formatCurrency(journal.totalCredit) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <template #footer>
      <div class="flex w-full justify-end">
        <UiButton variant="outline" @click="close">Close</UiButton>
      </div>
    </template>
  </UiModal>
</template>
