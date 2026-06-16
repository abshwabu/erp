<script setup lang="ts">
import { computed, reactive, watch } from 'vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import type { Account, AccountType } from '@/types/accounting'

interface Props {
  modelValue: boolean
  accountTypes: { label: string; value: AccountType }[]
  account?: Account | null
  parentOptions?: Account[]
}

const props = withDefaults(defineProps<Props>(), {
  account: null,
  parentOptions: () => [],
})

const emit = defineEmits<{
  (event: 'update:modelValue', value: boolean): void
  (event: 'save', value: Partial<Account>): void
}>()

const form = reactive({
  code: '',
  name: '',
  type: 'asset' as AccountType,
  parentId: '',
  currencyCode: 'USD',
  description: '',
  isActive: 'true',
})

const isEditing = computed(() => Boolean(props.account))

watch(
  () => props.account,
  (account) => {
    form.code = account?.code ?? ''
    form.name = account?.name ?? ''
    form.type = account?.type ?? 'asset'
    form.parentId = account?.parentId ?? ''
    form.currencyCode = account?.currencyCode ?? 'USD'
    form.description = account?.description ?? ''
    form.isActive = (account?.isActive ?? true) ? 'true' : 'false'
  },
  { immediate: true }
)

const close = () => emit('update:modelValue', false)

const handleSave = () => {
  emit('save', {
    id: props.account?.id,
    ...form,
    parentId: form.parentId || null,
    isActive: form.isActive === 'true',
  })
  close()
}
</script>

<template>
  <UiModal :model-value="modelValue" :title="isEditing ? 'Edit Account' : 'New Account'" size="lg" @update:modelValue="emit('update:modelValue', $event)">
    <div class="grid gap-4 md:grid-cols-2">
      <UiInput v-model="form.code" label="Account Code" placeholder="1100" />
      <UiInput v-model="form.name" label="Account Name" placeholder="Cash and Cash Equivalents" />
      <UiSelect
        v-model="form.type"
        label="Account Type"
        :options="accountTypes"
      />
      <UiSelect
        v-model="form.parentId"
        label="Parent Account"
        :options="[
          { label: 'No parent', value: '' },
          ...parentOptions.map((account) => ({ label: `${account.code} - ${account.name}`, value: account.id }))
        ]"
      />
      <UiInput v-model="form.currencyCode" label="Currency" placeholder="USD" />
      <UiSelect
        v-model="form.isActive"
        label="Status"
        :options="[
          { label: 'Active', value: 'true' },
          { label: 'Inactive', value: 'false' },
        ]"
      />
      <div class="md:col-span-2">
        <UiInput v-model="form.description" label="Description" placeholder="Optional account notes" />
      </div>
    </div>

    <template #footer>
      <div class="flex w-full justify-end gap-3">
        <UiButton variant="outline" @click="close">Cancel</UiButton>
        <UiButton @click="handleSave">{{ isEditing ? 'Save Changes' : 'Create Account' }}</UiButton>
      </div>
    </template>
  </UiModal>
</template>
