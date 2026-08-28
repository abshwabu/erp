<script setup lang="ts">
import { ref, reactive, watch, computed } from 'vue'
import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { documentsApi } from '@/api/documents'
import {
  Upload,
  FileText,
  X,
  CheckCircle2,
  AlertCircle,
  Loader2,
  Folder,
} from '@lucide/vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'

interface Props {
  modelValue: boolean
}

const props = defineProps<Props>()
const emit = defineEmits(['update:modelValue', 'uploaded'])

const queryClient = useQueryClient()
const fileInputRef = ref<HTMLInputElement | null>(null)
const isDragging = ref(false)

const form = reactive({
  name: '',
  folder: 'general',
  description: '',
  tags: '',
})

const selectedFile = ref<File | null>(null)
const errors = reactive<Record<string, string>>({})

const folderOptions = [
  { label: 'General', value: 'general' },
  { label: 'Contracts', value: 'contracts' },
  { label: 'Invoices', value: 'invoices' },
  { label: 'Receipts', value: 'receipts' },
  { label: 'Policies', value: 'policies' },
]

// Auto-fill name from filename
watch(selectedFile, (file) => {
  if (file && !form.name) {
    // Strip extension for a clean name
    const nameWithoutExt = file.name.replace(/\.[^/.]+$/, '')
    form.name = nameWithoutExt.replace(/[_-]/g, ' ')
  }
})

// Reset form when modal opens/closes
watch(
  () => props.modelValue,
  (open) => {
    if (open) {
      resetForm()
    }
  }
)

function resetForm() {
  form.name = ''
  form.folder = 'general'
  form.description = ''
  form.tags = ''
  selectedFile.value = null
  Object.keys(errors).forEach((key) => delete errors[key])
}

function formatFileSize(bytes: number): string {
  if (!bytes || bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
}

function validate(): boolean {
  Object.keys(errors).forEach((key) => delete errors[key])

  if (!selectedFile.value) errors.file = 'Please select a file to upload'
  if (!form.name.trim()) errors.name = 'Document name is required'

  return Object.keys(errors).length === 0
}

function triggerFileInput() {
  fileInputRef.value?.click()
}

function handleFileSelect(e: Event) {
  const target = e.target as HTMLInputElement
  if (target.files && target.files[0]) {
    selectedFile.value = target.files[0]
    delete errors.file
  }
}

function handleDragOver(e: DragEvent) {
  e.preventDefault()
  isDragging.value = true
}

function handleDragLeave() {
  isDragging.value = false
}

function handleDrop(e: DragEvent) {
  e.preventDefault()
  isDragging.value = false
  if (e.dataTransfer?.files && e.dataTransfer.files[0]) {
    selectedFile.value = e.dataTransfer.files[0]
    delete errors.file
  }
}

function removeFile() {
  selectedFile.value = null
  form.name = ''
  if (fileInputRef.value) fileInputRef.value.value = ''
}

const parsedTags = computed(() => {
  if (!form.tags.trim()) return []
  return form.tags
    .split(',')
    .map((t) => t.trim())
    .filter(Boolean)
})

const mutation = useMutation({
  mutationFn: () => {
    if (!selectedFile.value) throw new Error('No file selected')

    return documentsApi.uploadDocument({
      file: selectedFile.value,
      name: form.name.trim(),
      folder: form.folder,
      description: form.description.trim() || undefined,
      tags: parsedTags.value.length > 0 ? parsedTags.value : undefined,
    })
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['documents'] })
    emit('uploaded')
    emit('update:modelValue', false)
  },
  onError: (error: any) => {
    if (error?.errors) {
      Object.entries(error.errors).forEach(([key, msgs]: any) => {
        errors[key] = Array.isArray(msgs) ? msgs[0] : msgs
      })
    }
  },
})

function handleSubmit() {
  if (!validate()) return
  mutation.mutate()
}
</script>

