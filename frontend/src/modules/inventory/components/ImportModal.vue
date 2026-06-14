<script setup lang="ts">
import { ref } from 'vue'
import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { inventoryApi } from '@/api/inventory'
import { Upload, FileText, X, CheckCircle2, AlertCircle } from '@lucide/vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiButton from '@/components/ui/UiButton.vue'

interface Props {
  modelValue: boolean
}

const props = defineProps<Props>()
const emit = defineEmits(['update:modelValue', 'imported'])

const queryClient = useQueryClient()
const fileInput = ref<HTMLInputElement | null>(null)
const selectedFile = ref<File | null>(null)
const uploadStatus = ref<'idle' | 'uploading' | 'success' | 'error'>('idle')

const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    selectedFile.value = target.files[0]
  }
}

const mutation = useMutation({
  mutationFn: (file: File) => inventoryApi.importProducts(file),
  onSuccess: () => {
    uploadStatus.value = 'success'
    queryClient.invalidateQueries({ queryKey: ['inventory', 'products'] })
    emit('imported')
  },
  onError: () => {
    uploadStatus.value = 'error'
  }
})

const handleUpload = () => {
  if (!selectedFile.value) return
  uploadStatus.value = 'uploading'
  mutation.mutate(selectedFile.value)
}

const reset = () => {
  selectedFile.value = null
  uploadStatus.value = 'idle'
  if (fileInput.value) fileInput.value.value = ''
}
</script>

<template>
  <UiModal
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
    title="Import Products"
    size="md"
    @close="reset"
  >
    <div class="space-y-4">
      <div v-if="uploadStatus === 'idle' || uploadStatus === 'uploading'" class="space-y-4">
        <p class="text-sm text-slate-500">
          Upload a CSV or Excel file to import products in bulk. Download the 
          <a href="#" class="text-primary-600 hover:underline">sample template</a>.
        </p>

        <div 
          class="border-2 border-dashed border-slate-300 rounded-lg p-8 flex flex-col items-center justify-center cursor-pointer hover:border-primary-400 hover:bg-slate-50 transition-colors"
          @click="fileInput?.click()"
        >
          <input 
            type="file" 
            ref="fileInput" 
            class="hidden" 
            accept=".csv,.xlsx,.xls"
            @change="handleFileSelect"
          />
          
          <template v-if="!selectedFile">
            <Upload class="h-10 w-10 text-slate-400 mb-2" />
            <span class="text-sm font-medium text-slate-600">Click to upload or drag and drop</span>
            <span class="text-xs text-slate-400 mt-1">CSV, XLSX up to 10MB</span>
          </template>
          
          <template v-else>
            <FileText class="h-10 w-10 text-primary-500 mb-2" />
            <span class="text-sm font-medium text-slate-900">{{ selectedFile.name }}</span>
            <span class="text-xs text-slate-400 mt-1">{{ (selectedFile.size / 1024).toFixed(1) }} KB</span>
            <button @click.stop="selectedFile = null" class="mt-2 text-xs text-red-600 hover:text-red-700 font-medium">
              Remove file
            </button>
          </template>
        </div>
      </div>

      <div v-else-if="uploadStatus === 'success'" class="py-8 flex flex-col items-center justify-center text-center">
        <div class="h-12 w-12 bg-green-100 text-green-600 rounded-full flex items-center justify-center mb-4">
          <CheckCircle2 class="h-8 w-8" />
        </div>
        <h3 class="text-lg font-medium text-slate-900">Import Successful</h3>
        <p class="text-sm text-slate-500 mt-1">Your products have been queued for import and will appear shortly.</p>
        <UiButton class="mt-6" @click="emit('update:modelValue', false)">Done</UiButton>
      </div>

      <div v-else-if="uploadStatus === 'error'" class="py-8 flex flex-col items-center justify-center text-center">
        <div class="h-12 w-12 bg-red-100 text-red-600 rounded-full flex items-center justify-center mb-4">
          <AlertCircle class="h-8 w-8" />
        </div>
        <h3 class="text-lg font-medium text-slate-900">Import Failed</h3>
        <p class="text-sm text-slate-500 mt-1">There was an error processing your file. Please check the format and try again.</p>
        <UiButton class="mt-6" variant="outline" @click="uploadStatus = 'idle'">Try Again</UiButton>
      </div>
    </div>

    <template #footer v-if="uploadStatus === 'idle' || uploadStatus === 'uploading'">
      <UiButton variant="ghost" @click="emit('update:modelValue', false)" class="mr-2">Cancel</UiButton>
      <UiButton 
        :disabled="!selectedFile" 
        :loading="uploadStatus === 'uploading'"
        @click="handleUpload"
      >
        Upload and Import
      </UiButton>
    </template>
  </UiModal>
</template>
