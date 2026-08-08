<script setup lang="ts">
import { ref, reactive, watch } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import { inventoryApi } from '@/api/inventory'
import { Search, ArrowUpRight, ArrowDownLeft, ArrowLeftRight, Package, User as UserIcon } from '@lucide/vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiTable from '@/components/ui/UiTable.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiPagination from '@/components/ui/UiPagination.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import type { MovementFilters } from '@/types/inventory'

const page = ref(1)
const filters = reactive<MovementFilters>({
  search: '',
  type: '',
  location_id: '',
  start_date: '',
  end_date: '',
})

watch(
  () => [filters.search, filters.type, filters.location_id, filters.start_date, filters.end_date],
  () => {
    page.value = 1
  }
)

const { data, isLoading } = useQuery({
  queryKey: ['inventory', 'stock-movements', page, filters],
  queryFn: () => inventoryApi.getStockMovements(filters, page.value).then((res) => res.data),
})

const { data: locations } = useQuery({
  queryKey: ['inventory', 'locations'],
  queryFn: () => inventoryApi.getLocations().then((res) => res.data),
})

const columns = [
  { key: 'created_at', label: 'Date & Time' },
  { key: 'product_name', label: 'Product' },
  { key: 'type', label: 'Movement Type' },
  { key: 'location', label: 'Location' },
  { key: 'quantity', label: 'Quantity', align: 'right' as const },
  { key: 'reference', label: 'Reference' },
  { key: 'user_name', label: 'User' },
]

const getMovementVariant = (type: string) => {
  if (['sale', 'issue', 'adjustment'].includes(type) && type !== 'receive') {
    if (['sale', 'issue'].includes(type)) return 'danger'
  }
  if (['receive', 'opening', 'customer_return'].includes(type)) return 'success'
  if (type === 'transfer') return 'info'
  return 'default'
}

const formatType = (type: string) =>
  type?.replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase()) || 'Movement'

const locationLabel = (item: any) => {
  if (item.direction === 'transfer') {
    return `${item.from_location_name || '—'} → ${item.to_location_name || '—'}`
  }
  if (item.direction === 'in') return item.to_location_name || '—'
  return item.from_location_name || '—'
}
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-slate-900">Stock Movements</h1>
      <p class="text-slate-500 text-sm">Audit trail of receipts, sales, transfers, and adjustments.</p>
    </div>

    <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
      <div class="lg:col-span-2">
        <UiInput v-model="filters.search" label="Search" placeholder="Product name or SKU">
          <template #prefix>
            <Search class="h-4 w-4 text-slate-400" />
          </template>
        </UiInput>
      </div>
      <div>
        <UiSelect
          v-model="filters.type"
          label="Type"
          :options="[
            { label: 'All Types', value: '' },
            { label: 'Opening', value: 'opening' },
            { label: 'Receive', value: 'receive' },
            { label: 'Sale', value: 'sale' },
            { label: 'Adjustment', value: 'adjustment' },
            { label: 'Transfer', value: 'transfer' },
          ]"
        />
      </div>
      <div>
        <UiSelect
          v-model="filters.location_id"
          label="Location"
          :options="[
            { label: 'All Locations', value: '' },
            ...(Array.isArray(locations) ? locations.map((l) => ({ label: l.name, value: l.id })) : []),
          ]"
        />
      </div>
      <div>
        <UiInput v-model="filters.start_date" type="date" label="From" />
      </div>
      <div>
        <UiInput v-model="filters.end_date" type="date" label="To" />
      </div>
    </div>

    <UiTable
      :columns="columns"
      :data="data?.data || []"
      :loading="isLoading"
      empty-title="No stock movements yet"
      empty-description="Movements appear when you receive, sell, transfer, or adjust stock."
    >
      <template #cell(created_at)="{ value }">
        <div class="text-xs text-slate-600 font-mono">
          <div class="font-bold text-slate-800">{{ value ? new Date(value).toLocaleDateString() : '—' }}</div>
          <div class="text-slate-400">
            {{ value ? new Date(value).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '' }}
          </div>
        </div>
      </template>

      <template #cell(product_name)="{ item }">
        <div class="flex items-center">
          <div class="h-7 w-7 rounded bg-slate-100 flex items-center justify-center mr-2.5 shrink-0">
            <Package class="h-3.5 w-3.5 text-slate-400" />
          </div>
          <div>
            <div class="font-semibold text-slate-900 text-sm">{{ item.product_name || '—' }}</div>
            <div v-if="item.product_sku" class="text-xs text-slate-400 font-mono">{{ item.product_sku }}</div>
          </div>
        </div>
      </template>

      <template #cell(type)="{ value, item }">
        <div class="flex items-center">
          <div
            :class="[
              'p-1 rounded-full mr-2 shrink-0',
              item.direction === 'in'
                ? 'bg-emerald-100 text-emerald-600'
                : item.direction === 'transfer'
                  ? 'bg-blue-100 text-blue-600'
                  : 'bg-red-100 text-red-600',
            ]"
          >
            <ArrowDownLeft v-if="item.direction === 'in'" class="h-3 w-3" />
            <ArrowLeftRight v-else-if="item.direction === 'transfer'" class="h-3 w-3" />
            <ArrowUpRight v-else class="h-3 w-3" />
          </div>
          <UiBadge :variant="getMovementVariant(value)">{{ formatType(value) }}</UiBadge>
        </div>
      </template>

      <template #cell(location)="{ item }">
        <span class="text-xs text-slate-600">{{ locationLabel(item) }}</span>
      </template>

      <template #cell(quantity)="{ value, item }">
        <span
          :class="[
            'font-mono font-bold text-sm',
            item.direction === 'in'
              ? 'text-emerald-600'
              : item.direction === 'transfer'
                ? 'text-blue-600'
                : 'text-red-600',
          ]"
        >
          {{ item.direction === 'in' ? '+' : item.direction === 'out' ? '-' : '' }}{{ value }}
        </span>
      </template>

      <template #cell(reference)="{ item }">
        <span class="text-xs font-mono text-slate-700">
          {{ item.notes || item.reference_type || '—' }}
        </span>
      </template>

      <template #cell(user_name)="{ value }">
        <div class="flex items-center text-xs text-slate-600 font-medium">
          <UserIcon class="h-3.5 w-3.5 mr-1.5 text-slate-400" />
          {{ value || 'System' }}
        </div>
      </template>
    </UiTable>

    <div v-if="data?.meta" class="flex justify-between items-center">
      <p class="text-sm text-slate-500">
        Showing {{ data.meta.from ?? 0 }}–{{ data.meta.to ?? 0 }} of {{ data.meta.total }} movements
      </p>
      <UiPagination
        :current-page="page"
        @update:current-page="page = $event"
        :total-pages="data.meta.last_page"
        :has-next-page="data.meta.current_page < data.meta.last_page"
        :has-prev-page="data.meta.current_page > 1"
      />
    </div>
  </div>
</template>