<template>
  <UiModal
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
    title="Upload Document"
    size="lg"
    @close="resetForm"
  >
    <form @submit.prevent="handleSubmit" class="space-y-5">
      <!-- File Drop Zone -->
      <div class="space-y-1.5">
        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
          File <span class="text-red-500 font-bold">*</span>
        </label>
        <div
          :class="[
            'relative border-2 border-dashed rounded-2xl p-8 flex flex-col items-center justify-center cursor-pointer transition-all duration-200',
            isDragging
              ? 'border-blue-400 bg-blue-50/60 scale-[1.01]'
              : selectedFile
              ? 'border-emerald-300 bg-emerald-50/40'
              : 'border-slate-300 hover:border-blue-400 hover:bg-slate-50',
          ]"
          @click="triggerFileInput"
          @dragover="handleDragOver"
          @dragleave="handleDragLeave"
          @drop="handleDrop"
        >
          <input
            type="file"
            ref="fileInputRef"
            class="hidden"
            accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.png,.jpg,.jpeg,.gif,.zip,.rar"
            @change="handleFileSelect"
          />

          <template v-if="!selectedFile">
            <div class="p-3 bg-slate-100 rounded-2xl mb-3">
              <Upload class="h-8 w-8 text-slate-400" />
            </div>
            <span class="text-sm font-bold text-slate-700">Click to upload or drag and drop</span>
            <span class="text-xs text-slate-400 mt-1">PDF, DOC, XLS, CSV, images, ZIP — up to 20MB</span>
          </template>

          <template v-else>
            <div class="p-3 bg-emerald-100 rounded-2xl mb-3">
              <FileText class="h-8 w-8 text-emerald-600" />
            </div>
            <span class="text-sm font-bold text-slate-900">{{ selectedFile.name }}</span>
            <span class="text-xs text-slate-400 mt-1">{{ formatFileSize(selectedFile.size) }}</span>
            <button
              type="button"
              @click.stop="removeFile"
              class="mt-2.5 text-xs text-red-600 hover:text-red-700 font-bold inline-flex items-center gap-1 hover:underline"
            >
              <X class="w-3.5 h-3.5" /> Remove file
            </button>
          </template>
        </div>
        <p v-if="errors.file" class="text-[11px] font-semibold text-red-600 pl-1">{{ errors.file }}</p>
      </div>

      <!-- Document Name -->
      <UiInput
        v-model="form.name"
        label="Document Name"
        placeholder="e.g. Vendor Contract Q3 2026"
        :error="errors.name"
        required
      />

      <!-- Folder & Tags Row -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <UiSelect
          v-model="form.folder"
          label="Folder"
          :options="folderOptions"
        />

        <UiInput
          v-model="form.tags"
          label="Tags"
          placeholder="e.g. finance, q3, urgent"
          help-text="Comma-separated list of tags"
        />
      </div>

      <!-- Description -->
      <div class="space-y-1.5">
        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Description</label>
        <textarea
          v-model="form.description"
          rows="3"
          class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white text-sm p-3 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all shadow-2xs font-medium text-slate-900 placeholder:text-slate-400"
          placeholder="Optional description or notes about this document…"
        ></textarea>
      </div>

      <!-- Server Errors -->
      <div
        v-if="mutation.isError.value && !Object.keys(errors).length"
        class="flex items-start gap-2.5 p-3 rounded-xl bg-red-50 border border-red-200"
      >
        <AlertCircle class="w-4 h-4 text-red-500 mt-0.5 shrink-0" />
        <p class="text-xs text-red-700 font-medium">
          Upload failed. Please check your file and try again.
        </p>
      </div>
    </form>

    <template #footer>
      <div class="flex items-center justify-end gap-2.5">
        <UiButton variant="ghost" @click="emit('update:modelValue', false)">Cancel</UiButton>
        <UiButton
          type="submit"
          :loading="mutation.isPending.value"
          :disabled="!selectedFile"
          @click="handleSubmit"
        >
          <Upload class="w-4 h-4 mr-1.5" />
          Upload Document
        </UiButton>
      </div>
    </template>
  </UiModal>
</template>
