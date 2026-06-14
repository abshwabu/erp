<script setup lang="ts">
import { computed } from 'vue'
import { Inbox } from '@lucide/vue'
import UiIcon from './UiIcon.vue'

interface Props {
  title?: string
  description?: string
  icon?: any
}

const props = withDefaults(defineProps<Props>(), {
  title: 'No results found',
  description: 'Try adjusting your filters or search terms.',
})

const resolvedIcon = computed(() => props.icon ?? Inbox)
</script>

<template>
  <div class="text-center py-12 px-4">
    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 text-slate-400 mb-4">
      <UiIcon v-if="resolvedIcon && typeof resolvedIcon !== 'string'" :icon="resolvedIcon" :size="32" />
    </div>
    <h3 class="text-lg font-medium text-slate-900">{{ title }}</h3>
    <p class="mt-1 text-sm text-slate-500 max-w-xs mx-auto">
      {{ description }}
    </p>
    <div v-if="$slots.action" class="mt-6">
      <slot name="action" />
    </div>
  </div>
</template>
