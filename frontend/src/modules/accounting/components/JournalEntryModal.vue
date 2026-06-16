<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { Plus, Search, Trash2 } from '@lucide/vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiModal from '@/components/ui/UiModal.vue'
import type { Account, Journal, JournalLine } from '@/types/accounting'
import { formatCurrency } from '@/utils/format'

interface LineForm extends Partial<JournalLine> {
  accountSearch: string
  pickerOpen: boolean
}

interface Props {
  modelValue: boolean
  accounts: Account[]
  journal?: Pick<Journal, 'reference' | 'journalDate' | 'description' | 'status' | 'lines'> | null
}

const props = withDefaults(defineProps<Props>(), {
  journal: null,
})

const emit = defineEmits<{
  (event: 'update:modelValue', value: boolean): void
  (event: 'saved', value: { status: 'draft' | 'posted'; journal: JournalEntryPayload }): void
}>()

interface JournalEntryPayload {
  reference: string
  journalDate: string
  description: string
  status: 'draft' | 'posted'
  lines: Array<{
    accountId: string
    description: string
    debit: number
    credit: number
    currencyCode: string
  }>
}

const defaultLine = (): LineForm => ({
  accountId: '',
  description: '',
  debit: 0,
  credit: 0,
  currencyCode: 'USD',
  accountSearch: '',
  pickerOpen: false,
})

const form = reactive<JournalEntryPayload>({
  reference: '',
  journalDate: new Date().toISOString().slice(0, 10),
  description: '',
  status: 'draft',
  lines: [defaultLine() as JournalEntryPayload['lines'][number], defaultLine() as JournalEntryPayload['lines'][number]],
})

const lineState = ref<LineForm[]>([defaultLine(), defaultLine()])
const balanceError = ref('')

const close = () => emit('update:modelValue', false)

const reset = () => {
  form.reference = props.journal?.reference ?? ''
  form.journalDate = props.journal?.journalDate ?? new Date().toISOString().slice(0, 10)
  form.description = props.journal?.description ?? ''
  form.status = props.journal?.status === 'posted' ? 'posted' : 'draft'
  form.lines = props.journal?.lines.map((line) => ({
    accountId: line.accountId,
    description: line.description ?? '',
    debit: line.debit,
    credit: line.credit,
    currencyCode: line.currencyCode,
  })) ?? [
    { accountId: '', description: '', debit: 0, credit: 0, currencyCode: 'USD' },
    { accountId: '', description: '', debit: 0, credit: 0, currencyCode: 'USD' },
  ]

  lineState.value = form.lines.map((line) => ({
    ...line,
    accountSearch: props.accounts.find((account) => account.id === line.accountId)
      ? `${props.accounts.find((account) => account.id === line.accountId)?.code} - ${props.accounts.find((account) => account.id === line.accountId)?.name}`
      : '',
    pickerOpen: false,
  })) as LineForm[]
}

watch(
  () => props.modelValue,
  (isOpen) => {
    if (isOpen) {
      reset()
      balanceError.value = ''
    }
  },
  { immediate: true }
)

const totalDebits = computed(() => lineState.value.reduce((sum, line) => sum + Number(line.debit || 0), 0))
const totalCredits = computed(() => lineState.value.reduce((sum, line) => sum + Number(line.credit || 0), 0))
const balanceDifference = computed(() => totalDebits.value - totalCredits.value)
const isBalanced = computed(() => balanceDifference.value === 0)

const filteredAccountsFor = (index: number) => {
  const query = lineState.value[index]?.accountSearch?.trim().toLowerCase() ?? ''
  return props.accounts.filter((account) => {
    if (!query) {
      return true
    }

    return `${account.code} ${account.name} ${account.type}`.toLowerCase().includes(query)
  })
}

const selectAccount = (index: number, account: Account) => {
  const line = lineState.value[index]
  if (!line) {
    return
  }

  line.accountId = account.id
  line.accountSearch = `${account.code} - ${account.name}`
  line.pickerOpen = false
}

const addLine = () => {
  lineState.value.push(defaultLine())
}

const removeLine = (index: number) => {
  if (lineState.value.length <= 2) {
    return
  }

  lineState.value.splice(index, 1)
}

const buildPayload = (status: 'draft' | 'posted'): JournalEntryPayload => ({
  reference: form.reference,
  journalDate: form.journalDate,
  description: form.description,
  status,
  lines: lineState.value
    .map((line) => ({
      accountId: line.accountId || '',
      description: line.description || '',
      debit: Number(line.debit || 0),
      credit: Number(line.credit || 0),
      currencyCode: line.currencyCode || 'USD',
    }))
    .filter((line) => Boolean(line.accountId)),
})

const saveAsDraft = () => {
  balanceError.value = ''
  emit('saved', { status: 'draft', journal: buildPayload('draft') })
  close()
}

const postJournal = () => {
  if (!isBalanced.value) {
    balanceError.value = 'Journal must balance before posting.'
    return
  }

  balanceError.value = ''
  emit('saved', { status: 'posted', journal: buildPayload('posted') })
  close()
}
</script>

