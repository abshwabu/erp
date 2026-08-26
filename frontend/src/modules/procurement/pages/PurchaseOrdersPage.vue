<script setup lang="ts">
import { ref, computed, reactive } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { procurementApi, type PurchaseOrder } from '@/api/procurement'
import { inventoryApi } from '@/api/inventory'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import { Plus, Trash2, Package, Eye, CheckCircle, PackagePlus, Sparkles, Layers, Building2, MapPin } from '@lucide/vue'
import { useToast } from '@/composables/useToast'
import { formatCurrency } from '@/utils/format'
import CreateEditProductModal from '@/modules/inventory/components/CreateEditProductModal.vue'
import type { Product, ProductCategory } from '@/types/inventory'

const queryClient = useQueryClient()
const toast = useToast()

const isPOModalOpen = ref(false)
const isSupplierModalOpen = ref(false)
const isProductModalOpen = ref(false)
const isReceiveModalOpen = ref(false)
const isDetailsModalOpen = ref(false)

const selectedPO = ref<PurchaseOrder | null>(null)
const poToReceive = ref<PurchaseOrder | null>(null)
const receiveLocationId = ref('')
const activeLineIndexForNewProduct = ref<number | null>(null)

interface POLineDraft {
  product_id: string
  variant_id: string
  description: string
  quantity: number
  unit_cost: number
}

const poForm = reactive({
  supplier_id: '',
  order_date: new Date().toISOString().slice(0, 10),
  lines: [
    {
      product_id: '',
      variant_id: '',
      description: '',
      quantity: 1,
      unit_cost: 0,
    }
  ] as POLineDraft[],
})

const supplierForm = reactive({ name: '', email: '', phone: '' })

// ── Queries ──────────────────────────────────────────────────────────────────
const { data: orders, isLoading: ordersLoading } = useQuery({
  queryKey: ['procurement', 'orders'],
  queryFn: () => procurementApi.getPurchaseOrders().then((r) => r.data.data),
})

const { data: suppliers } = useQuery({
  queryKey: ['procurement', 'suppliers'],
  queryFn: () => procurementApi.getSuppliers().then((r) => r.data.data),
})

const { data: productsData } = useQuery({
  queryKey: ['inventory', 'products-simple'],
  queryFn: () => inventoryApi.getProducts({ status: 'active' }, 1).then((res) => res.data.data),
})

const { data: categoriesData } = useQuery({
  queryKey: ['inventory', 'categories'],
  queryFn: () => inventoryApi.getCategories().then((res) => res.data),
})

const { data: locations } = useQuery({
  queryKey: ['inventory', 'locations'],
  queryFn: () => inventoryApi.getLocations().then((r) => r.data),
})

// ── Computed Options ────────────────────────────────────────────────────────
const supplierOptions = computed(() =>
  (suppliers.value || []).map((s) => ({ label: s.name, value: s.id }))
)

const productsList = computed<Product[]>(() =>
  Array.isArray(productsData.value) ? productsData.value : []
)

const categoriesList = computed<ProductCategory[]>(() =>
  Array.isArray(categoriesData.value) ? categoriesData.value : []
)

const productSelectOptions = computed(() =>
  productsList.value.map((p) => ({
    label: `${p.name}${p.sku ? ` (${p.sku})` : ''}`,
    value: p.id,
  }))
)

const locationOptions = computed(() => {
  const list = Array.isArray(locations.value) ? locations.value : []
  return list.map((loc: any) => ({
    label: `${loc.name} (${loc.code || 'LOC'})`,
    value: loc.id,
  }))
})

function getProductVariants(productId: string) {
  const prod = productsList.value.find((p) => String(p.id) === String(productId))
  if (!prod || !Array.isArray(prod.variants)) return []
  return prod.variants.map((v: any) => ({
    label: `${v.name || v.sku || `Variant ${v.id}`}`,
    value: v.id,
  }))
}

function hasVariants(productId: string) {
  const prod = productsList.value.find((p) => String(p.id) === String(productId))
  return !!(prod?.has_variants || (Array.isArray(prod?.variants) && prod.variants.length > 0))
}

