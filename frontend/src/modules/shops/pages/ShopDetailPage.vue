<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { useI18n } from 'vue-i18n'
import { ArrowLeft, Package, Settings2, Users } from '@lucide/vue'
import { shopsApi } from '@/api/shops'
import { usePermission } from '@/composables/usePermission'
import { useToast } from '@/composables/useToast'
import { formatCurrency } from '@/utils/format'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiTable from '@/components/ui/UiTable.vue'
import StockAdjustmentModal from '@/modules/inventory/components/StockAdjustmentModal.vue'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const queryClient = useQueryClient()
const { hasPermission } = usePermission()
const toast = useToast()

const shopId = computed(() => String(route.params.id))
const canManage = computed(() => hasPermission('shops.manage'))
const activeTab = ref<'settings' | 'stock' | 'keepers'>('settings')
const errorMessage = ref('')

const settings = reactive({
  name: '',
  phone: '',
  notes: '',
  is_active: true,
})

const isAdjustOpen = ref(false)
const selectedAdjustProductId = ref<string | undefined>(undefined)

const selectedKeeperIds = ref<string[]>([])

const { data: shop, isLoading } = useQuery({
  queryKey: computed(() => ['shops', shopId.value]),
  queryFn: () => shopsApi.getShop(shopId.value).then((res) => res.data.data),
})

watch(
  shop,
  (value) => {
    if (!value) return
    settings.name = value.name
    settings.phone = value.phone || ''
    settings.notes = value.notes || ''
    settings.is_active = value.is_active
    selectedKeeperIds.value = (value.keepers || []).map((k) => k.id)
  },
  { immediate: true }
)

const { data: stock, isLoading: stockLoading } = useQuery({
  queryKey: computed(() => ['shops', shopId.value, 'stock']),
  queryFn: () => shopsApi.getStock(shopId.value).then((res) => res.data),
  enabled: computed(() => activeTab.value === 'stock'),
})

const { data: users } = useQuery({
  queryKey: ['shops', 'assignable-users'],
  queryFn: () => shopsApi.getAssignableUsers().then((res) => res.data),
  enabled: computed(() => canManage.value && activeTab.value === 'keepers'),
})

const tabs = computed(() => [
  { id: 'settings' as const, label: t('shops.detail.tabs.settings'), icon: Settings2 },
  { id: 'stock' as const, label: t('shops.detail.tabs.stock'), icon: Package },
  { id: 'keepers' as const, label: t('shops.detail.tabs.keepers'), icon: Users },
])

const stockColumns = computed(() => [
  { key: 'name', label: t('shops.detail.stock.columns.product') },
  { key: 'sku', label: t('shops.detail.stock.columns.sku') },
  { key: 'available_quantity', label: t('shops.detail.stock.columns.available'), align: 'center' as const },
  { key: 'value', label: t('shops.detail.stock.columns.value'), align: 'right' as const },
  { key: 'actions', label: '', align: 'right' as const },
])

const stockRows = computed(() =>
  (Array.isArray(stock.value) ? stock.value : []).map((row) => ({
    ...row,
    valueCents: row.available_quantity * (row.cost_price || 0),
  }))
)

const saveSettingsMutation = useMutation({
  mutationFn: () =>
    shopsApi.updateShop(shopId.value, {
      name: settings.name,
      phone: settings.phone || undefined,
      notes: settings.notes || undefined,
      ...(canManage.value ? { is_active: settings.is_active } : {}),
    }),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['shops'] })
    errorMessage.value = ''
    toast.success(t('shops.detail.settings.saveSuccess'))
  },
  onError: (err: any) => {
    errorMessage.value = err?.message || t('shops.detail.settings.saveError')
  },
})

const syncKeepersMutation = useMutation({
  mutationFn: () =>
    shopsApi.syncKeepers(
      shopId.value,
      selectedKeeperIds.value.map((user_id) => ({ user_id, role: 'keeper' }))
    ),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['shops', shopId.value] })
    queryClient.invalidateQueries({ queryKey: ['shops'] })
    errorMessage.value = ''
    toast.success(t('shops.detail.keepers.saveSuccess'))
  },
  onError: (err: any) => {
    errorMessage.value = err?.message || t('shops.detail.keepers.saveError')
  },
})

function toggleKeeper(id: string) {
  const idx = selectedKeeperIds.value.indexOf(id)
  if (idx >= 0) selectedKeeperIds.value.splice(idx, 1)
  else selectedKeeperIds.value.push(id)
}

