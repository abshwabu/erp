<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import { useI18n } from 'vue-i18n'
import { inventoryApi } from '@/api/inventory'
import { Search, ArrowUpRight, ArrowDownLeft, ArrowLeftRight, Package, User as UserIcon } from '@lucide/vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiTable from '@/components/ui/UiTable.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiPagination from '@/components/ui/UiPagination.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import type { MovementFilters } from '@/types/inventory'

const { t, te, locale } = useI18n()
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

const columns = computed(() => [
  { key: 'created_at', label: t('inventory.dateTime') },
  { key: 'product_name', label: t('inventory.products') },
  { key: 'type', label: t('inventory.movementType') },
  { key: 'location', label: t('inventory.warehouse') },
  { key: 'quantity', label: t('inventory.quantity'), align: 'right' as const },
  { key: 'reference', label: t('inventory.reference') },
  { key: 'user_name', label: t('inventory.user') },
])

const typeFilterOptions = computed(() => [
  { label: t('inventory.allTypes'), value: '' },
  { label: t('inventory.movementTypes.opening'), value: 'opening' },
  { label: t('inventory.movementTypes.receive'), value: 'receive' },
  { label: t('inventory.movementTypes.sale'), value: 'sale' },
  { label: t('inventory.movementTypes.adjustment'), value: 'adjustment' },
  { label: t('inventory.movementTypes.transfer'), value: 'transfer' },
])

const locationFilterOptions = computed(() => [
  { label: t('inventory.allLocations'), value: '' },
  ...(Array.isArray(locations.value) ? locations.value.map((l: any) => ({ label: l.name, value: l.id })) : []),
])

const getMovementVariant = (type: string) => {
  if (['sale', 'issue', 'adjustment'].includes(type) && type !== 'receive') {
    if (['sale', 'issue'].includes(type)) return 'danger'
  }
  if (['receive', 'opening', 'customer_return'].includes(type)) return 'success'
  if (type === 'transfer') return 'info'
  return 'default'
}

const formatType = (type: string) => {
  const key = `inventory.movementTypes.${type}`
  if (te(key)) return t(key)
  return type?.replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase()) || t('inventory.stockMovements')
}

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
      <h1 class="text-2xl font-bold text-slate-900">{{ $t('inventory.stockMovements') }}</h1>
      <p class="text-slate-500 text-sm">{{ $t('inventory.stockMovementsSubtitle') }}</p>
    </div>

    <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
      <div class="lg:col-span-2">
        <UiInput v-model="filters.search" :label="$t('common.search')" :placeholder="$t('inventory.searchMovementsPlaceholder')">
          <template #prefix>
            <Search class="h-4 w-4 text-slate-400" />
          </template>
        </UiInput>
      </div>
      <div>
        <UiSelect
          v-model="filters.type"
          :label="$t('inventory.movementType')"
          :options="typeFilterOptions"
        />
      </div>
      <div>
        <UiSelect
          v-model="filters.location_id"
          :label="$t('inventory.warehouse')"
          :options="locationFilterOptions"
        />
      </div>
      <div>
        <UiInput v-model="filters.start_date" type="date" :label="$t('inventory.dateFrom')" />
      </div>
      <div>
        <UiInput v-model="filters.end_date" type="date" :label="$t('inventory.dateTo')" />
      </div>
    </div>

    <UiTable
      :columns="columns"
      :data="data?.data || []"
      :loading="isLoading"
      :empty-title="$t('inventory.noMovementsYet')"
      :empty-description="$t('inventory.noMovementsDescription')"
    >
      <template #cell(created_at)="{ value }">
        <div class="text-xs text-slate-600 font-mono">
          <div class="font-bold text-slate-800">{{ value ? new Date(value).toLocaleDateString(locale === 'am' ? 'am-ET' : undefined) : '—' }}</div>
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
          {{ value || $t('inventory.system') }}
        </div>
      </template>
    </UiTable>

    <div v-if="data?.meta" class="flex justify-between items-center">
      <p class="text-sm text-slate-500">
        {{ $t('inventory.showingPagination', { from: data.meta.from ?? 0, to: data.meta.to ?? 0, total: data.meta.total, items: $t('inventory.stockMovements') }) }}
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
