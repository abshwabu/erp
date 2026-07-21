<script setup lang="ts">
import { ref, reactive, watch, computed } from 'vue'
import { useMutation, useQueryClient, useQuery } from '@tanstack/vue-query'
import { inventoryApi } from '@/api/inventory'
import UiModal from '@/components/ui/UiModal.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'

interface Props {
  modelValue: boolean
  productId?: string | number
}

const props = defineProps<Props>()
const emit = defineEmits(['update:modelValue', 'saved'])

const queryClient = useQueryClient()
const errorMsg = ref('')

const form = reactive({
  productId: props.productId || '' as string | number,
  locationId: '' as string | number,
  quantity: 1,
  type: 'add' as 'add' | 'remove',
  reason: 'stock_take',
  notes: ''
})

const { data: products } = useQuery({
  queryKey: ['inventory', 'products-simple'],
  queryFn: () => inventoryApi.getProducts({}, 1).then(res => res.data.data)
})

const { data: locations } = useQuery({
  queryKey: ['inventory', 'locations'],
  queryFn: () => inventoryApi.getLocations().then(res => res.data)
})

const locationOptions = computed(() => {
  if (Array.isArray(locations.value) && locations.value.length > 0) {
    return locations.value.map((loc: any) => ({
      label: `${loc.name} (${loc.code || 'LOC'})`,
      value: loc.id
    }))
  }
  return [{ label: 'Main Warehouse (WH-MAIN)', value: '' }]
})

watch([products, locations, () => props.productId], () => {
  if (props.productId) {
    form.productId = props.productId
  } else if (Array.isArray(products.value) && products.value.length > 0 && !form.productId) {
    const firstProd = products.value[0]
    if (firstProd) form.productId = firstProd.id
  }

  if (Array.isArray(locations.value) && locations.value.length > 0 && !form.locationId) {
    const firstLoc = locations.value[0]
    if (firstLoc) form.locationId = firstLoc.id
  }
}, { immediate: true })

const mutation = useMutation({
  mutationFn: (data: any) => inventoryApi.createStockAdjustment(data),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['inventory', 'stock'] })
    queryClient.invalidateQueries({ queryKey: ['inventory', 'stock-summary'] })
    queryClient.invalidateQueries({ queryKey: ['inventory', 'products'] })
    queryClient.invalidateQueries({ queryKey: ['inventory', 'products-pos'] })
    emit('saved')
    emit('update:modelValue', false)
    errorMsg.value = ''
  },
  onError: (err: any) => {
    errorMsg.value = err.response?.data?.message || 'Failed to apply stock adjustment.'
  }
})

const handleSubmit = () => {
  errorMsg.value = ''
  if (!form.productId) {
    errorMsg.value = 'Please select a product.'
    return
  }
  if (!form.locationId) {
    errorMsg.value = 'Please select a location.'
    return
  }
  if (!form.quantity || form.quantity <= 0) {
    errorMsg.value = 'Quantity must be greater than 0.'
    return
  }

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
      <div v-if="errorMsg" class="p-3 bg-red-50 text-red-700 text-xs font-semibold rounded-lg border border-red-200">
        {{ errorMsg }}
      </div>

      <UiSelect
        v-model="form.productId"
        label="Product"
        :options="Array.isArray(products) ? products.map((p: any) => ({ label: p.name, value: p.id })) : []"
        required
        :disabled="!!productId"
      />

      <UiSelect
        v-model="form.locationId"
        label="Location"
        :options="locationOptions"
        required
      />

      <div class="grid grid-cols-2 gap-4">
        <UiSelect
          v-model="form.type"
          label="Adjustment Type"
          :options="[
            { label: 'Add Stock (+)', value: 'add' },
            { label: 'Remove Stock (-)', value: 'remove' }
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
