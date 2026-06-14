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
  ChevronDown,
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

const isAdjustmentModalOpen = ref(false)
const selectedProductId = ref<number | undefined>(undefined)

const openAdjustment = (productId?: number) => {
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

// Define stats for summary display
const stats = computed(() => {
  const summary = data.value
  return [
    { label: 'Total Products', value: (summary as any)?.totalProducts || 0, icon: Package },
    { label: 'Total Value', value: `$${((summary as any)?.totalValue || 0).toLocaleString()}`, icon: Layers },
    { label: 'Low Stock Items', value: (summary as any)?.lowStockCount || 0, icon: AlertTriangle },
    { label: 'Out of Stock', value: (summary as any)?.outOfStockCount || 0, icon: TrendingDown },
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
        <UiButton variant="outline" size="sm">
          <History class="h-4 w-4 mr-2" /> Stock Movements
        </UiButton>
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
      <div class="w-48">
        <UiSelect
          v-model="filters.locationId"
          :options="[
            { label: 'All Locations', value: '' },
            { label: 'Main Warehouse', value: 1 },
            { label: 'Retail Store', value: 2 }
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
      :data="data?.data || []"
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
        <span class="text-slate-600">${{ value ? value.toLocaleString() : '0' }}</span>
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
            class="p-1.5 text-slate-400 hover:text-primary-600 hover:bg-primary-50 rounded transition-colors"
            title="Adjust Stock"
          >
            <Settings2 class="h-4 w-4" />
          </button>
          <button class="p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-50 rounded transition-colors">
            <ChevronRight class="h-4 w-4" />
          </button>
        </div>
      </template>
    </UiTable>

    <!-- Pagination -->
    <div v-if="data?.meta" class="flex justify-between items-center">
      <p class="text-sm text-slate-500">
        Showing {{ Array.isArray(data.data) ? data.data.length : 0 }} products
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
