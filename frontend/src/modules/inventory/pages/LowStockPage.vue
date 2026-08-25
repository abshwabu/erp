<script setup lang="ts">
import { ref, computed } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import { inventoryApi } from '@/api/inventory'
import { AlertTriangle, Package, Settings2, RefreshCw, Search, Plus, Warehouse as WarehouseIcon, MapPin, Filter } from '@lucide/vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiTable from '@/components/ui/UiTable.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiAlert from '@/components/ui/UiAlert.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import StockAdjustmentModal from '../components/StockAdjustmentModal.vue'
import type { LowStockItem } from '@/types/inventory'

const isAdjustmentModalOpen = ref(false)
const selectedProductId = ref<string | undefined>(undefined)
const selectedLocationId = ref<string | undefined>(undefined)
const selectedVariantId = ref<string | undefined>(undefined)
const search = ref('')
const filterLocation = ref('')

const openAdjustment = (productId?: string, locationId?: string, variantId?: string) => {
  selectedProductId.value = productId
  selectedLocationId.value = locationId
  selectedVariantId.value = variantId
  isAdjustmentModalOpen.value = true
}

const { data: locations } = useQuery({
  queryKey: ['inventory', 'locations'],
  queryFn: () => inventoryApi.getLocations().then((res) => res.data),
})

const { data, isLoading, refetch } = useQuery({
  queryKey: ['inventory', 'low-stock'],
  queryFn: () => inventoryApi.getLowStockProducts().then((res) => res.data),
})

const locationFilterOptions = computed(() => {
  const list = Array.isArray(locations.value) ? locations.value : []
  return [
    { label: 'All Warehouses & Locations', value: '' },
    ...list.map((loc: any) => ({
      label: `${loc.name} (${loc.code || 'LOC'})`,
      value: loc.id,
    }))
  ]
})

const rows = computed(() => {
  const list = Array.isArray(data.value) ? (data.value as LowStockItem[]) : []
  return list.map((item) => {
    const minQty = item.min_quantity ?? 0
    const available = item.available_quantity ?? 0
    return {
      ...item,
      shortfall: Math.max(0, minQty - available),
    }
  })
})

const filteredData = computed(() => {
  const term = search.value.trim().toLowerCase()
  const locFilter = filterLocation.value

  return rows.value.filter((item) => {
    const matchesSearch = !term ||
      item.product_name.toLowerCase().includes(term) ||
      item.sku.toLowerCase().includes(term) ||
      (item.warehouse_name || '').toLowerCase().includes(term) ||
      (item.location_name || '').toLowerCase().includes(term)

    const matchesLocation = !locFilter || String(item.location_id) === String(locFilter)

    return matchesSearch && matchesLocation
  })
})

const warehouseCount = computed(() => {
  const unique = new Set(filteredData.value.map(i => i.warehouse_name || i.location_name).filter(Boolean))
  return unique.size
})

const columns = [
  { key: 'product', label: 'Product & Variant' },
  { key: 'sku', label: 'SKU' },
  { key: 'warehouse', label: 'Warehouse' },
  { key: 'location_name', label: 'Stock Location' },
  { key: 'available_quantity', label: 'In-Store Stock', align: 'center' as const },
  { key: 'min_quantity', label: 'Min. Level', align: 'center' as const },
  { key: 'shortfall', label: 'Shortfall', align: 'center' as const },
  { key: 'actions', label: '', align: 'right' as const },
]
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Low Stock Alert</h1>
        <p class="text-slate-500 text-sm">
          Inventory levels evaluated separately for each warehouse and store location.
        </p>
      </div>
      <UiButton size="sm" @click="openAdjustment()">
        <Plus class="h-4 w-4 mr-2" /> Adjust Stock
      </UiButton>
    </div>

    <UiAlert v-if="filteredData.length > 0" variant="warning" class="shadow-sm">
      <div class="flex items-center gap-2">
        <AlertTriangle class="h-4 w-4 text-amber-600 shrink-0" />
        <span>
          <strong>{{ filteredData.length }}</strong> item(s) across <strong>{{ warehouseCount }}</strong> warehouse/location(s) need restocking or stock transfer.
        </span>
      </div>
    </UiAlert>

    <!-- Filters Bar -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
      <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1">
        <div class="w-full sm:w-72">
          <UiInput v-model="search" placeholder="Search product, variant or SKU...">
            <template #prefix><Search class="h-4 w-4 text-slate-400" /></template>
          </UiInput>
        </div>

        <div class="w-full sm:w-64">
          <UiSelect
            v-model="filterLocation"
            :options="locationFilterOptions"
            placeholder="Filter by Warehouse / Location"
          />
        </div>
      </div>

      <UiButton variant="ghost" size="sm" @click="refetch">
        <RefreshCw class="h-4 w-4 mr-2" :class="{ 'animate-spin': isLoading }" /> Refresh
      </UiButton>
    </div>

    <UiTable
      :columns="columns"
      :data="filteredData"
      :loading="isLoading"
      empty-title="No low stock alerts"
      empty-description="All warehouse and store stock levels look healthy."
    >
      <template #cell(product)="{ item }">
        <div class="flex items-center">
          <div class="h-8 w-8 rounded-lg bg-red-50 flex items-center justify-center mr-3 shrink-0 border border-red-100">
            <Package class="h-4 w-4 text-red-500" />
          </div>
          <div>
            <div class="font-semibold text-slate-900 leading-snug">{{ item.product_name }}</div>
            <div v-if="item.variant_name" class="text-xs text-blue-600 font-medium">Variant: {{ item.variant_name }}</div>
          </div>
        </div>
      </template>

      <template #cell(sku)="{ value }">
        <span class="font-mono text-xs text-slate-600 font-medium">{{ value }}</span>
      </template>

      <template #cell(warehouse)="{ item }">
        <div class="flex items-center gap-1.5 text-xs text-slate-700 font-medium">
          <WarehouseIcon class="h-3.5 w-3.5 text-slate-400 shrink-0" />
          <span>{{ item.warehouse_name || 'Main Warehouse' }}</span>
        </div>
      </template>

      <template #cell(location_name)="{ value }">
        <span class="text-xs text-slate-600 font-medium px-2 py-0.5 bg-slate-100 rounded-md border border-slate-200/60 inline-flex items-center gap-1">
          <MapPin class="h-3 w-3 text-slate-400" />
          {{ value || 'Unassigned' }}
        </span>
      </template>

      <template #cell(available_quantity)="{ value }">
        <UiBadge :variant="value <= 0 ? 'danger' : 'warning'">
          {{ value }} units
        </UiBadge>
      </template>

      <template #cell(min_quantity)="{ value }">
        <span class="font-mono font-medium text-slate-700">{{ value }}</span>
      </template>

      <template #cell(shortfall)="{ item }">
        <span class="text-red-600 font-bold font-mono">+{{ item.shortfall }}</span>
      </template>

      <template #cell(actions)="{ item }">
        <UiButton
          size="sm"
          variant="outline"
          class="text-xs"
          @click="openAdjustment(item.product_id, item.location_id, item.variant_id)"
        >
          <Settings2 class="mr-1.5 h-3.5 w-3.5" /> Adjust
        </UiButton>
      </template>
    </UiTable>

    <StockAdjustmentModal
      v-model="isAdjustmentModalOpen"
      :product-id="selectedProductId"
      :location-id="selectedLocationId"
      :variant-id="selectedVariantId"
      @saved="refetch"
    />
  </div>
</template>
