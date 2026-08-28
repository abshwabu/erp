<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { documentsApi, type DocumentItem } from '@/api/documents'
import {
  FileText,
  Upload,
  Download,
  Trash2,
  Folder,
  Search,
  FileCode,
  FileSpreadsheet,
  File,
  FileImage,
  FileArchive,
  Plus,
  X,
  Eye,
  Filter,
  HardDrive,
  FolderOpen,
  Clock,
} from '@lucide/vue'
import UiTable from '@/components/ui/UiTable.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiPagination from '@/components/ui/UiPagination.vue'
import UploadDocumentModal from '../components/UploadDocumentModal.vue'
import { useToast } from '@/composables/useToast'

const queryClient = useQueryClient()
const toast = useToast()

const page = ref(1)
const isUploadModalOpen = ref(false)

const filters = reactive({
  search: '',
  folder: 'all',
})

const debouncedSearch = ref('')
let debounceTimer: any = null

watch(
  () => filters.search,
  (val) => {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => {
      debouncedSearch.value = val || ''
      page.value = 1
    }, 300)
  }
)

watch(
  () => filters.folder,
  () => {
    page.value = 1
  }
)

const folders = [
  { label: 'All', value: 'all', icon: FolderOpen },
  { label: 'General', value: 'general', icon: Folder },
  { label: 'Contracts', value: 'contracts', icon: FileText },
  { label: 'Invoices', value: 'invoices', icon: FileSpreadsheet },
  { label: 'Receipts', value: 'receipts', icon: File },
  { label: 'Policies', value: 'policies', icon: FileCode },
]

// ── Data Fetching ────────────────────────────────────────────────────────────

const { data, isLoading, refetch } = useQuery({
  queryKey: computed(() => [
    'documents',
    page.value,
    debouncedSearch.value,
    filters.folder,
  ]),
  queryFn: async () => {
    try {
      const res = await documentsApi.getDocuments(
        {
          search: debouncedSearch.value || undefined,
          folder: filters.folder !== 'all' ? filters.folder : undefined,
        },
        page.value
      )
      return res.data
    } catch (e) {
      console.warn('Failed to load documents', e)
      return {
        data: [],
        meta: {
          current_page: 1,
          last_page: 1,
          total: 0,
          from: 0,
          to: 0,
          per_page: 25,
        },
      }
    }
  },
})

const documentList = computed(() => (data.value?.data || []) as DocumentItem[])
const totalCount = computed(() => data.value?.meta?.total ?? documentList.value.length)

// ── KPI Stats ────────────────────────────────────────────────────────────────

const totalSizeBytes = computed(() => {
  return documentList.value.reduce((acc, doc) => acc + (doc.file_size_bytes || 0), 0)
})

const folderCounts = computed(() => {
  const counts: Record<string, number> = {}
  documentList.value.forEach((doc) => {
    counts[doc.folder] = (counts[doc.folder] || 0) + 1
  })
  return counts
})

const uniqueFolderCount = computed(() => Object.keys(folderCounts.value).length)

const recentUploads = computed(() => {
  const oneWeekAgo = new Date()
  oneWeekAgo.setDate(oneWeekAgo.getDate() - 7)
  return documentList.value.filter((doc) => new Date(doc.created_at) >= oneWeekAgo).length
})

// ── Helpers ──────────────────────────────────────────────────────────────────

function formatBytes(bytes: number): string {
  if (!bytes || bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
}

function getFileIcon(mime: string) {
  if (!mime) return File
  if (mime.includes('pdf')) return FileText
  if (mime.includes('spreadsheet') || mime.includes('csv') || mime.includes('excel')) return FileSpreadsheet
  if (mime.includes('json') || mime.includes('javascript') || mime.includes('html') || mime.includes('xml')) return FileCode
  if (mime.includes('image')) return FileImage
  if (mime.includes('zip') || mime.includes('rar') || mime.includes('tar') || mime.includes('gzip')) return FileArchive
  return File
}

function getFileIconColor(mime: string): string {
  if (!mime) return 'text-slate-400'
  if (mime.includes('pdf')) return 'text-red-500'
  if (mime.includes('spreadsheet') || mime.includes('csv') || mime.includes('excel')) return 'text-emerald-500'
  if (mime.includes('json') || mime.includes('javascript') || mime.includes('html')) return 'text-amber-500'
  if (mime.includes('image')) return 'text-purple-500'
  if (mime.includes('zip') || mime.includes('rar')) return 'text-orange-500'
  if (mime.includes('word') || mime.includes('document')) return 'text-blue-500'
  return 'text-slate-400'
}

function getFolderBadgeClass(folder: string): string {
  const map: Record<string, string> = {
    general: 'bg-slate-100 text-slate-700',
    contracts: 'bg-blue-50 text-blue-700',
    invoices: 'bg-emerald-50 text-emerald-700',
    receipts: 'bg-amber-50 text-amber-700',
    policies: 'bg-purple-50 text-purple-700',
  }
  return map[folder] || 'bg-slate-100 text-slate-700'
}

function formatDate(dateStr: string): string {
  if (!dateStr) return '—'
  const date = new Date(dateStr)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

// ── Actions ──────────────────────────────────────────────────────────────────

const deleteMutation = useMutation({
  mutationFn: (id: string) => documentsApi.deleteDocument(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['documents'] })
    toast.success('Document deleted successfully')
  },
  onError: () => {
    toast.error('Failed to delete document')
  },
})

