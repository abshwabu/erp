<script setup lang="ts">
import { ref, computed, reactive } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import {
  manufacturingApi,
  type BillOfMaterial,
  type WorkOrder,
} from '@/api/manufacturing'
import { inventoryApi } from '@/api/inventory'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import {
  Plus,
  Play,
  CheckCircle2,
  XCircle,
  Trash2,
  Layers,
  Factory,
  Boxes,
  Calendar,
  AlertCircle,
  Eye,
  ArrowRight,
  Clock,
  Sparkles,
  MapPin,
  FileSpreadsheet,
  Check,
  Archive,
} from '@lucide/vue'
import { useToast } from '@/composables/useToast'
import { formatCurrency } from '@/utils/format'
import type { Product } from '@/types/inventory'

const queryClient = useQueryClient()
const toast = useToast()

const activeTab = ref<'work-orders' | 'boms'>('work-orders')

// Modal States
const isCreateWOModalOpen = ref(false)
const isCreateBOMModalOpen = ref(false)
const isCompleteWOModalOpen = ref(false)
const isViewWODetailsModalOpen = ref(false)
const isViewBOMDetailsModalOpen = ref(false)

const selectedWO = ref<WorkOrder | null>(null)
const selectedBOM = ref<BillOfMaterial | null>(null)
const completeLocationId = ref('')

// ── Form States ─────────────────────────────────────────────────────────────
const woForm = reactive({
  bom_id: '',
  quantity: 1,
  priority: 'normal' as 'low' | 'normal' | 'high' | 'urgent',
  planned_start: new Date().toISOString().slice(0, 10),
  planned_end: '',
  notes: '',
})

interface BOMLineDraft {
  material_id: string
  quantity: number
  unit: string
  notes: string
}

const bomForm = reactive({
  product_id: '',
  name: '',
  description: '',
  output_quantity: 1,
  status: 'active' as 'draft' | 'active',
  lines: [
    {
      material_id: '',
      quantity: 1,
      unit: 'pcs',
      notes: '',
    },
  ] as BOMLineDraft[],
})

// ── Queries ─────────────────────────────────────────────────────────────────
const { data: workOrdersData, isLoading: woLoading } = useQuery({
  queryKey: ['manufacturing', 'work-orders'],
  queryFn: () => manufacturingApi.getWorkOrders().then((r) => r.data.data),
})

const { data: bomsData, isLoading: bomsLoading } = useQuery({
  queryKey: ['manufacturing', 'boms'],
  queryFn: () => manufacturingApi.getBoms().then((r) => r.data.data),
})

const { data: productsData } = useQuery({
  queryKey: ['inventory', 'products-simple'],
  queryFn: () => inventoryApi.getProducts({ status: 'active' }, 1).then((res) => res.data.data),
})

const { data: locationsData } = useQuery({
  queryKey: ['inventory', 'locations'],
  queryFn: () => inventoryApi.getLocations().then((r) => r.data),
})

// ── Computed ────────────────────────────────────────────────────────────────
const workOrders = computed<WorkOrder[]>(() =>
  Array.isArray(workOrdersData.value) ? workOrdersData.value : []
)

const boms = computed<BillOfMaterial[]>(() =>
  Array.isArray(bomsData.value) ? bomsData.value : []
)

const activeBoms = computed(() =>
  boms.value.filter((b) => b.status === 'active')
)

const products = computed<Product[]>(() =>
  Array.isArray(productsData.value) ? productsData.value : []
)

const productOptions = computed(() =>
  products.value.map((p) => ({
    label: `${p.name}${p.sku ? ` (${p.sku})` : ''}`,
    value: p.id,
  }))
)

const bomOptions = computed(() =>
  activeBoms.value.map((b) => ({
    label: `${b.name} — Produces: ${b.product?.name || 'Product'} (Yield: ${b.output_quantity})`,
    value: b.id,
  }))
)

const locationOptions = computed(() => {
  const list = Array.isArray(locationsData.value) ? locationsData.value : []
  return list.map((loc: any) => ({
    label: `${loc.name} (${loc.code || 'LOC'})`,
    value: loc.id,
  }))
})

// KPI Metrics
const totalWOs = computed(() => workOrders.value.length)
const inProgressWOs = computed(
  () => workOrders.value.filter((wo) => wo.status === 'in_progress').length
)
const completedWOs = computed(
  () => workOrders.value.filter((wo) => wo.status === 'completed').length
)
const totalActiveBoms = computed(() => activeBoms.value.length)

// Selected BOM in Create WO Form for Realtime Material Requirements
const selectedBOMForWO = computed(() => {
  if (!woForm.bom_id) return null
  return boms.value.find((b) => String(b.id) === String(woForm.bom_id)) || null
})

