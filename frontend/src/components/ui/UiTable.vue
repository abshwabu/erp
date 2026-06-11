<script setup lang="ts">
import UiSpinner from './UiSpinner.vue'
import UiEmpty from './UiEmpty.vue'

interface Column {
  key: string
  label: string
  sortable?: boolean
  align?: 'left' | 'right' | 'center'
}

interface Props {
  columns: Column[]
  data: any[]
  loading?: boolean
  emptyTitle?: string
  emptyDescription?: string
}

defineProps<Props>()
const emit = defineEmits(['sort'])
</script>

<template>
  <div class="overflow-x-auto border border-slate-200 rounded-lg">
    <table class="min-w-full divide-y divide-slate-200">
      <thead class="bg-slate-50">
        <tr>
          <th
            v-for="col in columns"
            :key="col.key"
            scope="col"
            class="px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider"
            :class="[
              col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : 'text-left',
              col.sortable ? 'cursor-pointer hover:text-slate-700 select-none' : '',
            ]"
            @click="col.sortable && emit('sort', col.key)"
          >
            <div class="flex items-center space-x-1" :class="[col.align === 'right' ? 'justify-end' : col.align === 'center' ? 'justify-center' : '']">
              <span>{{ col.label }}</span>
              <!-- Sort icon placeholder -->
            </div>
          </th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-slate-200">
        <template v-if="loading">
          <tr v-for="i in 5" :key="i">
            <td v-for="col in columns" :key="col.key" class="px-6 py-4">
              <div class="h-4 bg-slate-100 rounded animate-pulse w-full"></div>
            </td>
          </tr>
        </template>
        <template v-else-if="data.length > 0">
          <tr v-for="(item, index) in data" :key="index" class="hover:bg-slate-50 transition-colors">
            <td
              v-for="col in columns"
              :key="col.key"
              class="px-6 py-4 whitespace-nowrap text-sm text-slate-900"
              :class="[col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : 'text-left']"
            >
              <slot :name="`cell(${col.key})`" :item="item" :value="item[col.key]">
                {{ item[col.key] }}
              </slot>
            </td>
          </tr>
        </template>
        <tr v-else>
          <td :colspan="columns.length" class="px-6 py-4">
            <UiEmpty :title="emptyTitle" :description="emptyDescription" />
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
