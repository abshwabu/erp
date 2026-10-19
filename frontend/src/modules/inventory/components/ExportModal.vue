<script setup lang="ts">
import { ref, computed } from 'vue'
import { inventoryApi } from '@/api/inventory'
import {
  Download,
  FileSpreadsheet,
  FileText,
  CheckCircle2,
  AlertCircle,
  Eye,
  CheckSquare,
  Square,
  Layers,
  Sparkles,
  Filter,
  Package,
} from '@lucide/vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiButton from '@/components/ui/UiButton.vue'
import { useToast } from '@/composables/useToast'

interface Props {
  modelValue: boolean
  filters?: {
    search?: string
    category_id?: string
    status?: string
    type?: string
  }
  selectedIds?: string[]
  totalCount?: number
}

const props = withDefaults(defineProps<Props>(), {
  filters: () => ({}),
  selectedIds: () => [],
  totalCount: 0,
})

const emit = defineEmits(['update:modelValue'])
const toast = useToast()

type ExportFormat = 'xlsx' | 'csv' | 'pdf'
type ExportScope = 'all' | 'filtered' | 'selected'

const format = ref<ExportFormat>('xlsx')
const isExporting = ref(false)
const isPreviewing = ref(false)

const availableColumns = [
  { key: 'name', label: 'Product Name', desc: 'Official item name / title', group: 'basic' },
  { key: 'sku', label: 'SKU', desc: 'Unique product code identifier', group: 'basic' },
  { key: 'category', label: 'Category', desc: 'Assigned category name', group: 'basic' },
  { key: 'selling_price', label: 'Selling Price', desc: 'Customer retail price', group: 'pricing' },
  { key: 'cost_price', label: 'Cost Price', desc: 'Internal purchase cost', group: 'pricing' },
  { key: 'quantity_on_hand', label: 'Total Stock', desc: 'Physical on-hand quantity', group: 'inventory' },
  { key: 'available_quantity', label: 'Available Stock', desc: 'Unreserved stock for sale', group: 'inventory' },
  { key: 'type', label: 'Type', desc: 'Stockable, consumable, or service', group: 'details' },
  { key: 'status', label: 'Status', desc: 'Active, inactive, or archived', group: 'details' },
  { key: 'barcode', label: 'Barcode', desc: 'Primary barcode / UPC / EAN', group: 'details' },
  { key: 'description', label: 'Description', desc: 'Product notes and specs', group: 'details' },
  { key: 'created_at', label: 'Created At', desc: 'Initial creation timestamp', group: 'details' },
]

// Default selected columns
const selectedColumns = ref<string[]>([
  'name',
  'sku',
  'category',
  'selling_price',
  'quantity_on_hand',
  'status',
])

const hasActiveFilters = computed(() => {
  return (
    !!props.filters.search ||
    (props.filters.category_id !== undefined && props.filters.category_id !== '') ||
    (props.filters.status !== undefined && props.filters.status !== '') ||
    (props.filters.type !== undefined && props.filters.type !== '')
  )
})

const hasSelectedRows = computed(() => props.selectedIds && props.selectedIds.length > 0)

const scope = ref<ExportScope>(
  hasSelectedRows.value ? 'selected' : (hasActiveFilters.value ? 'filtered' : 'all')
)

const isColumnSelected = (key: string) => selectedColumns.value.includes(key)

const toggleColumn = (key: string) => {
  const index = selectedColumns.value.indexOf(key)
  if (index > -1) {
    selectedColumns.value.splice(index, 1)
  } else {
    selectedColumns.value.push(key)
  }
}

// Quick Presets
const applyPreset = (preset: 'all' | 'pricing' | 'description' | 'inventory' | 'none') => {
  switch (preset) {
    case 'all':
      selectedColumns.value = availableColumns.map((c) => c.key)
      break
    case 'pricing':
      selectedColumns.value = ['name', 'sku', 'category', 'selling_price', 'cost_price']
      break
    case 'description':
      selectedColumns.value = ['name', 'description', 'category', 'sku']
      break
    case 'inventory':
      selectedColumns.value = ['name', 'sku', 'quantity_on_hand', 'available_quantity', 'cost_price', 'status']
      break
    case 'none':
      selectedColumns.value = []
      break
  }
}

