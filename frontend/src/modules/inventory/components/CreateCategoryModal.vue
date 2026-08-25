<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { inventoryApi } from '@/api/inventory'
import UiModal from '@/components/ui/UiModal.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import { Tag, Sparkles, AlertCircle, Layers, FolderPlus } from '@lucide/vue'

const props = defineProps({
  modelValue: { type: Boolean, required: true }
})

const emit = defineEmits(['update:modelValue', 'created'])
const queryClient = useQueryClient()

const form = reactive({
  name: '',
  description: ''
})

const error = ref('')

const generatedSlug = computed(() => {
  if (!form.name) return ''
  return form.name
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/(^-|-$)/g, '')
})

const mutation = useMutation({
  mutationFn: (data: { name: string; slug?: string; description?: string }) => inventoryApi.createCategory(data),
  onSuccess: (res) => {
    queryClient.invalidateQueries({ queryKey: ['inventory', 'categories'] })
    const createdCat = res.data?.data || res.data
    emit('created', createdCat)
    emit('update:modelValue', false)
    form.name = ''
    form.description = ''
    error.value = ''
  },
  onError: (err: any) => {
    error.value = err.response?.data?.message || 'Failed to create category. Please check the name and try again.'
  }
})

function handleSubmit() {
  if (!form.name.trim()) {
    error.value = 'Please provide a category name'
    return
  }
  error.value = ''
  mutation.mutate({
    name: form.name.trim(),
    slug: generatedSlug.value,
    description: form.description.trim()
  })
}
</script>

<template>
  <UiModal
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
    title="Create Product Category"
    size="md"
  >
    <form @submit.prevent="handleSubmit" class="space-y-5 py-1">
      <!-- Header Banner -->
      <div class="flex items-center space-x-3 p-3 bg-blue-50/70 border border-blue-100/80 rounded-xl">
        <div class="p-2 bg-blue-600 text-white rounded-lg shadow-xs">
          <FolderPlus class="w-5 h-5" />
        </div>
        <div>
          <h4 class="text-xs font-bold text-blue-950 uppercase tracking-wide">Category Classification</h4>
          <p class="text-xs text-blue-700/80">Group products together for organized POS checkouts and ecommerce collections.</p>
        </div>
      </div>

      <!-- Error Alert -->
      <div v-if="error" class="p-3 bg-red-50 text-red-700 text-xs font-semibold rounded-xl border border-red-200 flex items-center gap-2">
        <AlertCircle class="w-4 h-4 shrink-0 text-red-500" />
        <span>{{ error }}</span>
      </div>

      <!-- Category Name Input -->
      <div class="space-y-1.5">
        <UiInput
          v-model="form.name"
          label="Category Name"
          placeholder="e.g. Beverages, Footwear, Electronics, Fresh Produce"
          required
        />
        <div v-if="generatedSlug" class="flex items-center space-x-1.5 text-[11px] text-slate-400 font-mono pl-1">
          <span>Slug Preview:</span>
          <span class="text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded">{{ generatedSlug }}</span>
        </div>
      </div>

      <!-- Description Input -->
      <div class="space-y-1.5">
        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">Description (Optional)</label>
        <textarea
          v-model="form.description"
          rows="3"
          placeholder="Add short notes or description for this product group..."
          class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white text-sm p-3 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 transition-colors shadow-2xs"
        ></textarea>
      </div>
    </form>

    <template #footer>
      <div class="flex items-center justify-end space-x-2 pt-2">
        <UiButton variant="ghost" size="sm" @click="emit('update:modelValue', false)">
          Cancel
        </UiButton>
        <UiButton
          type="button"
          size="sm"
          @click="handleSubmit"
          :loading="mutation.isPending.value"
          class="shadow-sm"
        >
          <Tag class="w-3.5 h-3.5 mr-1.5" />
          Create Category
        </UiButton>
      </div>
    </template>
  </UiModal>
</template>
