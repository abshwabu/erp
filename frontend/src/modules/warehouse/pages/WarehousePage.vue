<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { useI18n } from 'vue-i18n'
import { ArrowRightLeft, MapPin, PackagePlus, Plus } from '@lucide/vue'
import { inventoryApi } from '@/api/inventory'
import { warehouseApi } from '@/api/warehouse'
import { useToast } from '@/composables/useToast'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiCard from '@/components/ui/UiCard.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiTable from '@/components/ui/UiTable.vue'

type TabId = 'locations' | 'receive' | 'transfer'

const { t, te } = useI18n()
const toast = useToast()
const queryClient = useQueryClient()
const activeTab = ref<TabId>('locations')

const locationForm = reactive({
  name: '',
  code: '',
  type: 'storage',
})

const receiveForm = reactive({
  product_id: '' as string,
  variant_id: '' as string,
  location_id: '' as string,
  quantity: 1,
  unit_cost: 0,
})

const transferForm = reactive({
  product_id: '' as string,
  variant_id: '' as string,
  from_location_id: '' as string,
  to_location_id: '' as string,
  quantity: 1,
})

const { data: locations, isLoading: locationsLoading } = useQuery({
  queryKey: ['warehouse', 'locations'],
  queryFn: () => warehouseApi.getLocations().then(res => res.data),
})

const { data: products } = useQuery({
  queryKey: ['inventory', 'products-simple'],
  queryFn: () => inventoryApi.getProducts({ status: 'active' }, 1).then(res => res.data.data),
})

const productOptions = computed(() =>
  (Array.isArray(products.value) ? products.value : []).map((p: any) => ({
    label: `${p.name}${p.sku ? ` (${p.sku})` : ''}`,
    value: p.id,
  }))
)

const locationOptions = computed(() =>
  (Array.isArray(locations.value) ? locations.value : []).map((loc: any) => ({
    label: `${loc.name} (${loc.code})`,
    value: loc.id,
  }))
)

// Variants for receive
const selectedReceiveProduct = computed(() => {
  if (!Array.isArray(products.value) || !receiveForm.product_id) return null
  return products.value.find((p: any) => String(p.id) === String(receiveForm.product_id))
})

const receiveHasVariants = computed(() => {
  const p = selectedReceiveProduct.value as any
  return !!(p?.has_variants || (Array.isArray(p?.variants) && p.variants.length > 0))
})

const receiveVariantOptions = computed(() => {
  const p = selectedReceiveProduct.value as any
  if (!p || !Array.isArray(p.variants)) return []
  return p.variants.map((v: any) => ({
    label: `${v.name || v.sku || `Variant ${v.id}`}`,
    value: v.id,
  }))
})

// Auto-select first variant on product select for receive
watch(selectedReceiveProduct, (prod: any) => {
  if (prod && Array.isArray(prod.variants) && prod.variants.length > 0) {
    receiveForm.variant_id = prod.variants[0].id
  } else {
    receiveForm.variant_id = ''
  }
})

// Variants for transfer
const selectedTransferProduct = computed(() => {
  if (!Array.isArray(products.value) || !transferForm.product_id) return null
  return products.value.find((p: any) => String(p.id) === String(transferForm.product_id))
})

const transferHasVariants = computed(() => {
  const p = selectedTransferProduct.value as any
  return !!(p?.has_variants || (Array.isArray(p?.variants) && p.variants.length > 0))
})

const transferVariantOptions = computed(() => {
  const p = selectedTransferProduct.value as any
  if (!p || !Array.isArray(p.variants)) return []
  return p.variants.map((v: any) => ({
    label: `${v.name || v.sku || `Variant ${v.id}`}`,
    value: v.id,
  }))
})

// Auto-select first variant on product select for transfer
watch(selectedTransferProduct, (prod: any) => {
  if (prod && Array.isArray(prod.variants) && prod.variants.length > 0) {
    transferForm.variant_id = prod.variants[0].id
  } else {
    transferForm.variant_id = ''
  }
})