const estimatedMaterialsForWO = computed(() => {
  if (!selectedBOMForWO.value || !Array.isArray(selectedBOMForWO.value.lines)) return []
  const ratio = (Number(woForm.quantity) || 1) / (selectedBOMForWO.value.output_quantity || 1)
  return selectedBOMForWO.value.lines.map((line) => ({
    ...line,
    totalRequired: Math.ceil(line.quantity * ratio),
  }))
})

// ── Helpers ─────────────────────────────────────────────────────────────────
function openCreateWO(preselectedBomId?: string) {
  woForm.bom_id = preselectedBomId || activeBoms.value[0]?.id || ''
  woForm.quantity = 1
  woForm.priority = 'normal'
  woForm.planned_start = new Date().toISOString().slice(0, 10)
  woForm.planned_end = ''
  woForm.notes = ''
  isCreateWOModalOpen.value = true
}

function openCreateBOM() {
  bomForm.product_id = products.value[0]?.id || ''
  bomForm.name = products.value[0]?.name ? `${products.value[0].name} Recipe` : ''
  bomForm.description = ''
  bomForm.output_quantity = 1
  bomForm.status = 'active'
  bomForm.lines = [
    {
      material_id: products.value[1]?.id || products.value[0]?.id || '',
      quantity: 1,
      unit: 'pcs',
      notes: '',
    },
  ]
  isCreateBOMModalOpen.value = true
}

function onFinishedProductSelect() {
  const prod = products.value.find((p) => String(p.id) === String(bomForm.product_id))
  if (prod && !bomForm.name) {
    bomForm.name = `${prod.name} Assembly BOM`
  }
}

function addBOMLine() {
  bomForm.lines.push({
    material_id: products.value[0]?.id || '',
    quantity: 1,
    unit: 'pcs',
    notes: '',
  })
}

function removeBOMLine(index: number) {
  if (bomForm.lines.length <= 1) return
  bomForm.lines.splice(index, 1)
}

function viewWODetails(wo: WorkOrder) {
  selectedWO.value = wo
  isViewWODetailsModalOpen.value = true
}

function viewBOMDetails(bom: BillOfMaterial) {
  selectedBOM.value = bom
  isViewBOMDetailsModalOpen.value = true
}

function openCompleteModal(wo: WorkOrder) {
  selectedWO.value = wo
  completeLocationId.value = locationsData.value?.[0]?.id || ''
  isCompleteWOModalOpen.value = true
}

// ── Mutations ───────────────────────────────────────────────────────────────
const createWOMutation = useMutation({
  mutationFn: () => {
    if (!woForm.bom_id) throw new Error('Please select a Bill of Material.')
    if (!woForm.quantity || woForm.quantity < 1) throw new Error('Quantity must be at least 1.')

    return manufacturingApi.createWorkOrder({
      bom_id: woForm.bom_id,
      quantity: Number(woForm.quantity),
      priority: woForm.priority,
      planned_start: woForm.planned_start || undefined,
      planned_end: woForm.planned_end || undefined,
      notes: woForm.notes || undefined,
    })
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['manufacturing', 'work-orders'] })
    isCreateWOModalOpen.value = false
    toast.success('Production Work Order created successfully')
  },
  onError: (e: any) => toast.error(e?.response?.data?.message || e?.message || 'Failed to create work order'),
})

const startWOMutation = useMutation({
  mutationFn: (id: string) => manufacturingApi.startWorkOrder(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['manufacturing', 'work-orders'] })
    toast.success('Work Order started! Production is in progress.')
  },
  onError: (e: any) => toast.error(e?.response?.data?.message || 'Failed to start work order'),
})

const completeWOMutation = useMutation({
  mutationFn: () => {
    if (!selectedWO.value?.id) throw new Error('No work order selected')
    return manufacturingApi.completeWorkOrder(selectedWO.value.id, completeLocationId.value || undefined)
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['manufacturing', 'work-orders'] })
    queryClient.invalidateQueries({ queryKey: ['inventory'] })
    isCompleteWOModalOpen.value = false
    if (selectedWO.value) {
      isViewWODetailsModalOpen.value = false
    }
    toast.success('Work Order completed! Output stock received into warehouse.')
  },
  onError: (e: any) => toast.error(e?.response?.data?.message || 'Failed to complete work order'),
})

const cancelWOMutation = useMutation({
  mutationFn: (id: string) => manufacturingApi.cancelWorkOrder(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['manufacturing', 'work-orders'] })
    toast.success('Work order cancelled')
  },
  onError: (e: any) => toast.error(e?.response?.data?.message || 'Failed to cancel work order'),
})

const deleteWOMutation = useMutation({
  mutationFn: (id: string) => manufacturingApi.deleteWorkOrder(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['manufacturing', 'work-orders'] })
    toast.success('Work order deleted')
  },
  onError: (e: any) => toast.error(e?.response?.data?.message || 'Failed to delete work order'),
})

