<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/api/client'
import { FileText, Upload, Download, Trash2, Folder, Search, FileCode, FileSpreadsheet, File } from '@lucide/vue'

interface DocumentItem {
  id: string
  name: string
  file_name: string
  mime_type: string
  file_size_bytes: number
  folder: string
  tags: string[] | null
  description: string | null
  created_at: string
  uploader?: { name: string; email: string }
}

const documents = ref<DocumentItem[]>([])
const loading = ref(true)
const selectedFolder = ref<string>('all')
const searchQuery = ref('')

const folders = ['all', 'general', 'contracts', 'invoices', 'receipts', 'policies']

function formatBytes(bytes: number) {
  if (!bytes || bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
}

function getFileIcon(mime: string) {
  if (mime.includes('pdf')) return FileText
  if (mime.includes('spreadsheet') || mime.includes('csv') || mime.includes('excel')) return FileSpreadsheet
  if (mime.includes('json') || mime.includes('javascript') || mime.includes('html')) return FileCode
  return File
}

async function fetchDocuments() {
  loading.value = true
  try {
    const params: Record<string, string> = {}
    if (selectedFolder.value !== 'all') params.folder = selectedFolder.value
    if (searchQuery.value) params.search = searchQuery.value

    const res = await api.get('/documents', { params })
    documents.value = res.data?.data?.data ?? res.data?.data ?? []
  } catch (e) {
    console.error('Failed to load documents', e)
  } finally {
    loading.value = false
  }
}

async function deleteDoc(id: string) {
  if (!confirm('Are you sure you want to delete this document?')) return
  try {
    await api.delete(`/documents/${id}`)
    documents.value = documents.value.filter(d => d.id !== id)
  } catch (e) {
    console.error('Failed to delete document', e)
  }
}

onMounted(fetchDocuments)
</script>

<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <div class="flex items-center space-x-3">
        <FileText class="w-7 h-7 text-gray-700" />
        <h1 class="text-2xl font-bold text-gray-900">Documents</h1>
      </div>
    </div>

    <!-- Filters and Search Bar -->
    <div class="flex flex-col sm:flex-row gap-4 justify-between items-start sm:items-center bg-white p-4 rounded-lg border border-gray-200">
      <!-- Folder tabs -->
      <div class="flex flex-wrap gap-2">
        <button
          v-for="folder in folders"
          :key="folder"
          @click="selectedFolder = folder; fetchDocuments()"
          :class="[
            selectedFolder === folder
              ? 'bg-primary-600 text-white font-medium shadow-sm'
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
            'px-3 py-1.5 rounded-md text-xs uppercase tracking-wide transition-colors flex items-center space-x-1.5'
          ]"
        >
          <Folder class="w-3.5 h-3.5" />
          <span>{{ folder }}</span>
        </button>
      </div>

      <!-- Search Input -->
      <div class="relative w-full sm:w-64">
        <Search class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" />
        <input
          v-model="searchQuery"
          @input="fetchDocuments"
          placeholder="Search documents…"
          class="w-full pl-9 pr-3 py-1.5 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-primary-500"
        />
      </div>
    </div>

    <div v-if="loading" class="text-center py-12 text-gray-500">Loading documents…</div>

    <div v-else-if="documents.length === 0" class="text-center py-16 bg-white rounded-lg border border-gray-200">
      <FileText class="w-12 h-12 text-gray-300 mx-auto mb-3" />
      <p class="text-gray-500 font-medium">No documents found</p>
      <p class="text-sm text-gray-400 mt-1">Upload business files, contracts, or receipts.</p>
    </div>

    <div v-else class="bg-white shadow rounded-lg overflow-hidden border border-gray-200">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Document</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Folder</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Size</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Uploaded By</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="doc in documents" :key="doc.id" class="hover:bg-gray-50 transition-colors">
            <td class="px-6 py-4">
              <div class="flex items-center space-x-3">
                <component :is="getFileIcon(doc.mime_type)" class="w-5 h-5 text-gray-400 shrink-0" />
                <div>
                  <p class="text-sm font-semibold text-gray-900">{{ doc.name }}</p>
                  <p class="text-xs text-gray-400">{{ doc.file_name }}</p>
                </div>
              </div>
            </td>
            <td class="px-6 py-4 text-xs font-mono text-gray-600">
              <span class="bg-gray-100 px-2 py-0.5 rounded">{{ doc.folder }}</span>
            </td>
            <td class="px-6 py-4 text-sm text-gray-500">{{ formatBytes(doc.file_size_bytes) }}</td>
            <td class="px-6 py-4 text-sm text-gray-700">{{ doc.uploader?.name ?? '—' }}</td>
            <td class="px-6 py-4 text-sm text-gray-400">{{ doc.created_at?.substring(0, 10) }}</td>
            <td class="px-6 py-4 text-right space-x-2">
              <button
                @click="deleteDoc(doc.id)"
                class="text-gray-400 hover:text-red-600 p-1 rounded hover:bg-gray-100 transition-colors"
                title="Delete"
              >
                <Trash2 class="w-4 h-4" />
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