function handleProductChange(index: number) {
  const line = poForm.lines[index]
  if (!line) return
  const prod = productsList.value.find((p) => String(p.id) === String(line.product_id))
  if (!prod) {
    line.variant_id = ''
    line.unit_cost = 0
    line.description = ''
    return
  }

  if (hasVariants(line.product_id)) {
    const vars = prod.variants || []
    if (vars.length > 0 && vars[0]) {
      line.variant_id = vars[0].id
      line.unit_cost = typeof vars[0].cost_price === 'number' ? vars[0].cost_price / 100 : (prod.cost_price ? prod.cost_price / 100 : 0)
      line.description = `${prod.name} - ${vars[0].name}`
    } else {
      line.variant_id = ''
      line.unit_cost = prod.cost_price ? prod.cost_price / 100 : 0
      line.description = prod.name
    }
  } else {
    line.variant_id = ''
    line.unit_cost = prod.cost_price ? prod.cost_price / 100 : 0
    line.description = prod.name
  }
}

function handleVariantChange(index: number) {
  const line = poForm.lines[index]
  if (!line) return
  const prod = productsList.value.find((p) => String(p.id) === String(line.product_id))
  if (!prod) return
  const variant = (prod.variants || []).find((v: any) => String(v.id) === String(line.variant_id))
  if (variant) {
    line.unit_cost = typeof variant.cost_price === 'number' ? variant.cost_price / 100 : (prod.cost_price ? prod.cost_price / 100 : 0)
    line.description = `${prod.name} - ${variant.name}`
  }
}

function addLineItem() {
  poForm.lines.push({
    product_id: '',
    variant_id: '',
    description: '',
    quantity: 1,
    unit_cost: 0,
  })
}

function removeLineItem(index: number) {
  if (poForm.lines.length <= 1) {
    poForm.lines[0] = {
      product_id: '',
      variant_id: '',
      description: '',
      quantity: 1,
      unit_cost: 0,
    }
    return
  }
  poForm.lines.splice(index, 1)
}

function openCreateProductForLine(index: number) {
  activeLineIndexForNewProduct.value = index
  isProductModalOpen.value = true
}

async function handleProductCreated() {
  await queryClient.invalidateQueries({ queryKey: ['inventory', 'products-simple'] })
  const updatedProducts = await inventoryApi.getProducts({ status: 'active' }, 1).then((res) => res.data.data)
  if (Array.isArray(updatedProducts) && updatedProducts.length > 0) {
    const latestProd = updatedProducts[0]
    if (latestProd && activeLineIndexForNewProduct.value !== null) {
      const targetLine = poForm.lines[activeLineIndexForNewProduct.value]
      if (targetLine) {
        targetLine.product_id = latestProd.id
        handleProductChange(activeLineIndexForNewProduct.value)
      }
    }
  }
  isProductModalOpen.value = false
  activeLineIndexForNewProduct.value = null
  toast.success('Product created and added to Purchase Order')
}

const poTotalAmount = computed(() => {
  return poForm.lines.reduce((sum, line) => sum + (Number(line.quantity || 0) * Number(line.unit_cost || 0)), 0)
})

function openNewPO() {
  poForm.supplier_id = suppliers.value?.[0]?.id || ''
  poForm.order_date = new Date().toISOString().slice(0, 10)
  poForm.lines = [
    {
      product_id: productsList.value?.[0]?.id || '',
      variant_id: '',
      description: '',
      quantity: 1,
      unit_cost: 0,
    }
  ]
  if (poForm.lines[0]?.product_id) {
    handleProductChange(0)
  }
  isPOModalOpen.value = true
}

function viewPODetails(po: PurchaseOrder) {
  selectedPO.value = po
  isDetailsModalOpen.value = true
}

function openReceiveModal(po: PurchaseOrder) {
  poToReceive.value = po
  receiveLocationId.value = locations.value?.[0]?.id || ''
  isReceiveModalOpen.value = true
}