// Live stock at source location
const { data: transferProductStock } = useQuery({
  queryKey: ['inventory', 'product-stock-transfer', computed(() => transferForm.product_id)],
  queryFn: () => transferForm.product_id ? inventoryApi.getProductStock(transferForm.product_id).then(res => res.data) : Promise.resolve([]),
  enabled: computed(() => !!transferForm.product_id)
})

const transferAvailableAtSource = computed(() => {
  if (!Array.isArray(transferProductStock.value) || !transferForm.from_location_id) return null
  const match = transferProductStock.value.find((sl: any) => {
    const locMatch = String(sl.location_id) === String(transferForm.from_location_id)
    const varMatch = transferForm.variant_id ? String(sl.variant_id) === String(transferForm.variant_id) : true
    return locMatch && varMatch
  })
  return match ? (match.available_quantity ?? match.quantity_on_hand ?? 0) : 0
})

const locationColumns = computed(() => [
  { key: 'name', label: t('warehouse.tableColumns.name') },
  { key: 'code', label: t('warehouse.tableColumns.code') },
  { key: 'type', label: t('warehouse.tableColumns.type') },
  { key: 'warehouse', label: t('warehouse.tableColumns.warehouse') },
  { key: 'status', label: t('warehouse.tableColumns.status'), align: 'center' as const },
])

const locationRows = computed(() =>
  (Array.isArray(locations.value) ? locations.value : []).map((loc: any) => ({
    id: loc.id,
    name: loc.name,
    code: loc.code,
    type: loc.type,
    warehouse: loc.warehouse?.name || '—',
    isActive: Boolean(loc.is_active),
  }))
)

function invalidateStockQueries() {
  queryClient.invalidateQueries({ queryKey: ['warehouse', 'locations'] })
  queryClient.invalidateQueries({ queryKey: ['inventory', 'locations'] })
  queryClient.invalidateQueries({ queryKey: ['inventory', 'stock'] })
  queryClient.invalidateQueries({ queryKey: ['inventory', 'stock-summary'] })
  queryClient.invalidateQueries({ queryKey: ['inventory', 'product-stock'] })
  queryClient.invalidateQueries({ queryKey: ['inventory', 'products'] })
  queryClient.invalidateQueries({ queryKey: ['shops'] })
}

const createLocationMutation = useMutation({
  mutationFn: () =>
    warehouseApi.createLocation({
      name: locationForm.name.trim(),
      code: locationForm.code.trim().toUpperCase(),
      type: locationForm.type || 'storage',
    }),
  onSuccess: () => {
    locationForm.name = ''
    locationForm.code = ''
    locationForm.type = 'storage'
    invalidateStockQueries()
    toast.success(t('warehouse.locationCreatedSuccess'))
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || t('warehouse.locationCreatedFailed'))
  },
})

const receiveMutation = useMutation({
  mutationFn: () =>
    warehouseApi.receive({
      product_id: receiveForm.product_id,
      location_id: receiveForm.location_id,
      quantity: Number(receiveForm.quantity),
      unit_cost: Number(receiveForm.unit_cost) || 0,
      variant_id: receiveForm.variant_id ? String(receiveForm.variant_id) : undefined,
    }),
  onSuccess: () => {
    receiveForm.quantity = 1
    receiveForm.unit_cost = 0
    invalidateStockQueries()
    toast.success(t('warehouse.stockReceivedSuccess'))
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || err?.message || t('warehouse.stockReceivedFailed'))
  },
})

const transferMutation = useMutation({
  mutationFn: () =>
    warehouseApi.transfer({
      product_id: transferForm.product_id,
      from_location_id: transferForm.from_location_id,
      to_location_id: transferForm.to_location_id,
      quantity: Number(transferForm.quantity),
      variant_id: transferForm.variant_id ? String(transferForm.variant_id) : undefined,
    }),
  onSuccess: () => {
    transferForm.quantity = 1
    invalidateStockQueries()
    toast.success(t('warehouse.stockTransferredSuccess'))
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || err?.message || t('warehouse.stockTransferredFailed'))
  },
})

function submitLocation() {
  if (!locationForm.name.trim() || !locationForm.code.trim()) {
    toast.error(t('warehouse.nameAndCodeRequired'))
    return
  }
  createLocationMutation.mutate()
}

