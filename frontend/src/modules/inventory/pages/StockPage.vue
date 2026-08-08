<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import { inventoryApi } from '@/api/inventory'
import {
  Plus,
  Search,
  Package,
  AlertTriangle,
  TrendingDown,
  Layers,
  Settings2,
} from '@lucide/vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiTable from '@/components/ui/UiTable.vue'
import UiStat from '@/components/ui/UiStat.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiPagination from '@/components/ui/UiPagination.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import StockAdjustmentModal from '../components/StockAdjustmentModal.vue'
import { formatCurrency } from '@/utils/format'
import type { Product } from '@/types/inventory'

const page = ref(1)
const filters = reactive({
  search: '',
  location_id: '' as string,
  low_stock: false,
})

watch(
  () => [filters.search, filters.location_id, filters.low_stock],
  () => {
    page.value = 1
  }
)

const { data, isLoading } = useQuery({
  queryKey: ['inventory', 'stock-summary', page, filters],
  queryFn: () =>
    inventoryApi
      .getStockSummary(
        {
          search: filters.search,
          location_id: filters.location_id,
          low_stock: filters.low_stock,
        },
        page.value
      )
      .then((res) => res.data),
})

const { data: locations } = useQuery({
  queryKey: ['inventory', 'locations'],
  queryFn: () => inventoryApi.getLocations().then((res) => res.data),
})

const isAdjustmentModalOpen = ref(false)
const selectedProductId = ref<string | undefined>(undefined)

const openAdjustment = (productId?: string) => {
  selectedProductId.value = productId
  isAdjustmentModalOpen.value = true
}

const columns = [
  { key: 'product', label: 'Product' },
  { key: 'sku', label: 'SKU' },
  { key: 'quantity_on_hand', label: 'On Hand', align: 'center' as const },
  { key: 'quantity_committed', label: 'Committed', align: 'center' as const },
  { key: 'available_quantity', label: 'Available', align: 'center' as const },
  { key: 'value', label: 'Stock Value', align: 'right' as const },
  { key: 'status', label: 'Status', align: 'center' as const },
  { key: 'actions', label: '', align: 'right' as const },
]

const rows = computed(() => {
  const list = data.value?.data
  if (!Array.isArray(list)) return []

  return list.map((p: Product) => {
    const onHand = p.quantity_on_hand ?? 0
    const available = p.available_quantity ?? 0
    const valueCents = onHand * (p.cost_price ?? 0)
    return {
      ...p,
      valueCents,
      isOut: available <= 0,
    }
  })
})

const stats = computed(() => {
  const list = rows.value
  const totalProducts = data.value?.meta?.total ?? list.length
  const totalValCents = list.reduce((sum, item) => sum + item.valueCents, 0)
  const outOfStockCount = list.filter((item) => item.isOut).length

  return [
    { label: 'Products', value: totalProducts, icon: Package },
    {
      label: 'Page Stock Value',
      value: formatCurrency(totalValCents / 100),
      icon: Layers,
    },
    { label: 'Out of Stock (page)', value: outOfStockCount, icon: TrendingDown },
    {
      label: 'Locations',
      value: Array.isArray(locations.value) ? locations.value.length : 0,
      icon: AlertTriangle,
    },
  ]
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Stock Levels</h1>
        <p class="text-slate-500 text-sm">Monitor quantity on hand across locations.</p>
      </div>
      <UiButton size="sm" @click="openAdjustment()">
        <Plus class="h-4 w-4 mr-2" /> Adjust Stock
      </UiButton>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <UiStat
        v-for="stat in stats"
        :key="stat.label"
        :label="stat.label"
        :value="stat.value"
        :icon="stat.icon"
      />
    </div>

    <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm flex flex-wrap gap-4 items-center">
      <div class="flex-1 min-w-[240px]">
        <UiInput v-model="filters.search" placeholder="Search by name or SKU...">
          <template #prefix>
            <Search class="h-4 w-4 text-slate-400" />
          </template>
        </UiInput>
      </div>
      <div class="w-56">
        <UiSelect
          v-model="filters.location_id"
          :options="[
            { label: 'All Locations', value: '' },
            ...(Array.isArray(locations) ? locations.map((l) => ({ label: l.name, value: l.id })) : []),
          ]"
        />
      </div>
      <label class="flex items-center space-x-2 text-sm text-slate-600 cursor-pointer">
        <input
          type="checkbox"
          v-model="filters.low_stock"
          class="rounded border-slate-300 text-primary-600 focus:ring-primary-500"
        />
        <span>Below reorder only</span>
      </label>
    </div>

    <UiTable
      :columns="columns"
      :data="rows"
      :loading="isLoading"
      empty-title="No stock records"
      empty-description="Create products or receive stock to populate levels."
    >
      <template #cell(product)="{ item }">
        <div class="flex items-center">
          <div class="h-8 w-8 rounded bg-slate-100 flex items-center justify-center mr-3">
            <Package class="h-4 w-4 text-slate-400" />
          </div>
          <span class="font-medium text-slate-900">{{ item.name }}</span>
        </div>
      </template>

      <template #cell(sku)="{ value }">
        <span class="font-mono text-xs text-slate-600">{{ value }}</span>
      </template>

      <template #cell(value)="{ item }">
        <span class="text-slate-600 font-mono">{{ formatCurrency(item.valueCents / 100) }}</span>
      </template>

      <template #cell(status)="{ item }">
        <UiBadge v-if="item.isOut" variant="danger">Out of Stock</UiBadge>
        <UiBadge v-else variant="success">In Stock</UiBadge>
      </template>

      <template #cell(actions)="{ item }">
        <button
          type="button"
          @click="openAdjustment(item.id)"
          class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded transition-colors"
          title="Adjust Stock"
        >
          <Settings2 class="h-4 w-4" />
        </button>
      </template>
    </UiTable>

    <div v-if="data?.meta" class="flex justify-between items-center">
      <p class="text-sm text-slate-500">
        Showing {{ data.meta.from ?? 0 }}–{{ data.meta.to ?? 0 }} of {{ data.meta.total }} products
      </p>
      <UiPagination
        :current-page="page"
        @update:current-page="page = $event"
        :total-pages="data.meta.last_page"
        :has-next-page="data.meta.current_page < data.meta.last_page"
        :has-prev-page="data.meta.current_page > 1"
      />
    </div>

    <StockAdjustmentModal v-model="isAdjustmentModalOpen" :product-id="selectedProductId" />
  </div>
</template>
