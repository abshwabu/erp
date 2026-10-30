<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  Tag,
  Plus,
  Trash2,
  Edit2,
  Check,
  X,
  TrendingUp,
  TrendingDown,
  Layers,
} from '@lucide/vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiButton from '@/components/ui/UiButton.vue'
import { financeApi } from '@/api/finance'
import { useToast } from '@/composables/useToast'
import type { FinanceCategory, SelectableAccounts } from '@/types/finance'

interface Props {
  modelValue: boolean
  categories: FinanceCategory[]
  accounts?: SelectableAccounts | null
}

const props = defineProps<Props>()
const emit = defineEmits(['update:modelValue', 'saved'])
const { t } = useI18n()
const toast = useToast()

const isAddingNew = ref(false)
const isSubmitting = ref(false)
const editingId = ref<string | null>(null)

const form = reactive({
  name: '',
  type: 'expense' as 'income' | 'expense' | 'both',
  color: '#3B82F6',
  description: '',
  account_id: '' as string,
})

const colorPresets = [
  '#10B981', // emerald
  '#059669', // dark green
  '#3B82F6', // blue
  '#6366F1', // indigo
  '#8B5CF6', // purple
  '#EC4899', // pink
  '#EF4444', // red
  '#F59E0B', // amber
  '#F97316', // orange
  '#14B8A6', // teal
  '#64748B', // slate
]

const resetForm = () => {
  form.name = ''
  form.type = 'expense'
  form.color = '#3B82F6'
  form.description = ''
  form.account_id = ''
  isAddingNew.value = false
  editingId.value = null
}

const handleStartAdd = () => {
  resetForm()
  isAddingNew.value = true
}

const handleStartEdit = (cat: FinanceCategory) => {
  editingId.value = cat.id
  form.name = cat.name
  form.type = cat.type
  form.color = cat.color || '#3B82F6'
  form.description = cat.description || ''
  form.account_id = cat.account_id || ''
  isAddingNew.value = true
}

const handleSave = async () => {
  if (!form.name.trim()) {
    toast.error('Category name is required')
    return
  }

  try {
    isSubmitting.value = true
    const payload = {
      name: form.name.trim(),
      type: form.type,
      color: form.color,
      description: form.description || null,
      account_id: form.account_id || null,
    }

    if (editingId.value) {
      await financeApi.updateCategory(editingId.value, payload)
      toast.success('Category updated')
    } else {
      await financeApi.createCategory(payload)
      toast.success('New category added')
    }

    resetForm()
    emit('saved')
  } catch (err: any) {
    console.error('Failed to save category', err)
    toast.error(err?.response?.data?.message || 'Failed to save category')
  } finally {
    isSubmitting.value = false
  }
}

const handleDelete = async (id: string) => {
  if (!confirm(t('finance.categoryModal.deleteConfirm'))) return

  try {
    await financeApi.deleteCategory(id)
    toast.success('Category removed')
    emit('saved')
  } catch (err: any) {
    console.error('Failed to delete category', err)
    toast.error(err?.response?.data?.message || 'Failed to delete category')
  }
}
</script>

