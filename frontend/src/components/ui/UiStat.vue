<script setup lang="ts">
import { TrendingUp, TrendingDown } from '@lucide/vue'
import UiIcon from './UiIcon.vue'

interface Props {
  label: string
  value: string | number
  change?: number
  changeLabel?: string
  icon?: any
  loading?: boolean
}

defineProps<Props>()
</script>

<template>
  <div class="bg-white p-6 rounded-lg border border-slate-200 shadow-sm">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm font-medium text-slate-500 truncate">{{ label }}</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ value }}</p>
      </div>
      <div v-if="icon && typeof icon !== 'string'" class="p-3 bg-primary-50 rounded-lg text-primary-600">
        <UiIcon :icon="icon" :size="24" />
      </div>
    </div>
    <div v-if="change !== undefined" class="mt-4 flex items-center">
      <span
        class="inline-flex items-center text-sm font-medium"
        :class="[change >= 0 ? 'text-green-600' : 'text-red-600']"
      >
        <TrendingUp v-if="change >= 0" class="mr-1 h-4 w-4" />
        <TrendingDown v-else class="mr-1 h-4 w-4" />
        {{ Math.abs(change) }}%
      </span>
      <span v-if="changeLabel" class="ml-2 text-sm text-slate-500 truncate">{{ changeLabel }}</span>
    </div>
  </div>
</template>