const buildExportPayload = (inline = false) => {
  const payload: any = {
    format: format.value,
    columns: selectedColumns.value,
    inline,
  }

  if (scope.value === 'selected' && props.selectedIds.length > 0) {
    payload.ids = props.selectedIds
  } else if (scope.value === 'filtered' || hasActiveFilters.value) {
    if (props.filters.search) payload.search = props.filters.search
    if (props.filters.category_id) payload.category_id = props.filters.category_id
    if (props.filters.status) payload.status = props.filters.status
    if (props.filters.type) payload.type = props.filters.type
  }

  return payload
}

const triggerDownload = (blob: Blob, filename: string) => {
  const url = window.URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.setAttribute('download', filename)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  window.URL.revokeObjectURL(url)
}

const handleExport = async () => {
  if (selectedColumns.value.length === 0) {
    toast.error('Please select at least one column to export')
    return
  }

  try {
    isExporting.value = true
    const payload = buildExportPayload(false)
    const res = await inventoryApi.exportProducts(payload)

    // Extract filename from header or fallback
    let filename = `products_export_${new Date().toISOString().slice(0, 10)}.${format.value}`
    const contentDisposition = res.headers?.['content-disposition']
    if (contentDisposition) {
      const match = contentDisposition.match(/filename="?([^";]+)"?/)
      if (match && match[1]) {
        filename = match[1]
      }
    }

    const mimeMap: Record<ExportFormat, string> = {
      xlsx: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      csv: 'text/csv;charset=utf-8;',
      pdf: 'application/pdf',
    }

    const blob = new Blob([res.data], { type: mimeMap[format.value] })
    triggerDownload(blob, filename)

    toast.success(`Export downloaded successfully (${filename})`)
    emit('update:modelValue', false)
  } catch (err: any) {
    console.error('Export failed', err)
    toast.error(err?.response?.data?.message || 'Failed to generate export file')
  } finally {
    isExporting.value = false
  }
}