function submitReceive() {
  if (!receiveForm.product_id || !receiveForm.location_id) {
    toast.error(t('warehouse.productAndLocationRequired'))
    return
  }
  if (receiveHasVariants.value && !receiveForm.variant_id) {
    toast.error(t('warehouse.selectVariantRequired'))
    return
  }
  if (!receiveForm.quantity || receiveForm.quantity < 1) {
    toast.error(t('warehouse.quantityMinOne'))
    return
  }
  receiveMutation.mutate()
}

function submitTransfer() {
  if (!transferForm.product_id || !transferForm.from_location_id || !transferForm.to_location_id) {
    toast.error(t('warehouse.productAndBothLocationsRequired'))
    return
  }
  if (transferHasVariants.value && !transferForm.variant_id) {
    toast.error(t('warehouse.selectVariantRequired'))
    return
  }
  if (transferForm.from_location_id === transferForm.to_location_id) {
    toast.error(t('warehouse.locationsMustDiffer'))
    return
  }
  if (!transferForm.quantity || transferForm.quantity < 1) {
    toast.error(t('warehouse.quantityMinOne'))
    return
  }
  if (transferAvailableAtSource.value !== null && transferForm.quantity > transferAvailableAtSource.value) {
    toast.error(t('warehouse.insufficientStockAtSource', { count: transferAvailableAtSource.value }))
    return
  }
  transferMutation.mutate()
}

const tabs = computed(() => [
  { id: 'locations' as const, label: t('warehouse.tabs.locations'), icon: MapPin },
  { id: 'receive' as const, label: t('warehouse.tabs.receive'), icon: PackagePlus },
  { id: 'transfer' as const, label: t('warehouse.tabs.transfer'), icon: ArrowRightLeft },
])

const typeOptions = computed(() => [
  { label: t('warehouse.locationTypes.storage'), value: 'storage' },
  { label: t('warehouse.locationTypes.receive'), value: 'receive' },
  { label: t('warehouse.locationTypes.pick'), value: 'pick' },
  { label: t('warehouse.locationTypes.pack'), value: 'pack' },
  { label: t('warehouse.locationTypes.stage'), value: 'stage' },
  { label: t('warehouse.locationTypes.ship'), value: 'ship' },
])

