<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { ArrowRightLeft, MapPin, PackagePlus, Plus } from '@lucide/vue'
import { inventoryApi } from '@/api/inventory'
import { warehouseApi } from '@/api/warehouse'
import { useToast } from '@/composables/useToast'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiCard from '@/components/ui/UiCard.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiTable from '@/components/ui/UiTable.vue'

type TabId = 'locations' | 'receive' | 'transfer'

const toast = useToast()
const queryClient = useQueryClient()
const activeTab = ref<TabId>('locations')

const locationForm = reactive({
  name: '',
  code: '',
  type: 'storage',
})

const receiveForm = reactive({
  product_id: '' as string,
  location_id: '' as string,
  quantity: 1,
  unit_cost: 0,
})

const transferForm = reactive({
  product_id: '' as string,
  from_location_id: '' as string,
  to_location_id: '' as string,
  quantity: 1,
})

const { data: locations, isLoading: locationsLoading } = useQuery({
  queryKey: ['warehouse', 'locations'],
  queryFn: () => warehouseApi.getLocations().then(res => res.data),
})

const { data: products } = useQuery({
  queryKey: ['inventory', 'products-simple'],
  queryFn: () => inventoryApi.getProducts({ status: 'active' }, 1).then(res => res.data.data),
})

const productOptions = computed(() =>
  (Array.isArray(products.value) ? products.value : []).map((p: any) => ({
    label: `${p.name}${p.sku ? ` (${p.sku})` : ''}`,
    value: p.id,
  }))
)

const locationOptions = computed(() =>
  (Array.isArray(locations.value) ? locations.value : []).map((loc: any) => ({
    label: `${loc.name} (${loc.code})`,
    value: loc.id,
  }))
)

const locationColumns = [
  { key: 'name', label: 'Name' },
  { key: 'code', label: 'Code' },
  { key: 'type', label: 'Type' },
  { key: 'warehouse', label: 'Warehouse' },
  { key: 'status', label: 'Status', align: 'center' as const },
]

const locationRows = computed(() =>
  (Array.isArray(locations.value) ? locations.value : []).map((loc: any) => ({
    id: loc.id,
    name: loc.name,
    code: loc.code,
    type: loc.type,
    warehouse: loc.warehouse?.name || '—',
    isActive: Boolean(loc.is_active),
  }))
)

function invalidateStockQueries() {
  queryClient.invalidateQueries({ queryKey: ['warehouse', 'locations'] })
  queryClient.invalidateQueries({ queryKey: ['inventory', 'locations'] })
  queryClient.invalidateQueries({ queryKey: ['inventory', 'stock'] })
  queryClient.invalidateQueries({ queryKey: ['inventory', 'stock-summary'] })
  queryClient.invalidateQueries({ queryKey: ['inventory', 'product-stock'] })
  queryClient.invalidateQueries({ queryKey: ['inventory', 'products'] })
}

const createLocationMutation = useMutation({
  mutationFn: () =>
    warehouseApi.createLocation({
      name: locationForm.name.trim(),
      code: locationForm.code.trim().toUpperCase(),
      type: locationForm.type || 'storage',
    }),
  onSuccess: () => {
    locationForm.name = ''
    locationForm.code = ''
    locationForm.type = 'storage'
    invalidateStockQueries()
    toast.success('Location created')
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || 'Failed to create location')
  },
})

const receiveMutation = useMutation({
  mutationFn: () =>
    warehouseApi.receive({
      product_id: receiveForm.product_id,
      location_id: receiveForm.location_id,
      quantity: Number(receiveForm.quantity),
      unit_cost: Number(receiveForm.unit_cost) || 0,
    }),
  onSuccess: () => {
    receiveForm.quantity = 1
    receiveForm.unit_cost = 0
    invalidateStockQueries()
    toast.success('Stock received')
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || 'Failed to receive stock')
  },
})

const transferMutation = useMutation({
  mutationFn: () =>
    warehouseApi.transfer({
      product_id: transferForm.product_id,
      from_location_id: transferForm.from_location_id,
      to_location_id: transferForm.to_location_id,
      quantity: Number(transferForm.quantity),
    }),
  onSuccess: () => {
    transferForm.quantity = 1
    invalidateStockQueries()
    toast.success('Stock transferred')
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || 'Failed to transfer stock')
  },
})

function submitLocation() {
  if (!locationForm.name.trim() || !locationForm.code.trim()) {
    toast.error('Name and code are required')
    return
  }
  createLocationMutation.mutate()
}

function submitReceive() {
  if (!receiveForm.product_id || !receiveForm.location_id) {
    toast.error('Product and location are required')
    return
  }
  if (!receiveForm.quantity || receiveForm.quantity < 1) {
    toast.error('Quantity must be at least 1')
    return
  }
  receiveMutation.mutate()
}

