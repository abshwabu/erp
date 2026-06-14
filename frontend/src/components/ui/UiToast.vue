<script setup lang="ts">
import { 
  CheckCircle, 
  AlertTriangle, 
  XCircle, 
  Info, 
  X 
} from '@lucide/vue'
import type { ToastType } from '@/stores/notification'

interface Props {
  id: string
  message: string
  type: ToastType
}

defineProps<Props>()
const emit = defineEmits(['close'])
</script>

<template>
  <div
    class="max-w-md w-full bg-white shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden"
  >
    <div class="p-4">
      <div class="flex items-start">
        <div class="flex-shrink-0">
          <CheckCircle v-if="type === 'success'" class="h-6 w-6 text-green-400" />
          <AlertTriangle v-else-if="type === 'warning'" class="h-6 w-6 text-yellow-400" />
          <XCircle v-else-if="type === 'error'" class="h-6 w-6 text-red-400" />
          <Info v-else class="h-6 w-6 text-blue-400" />
        </div>
        <div class="ml-3 w-0 flex-1 pt-0.5">
          <p class="text-sm font-medium text-slate-900">
            {{ message }}
          </p>
        </div>
        <div class="ml-4 flex-shrink-0 flex">
          <button
            @click="emit('close', id)"
            class="bg-white rounded-md inline-flex text-slate-400 hover:text-slate-500 focus:outline-none"
          >
            <span class="sr-only">Close</span>
            <X class="h-5 w-5" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
