<script setup lang="ts">
interface Props {
  variant?: 'primary' | 'secondary' | 'danger' | 'ghost' | 'outline'
  size?: 'sm' | 'md' | 'lg'
  isLoading?: boolean
  disabled?: boolean
}

withDefaults(defineProps<Props>(), {
  variant: 'primary',
  size: 'md',
  isLoading: false,
  disabled: false,
})
</script>

<template>
  <button
    :disabled="disabled || isLoading"
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
    <svg
      v-if="isLoading"
      class="mr-2 h-4 w-4 animate-spin"
      xmlns="http://www.w3.org/2000/svg"
      fill="none"
      viewBox="0 0 24 24"
    >
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg>
    <slot />
  </button>
</template>
