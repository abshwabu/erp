<script setup lang="ts">
import { ref, computed } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import { inventoryApi } from '@/api/inventory'
import { 
  AlertTriangle, 
  Package, 
  Settings2,
  RefreshCw,
  Search,
  Plus
} from '@lucide/vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiTable from '@/components/ui/UiTable.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiAlert from '@/components/ui/UiAlert.vue'
import UiInput from '@/components/ui/UiInput.vue'
import StockAdjustmentModal from '../components/StockAdjustmentModal.vue'

const isAdjustmentModalOpen = ref(false)
const selectedProductId = ref<string | number | undefined>(undefined)

const openAdjustment = (productId?: string | number) => {
  selectedProductId.value = productId
  isAdjustmentModalOpen.value = true
}

const { data, isLoading, refetch } = useQuery({
  queryKey: ['inventory', 'low-stock'],
  queryFn: () => inventoryApi.getLowStockProducts().then(res => res.data)
})

const mappedLowStock = computed(() => {
  const rawList = data.value
  if (!Array.isArray(rawList)) return []

  return rawList.map((item: any) => {
    const minQty = typeof item.min_quantity === 'number' ? item.min_quantity : (item.minStock || 5)
    const availQty = typeof item.available_quantity === 'number' ? item.available_quantity : (item.totalOnHand || 0)
    const shortfall = Math.max(0, minQty - availQty)

    return {
      id: item.product_id || item.productId || item.id,
      productId: item.product_id || item.productId || item.id,
      productName: item.product_name || item.productName || 'Unnamed Product',
      sku: item.sku || `SKU-${item.id}`,
      locationName: item.location_name || item.locationName || 'All Locations',
      minStock: minQty,
      availableQuantity: availQty,
      shortfall: shortfall,
      reorderQuantity: item.reorder_quantity || 10
    }
  })
})

const search = ref('')
const filteredData = computed(() => {
  const items = mappedLowStock.value
  if (!search.value) return items

  const term = search.value.toLowerCase()
  return items.filter(item => 
    item.productName.toLowerCase().includes(term) ||
    item.sku.toLowerCase().includes(term)
  )
})

const columns = [
  { key: 'product', label: 'Product' },
  { key: 'sku', label: 'SKU' },
  { key: 'locationName', label: 'Location' },
  { key: 'availableQuantity', label: 'Available Stock', align: 'center' as const },
  { key: 'minStock', label: 'Min. Level', align: 'center' as const },
  { key: 'shortfall', label: 'Shortfall', align: 'center' as const },
  { key: 'actions', label: '', align: 'right' as const }
]

const selectedItems = ref<(string | number)[]>([])

const toggleSelect = (id: string | number) => {
  const index = selectedItems.value.indexOf(id)
  if (index > -1) selectedItems.value.splice(index, 1)
  else selectedItems.value.push(id)
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Low Stock Alert</h1>
        <p class="text-slate-500 text-sm">Products currently below their minimum required stock levels.</p>
      </div>
      <UiButton size="sm" @click="openAdjustment()">
        <Plus class="h-4 w-4 mr-2" /> Adjust Stock
      </UiButton>
    </div>

    <UiAlert v-if="filteredData.length > 0" variant="warning" class="shadow-sm">
      <div class="flex items-center gap-2">
        <AlertTriangle class="h-4 w-4 text-amber-600 shrink-0" />
        <span>You have <strong>{{ filteredData.length }}</strong> products currently requiring replenishment.</span>
      </div>
    </UiAlert>

    <!-- Controls -->
    <div class="flex items-center justify-between gap-4 bg-white p-4 rounded-lg border border-slate-200 shadow-sm">
      <div class="w-80">
        <UiInput v-model="search" placeholder="Search low stock items by name or SKU...">
          <template #prefix><Search class="h-4 w-4 text-slate-400" /></template>
        </UiInput>
      </div>
      <UiButton variant="ghost" size="sm" @click="refetch">
        <RefreshCw class="h-4 w-4 mr-2" :class="{ 'animate-spin': isLoading }" /> Refresh
      </UiButton>
    </div>

    <!-- Table -->
    <UiTable
      :columns="columns"
      :data="filteredData"
      :loading="isLoading"
      empty-title="No low stock alerts"
      empty-message="All product inventory levels are currently sufficient."
    >
      <template #cell(product)="{ item }">
        <div class="flex items-center">
          <input 
            type="checkbox" 
            :checked="selectedItems.includes(item.productId)"
            @change="toggleSelect(item.productId)"
            class="mr-3 rounded border-slate-300 text-primary-600 focus:ring-primary-500"
          />
          <div class="flex items-center">
            <div class="h-8 w-8 rounded bg-red-50 flex items-center justify-center mr-3 shrink-0">
              <Package class="h-4 w-4 text-red-500" />
            </div>
            <span class="font-semibold text-slate-900">{{ item.productName }}</span>
          </div>
        </div>
      </template>

      <template #cell(sku)="{ value }">
        <span class="font-mono text-xs text-slate-600">{{ value }}</span>
      </template>

      <template #cell(locationName)="{ value }">
        <span class="text-xs text-slate-600 font-medium px-2 py-0.5 bg-slate-100 rounded border border-slate-200/60 inline-block">
          {{ value }}
        </span>
      </template>

      <template #cell(availableQuantity)="{ value }">
        <UiBadge :variant="value <= 0 ? 'danger' : 'warning'">
          {{ value }} units
        </UiBadge>
      </template>

      <template #cell(minStock)="{ value }">
        <span class="font-mono font-medium text-slate-700">{{ value }}</span>
      </template>

      <template #cell(shortfall)="{ item }">
        <span class="text-red-600 font-bold font-mono">+{{ item.shortfall }}</span>
      </template>

      <template #cell(actions)="{ item }">
        <UiButton 
          size="sm" 
          variant="outline" 
          @click="openAdjustment(item.productId)"
          class="text-xs"
        >
          <Settings2 class="mr-1.5 h-3.5 w-3.5" /> Adjust / Reorder
        </UiButton>
      </template>
    </UiTable>

    <StockAdjustmentModal
      v-model="isAdjustmentModalOpen"
      :product-id="selectedProductId"
      @saved="refetch"
    />
  </div>
</template>
