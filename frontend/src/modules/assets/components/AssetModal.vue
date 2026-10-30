<script setup lang="ts">
import { computed, reactive, watch, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import type { Asset, AssetCategory, AssetStatus, DepreciationMethod } from '@/types/assets'

interface Props {
  modelValue: boolean
  asset?: Asset | null
  saving?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  asset: null,
  saving: false,
})

const emit = defineEmits<{
  (event: 'update:modelValue', value: boolean): void
  (event: 'save', value: any): void
}>()

const { t } = useI18n()

const form = reactive({
  name: '',
  category: 'equipment' as AssetCategory,
  status: 'active' as AssetStatus,
  serial_number: '',
  purchase_date: new Date().toISOString().split('T')[0],
  purchase_cost: 0,
  salvage_value: 0,
  useful_life_years: 5,
  depreciation_method: 'straight_line' as DepreciationMethod,
  notes: '',
})

const error = ref('')
const isEditing = computed(() => Boolean(props.asset))

watch(
  () => props.asset,
  (asset) => {
    if (asset) {
      form.name = asset.name || ''
      form.category = asset.category || 'equipment'
      form.status = asset.status || 'active'
      form.serial_number = asset.serial_number || ''
      form.purchase_date = asset.purchase_date ? asset.purchase_date.split('T')[0] : new Date().toISOString().split('T')[0]
      form.purchase_cost = (asset.purchase_cost_cents || 0) / 100
      form.salvage_value = (asset.salvage_value_cents || 0) / 100
      form.useful_life_years = asset.useful_life_years || 5
      form.depreciation_method = asset.depreciation_method || 'straight_line'
      form.notes = asset.notes || ''
    } else {
      form.name = ''
      form.category = 'equipment'
      form.status = 'active'
      form.serial_number = ''
      form.purchase_date = new Date().toISOString().split('T')[0]
      form.purchase_cost = 0
      form.salvage_value = 0
      form.useful_life_years = 5
      form.depreciation_method = 'straight_line'
      form.notes = ''
    }
    error.value = ''
  },
  { immediate: true }
)

const categoryOptions = computed(() => [
  { label: t('assets.categories.equipment'), value: 'equipment' },
  { label: t('assets.categories.machinery'), value: 'machinery' },
  { label: t('assets.categories.vehicle'), value: 'vehicle' },
  { label: t('assets.categories.electronics'), value: 'electronics' },
  { label: t('assets.categories.furniture'), value: 'furniture' },
  { label: t('assets.categories.building'), value: 'building' },
])

const statusOptions = computed(() => [
  { label: t('assets.statuses.active'), value: 'active' },
  { label: t('assets.statuses.maintenance'), value: 'maintenance' },
  { label: t('assets.statuses.disposed'), value: 'disposed' },
  { label: t('assets.statuses.retired'), value: 'retired' },
])

const methodOptions = computed(() => [
  { label: t('assets.methods.straight_line'), value: 'straight_line' },
  { label: t('assets.methods.declining_balance'), value: 'declining_balance' },
])

const close = () => {
  emit('update:modelValue', false)
}

const handleSubmit = () => {
  if (!form.name.trim()) {
    error.value = 'Asset name is required.'
    return
  }
  if (form.purchase_cost < 0) {
    error.value = 'Purchase cost cannot be negative.'
    return
  }
  if (form.salvage_value < 0) {
    error.value = 'Salvage value cannot be negative.'
    return
  }
  if (form.useful_life_years < 1) {
    error.value = 'Useful life must be at least 1 year.'
    return
  }

  error.value = ''
  emit('save', {
    id: props.asset?.id,
    name: form.name.trim(),
    category: form.category,
    status: form.status,
    serial_number: form.serial_number.trim() || null,
    purchase_date: form.purchase_date || null,
    purchase_cost_cents: Math.round(form.purchase_cost * 100),
    salvage_value_cents: Math.round(form.salvage_value * 100),
    useful_life_years: Number(form.useful_life_years),
    depreciation_method: form.depreciation_method,
    notes: form.notes.trim() || null,
  })
}
</script>

<template>
  <UiModal
    :model-value="modelValue"
    :title="isEditing ? t('assets.modal.editTitle', { name: asset?.name }) : t('assets.modal.createTitle')"
    size="lg"
    @update:modelValue="emit('update:modelValue', $event)"
  >
    <form class="space-y-4" @submit.prevent="handleSubmit">
      <div v-if="error" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-xs sm:text-sm text-rose-700 font-medium">
        {{ error }}
      </div>

      <div class="grid gap-3 sm:gap-4 grid-cols-1 sm:grid-cols-2">
        <div class="sm:col-span-2">
          <UiInput
            v-model="form.name"
            :label="t('assets.modal.nameLabel')"
            :placeholder="t('assets.modal.namePlaceholder')"
            required
          />
        </div>

        <UiSelect
          v-model="form.category"
          :label="t('assets.modal.categoryLabel')"
          :options="categoryOptions"
        />

        <UiSelect
          v-model="form.status"
          :label="t('assets.modal.statusLabel')"
          :options="statusOptions"
        />

        <UiInput
          v-model="form.serial_number"
          :label="t('assets.modal.serialNumberLabel')"
          :placeholder="t('assets.modal.serialNumberPlaceholder')"
        />

        <UiInput
          v-model="form.purchase_date"
          :label="t('assets.modal.purchaseDateLabel')"
          type="date"
        />

        <UiInput
          v-model.number="form.purchase_cost"
          :label="t('assets.modal.purchaseCostLabel')"
          type="number"
          step="0.01"
          min="0"
          required
        />

        <UiInput
          v-model.number="form.salvage_value"
          :label="t('assets.modal.salvageValueLabel')"
          type="number"
          step="0.01"
          min="0"
        />

        <UiInput
          v-model.number="form.useful_life_years"
          :label="t('assets.modal.usefulLifeLabel')"
          type="number"
          min="1"
          required
        />

        <UiSelect
          v-model="form.depreciation_method"
          :label="t('assets.modal.methodLabel')"
          :options="methodOptions"
        />

        <div class="sm:col-span-2 space-y-1">
          <label class="block text-xs sm:text-sm font-medium text-slate-700">
            {{ t('assets.modal.notesLabel') }}
          </label>
          <textarea
            v-model="form.notes"
            rows="3"
            :placeholder="t('assets.modal.notesPlaceholder')"
            class="w-full rounded-2xl border border-slate-200 px-3.5 py-2.5 text-xs sm:text-sm text-slate-900 placeholder:text-slate-400 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 transition-colors"
          ></textarea>
        </div>
      </div>
    </form>

    <template #footer>
      <div class="flex w-full items-center justify-end gap-2">
        <UiButton variant="outline" :disabled="saving" @click="close">
          {{ t('assets.modal.cancel') }}
        </UiButton>
        <UiButton :loading="saving" @click="handleSubmit">
          {{ isEditing ? t('assets.modal.save') : t('assets.modal.create') }}
        </UiButton>
      </div>
    </template>
  </UiModal>
</template>
