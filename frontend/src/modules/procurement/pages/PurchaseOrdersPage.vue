<script setup lang="ts">
import { ref, computed } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { procurementApi } from '@/api/procurement'
import { inventoryApi } from '@/api/inventory'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiModal from '@/components/ui/UiModal.vue'
import { Plus } from '@lucide/vue'
import { useToast } from '@/composables/useToast'

const queryClient = useQueryClient()
const toast = useToast()
const isModalOpen = ref(false)
const supplierModal = ref(false)

const form = ref({
  supplier_id: '',
  order_date: new Date().toISOString().slice(0, 10),
  description: '',
  quantity: 1,
  unit_cost_cents: 0,
  product_id: '',
})
const supplierForm = ref({ name: '', email: '', phone: '' })

const { data: orders, isLoading } = useQuery({
  queryKey: ['procurement', 'orders'],
  queryFn: () => procurementApi.getPurchaseOrders().then(r => r.data.data),
})

const { data: suppliers } = useQuery({
  queryKey: ['procurement', 'suppliers'],
  queryFn: () => procurementApi.getSuppliers().then(r => r.data.data),
})

const { data: locations } = useQuery({
  queryKey: ['inventory', 'locations'],
  queryFn: () => inventoryApi.getLocations().then(r => r.data?.data ?? r.data),
})

const supplierOptions = computed(() =>
  (suppliers.value || []).map(s => ({ label: s.name, value: s.id }))
)

const createMutation = useMutation({
  mutationFn: () =>
    procurementApi.createPurchaseOrder({
      supplier_id: form.value.supplier_id,
      order_date: form.value.order_date,
      status: 'ordered',
      lines: [{
        product_id: form.value.product_id || null,
        description: form.value.description,
        quantity: form.value.quantity,
        unit_cost_cents: form.value.unit_cost_cents,
      }],
    }),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['procurement', 'orders'] })
    isModalOpen.value = false
    toast.success('Purchase order created')
  },
  onError: (e: any) => toast.error(e?.response?.data?.message || 'Failed to create PO'),
})

const createSupplierMutation = useMutation({
  mutationFn: () => procurementApi.createSupplier(supplierForm.value),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['procurement', 'suppliers'] })
    supplierModal.value = false
    toast.success('Supplier created')
  },
})

async function receive(id: string) {
  try {
    const locId = Array.isArray(locations.value) ? locations.value[0]?.id : null
    await procurementApi.receivePurchaseOrder(id, locId)
    queryClient.invalidateQueries({ queryKey: ['procurement', 'orders'] })
    toast.success('PO received into stock')
  } catch (e: any) {
    toast.error(e?.response?.data?.message || 'Receive failed')
  }
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Procurement</h1>
        <p class="text-sm text-slate-500">Suppliers and purchase orders.</p>
      </div>
      <div class="flex gap-2">
        <UiButton variant="outline" @click="supplierModal = true">Add Supplier</UiButton>
        <UiButton @click="isModalOpen = true"><Plus class="w-4 h-4 mr-2" /> New PO</UiButton>
      </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Number</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Supplier</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Date</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Status</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Total</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="isLoading"><td colspan="6" class="px-4 py-8 text-center text-slate-500">Loading…</td></tr>
          <tr v-else-if="!(orders || []).length"><td colspan="6" class="px-4 py-8 text-center text-slate-500">No purchase orders yet.</td></tr>
          <tr v-for="po in orders" :key="po.id" class="hover:bg-slate-50">
            <td class="px-4 py-3 font-mono text-sm">{{ po.number }}</td>
            <td class="px-4 py-3">{{ po.supplier?.name || '—' }}</td>
            <td class="px-4 py-3 text-sm">{{ po.order_date }}</td>
            <td class="px-4 py-3 text-sm capitalize">{{ po.status }}</td>
            <td class="px-4 py-3 text-right font-mono">${{ (po.total_cents / 100).toFixed(2) }}</td>
            <td class="px-4 py-3 text-right">
              <UiButton
                v-if="po.status !== 'received' && po.status !== 'cancelled'"
                size="sm"
                variant="outline"
                @click="receive(po.id)"
              >
                Receive
              </UiButton>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <UiModal v-model="isModalOpen" title="New Purchase Order">
      <div class="space-y-4">
        <UiSelect v-model="form.supplier_id" label="Supplier" :options="supplierOptions" placeholder="Select supplier" />
        <UiInput v-model="form.order_date" type="date" label="Order Date" />
        <UiInput v-model="form.description" label="Line Description" />
        <div class="grid grid-cols-2 gap-3">
          <UiInput v-model.number="form.quantity" type="number" label="Quantity" />
          <UiInput v-model.number="form.unit_cost_cents" type="number" label="Unit Cost (cents)" />
        </div>
        <UiInput v-model="form.product_id" label="Product ID (optional)" placeholder="UUID for stock receive" />
        <div class="flex justify-end gap-2">
          <UiButton variant="outline" @click="isModalOpen = false">Cancel</UiButton>
          <UiButton :loading="createMutation.isPending.value" @click="createMutation.mutate()">Create</UiButton>
        </div>
      </div>
    </UiModal>

    <UiModal v-model="supplierModal" title="Add Supplier">
      <div class="space-y-4">
        <UiInput v-model="supplierForm.name" label="Name" required />
        <UiInput v-model="supplierForm.email" label="Email" type="email" />
        <UiInput v-model="supplierForm.phone" label="Phone" />
        <div class="flex justify-end gap-2">
          <UiButton variant="outline" @click="supplierModal = false">Cancel</UiButton>
          <UiButton :loading="createSupplierMutation.isPending.value" @click="createSupplierMutation.mutate()">Save</UiButton>
        </div>
      </div>
    </UiModal>
  </div>
</template>
