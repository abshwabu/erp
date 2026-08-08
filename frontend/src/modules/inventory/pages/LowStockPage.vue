<script setup lang="ts">
import { ref, computed } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import { inventoryApi } from '@/api/inventory'
import { AlertTriangle, Package, Settings2, RefreshCw, Search, Plus } from '@lucide/vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiTable from '@/components/ui/UiTable.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiAlert from '@/components/ui/UiAlert.vue'
import UiInput from '@/components/ui/UiInput.vue'
import StockAdjustmentModal from '../components/StockAdjustmentModal.vue'
import type { LowStockItem } from '@/types/inventory'

const isAdjustmentModalOpen = ref(false)
const selectedProductId = ref<string | undefined>(undefined)
const search = ref('')

const openAdjustment = (productId?: string) => {
  selectedProductId.value = productId
  isAdjustmentModalOpen.value = true
}

const { data, isLoading, refetch } = useQuery({
  queryKey: ['inventory', 'low-stock'],
  queryFn: () => inventoryApi.getLowStockProducts().then((res) => res.data),
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
  if (!term) return rows.value
  return rows.value.filter(
    (item) =>
      item.product_name.toLowerCase().includes(term) || item.sku.toLowerCase().includes(term)
  )
})

const columns = [
  { key: 'product', label: 'Product' },
  { key: 'sku', label: 'SKU' },
  { key: 'location_name', label: 'Location' },
  { key: 'available_quantity', label: 'Available', align: 'center' as const },
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
          Products at or below reorder minimums, plus out-of-stock items without reorder settings.
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
          <strong>{{ filteredData.length }}</strong> product(s) need attention.
        </span>
      </div>
    </UiAlert>

    <div class="flex items-center justify-between gap-4 bg-white p-4 rounded-lg border border-slate-200 shadow-sm">
      <div class="w-80">
        <UiInput v-model="search" placeholder="Search by name or SKU...">
          <template #prefix><Search class="h-4 w-4 text-slate-400" /></template>
        </UiInput>
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
      empty-description="Inventory levels look healthy."
    >
      <template #cell(product)="{ item }">
        <div class="flex items-center">
          <div class="h-8 w-8 rounded bg-red-50 flex items-center justify-center mr-3 shrink-0">
            <Package class="h-4 w-4 text-red-500" />
          </div>
          <span class="font-semibold text-slate-900">{{ item.product_name }}</span>
        </div>
      </template>

      <template #cell(sku)="{ value }">
        <span class="font-mono text-xs text-slate-600">{{ value }}</span>
      </template>

      <template #cell(location_name)="{ value }">
        <span class="text-xs text-slate-600 font-medium px-2 py-0.5 bg-slate-100 rounded border border-slate-200/60 inline-block">
          {{ value || 'All Locations' }}
        </span>
      </template>

      <template #cell(available_quantity)="{ value }">
        <UiBadge :variant="value <= 0 ? 'danger' : 'warning'">{{ value }} units</UiBadge>
      </template>

      <template #cell(min_quantity)="{ value }">
        <span class="font-mono font-medium text-slate-700">{{ value }}</span>
      </template>

      <template #cell(shortfall)="{ item }">
        <span class="text-red-600 font-bold font-mono">+{{ item.shortfall }}</span>
      </template>

      <template #cell(actions)="{ item }">
        <UiButton size="sm" variant="outline" class="text-xs" @click="openAdjustment(item.product_id)">
          <Settings2 class="mr-1.5 h-3.5 w-3.5" /> Adjust
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