const createBOMMutation = useMutation({
  mutationFn: () => {
    if (!bomForm.product_id) throw new Error('Please select an output product.')
    if (!bomForm.name.trim()) throw new Error('BOM Name is required.')
    const validLines = bomForm.lines.filter((l) => l.material_id && l.quantity > 0)
    if (validLines.length === 0) throw new Error('Please add at least one component material.')

    return manufacturingApi.createBom({
      product_id: bomForm.product_id,
      name: bomForm.name.trim(),
      description: bomForm.description || undefined,
      output_quantity: Number(bomForm.output_quantity) || 1,
      status: bomForm.status,
      lines: validLines.map((l) => ({
        material_id: l.material_id,
        quantity: Number(l.quantity),
        unit: l.unit || 'pcs',
        notes: l.notes || undefined,
      })),
    })
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['manufacturing', 'boms'] })
    isCreateBOMModalOpen.value = false
    toast.success('Bill of Material created successfully')
  },
  onError: (e: any) => toast.error(e?.response?.data?.message || e?.message || 'Failed to create BOM'),
})

const activateBOMMutation = useMutation({
  mutationFn: (id: string) => manufacturingApi.activateBom(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['manufacturing', 'boms'] })
    toast.success('BOM activated')
  },
  onError: (e: any) => toast.error(e?.response?.data?.message || 'Failed to activate BOM'),
})

const archiveBOMMutation = useMutation({
  mutationFn: (id: string) => manufacturingApi.archiveBom(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['manufacturing', 'boms'] })
    toast.success('BOM archived')
  },
  onError: (e: any) => toast.error(e?.response?.data?.message || 'Failed to archive BOM'),
})

const priorityBadgeVariant = (p: string) => {
  switch (p) {
    case 'urgent':
      return 'danger'
    case 'high':
      return 'warning'
    case 'normal':
      return 'info'
    default:
      return 'default'
  }
}

