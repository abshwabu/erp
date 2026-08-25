<script setup lang="ts">
import { ref, reactive, watch, computed } from 'vue'
import { useMutation, useQueryClient, useQuery } from '@tanstack/vue-query'
import { inventoryApi } from '@/api/inventory'
import UiModal from '@/components/ui/UiModal.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import { Info } from '@lucide/vue'

interface Props {
  modelValue: boolean
  productId?: string
  locationId?: string
  variantId?: string
}

const props = defineProps<Props>()
const emit = defineEmits(['update:modelValue', 'saved'])

const queryClient = useQueryClient()
const errorMsg = ref('')

const form = reactive({
  productId: props.productId || '' as string,
  variantId: props.variantId || '' as string,
  locationId: props.locationId || '' as string,
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

const { data: productStockLevels } = useQuery({
  queryKey: ['inventory', 'product-stock', computed(() => form.productId)],
  queryFn: () => form.productId ? inventoryApi.getProductStock(form.productId).then(res => res.data) : Promise.resolve([]),
  enabled: computed(() => !!form.productId)
})

const selectedProduct = computed(() => {
  if (!Array.isArray(products.value) || !form.productId) return null
  return products.value.find((p: any) => String(p.id) === String(form.productId))
})

const hasVariants = computed(() => {
  const prod = selectedProduct.value as any
  return !!(prod?.has_variants || prod?.hasVariants)
})

const variantOptions = computed(() => {
  const prod = selectedProduct.value as any
  if (!prod || !Array.isArray(prod.variants)) return []
  return prod.variants.map((v: any) => ({
    label: `${v.name || v.sku || `Variant ${v.id}`}`,
    value: v.id
  }))
})

// Auto-select first variant on product select
watch(selectedProduct, (newProduct: any) => {
  if (newProduct && Array.isArray(newProduct.variants) && newProduct.variants.length > 0) {
    // only change if current variant is not in list
    const exists = newProduct.variants.some((v: any) => String(v.id) === String(form.variantId))
    if (!exists && newProduct.variants[0]) {
      form.variantId = newProduct.variants[0].id
    }
  } else {
    form.variantId = ''
  }
})

const currentLocationStock = computed(() => {
  if (!Array.isArray(productStockLevels.value) || !form.locationId) return 0
  const match = productStockLevels.value.find((l: any) => {
    const locMatch = String(l.location_id) === String(form.locationId)
    const varMatch = form.variantId ? String(l.variant_id) === String(form.variantId) : true
    return locMatch && varMatch
  })
  return match ? (match.available_quantity ?? match.quantity_on_hand ?? 0) : 0
})

const totalStockAllLocations = computed(() => {
  if (!Array.isArray(productStockLevels.value)) return 0
  const filtered = form.variantId 
    ? productStockLevels.value.filter((l: any) => String(l.variant_id) === String(form.variantId))
    : productStockLevels.value
  return filtered.reduce((sum: number, l: any) => sum + (l.available_quantity ?? l.quantity_on_hand ?? 0), 0)
})

const locationOptions = computed(() => {
  if (Array.isArray(locations.value) && locations.value.length > 0) {
    return locations.value.map((loc: any) => {
      const level = Array.isArray(productStockLevels.value)
        ? productStockLevels.value.find((l: any) => {
            const locMatch = String(l.location_id) === String(loc.id)
            const varMatch = form.variantId ? String(l.variant_id) === String(form.variantId) : true
            return locMatch && varMatch
          })
        : null
      const qty = level ? (level.available_quantity ?? level.quantity_on_hand ?? 0) : 0
      return {
        label: `${loc.name} (${qty} in stock)`,
        value: loc.id
      }
    })
  }
  return [{ label: 'Main Warehouse (WH-MAIN)', value: '' }]
})

watch([products, locations, productStockLevels, () => props.productId, () => props.locationId, () => props.variantId], () => {
  if (props.productId) {
    form.productId = props.productId
  } else if (Array.isArray(products.value) && products.value.length > 0 && !form.productId) {
    const firstProd = products.value[0]
    if (firstProd) form.productId = firstProd.id
  }

  if (props.locationId) {
    form.locationId = props.locationId
  } else if (Array.isArray(locations.value) && locations.value.length > 0 && !form.locationId) {
    if (Array.isArray(productStockLevels.value) && productStockLevels.value.length > 0) {
      const sorted = [...productStockLevels.value].sort((a: any, b: any) => (b.available_quantity || 0) - (a.available_quantity || 0))
      if (sorted[0]?.location_id) {
        form.locationId = sorted[0].location_id
        return
      }
    }
    const firstLoc = locations.value[0]
    if (firstLoc) form.locationId = firstLoc.id
  }

  if (props.variantId) {
    form.variantId = props.variantId
  }
}, { immediate: true })

const mutation = useMutation({
  mutationFn: () => inventoryApi.createStockAdjustment({
    product_id: String(form.productId),
    location_id: String(form.locationId),
    quantity: Math.abs(Number(form.quantity)),
    type: form.type,
    reason: form.reason,
    notes: form.notes || undefined,
    variant_id: form.variantId ? String(form.variantId) : undefined,
  }),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['inventory'] })
    emit('saved')
    emit('update:modelValue', false)
    errorMsg.value = ''
  },
  onError: (err: any) => {
    errorMsg.value = err?.message || err.response?.data?.message || 'Failed to apply stock adjustment.'
  }
})

const handleSubmit = () => {
  errorMsg.value = ''
  if (!form.productId) {
    errorMsg.value = 'Please select a product.'
    return
  }
  if (hasVariants.value && !form.variantId) {
    errorMsg.value = 'Please select a product variant.'
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

  if (form.type === 'remove' && form.quantity > currentLocationStock.value) {
    errorMsg.value = `Cannot remove ${form.quantity} units. Only ${currentLocationStock.value} unit(s) available at the selected location!`
    return
  }

  mutation.mutate()
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
        v-slot:default
        v-if="hasVariants"
        v-model="form.variantId"
        label="Variant"
        :options="variantOptions"
        required
      />

      <div class="space-y-1">
        <UiSelect
          v-model="form.locationId"
          label="Location"
          :options="locationOptions"
          required
        />
        <div v-if="form.locationId" class="flex items-center gap-1.5 text-xs text-slate-500 pt-1">
          <Info class="w-3.5 h-3.5 text-blue-500 shrink-0" />
          <span>Selected Location Stock: <strong class="text-slate-900 font-mono">{{ currentLocationStock }} units</strong> (Total across all locations: {{ totalStockAllLocations }} units)</span>
        </div>
      </div>

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