const handlePreviewPdf = async () => {
  if (selectedColumns.value.length === 0) {
    toast.error('Please select at least one column to export')
    return
  }

  try {
    isPreviewing.value = true
    const payload = buildExportPayload(true)
    const res = await inventoryApi.exportProducts(payload)

    const blob = new Blob([res.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    window.open(url, '_blank')
    toast.success('PDF preview opened in new tab')
  } catch (err: any) {
    console.error('PDF preview failed', err)
    toast.error('Failed to generate PDF preview')
  } finally {
    isPreviewing.value = false
  }
}
</script>

<template>
  <UiModal
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
    size="2xl"
    title="Export Products Catalog"
  >
    <div class="space-y-6 text-slate-800">
      <!-- 1. Choose Format -->
      <div>
        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2.5">
          1. Select Export Format
        </label>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <!-- XLSX -->
          <div
            @click="format = 'xlsx'"
            class="relative flex flex-col p-3.5 rounded-2xl border-2 cursor-pointer transition-all text-left"
            :class="[
              format === 'xlsx'
                ? 'border-emerald-600 bg-emerald-50/50 shadow-sm ring-2 ring-emerald-600/20'
                : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50/60',
            ]"
          >
            <div class="flex items-center justify-between">
              <div
                class="w-9 h-9 rounded-xl flex items-center justify-center font-bold text-xs"
                :class="format === 'xlsx' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600'"
              >
                <FileSpreadsheet class="w-5 h-5" />
              </div>
              <div
                class="w-4 h-4 rounded-full border flex items-center justify-center"
                :class="format === 'xlsx' ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-slate-300'"
              >
                <div v-if="format === 'xlsx'" class="w-1.5 h-1.5 rounded-full bg-white" />
              </div>
            </div>
            <h4 class="mt-2.5 font-bold text-sm text-slate-900">Excel (.xlsx)</h4>
            <p class="text-xs text-slate-500 mt-0.5 leading-snug">
              Formatted OpenXML spreadsheet for Excel & Google Sheets.
            </p>
          </div>

          <!-- CSV -->
          <div
            @click="format = 'csv'"
            class="relative flex flex-col p-3.5 rounded-2xl border-2 cursor-pointer transition-all text-left"
            :class="[
              format === 'csv'
                ? 'border-blue-600 bg-blue-50/50 shadow-sm ring-2 ring-blue-600/20'
                : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50/60',
            ]"
          >
            <div class="flex items-center justify-between">
              <div
                class="w-9 h-9 rounded-xl flex items-center justify-center font-bold text-xs"
                :class="format === 'csv' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600'"
              >
                <FileText class="w-5 h-5" />
              </div>
              <div
                class="w-4 h-4 rounded-full border flex items-center justify-center"
                :class="format === 'csv' ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-300'"
              >
                <div v-if="format === 'csv'" class="w-1.5 h-1.5 rounded-full bg-white" />
              </div>
            </div>
            <h4 class="mt-2.5 font-bold text-sm text-slate-900">CSV (.csv)</h4>
            <p class="text-xs text-slate-500 mt-0.5 leading-snug">
              Lightweight UTF-8 comma-separated file for ERPs & databases.
            </p>
          </div>

          <!-- PDF -->
          <div
            @click="format = 'pdf'"
            class="relative flex flex-col p-3.5 rounded-2xl border-2 cursor-pointer transition-all text-left"
            :class="[
              format === 'pdf'
                ? 'border-rose-600 bg-rose-50/50 shadow-sm ring-2 ring-rose-600/20'
                : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50/60',
            ]"
          >
            <div class="flex items-center justify-between">
              <div
                class="w-9 h-9 rounded-xl flex items-center justify-center font-bold text-xs"
                :class="format === 'pdf' ? 'bg-rose-600 text-white' : 'bg-slate-100 text-slate-600'"
              >
                <FileText class="w-5 h-5" />
              </div>
              <div
                class="w-4 h-4 rounded-full border flex items-center justify-center"
                :class="format === 'pdf' ? 'border-rose-600 bg-rose-600 text-white' : 'border-slate-300'"
              >
                <div v-if="format === 'pdf'" class="w-1.5 h-1.5 rounded-full bg-white" />
              </div>
            </div>
            <h4 class="mt-2.5 font-bold text-sm text-slate-900">PDF Report (.pdf)</h4>
            <p class="text-xs text-slate-500 mt-0.5 leading-snug">
              Print-ready vector document with professional layout & branding.
            </p>
          </div>
        </div>
      </div>

      <!-- 2. Export Scope -->
      <div v-if="hasActiveFilters || hasSelectedRows" class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200/80">
        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
          2. Export Scope
        </label>
        <div class="flex flex-wrap items-center gap-4 text-xs font-medium text-slate-700">
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              type="radio"
              v-model="scope"
              value="all"
              class="text-blue-600 focus:ring-blue-500"
            />
            <span>All Products ({{ totalCount || 'Full catalog' }})</span>
          </label>

          <label v-if="hasActiveFilters" class="flex items-center gap-2 cursor-pointer">
            <input
              type="radio"
              v-model="scope"
              value="filtered"
              class="text-blue-600 focus:ring-blue-500"
            />
            <span class="flex items-center gap-1 text-blue-700 font-semibold">
              <Filter class="w-3.5 h-3.5" />
              Filtered Results (matching current search/filters)
            </span>
          </label>

          <label v-if="hasSelectedRows" class="flex items-center gap-2 cursor-pointer">
            <input
              type="radio"
              v-model="scope"
              value="selected"
              class="text-blue-600 focus:ring-blue-500"
            />
            <span class="text-emerald-700 font-semibold">
              Selected Items Only ({{ selectedIds.length }} items)
            </span>
          </label>
        </div>
      </div>

      <!-- 3. Dynamic Column Selection -->
      <div>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2.5">
          <div class="flex items-center gap-2">
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">
              {{ hasActiveFilters || hasSelectedRows ? '3.' : '2.' }} Select Columns to Include
            </label>
            <span
              class="px-2 py-0.5 rounded-full text-xs font-extrabold"
              :class="selectedColumns.length > 0 ? 'bg-blue-100 text-blue-700' : 'bg-rose-100 text-rose-700'"
            >
              {{ selectedColumns.length }} of {{ availableColumns.length }} Selected
            </span>
          </div>

          <!-- Quick Presets -->
          <div class="flex flex-wrap items-center gap-1.5 text-xs">
            <span class="text-slate-400 text-[11px] font-semibold mr-1">Presets:</span>
            <button
              type="button"
              @click="applyPreset('all')"
              class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium transition-colors"
            >
              All
            </button>
            <button
              type="button"
              @click="applyPreset('pricing')"
              class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium transition-colors"
            >
              Name & Price
            </button>
            <button
              type="button"
              @click="applyPreset('description')"
              class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium transition-colors"
            >
              Name & Description
            </button>
            <button
              type="button"
              @click="applyPreset('inventory')"
              class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium transition-colors"
            >
              Stock & Cost
            </button>
            <button
              type="button"
              @click="applyPreset('none')"
              class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-rose-50 text-rose-600 font-medium transition-colors"
            >
              Clear
            </button>
          </div>
        </div>

        <!-- Columns Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5 p-3 rounded-2xl bg-slate-50 border border-slate-200/80 max-h-64 overflow-y-auto">
          <div
            v-for="col in availableColumns"
            :key="col.key"
            @click="toggleColumn(col.key)"
            class="flex items-start gap-2.5 p-2.5 rounded-xl border cursor-pointer select-none transition-all"
            :class="[
              isColumnSelected(col.key)
                ? 'bg-white border-blue-500 shadow-xs'
                : 'bg-white/60 border-slate-200/70 opacity-60 hover:opacity-100 hover:bg-white',
            ]"
          >
            <div class="mt-0.5">
              <CheckSquare v-if="isColumnSelected(col.key)" class="w-4 h-4 text-blue-600" />
              <Square v-else class="w-4 h-4 text-slate-300" />
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-900 truncate">{{ col.label }}</span>
                <span
                  v-if="col.group === 'pricing'"
                  class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-amber-50 text-amber-700"
                >
                  $
                </span>
                <span
                  v-else-if="col.group === 'inventory'"
                  class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-emerald-50 text-emerald-700"
                >
                  Stock
                </span>
              </div>
              <p class="text-[11px] text-slate-400 truncate mt-0.5">{{ col.desc }}</p>
            </div>
          </div>
        </div>

        <p v-if="selectedColumns.length === 0" class="text-xs font-semibold text-rose-600 mt-2 flex items-center gap-1">
          <AlertCircle class="w-3.5 h-3.5" />
          You must select at least one column to export.
        </p>
      </div>
    </div>

    <!-- Modal Footer Actions -->
    <template #footer>
      <div class="flex items-center justify-between w-full">
        <button
          type="button"
          @click="emit('update:modelValue', false)"
          class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 text-xs sm:text-sm font-semibold transition-colors"
        >
          Cancel
        </button>

        <div class="flex items-center gap-2">
          <!-- PDF Preview Button -->
          <UiButton
            v-if="format === 'pdf'"
            type="button"
            variant="secondary"
            :loading="isPreviewing"
            :disabled="isExporting || selectedColumns.length === 0"
            @click="handlePreviewPdf"
            class="text-xs sm:text-sm"
          >
            <Eye class="w-4 h-4 mr-1.5 text-slate-500" />
            Preview in Tab
          </UiButton>

          <!-- Main Export Button -->
          <UiButton
            type="button"
            variant="primary"
            :loading="isExporting"
            :disabled="isPreviewing || selectedColumns.length === 0"
            @click="handleExport"
            class="text-xs sm:text-sm shadow-md shadow-blue-600/20"
          >
            <Download class="w-4 h-4 mr-1.5" />
            {{ format === 'pdf' ? 'Download PDF' : format === 'csv' ? 'Download CSV' : 'Download Excel (.xlsx)' }}
          </UiButton>
        </div>
      </div>
    </template>
  </UiModal>
</template>