function handleDelete(doc: DocumentItem) {
  if (!confirm(`Are you sure you want to delete "${doc.name}"?`)) return
  deleteMutation.mutate(doc.id)
}

async function handleDownload(doc: DocumentItem) {
  try {
    const response = await documentsApi.downloadDocument(doc.id)
    const blob = new Blob([response.data])
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = doc.file_name
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (e) {
    console.error('Download failed', e)
    toast.error('Failed to download document')
  }
}

function resetFilters() {
  filters.search = ''
  debouncedSearch.value = ''
  filters.folder = 'all'
  page.value = 1
}

const hasActiveFilters = computed(() => {
  return !!filters.search || !!debouncedSearch.value || filters.folder !== 'all'
})

// ── Table Columns ────────────────────────────────────────────────────────────

const columns = [
  { key: 'icon', label: '', align: 'left' as const },
  { key: 'name', label: 'Document', sortable: true },
  { key: 'folder', label: 'Folder' },
  { key: 'file_size_bytes', label: 'Size', align: 'right' as const },
  { key: 'uploader', label: 'Uploaded By' },
  { key: 'created_at', label: 'Date', sortable: true },
  { key: 'actions', label: '', align: 'right' as const },
]
</script>

<template>
  <div class="space-y-6 pb-12 font-sans max-w-7xl mx-auto px-4 sm:px-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="flex items-center space-x-2">
          <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Documents</h1>
          <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-100">
            {{ totalCount }} Files
          </span>
        </div>
        <p class="text-slate-500 text-xs sm:text-sm mt-1">
          Upload, organize, and manage business files, contracts, and receipts.
        </p>
      </div>

      <!-- Upload Button -->
      <div class="flex items-center gap-2">
        <button
          type="button"
          @click="isUploadModalOpen = true"
          class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs sm:text-sm font-bold shadow-md shadow-blue-600/30 transition-all transform hover:-translate-y-0.5 active:translate-y-0"
        >
          <Upload class="h-4 w-4" />
          <span>Upload Document</span>
        </button>
      </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
      <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
        <div>
          <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Files</p>
          <h3 class="text-xl sm:text-2xl font-black text-slate-900 mt-1">{{ totalCount }}</h3>
        </div>
        <div class="p-2.5 bg-blue-50 text-blue-600 rounded-xl">
          <FileText class="w-5 h-5" />
        </div>
      </div>

      <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
        <div>
          <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Storage Used</p>
          <h3 class="text-xl sm:text-2xl font-black text-slate-900 mt-1">{{ formatBytes(totalSizeBytes) }}</h3>
        </div>
        <div class="p-2.5 bg-emerald-50 text-emerald-600 rounded-xl">
          <HardDrive class="w-5 h-5" />
        </div>
      </div>

      <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
        <div>
          <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Folders</p>
          <h3 class="text-xl sm:text-2xl font-black text-slate-900 mt-1">{{ uniqueFolderCount }}</h3>
        </div>
        <div class="p-2.5 bg-purple-50 text-purple-600 rounded-xl">
          <FolderOpen class="w-5 h-5" />
        </div>
      </div>

      <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
        <div>
          <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">This Week</p>
          <h3 class="text-xl sm:text-2xl font-black text-slate-900 mt-1">{{ recentUploads }}</h3>
        </div>
        <div class="p-2.5 bg-amber-50 text-amber-600 rounded-xl">
          <Clock class="w-5 h-5" />
        </div>
      </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm space-y-3">
      <div class="flex flex-col md:flex-row items-start md:items-center gap-3">
        <!-- Folder Tabs -->
        <div class="flex flex-wrap gap-1.5">
          <button
            v-for="folder in folders"
            :key="folder.value"
            @click="filters.folder = folder.value"
            :class="[
              filters.folder === folder.value
                ? 'bg-blue-600 text-white font-bold shadow-sm'
                : 'bg-slate-100 text-slate-600 hover:bg-slate-200 font-medium',
              'px-3 py-1.5 rounded-xl text-xs uppercase tracking-wide transition-all flex items-center space-x-1.5',
            ]"
          >
            <component :is="folder.icon" class="w-3.5 h-3.5" />
            <span>{{ folder.label }}</span>
          </button>
        </div>

        <!-- Search Input -->
        <div class="relative flex-1 w-full md:max-w-xs ml-auto">
          <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
          <input
            v-model="filters.search"
            type="text"
            placeholder="Search documents…"
            class="w-full pl-10 pr-10 py-2 rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 transition-colors"
          />
          <button
            v-if="filters.search"
            @click="filters.search = ''"
            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
          >
            <X class="w-4 h-4" />
          </button>
        </div>

        <!-- Reset -->
        <button
          v-if="hasActiveFilters"
          @click="resetFilters"
          class="text-xs text-blue-600 hover:text-blue-700 font-semibold px-2.5 py-1.5 rounded-lg hover:bg-blue-50 transition-colors whitespace-nowrap"
        >
          Reset Filters
        </button>
      </div>
    </div>

    <!-- Documents Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
      <UiTable
        :columns="columns"
        :data="documentList"
        :loading="isLoading"
        empty-title="No documents found"
        empty-description="Upload your first document or adjust your search filters."
      >
        <!-- File Icon -->
        <template #cell(icon)="{ item }">
          <div class="h-10 w-10 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-center shrink-0">
            <component :is="getFileIcon(item.mime_type)" :class="['h-5 w-5', getFileIconColor(item.mime_type)]" />
          </div>
        </template>

        <!-- Name & File Name -->
        <template #cell(name)="{ item }">
          <div class="py-1">
            <div class="font-bold text-slate-900 text-sm">{{ item.name }}</div>
            <div class="flex items-center space-x-2 mt-0.5">
              <span class="font-mono text-[11px] text-slate-400 font-medium truncate max-w-[200px]">
                {{ item.file_name }}
              </span>
              <span v-if="item.tags && item.tags.length > 0" class="text-[10px] text-slate-300">&bull;</span>
              <div v-if="item.tags && item.tags.length > 0" class="flex items-center gap-1 flex-wrap">
                <span
                  v-for="tag in item.tags.slice(0, 3)"
                  :key="tag"
                  class="text-[10px] px-1.5 py-0.5 rounded-md bg-blue-50 text-blue-600 font-semibold"
                >
                  {{ tag }}
                </span>
                <span v-if="item.tags.length > 3" class="text-[10px] text-slate-400 font-medium">
                  +{{ item.tags.length - 3 }}
                </span>
              </div>
            </div>
          </div>
        </template>

        <!-- Folder Badge -->
        <template #cell(folder)="{ value }">
          <span
            :class="[
              'inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold capitalize',
              getFolderBadgeClass(value),
            ]"
          >
            <Folder class="w-3 h-3 mr-1" />
            {{ value }}
          </span>
        </template>

        <!-- Size -->
        <template #cell(file_size_bytes)="{ value }">
          <span class="font-medium text-slate-600 text-xs font-mono">{{ formatBytes(value) }}</span>
        </template>

        <!-- Uploader -->
        <template #cell(uploader)="{ item }">
          <span class="text-sm text-slate-700">{{ item.uploader?.name ?? '—' }}</span>
        </template>

        <!-- Date -->
        <template #cell(created_at)="{ value }">
          <span class="text-xs text-slate-500 font-medium">{{ formatDate(value) }}</span>
        </template>

        <!-- Actions -->
        <template #cell(actions)="{ item }">
          <div class="flex items-center justify-end space-x-1">
            <button
              type="button"
              @click.stop="handleDownload(item)"
              title="Download"
              class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-all"
            >
              <Download class="h-4 w-4" />
            </button>
            <button
              type="button"
              @click.stop="handleDelete(item)"
              title="Delete"
              class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all"
            >
              <Trash2 class="h-4 w-4" />
            </button>
          </div>
        </template>
      </UiTable>
    </div>

    <!-- Pagination -->
    <div v-if="data?.meta && data.meta.total > 0" class="flex flex-col sm:flex-row justify-between items-center gap-3 pt-2">
      <p class="text-xs font-medium text-slate-500">
        Showing <span class="font-bold text-slate-700">{{ data.meta.from ?? 0 }}</span>–<span class="font-bold text-slate-700">{{ data.meta.to ?? 0 }}</span> of <span class="font-bold text-slate-700">{{ data.meta.total }}</span> documents
      </p>
      <UiPagination
        :current-page="page"
        @update:current-page="page = $event"
        :total-pages="data.meta.last_page || 1"
        :has-next-page="data.meta.current_page < data.meta.last_page"
        :has-prev-page="data.meta.current_page > 1"
      />
    </div>

    <!-- Upload Modal -->
    <UploadDocumentModal
      v-model="isUploadModalOpen"
      @uploaded="refetch"
    />
  </div>
</template>
