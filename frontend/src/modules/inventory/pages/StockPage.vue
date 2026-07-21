<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import { inventoryApi } from '@/api/inventory'
import { 
  Plus, 
  Search, 
  Package, 
  AlertTriangle, 
  TrendingDown, 
  Layers,
  ChevronRight,
  History,
  Settings2
} from '@lucide/vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiTable from '@/components/ui/UiTable.vue'
import UiStat from '@/components/ui/UiStat.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiPagination from '@/components/ui/UiPagination.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import StockAdjustmentModal from '../components/StockAdjustmentModal.vue'

const page = ref(1)
const filters = reactive({
  search: '',
  locationId: undefined,
  lowStockOnly: false
})

const { data, isLoading } = useQuery({
  queryKey: ['inventory', 'stock-summary', { page, ...filters }],
  queryFn: () => inventoryApi.getStockSummary(filters, page.value).then(res => res.data)
})

const { data: locations } = useQuery({
  queryKey: ['inventory', 'locations'],
  queryFn: () => inventoryApi.getLocations().then(res => res.data)
})

const isAdjustmentModalOpen = ref(false)
const selectedProductId = ref<string | number | undefined>(undefined)

const openAdjustment = (productId?: string | number) => {
  selectedProductId.value = productId
  isAdjustmentModalOpen.value = true
}

const columns = [
  { key: 'product', label: 'Product' },
  { key: 'sku', label: 'SKU' },
  { key: 'totalOnHand', label: 'On Hand', align: 'center' as const },
  { key: 'totalCommitted', label: 'Committed', align: 'center' as const },
  { key: 'totalAvailable', label: 'Available', align: 'center' as const },
  { key: 'value', label: 'Stock Value', align: 'right' as const },
  { key: 'status', label: 'Status', align: 'center' as const },
  { key: 'actions', label: '', align: 'right' as const }
]

const mappedTableData = computed(() => {
  const rawList = data.value?.data
  if (!Array.isArray(rawList)) return []
  return rawList.map((p: any) => {
    const qty = typeof p.available_quantity === 'number' ? p.available_quantity : (p.totalOnHand || 0)
    const price = typeof p.selling_price === 'number' ? (p.selling_price / 100) : 0
    return {
      id: p.id,
      productId: p.id,
      productName: p.name || p.productName || 'Unnamed Product',
      sku: p.sku || `SKU-${p.id}`,
      totalOnHand: qty,
      totalCommitted: p.totalCommitted || 0,
      totalAvailable: qty,
      value: qty * price,
      lowStock: qty <= 5
    }
  })
})

// Define stats for summary display
const stats = computed(() => {
  const list = mappedTableData.value
  const totalProducts = list.length
  const totalVal = list.reduce((sum, item) => sum + (item.value || 0), 0)
  const lowStockCount = list.filter(item => item.totalAvailable > 0 && item.totalAvailable <= 5).length
  const outOfStockCount = list.filter(item => item.totalAvailable <= 0).length

  return [
    { label: 'Total Products', value: totalProducts, icon: Package },
    { label: 'Total Value', value: `$${totalVal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`, icon: Layers },
    { label: 'Low Stock Items', value: lowStockCount, icon: AlertTriangle },
    { label: 'Out of Stock', value: outOfStockCount, icon: TrendingDown },
  ]
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Stock Levels</h1>
        <p class="text-slate-500 text-sm">Monitor and manage stock levels across all locations.</p>
      </div>
      <div class="flex items-center space-x-2">
        <UiButton size="sm" @click="openAdjustment()">
          <Plus class="h-4 w-4 mr-2" /> Adjust Stock
        </UiButton>
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <UiStat
        v-for="stat in stats"
        :key="stat.label"
        :label="stat.label"
        :value="stat.value"
        :icon="stat.icon"
      />
    </div>

    <!-- Filters -->
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
          v-model="filters.locationId"
          :options="[
            { label: 'All Locations', value: '' },
            ...(Array.isArray(locations) ? locations.map((l: any) => ({ label: l.name, value: l.id })) : [])
          ]"
        />
      </div>
      <label class="flex items-center space-x-2 text-sm text-slate-600 cursor-pointer">
        <input type="checkbox" v-model="filters.lowStockOnly" class="rounded border-slate-300 text-primary-600 focus:ring-primary-500" />
        <span>Low stock only</span>
      </label>
    </div>

    <!-- Table -->
    <UiTable
      :columns="columns"
      :data="mappedTableData"
      :loading="isLoading"
    >
      <template #cell(product)="{ item }">
        <div class="flex items-center">
          <div class="h-8 w-8 rounded bg-slate-100 flex items-center justify-center mr-3">
            <Package class="h-4 w-4 text-slate-400" />
          </div>
          <span class="font-medium text-slate-900">{{ item.productName }}</span>
        </div>
      </template>

      <template #cell(value)="{ value }">
        <span class="text-slate-600 font-mono">${{ typeof value === 'number' ? value.toFixed(2) : '0.00' }}</span>
      </template>

      <template #cell(status)="{ item }">
        <UiBadge v-if="item.totalAvailable <= 0" variant="danger">Out of Stock</UiBadge>
        <UiBadge v-else-if="item.lowStock" variant="warning">Low Stock</UiBadge>
        <UiBadge v-else variant="success">In Stock</UiBadge>
      </template>

      <template #cell(actions)="{ item }">
        <div class="flex items-center justify-end space-x-2">
          <button 
            @click="openAdjustment(item.productId)"
            class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded transition-colors"
            title="Adjust Stock"
          >
            <Settings2 class="h-4 w-4" />
          </button>
        </div>
      </template>
    </UiTable>

    <!-- Pagination -->
    <div v-if="data?.meta" class="flex justify-between items-center">
      <p class="text-sm text-slate-500">
        Showing {{ mappedTableData.length }} products
      </p>
      <UiPagination
        :current-page="page"
        @update:current-page="page = $event"
        :total-pages="data.meta.lastPage"
        :has-next-page="data.meta.currentPage < data.meta.lastPage"
        :has-prev-page="data.meta.currentPage > 1"
      />
    </div>

    <StockAdjustmentModal
      v-model="isAdjustmentModalOpen"
      :product-id="selectedProductId"
    />
  </div>
</template>
