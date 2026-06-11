<script setup lang="ts">
import { 
  CheckCircle, 
  AlertTriangle, 
  XCircle, 
  Info 
} from 'lucide-vue-next'

interface Props {
  variant?: 'success' | 'warning' | 'error' | 'info'
  title?: string
}

withDefaults(defineProps<Props>(), {
  variant: 'info',
})
</script>

<template>
  <div
    class="rounded-md p-4 flex"
    :class="[
      {
        'bg-green-50 text-green-800 border border-green-200': variant === 'success',
        'bg-yellow-50 text-yellow-800 border border-yellow-200': variant === 'warning',
        'bg-red-50 text-red-800 border border-red-200': variant === 'error',
        'bg-blue-50 text-blue-800 border border-blue-200': variant === 'info',
      },
    ]"
  >
    <div class="flex-shrink-0">
      <CheckCircle v-if="variant === 'success'" class="h-5 w-5 text-green-400" />
      <AlertTriangle v-else-if="variant === 'warning'" class="h-5 w-5 text-yellow-400" />
      <XCircle v-else-if="variant === 'error'" class="h-5 w-5 text-red-400" />
      <Info v-else class="h-5 w-5 text-blue-400" />
    </div>
    <div class="ml-3">
      <h3 v-if="title" class="text-sm font-medium" :class="[
        {
          'text-green-800': variant === 'success',
          'text-yellow-800': variant === 'warning',
          'text-red-800': variant === 'error',
          'text-blue-800': variant === 'info',
        }
      ]">
        {{ title }}
      </h3>
      <div class="mt-2 text-sm" :class="[
        {
          'text-green-700': variant === 'success',
          'text-yellow-700': variant === 'warning',
          'text-red-700': variant === 'error',
          'text-blue-700': variant === 'info',
        }
      ]">
        <slot />
      </div>
    </div>
  </div>
</template>
