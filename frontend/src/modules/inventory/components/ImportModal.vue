<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { inventoryApi } from '@/api/inventory'
import {
  Upload,
  FileSpreadsheet,
  FileText,
  X,
  CheckCircle2,
  AlertCircle,
  Download,
  AlertTriangle,
  ChevronDown,
  ChevronUp,
  RefreshCw,
  Boxes,
  PackagePlus,
  RotateCw,
} from '@lucide/vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiButton from '@/components/ui/UiButton.vue'
import type { StockLocation } from '@/types/inventory'

interface Props {
  modelValue: boolean
}

const props = defineProps<Props>()
const emit = defineEmits(['update:modelValue', 'imported'])

const queryClient = useQueryClient()
const fileInput = ref<HTMLInputElement | null>(null)
const selectedFile = ref<File | null>(null)
const isDragging = ref(false)
const updateExisting = ref(true)
const selectedLocationId = ref<string>('')
const locations = ref<StockLocation[]>([])
const isDownloading = ref<'xlsx' | 'csv' | null>(null)
const showErrorsList = ref(false)

const uploadStatus = ref<'idle' | 'uploading' | 'success' | 'error'>('idle')
const errorMessage = ref('')
const importResult = ref<{
  total_rows: number
  created_count: number
  updated_count: number
  skipped_count: number
  failed_count: number
  errors: Array<{ row: number; sku: string; name?: string; error: string }>
} | null>(null)

onMounted(async () => {
  try {
    const res = await inventoryApi.getLocations()
    locations.value = Array.isArray(res.data) ? res.data : []
  } catch (e) {
    console.warn('Could not load locations for import modal', e)
  }
})

const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    selectedFile.value = target.files[0]
  }
}

const handleDrop = (event: DragEvent) => {
  isDragging.value = false
  if (event.dataTransfer?.files && event.dataTransfer.files[0]) {
    const file = event.dataTransfer.files[0]
    const ext = file.name.split('.').pop()?.toLowerCase()
    if (['xlsx', 'xls', 'csv', 'tsv', 'txt'].includes(ext || '')) {
      selectedFile.value = file
    } else {
      errorMessage.value = 'Please select a valid spreadsheet file (.xlsx, .csv, .xls, .tsv).'
    }
  }
}

