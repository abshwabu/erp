<script setup lang="ts">
import { ref, reactive, watch, computed } from 'vue'
import { useMutation, useQueryClient, useQuery } from '@tanstack/vue-query'
import { useI18n } from 'vue-i18n'
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

const { t } = useI18n()
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

const { data: directProduct } = useQuery({
  queryKey: ['inventory', 'product-detail-modal', computed(() => form.productId)],
  queryFn: () => form.productId ? inventoryApi.getProduct(form.productId).then(res => res.data?.data || res.data) : Promise.resolve(null),
  enabled: computed(() => !!form.productId)
})

const selectedProduct = computed(() => {
  if (!Array.isArray(products.value) || !form.productId) return null
  return products.value.find((p: any) => String(p.id) === String(form.productId))
})

const availableVariants = computed(() => {
  const prod = directProduct.value || selectedProduct.value
  return Array.isArray(prod?.variants) ? prod.variants : []
})

const hasVariants = computed(() => {
  const prod = directProduct.value || selectedProduct.value
  return !!(prod?.has_variants || (Array.isArray(availableVariants.value) && availableVariants.value.length > 0))
})

const variantOptions = computed(() => {
  return availableVariants.value.map((v: any) => ({
    label: `${v.name || v.sku || `Variant ${v.id}`}`,
    value: v.id
  }))
})

// Auto-select first variant on product select
watch([availableVariants, () => props.variantId], ([newVariants, propVarId]) => {
  if (propVarId) {
    form.variantId = propVarId
    return
  }
  if (Array.isArray(newVariants) && newVariants.length > 0) {
    const exists = newVariants.some((v: any) => String(v.id) === String(form.variantId))
    if (!exists && newVariants[0]) {
      form.variantId = newVariants[0].id
    }
  } else {
    form.variantId = ''
  }
}, { immediate: true })

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
        label: `${loc.name} (${qty} ${t('inventory.inStock')})`,
        value: loc.id
      }
    })
  }
  return [{ label: `${t('inventory.mainWarehouse')} (WH-MAIN)`, value: '' }]
})

const typeOptions = computed(() => [
  { label: t('inventory.addStock'), value: 'add' },
  { label: t('inventory.removeStock'), value: 'remove' }
])

const reasonOptions = computed(() => [
  { label: t('inventory.reasons.stock_take'), value: 'stock_take' },
  { label: t('inventory.reasons.damage'), value: 'damaged' },
  { label: t('inventory.reasons.correction'), value: 'correction' },
  { label: t('inventory.reasons.loss'), value: 'loss' },
  { label: t('inventory.reasons.internal'), value: 'internal' },
  { label: t('inventory.reasons.other'), value: 'other' }
])

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
    errorMsg.value = err?.response?.data?.message || err?.message || 'Failed to apply stock adjustment.'
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
    :title="$t('inventory.adjustmentModalTitle')"
    size="md"
  >
    <form @submit.prevent="handleSubmit" class="space-y-4">
      <div v-if="errorMsg" class="p-3 bg-red-50 text-red-700 text-xs font-semibold rounded-lg border border-red-200">
        {{ errorMsg }}
      </div>

      <UiSelect
        v-model="form.productId"
        :label="$t('inventory.products')"
        :options="Array.isArray(products) ? products.map((p: any) => ({ label: p.name, value: p.id })) : []"
        required
        :disabled="!!productId"
      />

      <UiSelect
        v-if="hasVariants"
        v-model="form.variantId"
        :label="$t('inventory.variantInfo.variantName')"
        :options="variantOptions"
        required
      />

      <div class="space-y-1">
        <UiSelect
          v-model="form.locationId"
          :label="$t('inventory.warehouse')"
          :options="locationOptions"
          required
        />
        <div v-if="form.locationId" class="flex items-center gap-1.5 text-xs text-slate-500 pt-1">
          <Info class="w-3.5 h-3.5 text-blue-500 shrink-0" />
          <span>{{ $t('inventory.currentStock', { count: currentLocationStock }) }} ({{ totalStockAllLocations }} {{ $t('inventory.inStock') }})</span>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <UiSelect
          v-model="form.type"
          :label="$t('inventory.adjustmentType')"
          :options="typeOptions"
        />
        <UiInput
          v-model.number="form.quantity"
          type="number"
          :label="$t('inventory.quantity')"
          min="1"
          required
        />
      </div>

      <UiSelect
        v-model="form.reason"
        :label="$t('inventory.adjustmentReason')"
        :options="reasonOptions"
        required
      />

      <div class="space-y-1">
        <label class="block text-sm font-medium text-slate-700">{{ $t('inventory.reference') }}</label>
        <textarea
          v-model="form.notes"
          rows="3"
          class="block w-full rounded-md border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
          :placeholder="$t('inventory.notesPlaceholder')"
        ></textarea>
      </div>
    </form>

    <template #footer>
      <UiButton variant="ghost" @click="emit('update:modelValue', false)" class="mr-2">{{ $t('inventory.cancel') }}</UiButton>
      <UiButton :loading="mutation.isPending.value" @click="handleSubmit">
        {{ $t('inventory.adjustStock') }}
      </UiButton>
    </template>
  </UiModal>
</template>
