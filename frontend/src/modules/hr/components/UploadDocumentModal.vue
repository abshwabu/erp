<script setup lang="ts">
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { hrApi } from '@/api/hr'
import UiModal from '@/components/ui/UiModal.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import { UploadCloud, FileText, CheckCircle, AlertCircle, X } from '@lucide/vue'
import type { DocumentType } from '@/types/hr'

const props = defineProps<{
  modelValue: boolean
  employeeId: string
}>()

const emit = defineEmits(['update:modelValue', 'saved'])

const { t } = useI18n()
const queryClient = useQueryClient()

const fileInputRef = ref<HTMLInputElement | null>(null)
const selectedFile = ref<File | null>(null)
const isDragging = ref(false)
const errorMessage = ref('')

const form = ref({
  title: '',
  document_type: 'cv' as DocumentType,
  expiry_date: '',
  notes: '',
})

const documentTypes = computed(() => [
  { label: t('hr.uploadDocumentModal.categories.cv'), value: 'cv' },
  { label: t('hr.uploadDocumentModal.categories.contract'), value: 'contract' },
  { label: t('hr.uploadDocumentModal.categories.education'), value: 'education' },
  { label: t('hr.uploadDocumentModal.categories.idProof'), value: 'id_proof' },
  { label: t('hr.uploadDocumentModal.categories.certification'), value: 'certification' },
  { label: t('hr.uploadDocumentModal.categories.tax'), value: 'tax' },
  { label: t('hr.uploadDocumentModal.categories.other'), value: 'other' },
])

const onFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    handleFile(target.files[0])
  }
}

const onDrop = (event: DragEvent) => {
  isDragging.value = false
  if (event.dataTransfer?.files && event.dataTransfer.files[0]) {
    handleFile(event.dataTransfer.files[0])
  }
}

const handleFile = (file: File) => {
  if (file.size > 26214400) { // 25MB
    errorMessage.value = t('hr.uploadDocumentModal.sizeExceeded')
    return
  }
  selectedFile.value = file
  errorMessage.value = ''
  if (!form.value.title) {
    // Auto populate title from file name without extension
    form.value.title = file.name.replace(/\.[^/.]+$/, '')
  }
}

const removeSelectedFile = () => {
  selectedFile.value = null
  if (fileInputRef.value) {
    fileInputRef.value.value = ''
  }
}

const uploadMutation = useMutation({
  mutationFn: async () => {
    if (!selectedFile.value) {
      throw new Error(t('hr.uploadDocumentModal.selectDocFile'))
    }
    const formData = new FormData()
    formData.append('file', selectedFile.value)
    formData.append('title', form.value.title || selectedFile.value.name)
    formData.append('document_type', form.value.document_type)
    if (form.value.expiry_date) {
      formData.append('expiry_date', form.value.expiry_date)
    }
    if (form.value.notes) {
      formData.append('notes', form.value.notes)
    }
    return hrApi.uploadEmployeeDocument(props.employeeId, formData)
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['hr', 'employees', props.employeeId, 'documents'] })
    queryClient.invalidateQueries({ queryKey: ['hr', 'employees', props.employeeId] })
    emit('saved')
    emit('update:modelValue', false)
    resetForm()
  },
  onError: (err: any) => {
    errorMessage.value = err?.response?.data?.message || err?.message || t('hr.uploadDocumentModal.uploadError')
  },
})

const resetForm = () => {
  selectedFile.value = null
  form.value = {
    title: '',
    document_type: 'cv',
    expiry_date: '',
    notes: '',
  }
  errorMessage.value = ''
  if (fileInputRef.value) {
    fileInputRef.value.value = ''
  }
}

const formatBytes = (bytes: number) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}
</script>

<template>
  <UiModal
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
    :title="t('hr.uploadDocumentModal.title')"
    size="lg"
  >
    <div class="space-y-5">
      <div v-if="errorMessage" class="rounded-xl border border-red-200 bg-red-50 p-3 text-xs text-red-700 font-medium">
        {{ errorMessage }}
      </div>

      <!-- Drag & Drop Area -->
      <div
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="onDrop"
        class="border-2 border-dashed rounded-2xl p-6 text-center transition-all cursor-pointer"
        :class="isDragging ? 'border-primary-500 bg-primary-50/50' : 'border-slate-300 hover:border-slate-400 bg-slate-50/50'"
        @click="fileInputRef?.click()"
      >
        <input
          ref="fileInputRef"
          type="file"
          class="hidden"
          accept=".pdf,.doc,.docx,.png,.jpg,.jpeg,.xlsx,.csv,.txt"
          @change="onFileSelect"
        />

        <div v-if="!selectedFile" class="flex flex-col items-center justify-center space-y-2">
          <div class="p-3 bg-white rounded-full shadow-xs border border-slate-200 text-primary-600">
            <UploadCloud class="w-8 h-8" />
          </div>
          <div>
            <p class="text-sm font-bold text-slate-800">
              {{ t('hr.uploadDocumentModal.dragDropPrompt') }}
            </p>
            <p class="text-xs text-slate-400 mt-1">
              {{ t('hr.uploadDocumentModal.supportedFormats') }}
            </p>
          </div>
        </div>

        <div v-else class="flex items-center justify-between bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs" @click.stop>
          <div class="flex items-center gap-3 text-left">
            <div class="p-2.5 bg-blue-50 text-blue-600 rounded-lg">
              <FileText class="w-6 h-6" />
            </div>
            <div>
              <p class="text-sm font-bold text-slate-900 line-clamp-1">{{ selectedFile.name }}</p>
              <p class="text-xs font-mono text-slate-400">{{ formatBytes(selectedFile.size) }}</p>
            </div>
          </div>
          <button
            type="button"
            @click="removeSelectedFile"
            class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors"
          >
            <X class="w-4 h-4" />
          </button>
        </div>
      </div>

      <!-- Form Details -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <UiInput
          v-model="form.title"
          :label="t('hr.uploadDocumentModal.docTitle')"
          :placeholder="t('hr.uploadDocumentModal.titlePlaceholder')"
          required
        />
        <UiSelect
          v-model="form.document_type"
          :label="t('hr.uploadDocumentModal.docCategory')"
          :options="documentTypes"
          required
        />
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <UiInput
          v-model="form.expiry_date"
          :label="t('hr.uploadDocumentModal.expiryDate')"
          type="date"
          :placeholder="t('hr.uploadDocumentModal.expiryPlaceholder')"
        />
        <UiInput
          v-model="form.notes"
          :label="t('hr.uploadDocumentModal.notes')"
          :placeholder="t('hr.uploadDocumentModal.notesPlaceholder')"
        />
      </div>

      <!-- Actions -->
      <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
        <UiButton variant="outline" type="button" @click="emit('update:modelValue', false)" :disabled="uploadMutation.isPending.value">
          {{ t('hr.uploadDocumentModal.cancel') }}
        </UiButton>
        <UiButton
          type="button"
          :loading="uploadMutation.isPending.value"
          :disabled="!selectedFile || !form.title"
          @click="uploadMutation.mutate()"
        >
          {{ t('hr.uploadDocumentModal.uploadBtn') }}
        </UiButton>
      </div>
    </div>
  </UiModal>
</template>
