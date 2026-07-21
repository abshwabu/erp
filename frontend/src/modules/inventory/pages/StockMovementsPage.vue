<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import { inventoryApi } from '@/api/inventory'
import { 
  Search, 
  ArrowUpRight, 
  ArrowDownLeft, 
  Download,
  User as UserIcon,
  Package
} from '@lucide/vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiTable from '@/components/ui/UiTable.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiPagination from '@/components/ui/UiPagination.vue'
import UiSelect from '@/components/ui/UiSelect.vue'

const page = ref(1)
const filters = reactive({
  productId: undefined as string | undefined,
  type: '' as string,
  locationId: '' as string,
  startDate: '',
  endDate: ''
})

const { data, isLoading } = useQuery({
  queryKey: ['inventory', 'stock-movements', { page, ...filters }],
  queryFn: () => inventoryApi.getStockMovements(filters, page.value).then(res => res.data)
})

const { data: locations } = useQuery({
  queryKey: ['inventory', 'locations'],
  queryFn: () => inventoryApi.getLocations().then(res => res.data)
})

const columns = [
  { key: 'createdAt', label: 'Date & Time' },
  { key: 'productName', label: 'Product' },
  { key: 'type', label: 'Movement Type' },
  { key: 'locationName', label: 'Location' },
  { key: 'quantity', label: 'Quantity', align: 'right' as const },
  { key: 'reference', label: 'Reference / Notes' },
  { key: 'userName', label: 'User' }
]

const mappedMovements = computed(() => {
  const rawList = data.value?.data
  if (!Array.isArray(rawList)) return []

  return rawList.map((m: any) => {
    const isIncoming = m.to_location_id !== null && m.from_location_id === null
    const direction = isIncoming ? 'in' : 'out'
    const locName = m.toLocation?.name || m.to_location?.name || m.fromLocation?.name || m.from_location?.name || 'Main Warehouse'

    return {
      id: m.id,
      createdAt: m.created_at || m.createdAt,
      productName: m.product?.name || m.productName || 'Product #' + (m.product_id ? m.product_id.substring(0, 8) : ''),
      sku: m.product?.sku || m.sku || '',
      type: m.type || 'adjustment',
      direction: direction,
      locationName: locName,
      quantity: m.quantity || 0,
      reference: m.notes || m.reference_type || m.reference || 'N/A',
      userName: m.user?.name || m.userName || 'System Admin'
    }
  })
})

const getMovementVariant = (type: string) => {
  if (['sale', 'supplier_return', 'issue', 'remove'].includes(type)) return 'danger'
  if (['goods_received', 'receive', 'add', 'customer_return', 'opening_balance'].includes(type)) return 'success'
  return 'info'
}

const formatType = (type: string) => {
  return type?.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) || 'Adjustment'
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Stock Movements</h1>
        <p class="text-slate-500 text-sm">Track every stock change, receipt, sale, and transaction history.</p>
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
            { label: 'Receive', value: 'receive' },
            { label: 'Sale', value: 'sale' },
            { label: 'Adjustment', value: 'adjustment' },
            { label: 'Transfer', value: 'transfer' }
          ]"
        />
      </div>
      <div>
        <UiSelect
          v-model="filters.locationId"
          label="Location"
          :options="[
            { label: 'All Locations', value: '' },
            ...(Array.isArray(locations) ? locations.map((l: any) => ({ label: l.name, value: l.id })) : [])
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
          <Search class="h-4 w-4 mr-2" /> Filter Log
        </UiButton>
      </div>
    </div>

    <!-- Table -->
    <UiTable
      :columns="columns"
      :data="mappedMovements"
      :loading="isLoading"
      empty-title="No stock movements recorded yet"
    >
      <template #cell(createdAt)="{ value }">
        <div class="text-xs text-slate-600 font-mono">
          <div class="font-bold text-slate-800">{{ value ? new Date(value).toLocaleDateString() : '-' }}</div>
          <div class="text-slate-400">{{ value ? new Date(value).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' }) : '' }}</div>
        </div>
      </template>

      <template #cell(productName)="{ item }">
        <div class="flex items-center">
          <div class="h-7 w-7 rounded bg-slate-100 flex items-center justify-center mr-2.5 shrink-0">
            <Package class="h-3.5 w-3.5 text-slate-400" />
          </div>
          <div>
            <div class="font-semibold text-slate-900 text-sm">{{ item.productName }}</div>
            <div v-if="item.sku" class="text-xs text-slate-400 font-mono">{{ item.sku }}</div>
          </div>
        </div>
      </template>

      <template #cell(type)="{ value, item }">
        <div class="flex items-center">
          <div :class="[
            'p-1 rounded-full mr-2 shrink-0',
            item.direction === 'in' ? 'bg-emerald-100 text-emerald-600' : 'bg-red-100 text-red-600'
          ]">
            <ArrowDownLeft v-if="item.direction === 'in'" class="h-3 w-3" />
            <ArrowUpRight v-else class="h-3 w-3" />
          </div>
          <UiBadge :variant="getMovementVariant(value)">{{ formatType(value) }}</UiBadge>
        </div>
      </template>

      <template #cell(quantity)="{ value, item }">
        <span :class="[
          'font-mono font-bold text-sm',
          item.direction === 'in' ? 'text-emerald-600' : 'text-red-600'
        ]">
          {{ item.direction === 'in' ? '+' : '-' }}{{ value }}
        </span>
      </template>

      <template #cell(reference)="{ value }">
        <span class="text-xs font-medium text-slate-700 bg-slate-100 px-2 py-1 rounded border border-slate-200/60 inline-block font-mono">
          {{ value || 'N/A' }}
        </span>
      </template>

      <template #cell(userName)="{ value }">
        <div class="flex items-center text-xs text-slate-600 font-medium">
          <UserIcon class="h-3.5 w-3.5 mr-1.5 text-slate-400" />
          {{ value }}
        </div>
      </template>
    </UiTable>

    <!-- Pagination -->
    <div v-if="data?.meta" class="flex justify-between items-center">
      <p class="text-sm text-slate-500">
        Showing {{ mappedMovements.length }} movements
      </p>
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
