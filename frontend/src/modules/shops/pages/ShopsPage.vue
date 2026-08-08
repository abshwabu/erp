<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { useRouter } from 'vue-router'
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

const columns = [
  { key: 'name', label: 'Shop' },
  { key: 'code', label: 'Code' },
  { key: 'stock_mode', label: 'Stock' },
  { key: 'location', label: 'Location' },
  { key: 'keepers', label: 'Keepers', align: 'center' as const },
  { key: 'status', label: 'Status', align: 'center' as const },
]

const rows = computed(() =>
  (Array.isArray(shops.value) ? shops.value : []).map((shop) => ({
    id: shop.id,
    name: shop.name,
    code: shop.code,
    stock_mode: shop.stock_mode === 'own' ? 'Own stock' : 'Shared warehouse',
    location: shop.stock_location?.name || '—',
    keepers: shop.keepers?.length ?? 0,
    is_active: shop.is_active,
  }))
)

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
    errorMessage.value = err?.message || 'Failed to create shop'
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
    errorMessage.value = 'Name and code are required'
    return
  }
  if (form.stock_mode === 'shared_warehouse' && !form.stock_location_id) {
    errorMessage.value = 'Select a warehouse location to share'
    return
  }
  createMutation.mutate()
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Shops</h1>
        <p class="text-slate-500 text-sm">Create shops, assign keepers, and choose own or shared stock.</p>
      </div>
      <UiButton v-if="canManage" size="sm" @click="openCreate">
        <Plus class="h-4 w-4 mr-2" /> Add Shop
      </UiButton>
    </div>

    <UiTable
      :columns="columns"
      :data="rows"
      :loading="isLoading"
      empty-title="No shops yet"
      empty-description="Create a shop to assign keepers and run POS per location."
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
          {{ item.is_active ? 'Active' : 'Inactive' }}
        </UiBadge>
      </template>
    </UiTable>

    <UiModal v-model="isCreateOpen" title="Create Shop" size="md">
      <form class="space-y-4" @submit.prevent="submitCreate">
        <div v-if="errorMessage" class="p-3 bg-red-50 text-red-700 text-xs font-semibold rounded-lg border border-red-200">
          {{ errorMessage }}
        </div>
        <UiInput v-model="form.name" label="Shop Name" required placeholder="Downtown Store" />
        <UiInput v-model="form.code" label="Code" required placeholder="DT01" />
        <UiSelect
          v-model="form.stock_mode"
          label="Stock Mode"
          :options="[
            { label: 'Own stock (dedicated warehouse)', value: 'own' },
            { label: 'Share warehouse stock', value: 'shared_warehouse' },
          ]"
        />
        <UiSelect
          v-if="form.stock_mode === 'shared_warehouse'"
          v-model="form.stock_location_id"
          label="Warehouse Location"
          :options="[
            { label: 'Select location…', value: '' },
            ...(Array.isArray(locations) ? locations.map((l: any) => ({ label: `${l.name} (${l.code || l.id})`, value: l.id })) : []),
          ]"
        />
        <UiInput v-model="form.phone" label="Phone" />
        <UiInput v-model="form.notes" label="Notes" />
      </form>
      <template #footer>
        <UiButton variant="ghost" class="mr-2" @click="isCreateOpen = false">Cancel</UiButton>
        <UiButton :loading="createMutation.isPending.value" @click="submitCreate">Create Shop</UiButton>
      </template>
    </UiModal>
  </div>
</template>
