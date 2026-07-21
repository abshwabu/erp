<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { inventoryApi } from '@/api/inventory'
import UiModal from '@/components/ui/UiModal.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import { Tag } from '@lucide/vue'

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
    error.value = err.response?.data?.message || 'Failed to create category.'
  }
})

function handleSubmit() {
  if (!form.name.trim()) {
    error.value = 'Category name is required'
    return
  }
  error.value = ''
  const slug = form.name.trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '')
  mutation.mutate({ name: form.name.trim(), slug, description: form.description.trim() })
}
</script>

<template>
  <UiModal
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
    title="Create Product Category"
    size="md"
  >
    <form @submit.prevent="handleSubmit" class="space-y-4">
      <div v-if="error" class="p-3 bg-red-50 text-red-700 text-xs font-semibold rounded-lg border border-red-200">
        {{ error }}
      </div>

      <UiInput
        v-model="form.name"
        label="Category Name"
        placeholder="e.g. Electronics, Beverages, Clothing"
        required
      />

      <div class="space-y-1">
        <label class="block text-sm font-medium text-slate-700">Description (Optional)</label>
        <textarea
          v-model="form.description"
          rows="3"
          placeholder="Brief description of this category..."
          class="block w-full rounded-lg border border-slate-300 shadow-xs focus:border-blue-500 focus:ring-blue-500 text-sm p-2.5"
        ></textarea>
      </div>
    </form>

    <template #footer>
      <UiButton variant="ghost" @click="emit('update:modelValue', false)" class="mr-2">Cancel</UiButton>
      <UiButton @click="handleSubmit" :loading="mutation.isPending.value">
        Save Category
      </UiButton>
    </template>
  </UiModal>
</template>