const formatLocationType = (type: string) => {
  const key = `warehouse.locationTypes.${type}`
  if (te(key)) return t(key)
  return type
}
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-slate-900">{{ $t('warehouse.warehouse') }}</h1>
      <p class="text-slate-500 text-sm">{{ $t('warehouse.warehouseSubtitle') }}</p>
    </div>

    <div class="flex border-b border-slate-200">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        type="button"
        class="inline-flex items-center gap-2 px-6 py-3 text-sm font-medium border-b-2 transition-colors cursor-pointer"
        :class="activeTab === tab.id ? 'border-primary-600 text-primary-600' : 'border-transparent text-slate-500 hover:text-slate-700'"
        @click="activeTab = tab.id"
      >
        <component :is="tab.icon" class="h-4 w-4" />
        {{ tab.label }}
      </button>
    </div>

    <!-- Locations -->
    <div v-if="activeTab === 'locations'" class="space-y-6">
      <UiCard :title="$t('warehouse.createLocation')">
        <form class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end" @submit.prevent="submitLocation">
          <UiInput v-model="locationForm.name" :label="$t('warehouse.nameLabel')" required :placeholder="$t('warehouse.namePlaceholder')" />
          <UiInput v-model="locationForm.code" :label="$t('warehouse.codeLabel')" required :placeholder="$t('warehouse.codePlaceholder')" />
          <UiSelect v-model="locationForm.type" :label="$t('warehouse.typeLabel')" :options="typeOptions" />
          <UiButton type="submit" :disabled="createLocationMutation.isPending.value">
            <Plus class="h-4 w-4 mr-2" />
            {{ $t('warehouse.addLocationBtn') }}
          </UiButton>
        </form>
      </UiCard>

      <UiTable
        :columns="locationColumns"
        :data="locationRows"
        :loading="locationsLoading"
        :empty-title="$t('warehouse.noLocationsTitle')"
        :empty-description="$t('warehouse.noLocationsDescription')"
      >
        <template #cell(name)="{ item }">
          <span class="font-medium text-slate-900">{{ item.name }}</span>
        </template>
        <template #cell(code)="{ value }">
          <code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded">{{ value }}</code>
        </template>
        <template #cell(type)="{ value }">
          <span class="capitalize text-slate-600">{{ formatLocationType(value) }}</span>
        </template>
        <template #cell(status)="{ item }">
          <UiBadge :variant="item.isActive ? 'success' : 'default'">
            {{ item.isActive ? $t('warehouse.active') : $t('warehouse.inactive') }}
          </UiBadge>
        </template>
      </UiTable>
    </div>

    <!-- Receive -->
    <div v-else-if="activeTab === 'receive'">
      <UiCard :title="$t('warehouse.receiveStockTitle')">
        <form class="space-y-4 max-w-xl" @submit.prevent="submitReceive">
          <UiSelect
            v-model="receiveForm.product_id"
            :label="$t('warehouse.productLabel')"
            required
            :options="productOptions"
          />
          <UiSelect
            v-if="receiveHasVariants"
            v-model="receiveForm.variant_id"
            :label="$t('warehouse.variantLabel')"
            required
            :options="receiveVariantOptions"
          />
          <UiSelect
            v-model="receiveForm.location_id"
            :label="$t('warehouse.locationLabel')"
            required
            :options="locationOptions"
          />
          <div class="grid grid-cols-2 gap-4">
            <UiInput
              v-model.number="receiveForm.quantity"
              :label="$t('warehouse.quantityLabel')"
              type="number"
              min="1"
              required
            />
            <UiInput
              v-model.number="receiveForm.unit_cost"
              :label="$t('warehouse.unitCostLabel')"
              type="number"
              min="0"
              :help-text="$t('warehouse.unitCostHelp')"
            />
          </div>
          <UiButton type="submit" :disabled="receiveMutation.isPending.value">
            <PackagePlus class="h-4 w-4 mr-2" />
            {{ $t('warehouse.receiveStockBtn') }}
          </UiButton>
        </form>
      </UiCard>
    </div>

    <!-- Transfer -->
    <div v-else>
      <UiCard :title="$t('warehouse.transferTitle')">
        <form class="space-y-4 max-w-xl" @submit.prevent="submitTransfer">
          <UiSelect
            v-model="transferForm.product_id"
            :label="$t('warehouse.productLabel')"
            required
            :options="productOptions"
          />
          <UiSelect
            v-if="transferHasVariants"
            v-model="transferForm.variant_id"
            :label="$t('warehouse.variantLabel')"
            required
            :options="transferVariantOptions"
          />
          <div class="space-y-1">
            <UiSelect
              v-model="transferForm.from_location_id"
              :label="$t('warehouse.fromLocationLabel')"
              required
              :options="locationOptions"
            />
            <div
              v-if="transferForm.from_location_id && transferAvailableAtSource !== null"
              class="text-xs text-slate-500 font-medium px-1 flex items-center gap-1.5"
            >
              <span>{{ $t('warehouse.availableAtSource') }}</span>
              <UiBadge :variant="(transferAvailableAtSource || 0) > 0 ? 'success' : 'danger'">
                {{ $t('warehouse.unitsCount', { count: transferAvailableAtSource }) }}
              </UiBadge>
            </div>
          </div>
          <UiSelect
            v-model="transferForm.to_location_id"
            :label="$t('warehouse.toLocationLabel')"
            required
            :options="locationOptions"
          />
          <UiInput
            v-model.number="transferForm.quantity"
            :label="$t('warehouse.quantityLabel')"
            type="number"
            min="1"
            required
          />
          <UiButton type="submit" :disabled="transferMutation.isPending.value">
            <ArrowRightLeft class="h-4 w-4 mr-2" />
            {{ $t('warehouse.transferStockBtn') }}
          </UiButton>
        </form>
      </UiCard>
    </div>
  </div>
</template>
