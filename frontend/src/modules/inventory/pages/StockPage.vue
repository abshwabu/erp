<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import { useI18n } from 'vue-i18n'
import { inventoryApi } from '@/api/inventory'
import {
  Plus,
  Search,
  Package,
  AlertTriangle,
  TrendingDown,
  Layers,
  Settings2,
  FileSpreadsheet,
  Download,
} from '@lucide/vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiTable from '@/components/ui/UiTable.vue'
import UiStat from '@/components/ui/UiStat.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiPagination from '@/components/ui/UiPagination.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import StockAdjustmentModal from '../components/StockAdjustmentModal.vue'
import ImportModal from '../components/ImportModal.vue'
import ExportModal from '../components/ExportModal.vue'
import { formatCurrency } from '@/utils/format'
import type { Product } from '@/types/inventory'

const { t, locale } = useI18n()
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
const isImportModalOpen = ref(false)
const isExportModalOpen = ref(false)
const selectedProductId = ref<string | undefined>(undefined)

const openAdjustment = (productId?: string) => {
  selectedProductId.value = productId
  isAdjustmentModalOpen.value = true
}

const formatMoney = (cents: number) => {
  const val = (cents || 0) / 100
  if (locale.value === 'am') {
    return (
      new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }).format(val) + ' ብር'
    )
  }
  return formatCurrency(val)
}

const columns = computed(() => [
  { key: 'product', label: t('inventory.products') },
  { key: 'sku', label: 'SKU' },
  { key: 'quantity_on_hand', label: t('inventory.onHand'), align: 'center' as const },
  { key: 'quantity_committed', label: t('inventory.committed'), align: 'center' as const },
  { key: 'available_quantity', label: t('inventory.available'), align: 'center' as const },
  { key: 'value', label: t('inventory.stockValue'), align: 'right' as const },
  { key: 'status', label: t('inventory.generalInfo.statusLabel'), align: 'center' as const },
  { key: 'actions', label: '', align: 'right' as const },
])

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
    { label: t('inventory.products'), value: totalProducts, icon: Package },
    {
      label: t('inventory.pageStockValue'),
      value: formatMoney(totalValCents),
      icon: Layers,
    },
    { label: t('inventory.outOfStock'), value: outOfStockCount, icon: TrendingDown },
    {
      label: t('inventory.warehouse'),
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
        <h1 class="text-2xl font-bold text-slate-900">{{ $t('inventory.stockLevels') }}</h1>
        <p class="text-slate-500 text-sm">{{ $t('inventory.stockLevelsSubtitle') }}</p>
      </div>
      <div class="flex items-center gap-2">
        <UiButton variant="outline" size="sm" @click="isImportModalOpen = true">
          <FileSpreadsheet class="h-4 w-4 mr-1.5 text-emerald-600" /> {{ $t('inventory.importSpreadsheet') }}
        </UiButton>
        <UiButton variant="outline" size="sm" @click="isExportModalOpen = true">
          <Download class="h-4 w-4 mr-1.5 text-blue-600" /> {{ $t('inventory.exportData') }}
        </UiButton>
        <UiButton size="sm" @click="openAdjustment()">
          <Plus class="h-4 w-4 mr-2" /> {{ $t('inventory.adjustStock') }}
        </UiButton>
      </div>
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
        <UiInput v-model="filters.search" :placeholder="$t('inventory.searchProductsPlaceholder')">
          <template #prefix>
            <Search class="h-4 w-4 text-slate-400" />
          </template>
        </UiInput>
      </div>
      <div class="w-56">
        <UiSelect
          v-model="filters.location_id"
          :options="[
            { label: $t('inventory.allLocations'), value: '' },
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
        <span>{{ $t('inventory.belowReorderOnly') }}</span>
      </label>
    </div>

    <UiTable
      :columns="columns"
      :data="rows"
      :loading="isLoading"
      :empty-title="$t('inventory.noStockRecords')"
      :empty-description="$t('inventory.noStockDescription')"
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
        <span class="text-slate-600 font-mono">{{ formatMoney(item.valueCents) }}</span>
      </template>

      <template #cell(status)="{ item }">
        <UiBadge v-if="item.isOut" variant="danger">{{ $t('inventory.outOfStock') }}</UiBadge>
        <UiBadge v-else variant="success">{{ $t('inventory.inStock') }}</UiBadge>
      </template>

      <template #cell(actions)="{ item }">
        <button
          type="button"
          @click="openAdjustment(item.id)"
          class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded transition-colors"
          :title="$t('inventory.adjustStock')"
        >
          <Settings2 class="h-4 w-4" />
        </button>
      </template>
    </UiTable>

    <div v-if="data?.meta" class="flex justify-between items-center">
      <p class="text-sm text-slate-500">
        {{ $t('inventory.showingPagination', { from: data.meta.from ?? 0, to: data.meta.to ?? 0, total: data.meta.total, items: $t('inventory.products') }) }}
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
    <ImportModal v-model="isImportModalOpen" />
    <ExportModal
      v-model="isExportModalOpen"
      :filters="{ search: filters.search }"
      :total-count="data?.meta?.total"
    />
  </div>
</template>