// ── Mutations ───────────────────────────────────────────────────────────────
const createPOMutation = useMutation({
  mutationFn: () => {
    if (!poForm.supplier_id) throw new Error('Please select a supplier.')
    const validLines = poForm.lines.filter((l) => l.product_id && l.quantity > 0)
    if (validLines.length === 0) throw new Error('Please add at least one product line item.')

    for (const l of validLines) {
      if (hasVariants(l.product_id) && !l.variant_id) {
        throw new Error('Please select a variant for each multi-variant product line.')
      }
    }

    return procurementApi.createPurchaseOrder({
      supplier_id: poForm.supplier_id,
      order_date: poForm.order_date,
      status: 'ordered',
      lines: validLines.map((l) => ({
        product_id: l.product_id,
        variant_id: l.variant_id || null,
        description: l.description,
        quantity: Number(l.quantity),
        unit_cost_cents: Math.round(Number(l.unit_cost) * 100),
      })),
    })
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['procurement', 'orders'] })
    isPOModalOpen.value = false
    toast.success('Purchase order created successfully')
  },
  onError: (e: any) => toast.error(e?.response?.data?.message || e?.message || 'Failed to create PO'),
})

const createSupplierMutation = useMutation({
  mutationFn: () => {
    if (!supplierForm.name.trim()) throw new Error('Supplier name is required')
    return procurementApi.createSupplier(supplierForm)
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['procurement', 'suppliers'] })
    isSupplierModalOpen.value = false
    supplierForm.name = ''
    supplierForm.email = ''
    supplierForm.phone = ''
    toast.success('Supplier created')
  },
  onError: (e: any) => toast.error(e?.response?.data?.message || e?.message || 'Failed to create supplier'),
})

