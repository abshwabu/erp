<script setup lang="ts">
interface Props {
  title?: string
  noPadding?: boolean
}

defineProps<Props>()
</script>

<template>
  <div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden">
    <div v-if="title || $slots.header || $slots.actions" class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
      <slot name="header">
        <h3 v-if="title" class="text-lg font-medium text-slate-900 leading-6">{{ title }}</h3>
      </slot>
      <div v-if="$slots.actions" class="flex-shrink-0">
        <slot name="actions" />
      </div>
    </div>
    <div :class="[noPadding ? '' : 'px-6 py-4']">
      <slot />
    </div>
    <div v-if="$slots.footer" class="px-6 py-4 bg-slate-50 border-t border-slate-200">
      <slot name="footer" />
    </div>
  </div>
</template>
