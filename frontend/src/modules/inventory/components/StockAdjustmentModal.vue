<script setup lang="ts">
import { reactive, computed } from 'vue'
import { useMutation, useQueryClient, useQuery } from '@tanstack/vue-query'
import { inventoryApi } from '@/api/inventory'
import UiModal from '@/components/ui/UiModal.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import type { StockAdjustment } from '@/types/inventory'

interface Props {
  modelValue: boolean
  productId?: number
}

const props = defineProps<Props>()
const emit = defineEmits(['update:modelValue', 'saved'])

const queryClient = useQueryClient()

const form = reactive<StockAdjustment>({
  productId: props.productId || 0,
  locationId: 0,
  quantity: 0,
  type: 'add',
  reason: '',
  notes: ''
})

const { data: products } = useQuery({
  queryKey: ['inventory', 'products-simple'],
  queryFn: () => inventoryApi.getProducts({}, 1).then(res => res.data.data)
})

// In a real app, we'd fetch locations from an API
const locations = [
  { label: 'Main Warehouse', value: 1 },
  { label: 'Retail Store', value: 2 },
  { label: 'Secondary Warehouse', value: 3 }
]

const mutation = useMutation({
  mutationFn: (data: StockAdjustment) => inventoryApi.createStockAdjustment(data),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['inventory', 'stock'] })
    queryClient.invalidateQueries({ queryKey: ['inventory', 'products'] })
    emit('saved')
    emit('update:modelValue', false)
  }
})

const handleSubmit = () => {
  if (!form.productId || !form.locationId || form.quantity <= 0) return
  mutation.mutate(form)
}
</script>

<template>
  <UiModal
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
    title="Stock Adjustment"
    size="md"
  >
    <form @submit.prevent="handleSubmit" class="space-y-4">
      <UiSelect
        v-model="form.productId"
        label="Product"
        :options="Array.isArray(products) ? products.map(p => ({ label: p.name, value: p.id })) : []"
        required
        :disabled="!!productId"
      />

      <UiSelect
        v-model="form.locationId"
        label="Location"
        :options="locations"
        required
      />

      <div class="grid grid-cols-2 gap-4">
        <UiSelect
          v-model="form.type"
          label="Adjustment Type"
          :options="[
            { label: 'Add Stock', value: 'add' },
            { label: 'Remove Stock', value: 'remove' }
          ]"
        />
        <UiInput
          v-model.number="form.quantity"
          type="number"
          label="Quantity"
          min="1"
          required
        />
      </div>

      <UiSelect
        v-model="form.reason"
        label="Reason"
        :options="[
          { label: 'Stock Take', value: 'stock_take' },
          { label: 'Damaged', value: 'damaged' },
          { label: 'Correction', value: 'correction' },
          { label: 'Return', value: 'return' }
        ]"
        required
      />

      <div class="space-y-1">
        <label class="block text-sm font-medium text-slate-700">Notes</label>
        <textarea
          v-model="form.notes"
          rows="3"
          class="block w-full rounded-md border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
          placeholder="Optional notes..."
        ></textarea>
      </div>
    </form>

    <template #footer>
      <UiButton variant="ghost" @click="emit('update:modelValue', false)" class="mr-2">Cancel</UiButton>
      <UiButton :loading="mutation.isPending.value" @click="handleSubmit">
        Apply Adjustment
      </UiButton>
    </template>
  </UiModal>
</template>