<template>
  <UiModal :model-value="modelValue" title="New Journal Entry" size="full" @update:modelValue="emit('update:modelValue', $event)">
    <div class="space-y-6">
      <div class="grid gap-4 md:grid-cols-3">
        <UiInput v-model="form.reference" label="Reference" placeholder="JE-2026-0040" />
        <UiInput v-model="form.journalDate" label="Date" type="date" />
        <UiInput v-model="form.description" label="Description" placeholder="Brief entry description" />
      </div>

      <div class="overflow-hidden rounded-2xl border border-slate-200">
        <table class="min-w-full divide-y divide-slate-200">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Account</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Description</th>
              <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Debit</th>
              <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Credit</th>
              <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 bg-white">
            <tr v-for="(line, index) in lineState" :key="`${index}-${line.accountId}`" class="align-top">
              <td class="px-4 py-3">
                <div class="relative">
                  <input
                    v-model="line.accountSearch"
                    type="text"
                    placeholder="Search account..."
                    class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-primary-500 focus:ring-primary-500"
                    @focus="line.pickerOpen = true"
                    @input="line.pickerOpen = true"
                  />
                  <Search class="pointer-events-none absolute right-3 top-2.5 h-4 w-4 text-slate-400" />
                  <div v-if="line.pickerOpen" class="absolute z-20 mt-2 max-h-56 w-full overflow-auto rounded-xl border border-slate-200 bg-white shadow-lg">
                    <button
                      v-for="account in filteredAccountsFor(index)"
                      :key="account.id"
                      type="button"
                      class="flex w-full items-start justify-between gap-3 px-3 py-2 text-left hover:bg-slate-50"
                      @click="selectAccount(index, account)"
                    >
                      <span>
                        <span class="block text-sm font-medium text-slate-900">{{ account.code }} - {{ account.name }}</span>
                        <span class="block text-xs text-slate-500">{{ account.type }}</span>
                      </span>
                      <span class="text-xs text-slate-500">{{ formatCurrency(account.currentPeriodBalance) }}</span>
                    </button>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3">
                <input
                  v-model="line.description"
                  type="text"
                  placeholder="Line description"
                  class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-primary-500 focus:ring-primary-500"
                />
              </td>
              <td class="px-4 py-3">
                <input
                  v-model="line.debit"
                  type="number"
                  min="0"
                  step="0.01"
                  class="block w-full rounded-md border border-slate-300 px-3 py-2 text-right text-sm text-slate-900 focus:border-primary-500 focus:ring-primary-500"
                />
              </td>
              <td class="px-4 py-3">
                <input
                  v-model="line.credit"
                  type="number"
                  min="0"
                  step="0.01"
                  class="block w-full rounded-md border border-slate-300 px-3 py-2 text-right text-sm text-slate-900 focus:border-primary-500 focus:ring-primary-500"
                />
              </td>
              <td class="px-4 py-3 text-right">
                <button
                  type="button"
                  class="inline-flex items-center justify-center rounded-md border border-slate-200 p-2 text-slate-500 hover:bg-red-50 hover:text-red-600 disabled:opacity-40"
                  :disabled="lineState.length <= 2"
                  @click="removeLine(index)"
                >
                  <Trash2 class="h-4 w-4" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4">
        <UiButton variant="outline" @click="addLine">
          <Plus class="mr-2 h-4 w-4" /> Add Line
        </UiButton>
        <div class="flex flex-wrap items-center gap-4">
          <div class="rounded-xl bg-white px-4 py-2">
            <p class="text-xs uppercase tracking-wide text-slate-500">Total Debits</p>
            <p class="text-lg font-semibold text-slate-900">{{ formatCurrency(totalDebits) }}</p>
          </div>
          <div class="rounded-xl bg-white px-4 py-2">
            <p class="text-xs uppercase tracking-wide text-slate-500">Total Credits</p>
            <p class="text-lg font-semibold text-slate-900">{{ formatCurrency(totalCredits) }}</p>
          </div>
          <div
            class="rounded-xl px-4 py-2"
            :class="isBalanced ? 'bg-emerald-50 text-emerald-800' : 'bg-rose-50 text-rose-800'"
          >
            <p class="text-xs uppercase tracking-wide">Balance</p>
            <p class="text-lg font-semibold">
              {{ isBalanced ? 'Balanced' : `Out by ${formatCurrency(Math.abs(balanceDifference))}` }}
            </p>
          </div>
        </div>
      </div>

      <p v-if="balanceError" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
        {{ balanceError }}
      </p>
    </div>

    <template #footer>
      <div class="flex w-full flex-wrap justify-end gap-3">
        <UiButton variant="outline" @click="close">Cancel</UiButton>
        <UiButton variant="secondary" @click="saveAsDraft">Save as Draft</UiButton>
        <UiButton :disabled="!isBalanced" @click="postJournal">Post</UiButton>
      </div>
    </template>
  </UiModal>
</template>
