<script setup lang="ts">
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'

interface Props {
  currentPage: number
  totalPages: number
  hasNextPage?: boolean
  hasPrevPage?: boolean
  totalItems?: number
  pageSize?: number
}

defineProps<Props>()
const emit = defineEmits(['update:currentPage'])
</script>

<template>
  <div class="flex items-center justify-between px-4 py-3 sm:px-6">
    <div class="flex flex-1 justify-between sm:hidden">
      <button
        @click="hasPrevPage && emit('update:currentPage', currentPage - 1)"
        :disabled="!hasPrevPage"
        class="relative inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50"
      >
        Previous
      </button>
      <button
        @click="hasNextPage && emit('update:currentPage', currentPage + 1)"
        :disabled="!hasNextPage"
        class="relative ml-3 inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50"
      >
        Next
      </button>
    </div>
    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
      <div>
        <p v-if="totalItems !== undefined" class="text-sm text-slate-700">
          Showing
          <span class="font-medium">{{ (currentPage - 1) * (pageSize || 10) + 1 }}</span>
          to
          <span class="font-medium">{{ Math.min(currentPage * (pageSize || 10), totalItems) }}</span>
          of
          <span class="font-medium">{{ totalItems }}</span>
          results
        </p>
      </div>
      <div>
        <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
          <button
            @click="hasPrevPage && emit('update:currentPage', currentPage - 1)"
            :disabled="!hasPrevPage"
            class="relative inline-flex items-center rounded-l-md px-2 py-2 text-slate-400 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50"
          >
            <span class="sr-only">Previous</span>
            <ChevronLeft class="h-5 w-5" aria-hidden="true" />
          </button>
          
          <!-- Page numbers could be added here for non-cursor pagination -->
          <span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-slate-900 ring-1 ring-inset ring-slate-300 focus:outline-offset-0">
            Page {{ currentPage }}
          </span>

          <button
            @click="hasNextPage && emit('update:currentPage', currentPage + 1)"
            :disabled="!hasNextPage"
            class="relative inline-flex items-center rounded-r-md px-2 py-2 text-slate-400 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50"
          >
            <span class="sr-only">Next</span>
            <ChevronRight class="h-5 w-5" aria-hidden="true" />
          </button>
        </nav>
      </div>
    </div>
  </div>
</template>
