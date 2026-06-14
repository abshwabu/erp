<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import { inventoryApi } from '@/api/inventory'
import { 
  Search, 
  ArrowUpRight, 
  ArrowDownLeft, 
  RefreshCw, 
  Download,
  Calendar,
  User as UserIcon
} from '@lucide/vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiTable from '@/components/ui/UiTable.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiPagination from '@/components/ui/UiPagination.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import type { MovementType } from '@/types/inventory'

const page = ref(1)
const filters = reactive({
  productId: undefined,
  type: undefined as MovementType | undefined,
  locationId: undefined,
  startDate: '',
  endDate: ''
})

const { data, isLoading } = useQuery({
  queryKey: ['inventory', 'stock-movements', { page, ...filters }],
  queryFn: () => inventoryApi.getStockMovements(filters, page.value).then(res => res.data)
})

const columns = [
  { key: 'createdAt', label: 'Date' },
  { key: 'productName', label: 'Product' },
  { key: 'type', label: 'Type' },
  { key: 'locationName', label: 'Location' },
  { key: 'quantity', label: 'Quantity', align: 'right' as const },
  { key: 'reference', label: 'Reference' },
  { key: 'userName', label: 'User' }
]

const getMovementVariant = (type: MovementType) => {
  if (['sale', 'supplier_return', 'production_consumption'].includes(type)) return 'danger'
  if (['goods_received', 'customer_return', 'production_output', 'opening_balance'].includes(type)) return 'success'
  return 'info'
}

const formatType = (type: string) => {
  return type?.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) || 'Unknown'
}

// Explicit icon definitions removed, using icons directly
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Stock Movements</h1>
        <p class="text-slate-500 text-sm">Track every stock change and transaction history.</p>
      </div>
      <div class="flex items-center space-x-2">
        <UiButton variant="outline" size="sm">
          <Download class="h-4 w-4 mr-2" /> Export Log
        </UiButton>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
      <div>
        <UiSelect
          v-model="filters.type"
          label="Movement Type"
          :options="[
            { label: 'All Types', value: '' },
            { label: 'Goods Received', value: 'goods_received' },
            { label: 'Sale', value: 'sale' },
            { label: 'Adjustment', value: 'stock_adjustment' },
            { label: 'Transfer', value: 'internal_transfer' }
          ]"
        />
      </div>
      <div>
        <UiSelect
          v-model="filters.locationId"
          label="Location"
          :options="[
            { label: 'All Locations', value: '' },
            { label: 'Main Warehouse', value: 1 },
            { label: 'Retail Store', value: 2 }
          ]"
        />
      </div>
      <div>
        <UiInput v-model="filters.startDate" type="date" label="From Date" />
      </div>
      <div>
        <UiInput v-model="filters.endDate" type="date" label="To Date" />
      </div>
      <div class="flex items-end">
        <UiButton variant="secondary" class="w-full" @click="page = 1">
          <Search class="h-4 w-4 mr-2" /> Filter
        </UiButton>
      </div>
    </div>

    <!-- Table -->
    <UiTable
      :columns="columns"
      :data="Array.isArray(data?.data) ? data.data : []"
      :loading="isLoading"
      empty-title="No movements found"
    >
      <template #cell(createdAt)="{ value }">
        <div class="text-xs text-slate-500">
          {{ value ? new Date(value).toLocaleDateString() : '-' }}<br/>
          {{ value ? new Date(value).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '' }}
        </div>
      </template>

      <template #cell(type)="{ value, item }">
        <div class="flex items-center">
          <div :class="[
            'p-1 rounded-full mr-2',
            item.direction === 'in' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'
          ]">
            <ArrowDownLeft v-if="item.direction === 'in'" class="h-3 w-3" />
            <ArrowUpRight v-else class="h-3 w-3" />
          </div>
          <UiBadge :variant="getMovementVariant(value)">{{ formatType(value) }}</UiBadge>
        </div>
      </template>

      <template #cell(quantity)="{ value, item }">
        <span :class="[
          'font-mono font-medium',
          item.direction === 'in' ? 'text-green-600' : 'text-red-600'
        ]">
          {{ item.direction === 'in' ? '+' : '-' }}{{ value }}
        </span>
      </template>

      <template #cell(reference)="{ value }">
        <span class="text-primary-600 hover:underline cursor-pointer">{{ value || '-' }}</span>
      </template>

      <template #cell(userName)="{ value }">
        <div class="flex items-center text-slate-600">
          <UserIcon class="h-3.5 w-3.5 mr-1.5 text-slate-400" />
          {{ value }}
        </div>
      </template>
    </UiTable>

    <!-- Pagination -->
    <div v-if="data?.meta" class="flex justify-between items-center">
      <UiPagination
        :current-page="page"
        @update:current-page="page = $event"
        :total-pages="data.meta.lastPage"
        :has-next-page="data.meta.currentPage < data.meta.lastPage"
        :has-prev-page="data.meta.currentPage > 1"
      />
    </div>
  </div>
</template>