function submitTransfer() {
  if (!transferForm.product_id || !transferForm.from_location_id || !transferForm.to_location_id) {
    toast.error('Product and both locations are required')
    return
  }
  if (transferForm.from_location_id === transferForm.to_location_id) {
    toast.error('From and to locations must differ')
    return
  }
  if (!transferForm.quantity || transferForm.quantity < 1) {
    toast.error('Quantity must be at least 1')
    return
  }
  transferMutation.mutate()
}

const tabs = [
  { id: 'locations' as const, label: 'Locations', icon: MapPin },
  { id: 'receive' as const, label: 'Receive', icon: PackagePlus },
  { id: 'transfer' as const, label: 'Transfer', icon: ArrowRightLeft },
]

const typeOptions = [
  { label: 'Storage', value: 'storage' },
  { label: 'Receive', value: 'receive' },
  { label: 'Pick', value: 'pick' },
  { label: 'Pack', value: 'pack' },
  { label: 'Stage', value: 'stage' },
  { label: 'Ship', value: 'ship' },
]
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-slate-900">Warehouse</h1>
      <p class="text-slate-500 text-sm">Manage locations, receive stock, and transfer between bins.</p>
    </div>

    <div class="flex border-b border-slate-200">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        type="button"
        class="inline-flex items-center gap-2 px-6 py-3 text-sm font-medium border-b-2 transition-colors"
        :class="activeTab === tab.id ? 'border-primary-600 text-primary-600' : 'border-transparent text-slate-500 hover:text-slate-700'"
        @click="activeTab = tab.id"
      >
        <component :is="tab.icon" class="h-4 w-4" />
        {{ tab.label }}
      </button>
    </div>

    <!-- Locations -->
    <div v-if="activeTab === 'locations'" class="space-y-6">
      <UiCard title="Create location">
        <form class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end" @submit.prevent="submitLocation">
          <UiInput v-model="locationForm.name" label="Name" required placeholder="Receiving dock" />
          <UiInput v-model="locationForm.code" label="Code" required placeholder="RECV-01" />
          <UiSelect v-model="locationForm.type" label="Type" :options="typeOptions" />
          <UiButton type="submit" :disabled="createLocationMutation.isPending">
            <Plus class="h-4 w-4 mr-2" />
            Add location
          </UiButton>
        </form>
      </UiCard>

      <UiTable
        :columns="locationColumns"
        :data="locationRows"
        :loading="locationsLoading"
        empty-title="No locations yet"
        empty-description="Create a stock location above to get started."
      >
        <template #cell(name)="{ item }">
          <span class="font-medium text-slate-900">{{ item.name }}</span>
        </template>
        <template #cell(code)="{ value }">
          <code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded">{{ value }}</code>
        </template>
        <template #cell(type)="{ value }">
          <span class="capitalize text-slate-600">{{ value }}</span>
        </template>
        <template #cell(status)="{ item }">
          <UiBadge :variant="item.isActive ? 'success' : 'default'">
            {{ item.isActive ? 'Active' : 'Inactive' }}
          </UiBadge>
        </template>
      </UiTable>
    </div>

    <!-- Receive -->
    <div v-else-if="activeTab === 'receive'">
      <UiCard title="Receive stock">
        <form class="space-y-4 max-w-xl" @submit.prevent="submitReceive">
          <UiSelect
            v-model="receiveForm.product_id"
            label="Product"
            required
            :options="productOptions"
          />
          <UiSelect
            v-model="receiveForm.location_id"
            label="Location"
            required
            :options="locationOptions"
          />
          <div class="grid grid-cols-2 gap-4">
            <UiInput
              v-model.number="receiveForm.quantity"
              label="Quantity"
              type="number"
              min="1"
              required
            />
            <UiInput
              v-model.number="receiveForm.unit_cost"
              label="Unit cost (cents)"
              type="number"
              min="0"
              help-text="Optional; defaults to product cost"
            />
          </div>
          <UiButton type="submit" :disabled="receiveMutation.isPending">
            <PackagePlus class="h-4 w-4 mr-2" />
            Receive stock
          </UiButton>
        </form>
      </UiCard>
    </div>

    <!-- Transfer -->
    <div v-else>
      <UiCard title="Transfer between locations">
        <form class="space-y-4 max-w-xl" @submit.prevent="submitTransfer">
          <UiSelect
            v-model="transferForm.product_id"
            label="Product"
            required
            :options="productOptions"
          />
          <UiSelect
            v-model="transferForm.from_location_id"
            label="From location"
            required
            :options="locationOptions"
          />
          <UiSelect
            v-model="transferForm.to_location_id"
            label="To location"
            required
            :options="locationOptions"
          />
          <UiInput
            v-model.number="transferForm.quantity"
            label="Quantity"
            type="number"
            min="1"
            required
          />
          <UiButton type="submit" :disabled="transferMutation.isPending">
            <ArrowRightLeft class="h-4 w-4 mr-2" />
            Transfer stock
          </UiButton>
        </form>
      </UiCard>
    </div>
  </div>
</template>
