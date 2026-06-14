<script setup lang="ts">
import { ref, computed } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import { inventoryApi } from '@/api/inventory'
import { 
  AlertCircle, 
  ShoppingCart, 
  Package, 
  ArrowRight,
  RefreshCw,
  Search
} from '@lucide/vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiTable from '@/components/ui/UiTable.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiAlert from '@/components/ui/UiAlert.vue'
import UiInput from '@/components/ui/UiInput.vue'

const { data, isLoading, refetch } = useQuery({
  queryKey: ['inventory', 'low-stock'],
  queryFn: () => inventoryApi.getLowStockProducts().then(res => res.data)
})

const search = ref('')
const filteredData = computed(() => {
  const items = Array.isArray(data.value) ? data.value : []
  if (!search.value) return items
  
  return items.filter(item => 
    item.productName?.toLowerCase().includes(search.value.toLowerCase()) ||
    item.sku?.toLowerCase().includes(search.value.toLowerCase())
  )
})

const columns = [
  { key: 'product', label: 'Product' },
  { key: 'sku', label: 'SKU' },
  { key: 'totalOnHand', label: 'Stock On Hand', align: 'center' as const },
  { key: 'minStock', label: 'Min. Level', align: 'center' as const },
  { key: 'shortfall', label: 'Shortfall', align: 'center' as const },
  { key: 'actions', label: '', align: 'right' as const }
]

const selectedItems = ref<number[]>([])

const toggleSelect = (id: number) => {
  const index = selectedItems.value.indexOf(id)
  if (index > -1) selectedItems.value.splice(index, 1)
  else selectedItems.value.push(id)
}

const handleCreatePO = () => {
  if (selectedItems.value.length === 0) return
  alert(`Creating Purchase Orders for ${selectedItems.value.length} items...`)
  selectedItems.value = []
}
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-slate-900">Low Stock Alert</h1>
      <p class="text-slate-500 text-sm">Products currently below their minimum stock levels.</p>
    </div>

    <UiAlert variant="warning" class="shadow-sm">
      <span>You have {{ filteredData.length }} products that require replenishment.</span>
      <template #action>
        <UiButton v-if="selectedItems.length > 0" size="sm" variant="primary" @click="handleCreatePO">
          <ShoppingCart class="h-4 w-4 mr-2" /> Create PO for {{ selectedItems.length }} items
        </UiButton>
      </template>
    </UiAlert>

    <!-- Controls -->
    <div class="flex items-center justify-between gap-4">
      <div class="w-72">
        <UiInput v-model="search" placeholder="Filter low stock items...">
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
    >
      <template #cell(product)="{ item }">
        <div class="flex items-center">
          <input 
            type="checkbox" 
            :checked="selectedItems.includes(item.productId)"
            @change="toggleSelect(item.productId)"
            class="mr-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500"
          />
          <div class="flex items-center">
            <div class="h-8 w-8 rounded bg-red-50 flex items-center justify-center mr-3">
              <Package class="h-4 w-4 text-red-500" />
            </div>
            <span class="font-medium text-slate-900">{{ item.productName }}</span>
          </div>
        </div>
      </template>

      <template #cell(shortfall)="{ item }">
        <span class="text-red-600 font-bold">{{ item.shortfall || (item.minStock - item.totalOnHand) }}</span>
      </template>

      <template #cell(actions)>
        <UiButton size="sm" variant="ghost" class="text-primary-600 hover:text-primary-700 hover:bg-primary-50">
          Reorder <ArrowRight class="ml-1 h-3.5 w-3.5" />
        </UiButton>
      </template>
    </UiTable>
  </div>
</template>