const handleDownloadTemplate = async (format: 'xlsx' | 'csv') => {
  try {
    isDownloading.value = format
    const res = await inventoryApi.downloadTemplate(format)
    const mime =
      format === 'xlsx'
        ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        : 'text/csv;charset=utf-8;'
    const blob = new Blob([res.data], { type: mime })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `inventory_import_template.${format}`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (err: any) {
    console.error('Failed to download template', err)
  } finally {
    isDownloading.value = null
  }
}

const mutation = useMutation({
  mutationFn: (file: File) =>
    inventoryApi.importProducts(file, {
      update_existing: updateExisting.value,
      location_id: selectedLocationId.value || undefined,
      sync: true,
    }),
  onSuccess: (res) => {
    uploadStatus.value = 'success'
    importResult.value = res.data?.data || null
    queryClient.invalidateQueries({ queryKey: ['inventory', 'products'] })
    queryClient.invalidateQueries({ queryKey: ['inventory', 'stock'] })
    emit('imported')
  },
  onError: (err: any) => {
    uploadStatus.value = 'error'
    errorMessage.value =
      err?.response?.data?.message ||
      err?.response?.data?.errors?.file?.[0] ||
      'Failed to process spreadsheet. Please check the file formatting and try again.'
  },
})

const handleUpload = () => {
  if (!selectedFile.value) return
  uploadStatus.value = 'uploading'
  errorMessage.value = ''
  mutation.mutate(selectedFile.value)
}

const reset = () => {
  selectedFile.value = null
  uploadStatus.value = 'idle'
  errorMessage.value = ''
  importResult.value = null
  showErrorsList.value = false
  if (fileInput.value) fileInput.value.value = ''
}
</script>

<template>
  <UiModal
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
    title="Import Inventory from Spreadsheet"
    size="lg"
    @close="reset"
  >
    <div class="space-y-5">
      <!-- 1. Download Template Card -->
      <div class="rounded-xl border border-blue-100 bg-blue-50/70 p-4 transition-all">
        <div class="flex items-start justify-between gap-4">
          <div class="space-y-1">
            <div class="flex items-center gap-1.5 text-xs font-bold text-blue-900 uppercase tracking-wide">
              <Download class="w-3.5 h-3.5 text-blue-600" />
              <span>Step 1: Download Database Template</span>
            </div>
            <p class="text-xs text-blue-700/90 leading-relaxed">
              Use our pre-formatted spreadsheet template matching the database schema. It includes sample data for
              <strong class="font-semibold text-blue-900">SKU, Name, Category, Prices, Initial Stock, Warehouse, and Barcodes</strong>.
            </p>
          </div>

          <div class="flex flex-col sm:flex-row items-center gap-2 shrink-0">
            <!-- Download Excel .xlsx -->
            <button
              type="button"
              @click="handleDownloadTemplate('xlsx')"
              :disabled="isDownloading !== null"
              class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-2xs hover:shadow-xs transition-all active:scale-95 disabled:opacity-50 cursor-pointer"
              title="Download Microsoft Excel workbook (.xlsx)"
            >
              <FileSpreadsheet class="w-3.5 h-3.5" />
              <span>{{ isDownloading === 'xlsx' ? 'Downloading...' : 'Excel (.xlsx)' }}</span>
            </button>

            <!-- Download CSV .csv -->
            <button
              type="button"
              @click="handleDownloadTemplate('csv')"
              :disabled="isDownloading !== null"
              class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-3 py-1.5 bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 text-xs font-semibold rounded-lg shadow-2xs hover:shadow-xs transition-all active:scale-95 disabled:opacity-50 cursor-pointer"
              title="Download Comma Separated Values (.csv)"
            >
              <FileText class="w-3.5 h-3.5 text-slate-500" />
              <span>{{ isDownloading === 'csv' ? 'Downloading...' : 'CSV (.csv)' }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- 2. Upload / Input View -->
      <div v-if="uploadStatus === 'idle' || uploadStatus === 'uploading'" class="space-y-4">
        <!-- Configuration Options -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
          <!-- Update existing products checkbox -->
          <label class="flex items-start gap-2.5 p-3 rounded-xl border border-slate-200 bg-slate-50/60 cursor-pointer hover:bg-slate-50 transition-colors">
            <input
              type="checkbox"
              v-model="updateExisting"
              class="mt-0.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
            />
            <div class="text-xs">
              <span class="font-semibold text-slate-800 block">Update Existing Products</span>
              <span class="text-slate-500 block text-[11px] mt-0.5 leading-snug">
                If the SKU matches an existing record, update its details & adjust stock.
              </span>
            </div>
          </label>

          <!-- Default Warehouse Location Select -->
          <div class="p-3 rounded-xl border border-slate-200 bg-slate-50/60 flex flex-col justify-between">
            <label class="block text-xs font-semibold text-slate-800 mb-1">
              Fallback Stock Location
            </label>
            <select
              v-model="selectedLocationId"
              class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all"
            >
              <option value="">Default Location (From File or Main Warehouse)</option>
              <option v-for="loc in locations" :key="loc.id" :value="loc.id">
                {{ loc.name }} ({{ loc.code }})
              </option>
            </select>
          </div>
        </div>

        <!-- Drag & Drop Zone -->
        <div
          class="border-2 border-dashed rounded-xl p-8 flex flex-col items-center justify-center cursor-pointer transition-all"
          :class="[
            isDragging ? 'border-blue-500 bg-blue-50/50 scale-[0.99]' : 'border-slate-300 hover:border-blue-400 hover:bg-slate-50/60',
            selectedFile ? 'bg-slate-50/80 border-blue-400' : ''
          ]"
          @click="fileInput?.click()"
          @dragover.prevent="isDragging = true"
          @dragleave.prevent="isDragging = false"
          @drop.prevent="handleDrop"
        >
          <input
            type="file"
            ref="fileInput"
            class="hidden"
            accept=".csv,.xlsx,.xls,.tsv,.txt"
            @change="handleFileSelect"
          />

          <template v-if="!selectedFile">
            <div class="h-12 w-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center mb-3 shadow-2xs">
              <Upload class="h-6 w-6" />
            </div>
            <span class="text-sm font-semibold text-slate-700">Choose file or drag & drop</span>
            <span class="text-xs text-slate-400 mt-1">Excel (.xlsx, .xls) or CSV (.csv, .tsv) up to 20MB</span>
          </template>

          <template v-else>
            <div class="h-12 w-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-3 shadow-2xs">
              <FileSpreadsheet class="h-6 w-6" />
            </div>
            <span class="text-sm font-bold text-slate-900">{{ selectedFile.name }}</span>
            <span class="text-xs text-slate-500 mt-0.5">{{ (selectedFile.size / 1024).toFixed(1) }} KB</span>
            <button
              type="button"
              @click.stop="selectedFile = null"
              class="mt-3 text-xs text-rose-600 hover:text-rose-700 font-semibold flex items-center gap-1 px-2.5 py-1 rounded-md bg-rose-50 hover:bg-rose-100 transition-colors"
            >
              <X class="w-3.5 h-3.5" />
              Remove file
            </button>
          </template>
        </div>

        <!-- Inline error message if any -->
        <div v-if="errorMessage" class="p-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center gap-2">
          <AlertCircle class="w-4 h-4 shrink-0 text-rose-600" />
          <span>{{ errorMessage }}</span>
        </div>
      </div>

      <!-- 3. Uploading Loader View -->
      <div v-else-if="uploadStatus === 'uploading'" class="py-12 flex flex-col items-center justify-center text-center">
        <div class="h-14 w-14 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center mb-4">
          <RotateCw class="h-8 w-8 animate-spin" />
        </div>
        <h3 class="text-base font-bold text-slate-900">Processing Inventory Spreadsheet...</h3>
        <p class="text-xs text-slate-500 mt-1 max-w-sm">
          Validating product SKUs, categories, unit prices, barcodes, and initializing warehouse stock levels.
        </p>
      </div>

      <!-- 4. Success View with Detailed Breakdown -->
      <div v-else-if="uploadStatus === 'success'" class="space-y-4 py-2">
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/80 p-4 text-center">
          <div class="h-10 w-10 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-2">
            <CheckCircle2 class="h-6 w-6" />
          </div>
          <h3 class="text-base font-bold text-emerald-950">Import Completed Successfully</h3>
          <p class="text-xs text-emerald-700 mt-0.5">
            Your inventory records and stock movements have been processed and saved.
          </p>
        </div>

        <!-- Result Metrics Cards -->
        <div v-if="importResult" class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
          <div class="rounded-xl border border-slate-200 bg-white p-3 text-center shadow-2xs">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block">Total Rows</span>
            <span class="text-xl font-extrabold text-slate-800 font-mono">{{ importResult.total_rows }}</span>
          </div>

          <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-3 text-center shadow-2xs">
            <span class="text-[10px] uppercase font-bold text-emerald-600 tracking-wider block">Created</span>
            <span class="text-xl font-extrabold text-emerald-700 font-mono">{{ importResult.created_count }}</span>
          </div>

          <div class="rounded-xl border border-blue-200 bg-blue-50/50 p-3 text-center shadow-2xs">
            <span class="text-[10px] uppercase font-bold text-blue-600 tracking-wider block">Updated</span>
            <span class="text-xl font-extrabold text-blue-700 font-mono">{{ importResult.updated_count }}</span>
          </div>

          <div
            class="rounded-xl border p-3 text-center shadow-2xs"
            :class="importResult.failed_count > 0 ? 'border-amber-200 bg-amber-50/50' : 'border-slate-200 bg-slate-50/50'"
          >
            <span class="text-[10px] uppercase font-bold tracking-wider block" :class="importResult.failed_count > 0 ? 'text-amber-700' : 'text-slate-400'">
              Errors
            </span>
            <span class="text-xl font-extrabold font-mono" :class="importResult.failed_count > 0 ? 'text-amber-800' : 'text-slate-600'">
              {{ importResult.failed_count }}
            </span>
          </div>
        </div>

        <!-- Expandable Row Errors (if any rows failed) -->
        <div v-if="importResult && importResult.errors && importResult.errors.length > 0" class="rounded-xl border border-amber-200 bg-amber-50/40 p-3.5 space-y-2">
          <button
            type="button"
            @click="showErrorsList = !showErrorsList"
            class="w-full flex items-center justify-between text-left text-xs font-bold text-amber-900 cursor-pointer"
          >
            <div class="flex items-center gap-1.5">
              <AlertTriangle class="w-4 h-4 text-amber-600 shrink-0" />
              <span>{{ importResult.errors.length }} row(s) had warnings or could not be imported</span>
            </div>
            <component :is="showErrorsList ? ChevronUp : ChevronDown" class="w-4 h-4 text-amber-700" />
          </button>

          <div v-if="showErrorsList" class="max-h-48 overflow-y-auto space-y-1 pt-1 text-xs font-mono">
            <div
              v-for="(err, idx) in importResult.errors"
              :key="idx"
              class="p-2 rounded bg-white border border-amber-200 text-amber-900 text-[11px] flex items-start justify-between gap-2"
            >
              <div>
                <span class="font-bold text-slate-800">Row {{ err.row }}:</span>
                <span v-if="err.sku" class="ml-1 text-blue-700 font-semibold">[{{ err.sku }}]</span>
                <span v-if="err.name" class="ml-1 text-slate-600">{{ err.name }}</span>
                <p class="text-rose-700 font-sans mt-0.5">{{ err.error }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 5. General Error View -->
      <div v-else-if="uploadStatus === 'error'" class="py-6 flex flex-col items-center justify-center text-center">
        <div class="h-12 w-12 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mb-3">
          <AlertCircle class="h-7 w-7" />
        </div>
        <h3 class="text-base font-bold text-slate-900">Import Processing Failed</h3>
        <p class="text-xs text-rose-600 mt-1 max-w-sm">{{ errorMessage }}</p>
        <UiButton class="mt-5" variant="outline" @click="uploadStatus = 'idle'">Try Again</UiButton>
      </div>
    </div>

    <!-- Modal Footer Actions -->
    <template #footer>
      <template v-if="uploadStatus === 'idle'">
        <UiButton variant="ghost" @click="emit('update:modelValue', false)" class="mr-2">Cancel</UiButton>
        <UiButton
          :disabled="!selectedFile"
          :loading="mutation.isPending.value"
          @click="handleUpload"
        >
          Upload and Import
        </UiButton>
      </template>

      <template v-else-if="uploadStatus === 'success'">
        <UiButton variant="ghost" @click="reset" class="mr-2">Import Another File</UiButton>
        <UiButton @click="emit('update:modelValue', false)">Done</UiButton>
      </template>
    </template>
  </UiModal>
</template>