const receiveMutation = useMutation({
  mutationFn: () => {
    if (!poToReceive.value?.id) throw new Error('No purchase order selected')
    return procurementApi.receivePurchaseOrder(poToReceive.value.id, receiveLocationId.value || undefined)
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['procurement', 'orders'] })
    queryClient.invalidateQueries({ queryKey: ['inventory'] })
    isReceiveModalOpen.value = false
    if (selectedPO.value && poToReceive.value && selectedPO.value.id === poToReceive.value.id) {
      isDetailsModalOpen.value = false
    }
    toast.success('Purchase Order received into warehouse inventory!')
  },
  onError: (e: any) => toast.error(e?.response?.data?.message || e?.message || 'Receive failed'),
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Procurement & Purchase Orders</h1>
        <p class="text-sm text-slate-500">
          Source products from suppliers, manage purchase order line items, and receive stock into warehouses.
        </p>
      </div>
      <div class="flex items-center gap-2.5">
        <UiButton variant="outline" @click="isSupplierModalOpen = true">
          <Building2 class="w-4 h-4 mr-2" /> Add Supplier
        </UiButton>
        <UiButton @click="openNewPO()">
          <Plus class="w-4 h-4 mr-2" /> New Purchase Order
        </UiButton>
      </div>
    </div>

    <!-- PO Table -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50/80">
          <tr>
            <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">PO Number</th>
            <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Supplier</th>
            <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Order Date</th>
            <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Items / Lines</th>
            <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
            <th class="px-5 py-3.5 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Total Amount</th>
            <th class="px-5 py-3.5 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 bg-white text-sm">
          <tr v-if="ordersLoading">
            <td colspan="7" class="px-5 py-10 text-center text-slate-500">Loading purchase orders…</td>
          </tr>
          <tr v-else-if="!(orders || []).length">
            <td colspan="7" class="px-5 py-12 text-center">
              <Package class="w-8 h-8 text-slate-300 mx-auto mb-2" />
              <p class="font-medium text-slate-700">No purchase orders created yet</p>
              <p class="text-xs text-slate-400 mt-1">Create your first purchase order to replenish stock.</p>
            </td>
          </tr>
          <tr v-for="po in orders" :key="po.id" class="hover:bg-slate-50/60 transition-colors">
            <td class="px-5 py-3.5 font-mono font-bold text-blue-600">
              <button type="button" @click="viewPODetails(po)" class="hover:underline">
                {{ po.number }}
              </button>
            </td>
            <td class="px-5 py-3.5 font-medium text-slate-900">
              {{ po.supplier?.name || '—' }}
            </td>
            <td class="px-5 py-3.5 text-slate-600">{{ po.order_date }}</td>
            <td class="px-5 py-3.5">
              <div class="flex items-center gap-1.5 flex-wrap">
                <span class="text-xs font-semibold px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 border border-slate-200/60">
                  {{ po.lines?.length || 0 }} item(s)
                </span>
                <span v-if="po.lines?.[0]?.product?.name" class="text-xs text-slate-500 truncate max-w-[180px]">
                  {{ po.lines[0].product.name }}
                  <span v-if="(po.lines?.length || 0) > 1" class="text-blue-600 font-medium"> +{{ po.lines.length - 1 }} more</span>
                </span>
              </div>
            </td>
            <td class="px-5 py-3.5">
              <UiBadge
                :variant="
                  po.status === 'received' ? 'success' :
                  po.status === 'ordered' ? 'info' :
                  po.status === 'cancelled' ? 'danger' : 'default'
                "
              >
                {{ po.status }}
              </UiBadge>
            </td>
            <td class="px-5 py-3.5 text-right font-mono font-bold text-slate-900">
              {{ formatCurrency(po.total_cents / 100) }}
            </td>
            <td class="px-5 py-3.5 text-right">
              <div class="inline-flex items-center gap-2">
                <UiButton size="sm" variant="ghost" class="text-xs" @click="viewPODetails(po)">
                  <Eye class="w-3.5 h-3.5 mr-1" /> View
                </UiButton>
                <UiButton
                  v-if="po.status !== 'received' && po.status !== 'cancelled'"
                  size="sm"
                  variant="outline"
                  class="text-xs border-emerald-300 text-emerald-700 hover:bg-emerald-50"
                  @click="openReceiveModal(po)"
                >
                  <PackagePlus class="w-3.5 h-3.5 mr-1" /> Receive
                </UiButton>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Create Purchase Order Modal -->
    <UiModal v-model="isPOModalOpen" title="Create Purchase Order" size="2xl">
      <form @submit.prevent="createPOMutation.mutate()" class="space-y-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <div class="flex items-center justify-between">
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Supplier *</label>
              <button
                type="button"
                class="text-xs font-bold text-blue-600 hover:underline flex items-center gap-1"
                @click="isSupplierModalOpen = true"
              >
                <Plus class="w-3.5 h-3.5" /> New Supplier
              </button>
            </div>
            <UiSelect
              v-model="poForm.supplier_id"
              :options="supplierOptions"
              placeholder="Select supplier..."
              required
            />
          </div>

          <UiInput
            v-model="poForm.order_date"
            type="date"
            label="Order Date *"
            required
          />
        </div>

        <!-- Line Items Section -->
        <div class="space-y-3 pt-2">
          <div class="flex items-center justify-between border-b border-slate-200 pb-2">
            <div>
              <h3 class="text-sm font-bold text-slate-900">Purchase Order Line Items</h3>
              <p class="text-xs text-slate-500">Select existing product & variant, or create a brand new product.</p>
            </div>
            <div class="flex items-center gap-2">
              <button
                type="button"
                class="text-xs font-bold text-blue-600 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 px-3 py-1.5 rounded-lg flex items-center gap-1.5 transition-colors"
                @click="openCreateProductForLine(poForm.lines.length - 1)"
              >
                <Sparkles class="w-3.5 h-3.5 text-blue-600" /> + Create New Product
              </button>
              <UiButton type="button" size="sm" variant="outline" @click="addLineItem()">
                <Plus class="w-3.5 h-3.5 mr-1" /> Add Line
              </UiButton>
            </div>
          </div>

          <!-- Lines List -->
          <div class="space-y-3 max-h-[380px] overflow-y-auto pr-1">
            <div
              v-for="(line, idx) in poForm.lines"
              :key="idx"
              class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-3 relative group"
            >
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-700">Line #{{ idx + 1 }}</span>
                <button
                  type="button"
                  class="text-slate-400 hover:text-red-500 transition-colors p-1"
                  @click="removeLineItem(idx)"
                  title="Remove Line Item"
                >
                  <Trash2 class="w-4 h-4" />
                </button>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                <!-- Product Selector -->
                <div :class="hasVariants(line.product_id) ? 'sm:col-span-4' : 'sm:col-span-6'">
                  <div class="flex items-center justify-between mb-1">
                    <label class="text-xs font-bold text-slate-700">Product *</label>
                  </div>
                  <UiSelect
                    v-model="line.product_id"
                    :options="[{ label: 'Select product...', value: '' }, ...productSelectOptions]"
                    @update:model-value="handleProductChange(idx)"
                    required
                  />
                </div>

                <!-- Variant Selector (if product has variants) -->
                <div v-if="hasVariants(line.product_id)" class="sm:col-span-3">
                  <label class="text-xs font-bold text-slate-700 mb-1 block">Variant *</label>
                  <UiSelect
                    v-model="line.variant_id"
                    :options="getProductVariants(line.product_id)"
                    @update:model-value="handleVariantChange(idx)"
                    required
                  />
                </div>

                <!-- Quantity -->
                <div class="sm:col-span-2">
                  <UiInput
                    v-model.number="line.quantity"
                    type="number"
                    min="1"
                    step="1"
                    label="Qty *"
                    required
                  />
                </div>

                <!-- Unit Cost -->
                <div class="sm:col-span-3">
                  <UiInput
                    v-model.number="line.unit_cost"
                    type="number"
                    min="0"
                    step="0.01"
                    label="Cost ($) *"
                    required
                  >
                    <template #prefix><span class="text-xs font-bold text-slate-400">$</span></template>
                  </UiInput>
                </div>
              </div>

              <!-- Line Summary Row -->
              <div class="flex items-center justify-between pt-1 text-xs text-slate-500">
                <span class="truncate max-w-[320px]">{{ line.description || 'No description' }}</span>
                <span class="font-bold text-slate-900 font-mono">
                  Line Total: ${{ ((Number(line.quantity || 0) * Number(line.unit_cost || 0))).toFixed(2) }}
                </span>
              </div>
            </div>
          </div>

          <!-- Total Banner -->
          <div class="flex items-center justify-between p-4 rounded-xl bg-slate-900 text-white font-mono">
            <span class="text-sm uppercase tracking-wider text-slate-300 font-bold">Purchase Order Total:</span>
            <span class="text-xl font-black text-emerald-400">${{ poTotalAmount.toFixed(2) }}</span>
          </div>
        </div>

        <div class="flex justify-end gap-3 pt-2">
          <UiButton variant="ghost" @click="isPOModalOpen = false">Cancel</UiButton>
          <UiButton :loading="createPOMutation.isPending.value" type="submit">
            Create Purchase Order
          </UiButton>
        </div>
      </form>
    </UiModal>

    <!-- Create Supplier Modal -->
    <UiModal v-model="isSupplierModalOpen" title="Add Supplier" size="md">
      <form @submit.prevent="createSupplierMutation.mutate()" class="space-y-4">
        <UiInput v-model="supplierForm.name" label="Supplier Company / Name *" required placeholder="e.g. Acme Corp" />
        <UiInput v-model="supplierForm.email" label="Email Address" type="email" placeholder="contact@acme.com" />
        <UiInput v-model="supplierForm.phone" label="Phone Number" placeholder="+1 (555) 000-0000" />
        <div class="flex justify-end gap-2 pt-2">
          <UiButton variant="ghost" @click="isSupplierModalOpen = false">Cancel</UiButton>
          <UiButton :loading="createSupplierMutation.isPending.value" type="submit">Save Supplier</UiButton>
        </div>
      </form>
    </UiModal>

    <!-- Receive Stock Modal -->
    <UiModal v-model="isReceiveModalOpen" title="Receive Purchase Order Stock" size="md">
      <div class="space-y-4">
        <p class="text-sm text-slate-600">
          Confirm receiving stock for Purchase Order <strong class="text-slate-900 font-mono">{{ poToReceive?.number }}</strong>.
          Products and variants will automatically be credited to the chosen stock location.
        </p>

        <div class="space-y-1.5">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Destination Warehouse / Location *</label>
          <UiSelect
            v-model="receiveLocationId"
            :options="locationOptions"
            placeholder="Select location..."
            required
          />
        </div>

        <div class="p-3 bg-blue-50 rounded-xl text-xs text-blue-800 border border-blue-200 flex items-start gap-2">
          <MapPin class="w-4 h-4 text-blue-600 shrink-0 mt-0.5" />
          <span>Stock movement will record a <strong>receive</strong> entry tied to this Purchase Order.</span>
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <UiButton variant="ghost" @click="isReceiveModalOpen = false">Cancel</UiButton>
          <UiButton
            :loading="receiveMutation.isPending.value"
            class="bg-emerald-600 hover:bg-emerald-700 text-white"
            @click="receiveMutation.mutate()"
          >
            Confirm & Receive Stock
          </UiButton>
        </div>
      </div>
    </UiModal>

    <!-- PO Details Modal -->
    <UiModal v-model="isDetailsModalOpen" :title="`Purchase Order Details - ${selectedPO?.number}`" size="2xl">
      <div v-if="selectedPO" class="space-y-5">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 p-4 rounded-xl bg-slate-50 border border-slate-200">
          <div>
            <span class="text-xs text-slate-500 uppercase font-bold block">Supplier</span>
            <span class="font-bold text-slate-900">{{ selectedPO.supplier?.name || '—' }}</span>
          </div>
          <div>
            <span class="text-xs text-slate-500 uppercase font-bold block">Order Date</span>
            <span class="font-medium text-slate-900">{{ selectedPO.order_date }}</span>
          </div>
          <div>
            <span class="text-xs text-slate-500 uppercase font-bold block">Status</span>
            <UiBadge :variant="selectedPO.status === 'received' ? 'success' : 'info'" class="mt-1">
              {{ selectedPO.status }}
            </UiBadge>
          </div>
          <div>
            <span class="text-xs text-slate-500 uppercase font-bold block">Total Cost</span>
            <span class="font-mono font-black text-slate-900 text-base">
              {{ formatCurrency(selectedPO.total_cents / 100) }}
            </span>
          </div>
        </div>

        <!-- Line Items Details Table -->
        <div class="border border-slate-200 rounded-xl overflow-hidden">
          <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-4 py-2.5 text-left text-xs font-bold text-slate-500 uppercase">Product & Variant</th>
                <th class="px-4 py-2.5 text-left text-xs font-bold text-slate-500 uppercase">SKU</th>
                <th class="px-4 py-2.5 text-center text-xs font-bold text-slate-500 uppercase">Ordered</th>
                <th class="px-4 py-2.5 text-center text-xs font-bold text-slate-500 uppercase">Received</th>
                <th class="px-4 py-2.5 text-right text-xs font-bold text-slate-500 uppercase">Unit Cost</th>
                <th class="px-4 py-2.5 text-right text-xs font-bold text-slate-500 uppercase">Subtotal</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
              <tr v-for="line in selectedPO.lines || []" :key="line.id">
                <td class="px-4 py-3">
                  <div class="font-semibold text-slate-900">{{ line.product?.name || line.description }}</div>
                  <div v-if="line.variant" class="text-xs text-blue-600 font-medium">Variant: {{ line.variant.name }}</div>
                </td>
                <td class="px-4 py-3 font-mono text-xs text-slate-600">
                  {{ line.variant?.sku || line.product?.sku || '—' }}
                </td>
                <td class="px-4 py-3 text-center font-bold text-slate-900">{{ line.quantity }}</td>
                <td class="px-4 py-3 text-center">
                  <UiBadge :variant="(line.received_quantity || 0) >= line.quantity ? 'success' : 'default'">
                    {{ line.received_quantity || 0 }}
                  </UiBadge>
                </td>
                <td class="px-4 py-3 text-right font-mono">${{ (line.unit_cost_cents / 100).toFixed(2) }}</td>
                <td class="px-4 py-3 text-right font-mono font-bold text-slate-900">
                  ${{ ((Number(line.quantity) * Number(line.unit_cost_cents)) / 100).toFixed(2) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex justify-between items-center pt-2">
          <UiButton
            v-if="selectedPO.status !== 'received' && selectedPO.status !== 'cancelled'"
            variant="outline"
            class="border-emerald-300 text-emerald-700 hover:bg-emerald-50"
            @click="openReceiveModal(selectedPO)"
          >
            <PackagePlus class="w-4 h-4 mr-2" /> Receive Into Stock
          </UiButton>
          <div v-else></div>

          <UiButton variant="ghost" @click="isDetailsModalOpen = false">Close</UiButton>
        </div>
      </div>
    </UiModal>

    <!-- Create New Product Modal (Integrated with full options & variants) -->
    <CreateEditProductModal
      v-model="isProductModalOpen"
      :categories="categoriesList"
      @saved="handleProductCreated"
    />
  </div>
</template>
