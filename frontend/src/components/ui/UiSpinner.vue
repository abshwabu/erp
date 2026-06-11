<script setup lang="ts">
interface Props {
  size?: 'sm' | 'md' | 'lg' | 'xl'
  color?: string
}

withDefaults(defineProps<Props>(), {
  size: 'md',
  color: 'text-primary-600',
})
</script>

<template>
  <div
    class="animate-spin inline-block rounded-full border-2 border-current border-t-transparent"
    :class="[
      color,
      {
        'h-4 w-4': size === 'sm',
        'h-8 w-8': size === 'md',
        'h-12 w-12': size === 'lg',
        'h-16 w-16': size === 'xl',
      },
    ]"
    role="status"
    aria-label="loading"
  >
    <span class="sr-only">Loading...</span>
  </div>
</template>
