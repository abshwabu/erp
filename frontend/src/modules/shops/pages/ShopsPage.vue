<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { MapPin, Plus, Store, Users } from '@lucide/vue'
import { shopsApi, type CreateShopPayload, type ShopStockMode } from '@/api/shops'
import { inventoryApi } from '@/api/inventory'
import { usePermission } from '@/composables/usePermission'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiTable from '@/components/ui/UiTable.vue'

const { t } = useI18n()
const router = useRouter()
const queryClient = useQueryClient()
const { hasPermission } = usePermission()
const canManage = computed(() => hasPermission('shops.manage'))

const isCreateOpen = ref(false)
const errorMessage = ref('')

const form = reactive({
  name: '',
  code: '',
  stock_mode: 'own' as ShopStockMode,
  stock_location_id: '',
  phone: '',
  notes: '',
})

const { data: shops, isLoading } = useQuery({
  queryKey: ['shops'],
  queryFn: () => shopsApi.getShops().then((res) => res.data),
})

const { data: locations } = useQuery({
  queryKey: ['inventory', 'locations'],
  queryFn: () => inventoryApi.getLocations().then((res) => res.data),
  enabled: computed(() => canManage.value && isCreateOpen.value),
})

const columns = computed(() => [
  { key: 'name', label: t('shops.columns.shop') },
  { key: 'code', label: t('shops.columns.code') },
  { key: 'stock_mode', label: t('shops.columns.stock') },
  { key: 'location', label: t('shops.columns.location') },
  { key: 'keepers', label: t('shops.columns.keepers'), align: 'center' as const },
  { key: 'status', label: t('shops.columns.status'), align: 'center' as const },
])

const rows = computed(() =>
  (Array.isArray(shops.value) ? shops.value : []).map((shop) => ({
    id: shop.id,
    name: shop.name,
    code: shop.code,
    stock_mode: shop.stock_mode === 'own' ? t('shops.stockModes.own') : t('shops.stockModes.shared'),
    location: shop.stock_location?.name || '—',
    keepers: shop.keepers?.length ?? 0,
    is_active: shop.is_active,
  }))
)

const stockModeOptions = computed(() => [
  { label: t('shops.stockModes.ownDedicated'), value: 'own' },
  { label: t('shops.stockModes.shareWarehouse'), value: 'shared_warehouse' },
])

const warehouseLocationOptions = computed(() => [
  { label: t('shops.createModal.selectLocationPlaceholder'), value: '' },
  ...(Array.isArray(locations.value)
    ? locations.value.map((l: any) => ({ label: `${l.name} (${l.code || l.id})`, value: l.id }))
    : []),
])

const createMutation = useMutation({
  mutationFn: () => {
    const payload: CreateShopPayload = {
      name: form.name.trim(),
      code: form.code.trim().toUpperCase(),
      stock_mode: form.stock_mode,
      phone: form.phone || undefined,
      notes: form.notes || undefined,
    }
    if (form.stock_mode === 'shared_warehouse') {
      payload.stock_location_id = form.stock_location_id
    }
    return shopsApi.createShop(payload)
  },
  onSuccess: (res) => {
    queryClient.invalidateQueries({ queryKey: ['shops'] })
    isCreateOpen.value = false
    errorMessage.value = ''
    const id = res.data?.data?.id
    if (id) router.push(`/shops/${id}`)
  },
  onError: (err: any) => {
    errorMessage.value = err?.message || t('shops.errors.createFailed')
  },
})

function openCreate() {
  form.name = ''
  form.code = ''
  form.stock_mode = 'own'
  form.stock_location_id = ''
  form.phone = ''
  form.notes = ''
  errorMessage.value = ''
  isCreateOpen.value = true
}

function submitCreate() {
  if (!form.name.trim() || !form.code.trim()) {
    errorMessage.value = t('shops.errors.nameAndCodeRequired')
    return
  }
  if (form.stock_mode === 'shared_warehouse' && !form.stock_location_id) {
    errorMessage.value = t('shops.errors.selectWarehouseLocation')
    return
  }
  createMutation.mutate()
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">{{ t('shops.title') }}</h1>
        <p class="text-slate-500 text-sm">{{ t('shops.subtitle') }}</p>
      </div>
      <UiButton v-if="canManage" size="sm" @click="openCreate">
        <Plus class="h-4 w-4 mr-2" /> {{ t('shops.addShop') }}
      </UiButton>
    </div>

    <UiTable
      :columns="columns"
      :data="rows"
      :loading="isLoading"
      :empty-title="t('shops.emptyTitle')"
      :empty-description="t('shops.emptyDescription')"
    >
      <template #cell(name)="{ item }">
        <button
          type="button"
          class="flex items-center text-left hover:text-blue-700"
          @click="router.push(`/shops/${item.id}`)"
        >
          <div class="h-8 w-8 rounded bg-slate-100 flex items-center justify-center mr-3">
            <Store class="h-4 w-4 text-slate-500" />
          </div>
          <span class="font-medium text-slate-900">{{ item.name }}</span>
        </button>
      </template>

      <template #cell(code)="{ value }">
        <span class="font-mono text-xs text-slate-600">{{ value }}</span>
      </template>

      <template #cell(location)="{ value }">
        <span class="inline-flex items-center gap-1 text-xs text-slate-600">
          <MapPin class="h-3.5 w-3.5" /> {{ value }}
        </span>
      </template>

      <template #cell(keepers)="{ value }">
        <span class="inline-flex items-center gap-1 text-sm text-slate-700">
          <Users class="h-3.5 w-3.5 text-slate-400" /> {{ value }}
        </span>
      </template>

      <template #cell(status)="{ item }">
        <UiBadge :variant="item.is_active ? 'success' : 'danger'">
          {{ item.is_active ? t('shops.statusActive') : t('shops.statusInactive') }}
        </UiBadge>
      </template>
    </UiTable>

    <UiModal v-model="isCreateOpen" :title="t('shops.createModal.title')" size="md">
      <form class="space-y-4" @submit.prevent="submitCreate">
        <div v-if="errorMessage" class="p-3 bg-red-50 text-red-700 text-xs font-semibold rounded-lg border border-red-200">
          {{ errorMessage }}
        </div>
        <UiInput
          v-model="form.name"
          :label="t('shops.createModal.nameLabel')"
          required
          :placeholder="t('shops.createModal.namePlaceholder')"
        />
        <UiInput
          v-model="form.code"
          :label="t('shops.createModal.codeLabel')"
          required
          :placeholder="t('shops.createModal.codePlaceholder')"
        />
        <UiSelect
          v-model="form.stock_mode"
          :label="t('shops.createModal.stockModeLabel')"
          :options="stockModeOptions"
        />
        <UiSelect
          v-if="form.stock_mode === 'shared_warehouse'"
          v-model="form.stock_location_id"
          :label="t('shops.createModal.warehouseLocationLabel')"
          :options="warehouseLocationOptions"
        />
        <UiInput
          v-model="form.phone"
          :label="t('shops.createModal.phoneLabel')"
          :placeholder="t('shops.createModal.phonePlaceholder')"
        />
        <UiInput
          v-model="form.notes"
          :label="t('shops.createModal.notesLabel')"
          :placeholder="t('shops.createModal.notesPlaceholder')"
        />
      </form>
      <template #footer>
        <UiButton variant="ghost" class="mr-2" @click="isCreateOpen = false">{{ t('common.cancel') }}</UiButton>
        <UiButton :loading="createMutation.isPending.value" @click="submitCreate">{{ t('shops.createModal.submit') }}</UiButton>
      </template>
    </UiModal>
  </div>
</template>
