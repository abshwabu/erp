<script setup lang="ts">
import { Loader2 } from '@lucide/vue'

interface Props {
  variant?: 'primary' | 'secondary' | 'danger' | 'ghost' | 'outline'
  size?: 'sm' | 'md' | 'lg'
  loading?: boolean
  disabled?: boolean
  type?: 'button' | 'submit' | 'reset'
}

withDefaults(defineProps<Props>(), {
  variant: 'primary',
  size: 'md',
  loading: false,
  disabled: false,
  type: 'button',
})
</script>

<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    class="inline-flex items-center justify-center rounded-md font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 disabled:pointer-events-none disabled:opacity-50"
    :class="[
      {
        'bg-primary-600 text-white hover:bg-primary-700': variant === 'primary',
        'bg-slate-100 text-slate-900 hover:bg-slate-200': variant === 'secondary',
        'bg-red-600 text-white hover:bg-red-700': variant === 'danger',
        'hover:bg-slate-100 text-slate-600': variant === 'ghost',
        'border border-slate-200 bg-transparent hover:bg-slate-50 text-slate-600': variant === 'outline',
      },
      {
        'h-8 px-3 text-xs': size === 'sm',
        'h-10 px-4 py-2': size === 'md',
        'h-12 px-6 text-lg': size === 'lg',
      },
    ]"
  >
    <Loader2 v-if="loading" class="mr-2 h-4 w-4 animate-spin" />
    <slot />
  </button>
</template>
