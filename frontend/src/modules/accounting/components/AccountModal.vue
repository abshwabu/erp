<script setup lang="ts">
import { computed, reactive, watch } from 'vue'
import { useI18n } from 'vue-i18n'
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

const { t } = useI18n()

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
  <UiModal :model-value="modelValue" :title="isEditing ? t('accounting.chartOfAccounts.modal.editTitle') : t('accounting.chartOfAccounts.modal.newTitle')" size="lg" @update:modelValue="emit('update:modelValue', $event)">
    <div class="grid gap-3 sm:gap-4 grid-cols-1 sm:grid-cols-2">
      <UiInput v-model="form.code" :label="t('accounting.chartOfAccounts.modal.codeLabel')" :placeholder="t('accounting.chartOfAccounts.modal.codePlaceholder')" />
      <UiInput v-model="form.name" :label="t('accounting.chartOfAccounts.modal.nameLabel')" :placeholder="t('accounting.chartOfAccounts.modal.namePlaceholder')" />
      <UiSelect
        v-model="form.type"
        :label="t('accounting.chartOfAccounts.modal.typeLabel')"
        :options="accountTypes"
      />
      <UiSelect
        v-model="form.parentId"
        :label="t('accounting.chartOfAccounts.modal.parentLabel')"
        :options="[
          { label: t('accounting.chartOfAccounts.modal.noParent'), value: '' },
          ...parentOptions.map((account) => ({ label: `${account.code} - ${account.name}`, value: account.id }))
        ]"
      />
      <UiInput v-model="form.currencyCode" :label="t('accounting.chartOfAccounts.modal.currencyLabel')" placeholder="USD" />
      <UiSelect
        v-model="form.isActive"
        :label="t('accounting.chartOfAccounts.modal.statusLabel')"
        :options="[
          { label: t('accounting.common.active'), value: 'true' },
          { label: t('accounting.common.inactive'), value: 'false' },
        ]"
      />
      <div class="sm:col-span-2">
        <UiInput v-model="form.description" :label="t('accounting.chartOfAccounts.modal.descLabel')" :placeholder="t('accounting.chartOfAccounts.modal.descPlaceholder')" />
      </div>
    </div>

    <template #footer>
      <div class="flex flex-col-reverse sm:flex-row w-full justify-end gap-2 sm:gap-3">
        <UiButton variant="outline" class="w-full sm:w-auto justify-center" @click="close">{{ t('accounting.common.cancel') }}</UiButton>
        <UiButton class="w-full sm:w-auto justify-center" @click="handleSave">{{ isEditing ? t('accounting.chartOfAccounts.modal.save') : t('accounting.chartOfAccounts.modal.create') }}</UiButton>
      </div>
    </template>
  </UiModal>
</template>