const statusBadgeVariant = (s: string) => {
  switch (s) {
    case 'completed':
      return 'success'
    case 'in_progress':
      return 'info'
    case 'cancelled':
      return 'danger'
    default:
      return 'default'
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2.5">
          <Factory class="w-7 h-7 text-blue-600" />
          Manufacturing & Assembly
        </h1>
        <p class="text-sm text-slate-500">
          Manage Bills of Material (BOMs), schedule production runs, track components, and output finished stock.
        </p>
      </div>

      <div class="flex items-center gap-2.5">
        <UiButton variant="outline" @click="openCreateBOM()">
          <FileSpreadsheet class="w-4 h-4 mr-2" /> New BOM Recipe
        </UiButton>
        <UiButton @click="openCreateWO()">
          <Plus class="w-4 h-4 mr-2" /> Schedule Work Order
        </UiButton>
      </div>
    </div>

    <!-- KPI Summary Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs flex items-center gap-3.5">
        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
          <Factory class="w-5 h-5" />
        </div>
        <div>
          <span class="text-xs text-slate-500 uppercase font-bold block">Total Work Orders</span>
          <span class="text-xl font-black text-slate-900">{{ totalWOs }}</span>
        </div>
      </div>

      <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs flex items-center gap-3.5">
        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold">
          <Play class="w-5 h-5" />
        </div>
        <div>
          <span class="text-xs text-slate-500 uppercase font-bold block">In Progress Runs</span>
          <span class="text-xl font-black text-amber-600">{{ inProgressWOs }}</span>
        </div>
      </div>

      <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs flex items-center gap-3.5">
        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
          <CheckCircle2 class="w-5 h-5" />
        </div>
        <div>
          <span class="text-xs text-slate-500 uppercase font-bold block">Completed Runs</span>
          <span class="text-xl font-black text-emerald-600">{{ completedWOs }}</span>
        </div>
      </div>

      <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs flex items-center gap-3.5">
        <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
          <Layers class="w-5 h-5" />
        </div>
        <div>
          <span class="text-xs text-slate-500 uppercase font-bold block">Active BOM Recipes</span>
          <span class="text-xl font-black text-indigo-600">{{ totalActiveBoms }}</span>
        </div>
      </div>
    </div>

    <!-- Tab Navigation -->
    <div class="flex border-b border-slate-200">
      <button
        type="button"
        @click="activeTab = 'work-orders'"
        :class="[
          'inline-flex items-center gap-2 px-6 py-3 text-sm font-bold border-b-2 transition-colors',
          activeTab === 'work-orders'
            ? 'border-blue-600 text-blue-600'
            : 'border-transparent text-slate-500 hover:text-slate-700'
        ]"
      >
        <Play class="w-4 h-4" /> Work Orders (Production Runs)
        <span class="ml-1 px-2 py-0.5 rounded-full text-xs bg-slate-100 text-slate-700 font-bold">
          {{ workOrders.length }}
        </span>
      </button>

      <button
        type="button"
        @click="activeTab = 'boms'"
        :class="[
          'inline-flex items-center gap-2 px-6 py-3 text-sm font-bold border-b-2 transition-colors',
          activeTab === 'boms'
            ? 'border-blue-600 text-blue-600'
            : 'border-transparent text-slate-500 hover:text-slate-700'
        ]"
      >
        <Layers class="w-4 h-4" /> Bills of Material (BOMs & Recipes)
        <span class="ml-1 px-2 py-0.5 rounded-full text-xs bg-slate-100 text-slate-700 font-bold">
          {{ boms.length }}
        </span>
      </button>
    </div>

    <!-- 1. WORK ORDERS TAB -->
    <div v-if="activeTab === 'work-orders'" class="space-y-4">
      <div class="bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200">
          <thead class="bg-slate-50/80">
            <tr>
              <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">WO Number</th>
              <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Output Product & BOM</th>
              <th class="px-5 py-3.5 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Batch Qty</th>
              <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Priority</th>
              <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
              <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Schedule</th>
              <th class="px-5 py-3.5 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 bg-white text-sm">
            <tr v-if="woLoading">
              <td colspan="7" class="px-5 py-10 text-center text-slate-500">Loading work orders…</td>
            </tr>
            <tr v-else-if="!workOrders.length">
              <td colspan="7" class="px-5 py-12 text-center">
                <Factory class="w-8 h-8 text-slate-300 mx-auto mb-2" />
                <p class="font-medium text-slate-700">No work orders scheduled yet</p>
                <p class="text-xs text-slate-400 mt-1">Create a BOM recipe first, then schedule your manufacturing run.</p>
                <UiButton size="sm" class="mt-3" @click="openCreateWO()">Schedule Work Order</UiButton>
              </td>
            </tr>
            <tr v-for="wo in workOrders" :key="wo.id" class="hover:bg-slate-50/60 transition-colors">
              <td class="px-5 py-3.5 font-mono font-bold text-blue-600">
                <button type="button" @click="viewWODetails(wo)" class="hover:underline">
                  {{ wo.number }}
                </button>
              </td>
              <td class="px-5 py-3.5">
                <div class="font-bold text-slate-900">{{ wo.bom?.product?.name || wo.bom?.name || '—' }}</div>
                <div class="text-xs text-slate-500 flex items-center gap-1.5 mt-0.5">
                  <span class="font-mono bg-slate-100 px-1.5 py-0.2 rounded">{{ wo.bom?.product?.sku || 'SKU' }}</span>
                  <span>•</span>
                  <span>BOM: {{ wo.bom?.name }}</span>
                </div>
              </td>
              <td class="px-5 py-3.5 text-center font-bold text-slate-900 font-mono">
                {{ wo.quantity }} units
              </td>
              <td class="px-5 py-3.5">
                <UiBadge :variant="priorityBadgeVariant(wo.priority)" class="capitalize">
                  {{ wo.priority }}
                </UiBadge>
              </td>
              <td class="px-5 py-3.5">
                <UiBadge :variant="statusBadgeVariant(wo.status)" class="capitalize">
                  {{ wo.status.replace('_', ' ') }}
                </UiBadge>
              </td>
              <td class="px-5 py-3.5 text-xs text-slate-600">
                <div>Start: {{ wo.planned_start || 'Immediate' }}</div>
                <div v-if="wo.planned_end" class="text-slate-400">Due: {{ wo.planned_end }}</div>
              </td>
              <td class="px-5 py-3.5 text-right">
                <div class="inline-flex items-center gap-2">
                  <UiButton size="sm" variant="ghost" class="text-xs" @click="viewWODetails(wo)">
                    <Eye class="w-3.5 h-3.5 mr-1" /> View
                  </UiButton>

                  <!-- Start Run -->
                  <UiButton
                    v-if="wo.status === 'draft'"
                    size="sm"
                    variant="outline"
                    class="text-xs border-blue-300 text-blue-700 hover:bg-blue-50"
                    :loading="startWOMutation.isPending.value"
                    @click="startWOMutation.mutate(wo.id)"
                  >
                    <Play class="w-3.5 h-3.5 mr-1" /> Start Run
                  </UiButton>

                  <!-- Complete Run -->
                  <UiButton
                    v-if="wo.status === 'in_progress'"
                    size="sm"
                    variant="outline"
                    class="text-xs border-emerald-300 text-emerald-700 hover:bg-emerald-50 font-bold"
                    @click="openCompleteModal(wo)"
                  >
                    <CheckCircle2 class="w-3.5 h-3.5 mr-1" /> Complete
                  </UiButton>

                  <!-- Cancel Run -->
                  <UiButton
                    v-if="wo.status === 'draft' || wo.status === 'in_progress'"
                    size="sm"
                    variant="ghost"
                    class="text-xs text-rose-600 hover:bg-rose-50"
                    @click="cancelWOMutation.mutate(wo.id)"
                  >
                    <XCircle class="w-3.5 h-3.5" />
                  </UiButton>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- 2. BILLS OF MATERIAL TAB -->
    <div v-else class="space-y-4">
      <div class="bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200">
          <thead class="bg-slate-50/80">
            <tr>
              <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">BOM Recipe Name</th>
              <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Output Finished Product</th>
              <th class="px-5 py-3.5 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Yield / Batch</th>
              <th class="px-5 py-3.5 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Components</th>
              <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
              <th class="px-5 py-3.5 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 bg-white text-sm">
            <tr v-if="bomsLoading">
              <td colspan="6" class="px-5 py-10 text-center text-slate-500">Loading Bills of Material…</td>
            </tr>
            <tr v-else-if="!boms.length">
              <td colspan="6" class="px-5 py-12 text-center">
                <Layers class="w-8 h-8 text-slate-300 mx-auto mb-2" />
                <p class="font-medium text-slate-700">No Bills of Material defined yet</p>
                <p class="text-xs text-slate-400 mt-1">Create a recipe linking raw components to your finished products.</p>
                <UiButton size="sm" class="mt-3" @click="openCreateBOM()">+ New BOM Recipe</UiButton>
              </td>
            </tr>
            <tr v-for="bom in boms" :key="bom.id" class="hover:bg-slate-50/60 transition-colors">
              <td class="px-5 py-3.5 font-bold text-slate-900">
                <button type="button" @click="viewBOMDetails(bom)" class="hover:underline text-left">
                  {{ bom.name }}
                </button>
                <div v-if="bom.description" class="text-xs text-slate-400 truncate max-w-[240px] font-normal">
                  {{ bom.description }}
                </div>
              </td>
              <td class="px-5 py-3.5">
                <div class="font-semibold text-slate-900">{{ bom.product?.name || '—' }}</div>
                <div class="text-xs font-mono text-slate-500">{{ bom.product?.sku || 'SKU' }}</div>
              </td>
              <td class="px-5 py-3.5 text-center font-bold font-mono text-slate-900">
                {{ bom.output_quantity }} units
              </td>
              <td class="px-5 py-3.5 text-center">
                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200/60">
                  {{ bom.lines?.length || 0 }} components
                </span>
              </td>
              <td class="px-5 py-3.5">
                <UiBadge
                  :variant="bom.status === 'active' ? 'success' : bom.status === 'archived' ? 'default' : 'warning'"
                  class="capitalize"
                >
                  {{ bom.status }}
                </UiBadge>
              </td>
              <td class="px-5 py-3.5 text-right">
                <div class="inline-flex items-center gap-2">
                  <UiButton size="sm" variant="ghost" class="text-xs" @click="viewBOMDetails(bom)">
                    <Eye class="w-3.5 h-3.5 mr-1" /> Recipe
                  </UiButton>

                  <UiButton
                    v-if="bom.status === 'active'"
                    size="sm"
                    variant="outline"
                    class="text-xs border-blue-300 text-blue-700 hover:bg-blue-50"
                    @click="openCreateWO(bom.id)"
                  >
                    <Play class="w-3.5 h-3.5 mr-1" /> Produce
                  </UiButton>

                  <UiButton
                    v-if="bom.status === 'draft'"
                    size="sm"
                    variant="outline"
                    class="text-xs border-emerald-300 text-emerald-700 hover:bg-emerald-50"
                    :loading="activateBOMMutation.isPending.value"
                    @click="activateBOMMutation.mutate(bom.id)"
                  >
                    <Check class="w-3.5 h-3.5 mr-1" /> Activate
                  </UiButton>

                  <UiButton
                    v-if="bom.status === 'active'"
                    size="sm"
                    variant="ghost"
                    class="text-xs text-slate-500 hover:text-slate-700"
                    title="Archive BOM"
                    @click="archiveBOMMutation.mutate(bom.id)"
                  >
                    <Archive class="w-3.5 h-3.5" />
                  </UiButton>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create Work Order Modal -->
    <UiModal v-model="isCreateWOModalOpen" title="Schedule Production Work Order" size="xl">
      <form @submit.prevent="createWOMutation.mutate()" class="space-y-4">
        <div class="space-y-1.5">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Select BOM Recipe *</label>
          <UiSelect
            v-model="woForm.bom_id"
            :options="bomOptions"
            placeholder="Select a bill of materials..."
            required
          />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UiInput
            v-model.number="woForm.quantity"
            type="number"
            min="1"
            step="1"
            label="Production Quantity (Finished Units) *"
            required
          />

          <div class="space-y-1.5">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Priority Level</label>
            <div class="grid grid-cols-4 gap-2">
              <button
                v-for="p in ['low', 'normal', 'high', 'urgent'] as const"
                :key="p"
                type="button"
                @click="woForm.priority = p"
                :class="[
                  'py-2 rounded-xl text-xs font-bold capitalize transition-all border text-center',
                  woForm.priority === p
                    ? 'bg-blue-50 text-blue-700 border-blue-400 ring-2 ring-blue-500/10 shadow-xs'
                    : 'bg-slate-50 text-slate-500 border-slate-200 hover:bg-slate-100'
                ]"
              >
                {{ p }}
              </button>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UiInput v-model="woForm.planned_start" type="date" label="Planned Start Date" />
          <UiInput v-model="woForm.planned_end" type="date" label="Target Completion Date" />
        </div>

        <!-- Live Material Requirements Estimation Matrix -->
        <div v-if="selectedBOMForWO && estimatedMaterialsForWO.length > 0" class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
          <div class="flex items-center justify-between text-xs font-bold text-slate-700">
            <span>Required Components for {{ woForm.quantity }} Unit(s):</span>
            <span class="text-blue-600 font-mono">BOM Yield: {{ selectedBOMForWO.output_quantity }} unit(s)</span>
          </div>
          <div class="space-y-1.5 max-h-36 overflow-y-auto pr-1">
            <div
              v-for="(comp, i) in estimatedMaterialsForWO"
              :key="i"
              class="flex items-center justify-between text-xs p-2 rounded-lg bg-white border border-slate-200/60"
            >
              <div class="flex items-center gap-2">
                <Boxes class="w-3.5 h-3.5 text-slate-400" />
                <span class="font-medium text-slate-900">{{ comp.material?.name || 'Raw Component' }}</span>
                <span class="font-mono text-[10px] text-slate-400">({{ comp.material?.sku || 'SKU' }})</span>
              </div>
              <div class="font-bold text-slate-900 font-mono">
                {{ comp.totalRequired }} {{ comp.unit || 'pcs' }}
              </div>
            </div>
          </div>
        </div>

        <div class="space-y-1.5">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Production Notes / Batch Code</label>
          <textarea
            v-model="woForm.notes"
            rows="2"
            class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-sm font-medium text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20"
            placeholder="e.g. Line 2 priority assembly run for client order..."
          ></textarea>
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <UiButton variant="ghost" @click="isCreateWOModalOpen = false">Cancel</UiButton>
          <UiButton :loading="createWOMutation.isPending.value" type="submit">
            Schedule Production
          </UiButton>
        </div>
      </form>
    </UiModal>

    <!-- Create Bill of Materials Modal -->
    <UiModal v-model="isCreateBOMModalOpen" title="Create Bill of Materials (Recipe)" size="2xl">
      <form @submit.prevent="createBOMMutation.mutate()" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Finished Output Product *</label>
            <UiSelect
              v-model="bomForm.product_id"
              :options="productOptions"
              placeholder="Select finished product..."
              @update:model-value="onFinishedProductSelect"
              required
            />
          </div>

          <UiInput
            v-model="bomForm.name"
            label="BOM Recipe Name *"
            placeholder="e.g. Deluxe Gaming Chair Assembly"
            required
          />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UiInput
            v-model.number="bomForm.output_quantity"
            type="number"
            min="1"
            step="1"
            label="Output Batch Size (Yield) *"
            help-text="Units of finished product produced per batch"
            required
          />

          <div class="space-y-1.5">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Initial Status</label>
            <div class="grid grid-cols-2 gap-2">
              <button
                type="button"
                @click="bomForm.status = 'active'"
                :class="[
                  'py-2 rounded-xl text-xs font-bold border text-center transition-all',
                  bomForm.status === 'active'
                    ? 'bg-emerald-50 text-emerald-700 border-emerald-300 ring-2 ring-emerald-500/10'
                    : 'bg-slate-50 text-slate-500 border-slate-200 hover:bg-slate-100'
                ]"
              >
                Active
              </button>
              <button
                type="button"
                @click="bomForm.status = 'draft'"
                :class="[
                  'py-2 rounded-xl text-xs font-bold border text-center transition-all',
                  bomForm.status === 'draft'
                    ? 'bg-amber-50 text-amber-700 border-amber-300 ring-2 ring-amber-500/10'
                    : 'bg-slate-50 text-slate-500 border-slate-200 hover:bg-slate-100'
                ]"
              >
                Draft
              </button>
            </div>
          </div>
        </div>

        <!-- BOM Component Lines Section -->
        <div class="space-y-3 pt-2">
          <div class="flex items-center justify-between border-b border-slate-200 pb-2">
            <div>
              <h3 class="text-sm font-bold text-slate-900">Required Raw Materials & Components</h3>
              <p class="text-xs text-slate-500">Components required to manufacture {{ bomForm.output_quantity }} unit(s) of finished goods.</p>
            </div>
            <UiButton type="button" size="sm" variant="outline" @click="addBOMLine()">
              <Plus class="w-3.5 h-3.5 mr-1" /> Add Component
            </UiButton>
          </div>

          <div class="space-y-2.5 max-h-[300px] overflow-y-auto pr-1">
            <div
              v-for="(line, idx) in bomForm.lines"
              :key="idx"
              class="p-3 rounded-xl border border-slate-200 bg-slate-50/50 grid grid-cols-1 sm:grid-cols-12 gap-2.5 items-end"
            >
              <div class="sm:col-span-6">
                <label class="block text-xs font-bold text-slate-700 mb-1">Component Product *</label>
                <UiSelect
                  v-model="line.material_id"
                  :options="productOptions"
                  placeholder="Select material..."
                  required
                />
              </div>

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

              <div class="sm:col-span-3">
                <UiInput
                  v-model="line.unit"
                  label="Unit"
                  placeholder="pcs / kg / m"
                />
              </div>

              <div class="sm:col-span-1 flex justify-center pb-1">
                <button
                  type="button"
                  class="text-slate-400 hover:text-red-500 transition-colors p-1"
                  @click="removeBOMLine(idx)"
                  title="Remove Component"
                >
                  <Trash2 class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <UiButton variant="ghost" @click="isCreateBOMModalOpen = false">Cancel</UiButton>
          <UiButton :loading="createBOMMutation.isPending.value" type="submit">
            Save Bill of Materials
          </UiButton>
        </div>
      </form>
    </UiModal>

    <!-- Complete Work Order Modal -->
    <UiModal v-model="isCompleteWOModalOpen" title="Complete Work Order & Store Stock" size="md">
      <div class="space-y-4">
        <p class="text-sm text-slate-600">
          Completing Work Order <strong class="text-slate-900 font-mono">{{ selectedWO?.number }}</strong> will record the manufacturing run as complete.
        </p>

        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-1">
          <div class="text-xs text-slate-500 font-bold uppercase">Manufactured Output:</div>
          <div class="font-bold text-slate-900 text-base">
            {{ selectedWO?.quantity }} × {{ selectedWO?.bom?.product?.name || selectedWO?.bom?.name }}
          </div>
          <div class="text-xs font-mono text-slate-400">SKU: {{ selectedWO?.bom?.product?.sku || '—' }}</div>
        </div>

        <div class="space-y-1.5">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Destination Warehouse / Location *</label>
          <UiSelect
            v-model="completeLocationId"
            :options="locationOptions"
            placeholder="Select location to store finished goods..."
            required
          />
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <UiButton variant="ghost" @click="isCompleteWOModalOpen = false">Cancel</UiButton>
          <UiButton
            :loading="completeWOMutation.isPending.value"
            class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold"
            @click="completeWOMutation.mutate()"
          >
            Confirm & Complete Production
          </UiButton>
        </div>
      </div>
    </UiModal>

    <!-- View Work Order Details Modal -->
    <UiModal v-model="isViewWODetailsModalOpen" :title="`Work Order Details - ${selectedWO?.number}`" size="xl">
      <div v-if="selectedWO" class="space-y-5">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 p-4 rounded-xl bg-slate-50 border border-slate-200">
          <div>
            <span class="text-xs text-slate-500 uppercase font-bold block">Output Product</span>
            <span class="font-bold text-slate-900">{{ selectedWO.bom?.product?.name || '—' }}</span>
            <span class="text-xs font-mono text-slate-400 block">{{ selectedWO.bom?.product?.sku }}</span>
          </div>
          <div>
            <span class="text-xs text-slate-500 uppercase font-bold block">Batch Quantity</span>
            <span class="font-bold text-slate-900 font-mono text-base">{{ selectedWO.quantity }} units</span>
          </div>
          <div>
            <span class="text-xs text-slate-500 uppercase font-bold block">Status</span>
            <UiBadge :variant="statusBadgeVariant(selectedWO.status)" class="mt-1 capitalize">
              {{ selectedWO.status.replace('_', ' ') }}
            </UiBadge>
          </div>
          <div>
            <span class="text-xs text-slate-500 uppercase font-bold block">Priority</span>
            <UiBadge :variant="priorityBadgeVariant(selectedWO.priority)" class="mt-1 capitalize">
              {{ selectedWO.priority }}
            </UiBadge>
          </div>
        </div>

        <!-- Required Components in this WO -->
        <div class="space-y-2">
          <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">Required Recipe Components</h4>
          <div class="border border-slate-200 rounded-xl overflow-hidden">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
              <thead class="bg-slate-50">
                <tr>
                  <th class="px-4 py-2.5 text-left text-xs font-bold text-slate-500 uppercase">Material</th>
                  <th class="px-4 py-2.5 text-left text-xs font-bold text-slate-500 uppercase">SKU</th>
                  <th class="px-4 py-2.5 text-center text-xs font-bold text-slate-500 uppercase">Per Batch</th>
                  <th class="px-4 py-2.5 text-right text-xs font-bold text-slate-500 uppercase">Total Required</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 bg-white">
                <tr v-for="(line, idx) in selectedWO.bom?.lines || []" :key="idx">
                  <td class="px-4 py-3 font-semibold text-slate-900">{{ line.material?.name || 'Component' }}</td>
                  <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ line.material?.sku || '—' }}</td>
                  <td class="px-4 py-3 text-center font-mono">{{ line.quantity }} {{ line.unit || 'pcs' }}</td>
                  <td class="px-4 py-3 text-right font-bold font-mono text-slate-900">
                    {{ Math.ceil(line.quantity * (selectedWO.quantity / (selectedWO.bom?.output_quantity || 1))) }} {{ line.unit || 'pcs' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div v-if="selectedWO.notes" class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs text-slate-700">
          <strong class="block text-slate-900 mb-0.5">Production Notes:</strong>
          {{ selectedWO.notes }}
        </div>

        <div class="flex justify-between items-center pt-2">
          <div class="flex items-center gap-2">
            <UiButton
              v-if="selectedWO.status === 'draft'"
              variant="outline"
              class="border-blue-300 text-blue-700"
              @click="startWOMutation.mutate(selectedWO.id)"
            >
              <Play class="w-3.5 h-3.5 mr-1" /> Start Run
            </UiButton>
            <UiButton
              v-if="selectedWO.status === 'in_progress'"
              class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold"
              @click="openCompleteModal(selectedWO)"
            >
              <CheckCircle2 class="w-3.5 h-3.5 mr-1" /> Complete & Store Stock
            </UiButton>
          </div>

          <UiButton variant="ghost" @click="isViewWODetailsModalOpen = false">Close</UiButton>
        </div>
      </div>
    </UiModal>

    <!-- View BOM Details Modal -->
    <UiModal v-model="isViewBOMDetailsModalOpen" :title="`Bill of Materials - ${selectedBOM?.name}`" size="xl">
      <div v-if="selectedBOM" class="space-y-5">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 p-4 rounded-xl bg-slate-50 border border-slate-200">
          <div>
            <span class="text-xs text-slate-500 uppercase font-bold block">Output Finished Product</span>
            <span class="font-bold text-slate-900">{{ selectedBOM.product?.name }}</span>
            <span class="text-xs font-mono text-slate-400 block">{{ selectedBOM.product?.sku }}</span>
          </div>
          <div>
            <span class="text-xs text-slate-500 uppercase font-bold block">Batch Output Size</span>
            <span class="font-bold font-mono text-slate-900 text-base">{{ selectedBOM.output_quantity }} units</span>
          </div>
          <div>
            <span class="text-xs text-slate-500 uppercase font-bold block">Status</span>
            <UiBadge :variant="selectedBOM.status === 'active' ? 'success' : 'default'" class="mt-1 capitalize">
              {{ selectedBOM.status }}
            </UiBadge>
          </div>
        </div>

        <div class="space-y-2">
          <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">Component Ingredients / Parts List</h4>
          <div class="border border-slate-200 rounded-xl overflow-hidden">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
              <thead class="bg-slate-50">
                <tr>
                  <th class="px-4 py-2.5 text-left text-xs font-bold text-slate-500 uppercase">Material / Component</th>
                  <th class="px-4 py-2.5 text-left text-xs font-bold text-slate-500 uppercase">SKU</th>
                  <th class="px-4 py-2.5 text-center text-xs font-bold text-slate-500 uppercase">Quantity</th>
                  <th class="px-4 py-2.5 text-center text-xs font-bold text-slate-500 uppercase">Unit</th>
                  <th class="px-4 py-2.5 text-right text-xs font-bold text-slate-500 uppercase">Notes</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 bg-white">
                <tr v-for="(line, idx) in selectedBOM.lines || []" :key="idx">
                  <td class="px-4 py-3 font-semibold text-slate-900">{{ line.material?.name || '—' }}</td>
                  <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ line.material?.sku || '—' }}</td>
                  <td class="px-4 py-3 text-center font-mono font-bold text-slate-900">{{ line.quantity }}</td>
                  <td class="px-4 py-3 text-center text-slate-600">{{ line.unit || 'pcs' }}</td>
                  <td class="px-4 py-3 text-right text-slate-400 text-xs">{{ line.notes || '—' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="flex justify-between items-center pt-2">
          <UiButton
            v-if="selectedBOM.status === 'active'"
            variant="outline"
            class="border-blue-300 text-blue-700"
            @click="isViewBOMDetailsModalOpen = false; openCreateWO(selectedBOM.id)"
          >
            <Play class="w-3.5 h-3.5 mr-1" /> Produce From This Recipe
          </UiButton>
          <div v-else></div>

          <UiButton variant="ghost" @click="isViewBOMDetailsModalOpen = false">Close</UiButton>
        </div>
      </div>
    </UiModal>
  </div>
</template>