function openAdjust(productId?: string) {
  selectedAdjustProductId.value = productId
  isAdjustOpen.value = true
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-start justify-between gap-4">
      <div>
        <button
          type="button"
          class="inline-flex items-center text-sm text-slate-500 hover:text-slate-800 mb-2"
          @click="router.push('/shops')"
        >
          <ArrowLeft class="h-4 w-4 mr-1" /> {{ t('shops.detail.backToShops') }}
        </button>
        <h1 class="text-2xl font-bold text-slate-900">{{ shop?.name || t('shops.detail.fallbackShopName') }}</h1>
        <p class="text-slate-500 text-sm font-mono">{{ shop?.code }}</p>
      </div>
      <UiBadge v-if="shop" :variant="shop.is_active ? 'success' : 'danger'">
        {{ shop.is_active ? t('shops.statusActive') : t('shops.statusInactive') }}
      </UiBadge>
    </div>

    <div v-if="errorMessage" class="p-3 bg-red-50 text-red-700 text-sm rounded-lg border border-red-200">
      {{ errorMessage }}
    </div>

    <div class="flex gap-2 border-b border-slate-200">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        type="button"
        class="px-4 py-2 text-sm font-medium border-b-2 -mb-px inline-flex items-center gap-2"
        :class="activeTab === tab.id ? 'border-blue-600 text-blue-700' : 'border-transparent text-slate-500'"
        @click="activeTab = tab.id"
      >
        <component :is="tab.icon" class="h-4 w-4" />
        {{ tab.label }}
      </button>
    </div>

    <div v-if="isLoading" class="text-sm text-slate-500">{{ t('shops.detail.loading') }}</div>

    <div v-else-if="activeTab === 'settings'" class="bg-white border border-slate-200 rounded-lg p-6 space-y-4 max-w-xl">
      <UiInput v-model="settings.name" :label="t('shops.detail.settings.name')" />
      <UiInput v-model="settings.phone" :label="t('shops.detail.settings.phone')" />
      <UiInput v-model="settings.notes" :label="t('shops.detail.settings.notes')" />
      <label v-if="canManage" class="flex items-center gap-2 text-sm text-slate-700">
        <input v-model="settings.is_active" type="checkbox" class="rounded border-slate-300" />
        {{ t('shops.detail.settings.activeCheckbox') }}
      </label>
      <div class="text-xs text-slate-500 space-y-1">
        <p>{{ t('shops.detail.settings.stockMode') }}: <strong>{{ shop?.stock_mode === 'own' ? t('shops.stockModes.own') : t('shops.stockModes.shared') }}</strong></p>
        <p>{{ t('shops.detail.settings.location') }}: <strong>{{ shop?.stock_location?.name || '—' }}</strong></p>
        <p>{{ t('shops.detail.settings.warehouse') }}: <strong>{{ shop?.warehouse?.name || '—' }}</strong></p>
      </div>
      <UiButton :loading="saveSettingsMutation.isPending.value" @click="saveSettingsMutation.mutate()">
        {{ t('shops.detail.settings.save') }}
      </UiButton>
    </div>

    <div v-else-if="activeTab === 'stock'" class="space-y-4">
      <div class="flex justify-end">
        <UiButton size="sm" @click="openAdjust()">{{ t('shops.detail.stock.adjustStock') }}</UiButton>
      </div>
      <UiTable
        :columns="stockColumns"
        :data="stockRows"
        :loading="stockLoading"
        :empty-title="t('shops.detail.stock.emptyTitle')"
      >
        <template #cell(sku)="{ value }">
          <span class="font-mono text-xs">{{ value }}</span>
        </template>
        <template #cell(value)="{ item }">
          {{ formatCurrency(item.valueCents / 100) }}
        </template>
        <template #cell(actions)="{ item }">
          <UiButton size="sm" variant="outline" @click="openAdjust(item.product_id)">{{ t('shops.detail.stock.adjust') }}</UiButton>
        </template>
      </UiTable>
    </div>

    <div v-else class="bg-white border border-slate-200 rounded-lg p-6 space-y-4 max-w-2xl">
      <template v-if="canManage">
        <p class="text-sm text-slate-600">{{ t('shops.detail.keepers.description') }}</p>
        <div class="space-y-2 max-h-80 overflow-y-auto">
          <label
            v-for="user in users || []"
            :key="user.id"
            class="flex items-center gap-3 p-2 rounded hover:bg-slate-50 text-sm"
          >
            <input
              type="checkbox"
              :checked="selectedKeeperIds.includes(user.id)"
              @change="toggleKeeper(user.id)"
              class="rounded border-slate-300"
            />
            <span class="font-medium text-slate-900">{{ user.name }}</span>
            <span class="text-slate-500">{{ user.email }}</span>
          </label>
        </div>
        <UiButton :loading="syncKeepersMutation.isPending.value" @click="syncKeepersMutation.mutate()">
          {{ t('shops.detail.keepers.save') }}
        </UiButton>
      </template>
      <template v-else>
        <ul class="space-y-2">
          <li v-for="k in shop?.keepers || []" :key="k.id" class="text-sm text-slate-700">
            {{ k.name }} <span class="text-slate-400">({{ k.email }})</span>
          </li>
          <li v-if="!(shop?.keepers?.length)" class="text-sm text-slate-500">{{ t('shops.detail.keepers.empty') }}</li>
        </ul>
      </template>
    </div>

    <StockAdjustmentModal
      v-model="isAdjustOpen"
      :product-id="selectedAdjustProductId"
      :location-id="shop?.stock_location?.id"
      @saved="queryClient.invalidateQueries({ queryKey: ['shops', shopId, 'stock'] })"
    />
  </div>
</template>