<template>
  <UiModal
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
    size="lg"
    :title="t('finance.categoryModal.title')"
  >
    <div class="space-y-4 text-slate-800">
      <!-- Create / Edit Sub-form -->
      <div v-if="isAddingNew" class="p-4 bg-slate-50 border border-slate-200 rounded-2xl space-y-3.5">
        <div class="flex items-center justify-between">
          <h4 class="text-xs font-bold uppercase tracking-wider text-slate-600">
            {{ editingId ? t('finance.categoryModal.editCategory') : t('finance.categoryModal.newCategory') }}
          </h4>
          <button
            type="button"
            @click="resetForm"
            class="text-slate-400 hover:text-slate-600 p-1"
          >
            <X class="w-4 h-4" />
          </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <!-- Name -->
          <div>
            <label class="block text-xs font-bold text-slate-600 mb-1">{{ t('finance.categoryModal.categoryName') }} *</label>
            <input
              type="text"
              v-model="form.name"
              :placeholder="t('finance.categoryModal.categoryNamePlaceholder')"
              class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-2xs"
            />
          </div>

          <!-- Type -->
          <div>
            <label class="block text-xs font-bold text-slate-600 mb-1">{{ t('finance.categoryModal.type') }} *</label>
            <select
              v-model="form.type"
              class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-2xs"
            >
              <option value="expense">{{ t('finance.categoryModal.expenseOnly') }}</option>
              <option value="income">{{ t('finance.categoryModal.incomeOnly') }}</option>
              <option value="both">{{ t('finance.categoryModal.both') }}</option>
            </select>
          </div>
        </div>

        <!-- Color Swatches -->
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">{{ t('finance.categoryModal.badgeColor') }}</label>
          <div class="flex flex-wrap items-center gap-1.5">
            <button
              v-for="color in colorPresets"
              :key="color"
              type="button"
              @click="form.color = color"
              class="w-6 h-6 rounded-full border-2 transition-transform hover:scale-110 flex items-center justify-center"
              :style="{ backgroundColor: color }"
              :class="form.color === color ? 'border-slate-900 ring-2 ring-slate-900/20' : 'border-white'"
            >
              <Check v-if="form.color === color" class="w-3 h-3 text-white stroke-[3]" />
            </button>
          </div>
        </div>

        <!-- Description -->
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">{{ t('finance.categoryModal.description') }}</label>
          <input
            type="text"
            v-model="form.description"
            :placeholder="t('finance.categoryModal.descriptionPlaceholder')"
            class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-2xs"
          />
        </div>

        <!-- Save Button -->
        <div class="flex items-center justify-end gap-2 pt-1">
          <button
            type="button"
            @click="resetForm"
            class="px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-200/60 rounded-lg"
          >
            {{ t('finance.categoryModal.cancel') }}
          </button>
          <UiButton
            type="button"
            size="sm"
            variant="primary"
            :loading="isSubmitting"
            @click="handleSave"
            class="text-xs"
          >
            {{ editingId ? t('finance.categoryModal.updateCategory') : t('finance.categoryModal.saveCategory') }}
          </UiButton>
        </div>
      </div>

      <!-- Add Category Trigger -->
      <div v-else class="flex justify-end">
        <button
          type="button"
          @click="handleStartAdd"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-blue-50 text-blue-700 hover:bg-blue-100 font-bold text-xs transition-colors"
        >
          <Plus class="w-4 h-4" />
          <span>{{ t('finance.categoryModal.addCategory') }}</span>
        </button>
      </div>

      <!-- Category List -->
      <div class="border border-slate-200 rounded-2xl overflow-hidden divide-y divide-slate-100 max-h-80 overflow-y-auto">
        <div
          v-for="cat in categories"
          :key="cat.id"
          class="p-3 flex items-center justify-between hover:bg-slate-50/80 transition-colors"
        >
          <div class="flex items-center gap-3">
            <span
              class="w-3.5 h-3.5 rounded-full shrink-0"
              :style="{ backgroundColor: cat.color || '#3B82F6' }"
            />
            <div>
              <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-slate-900">{{ cat.name }}</span>
                <span
                  class="px-1.5 py-0.2 rounded text-[10px] font-extrabold uppercase"
                  :class="[
                    cat.type === 'income'
                      ? 'bg-emerald-50 text-emerald-700'
                      : cat.type === 'expense'
                      ? 'bg-rose-50 text-rose-700'
                      : 'bg-blue-50 text-blue-700',
                  ]"
                >
                  {{ t(`finance.common.${cat.type}`) || cat.type }}
                </span>
                <span v-if="cat.is_system" class="text-[10px] text-slate-400 font-semibold">
                  {{ t('finance.categoryModal.default') }}
                </span>
              </div>
              <p v-if="cat.description" class="text-[11px] text-slate-400 mt-0.5">
                {{ cat.description }}
              </p>
            </div>
          </div>

          <div class="flex items-center gap-1">
            <button
              type="button"
              @click="handleStartEdit(cat)"
              class="p-1 text-slate-400 hover:text-blue-600 rounded-lg hover:bg-slate-100"
              :title="t('finance.categoryModal.edit')"
            >
              <Edit2 class="w-3.5 h-3.5" />
            </button>
            <button
              v-if="!cat.is_system"
              type="button"
              @click="handleDelete(cat.id)"
              class="p-1 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-slate-100"
              :title="t('finance.categoryModal.delete')"
            >
              <Trash2 class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-end w-full">
        <UiButton
          type="button"
          variant="secondary"
          @click="emit('update:modelValue', false)"
          class="text-xs sm:text-sm"
        >
          {{ t('finance.categoryModal.done') }}
        </UiButton>
      </div>
    </template>
  </UiModal>
</template>
