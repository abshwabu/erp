<script setup lang="ts">
import { ref } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { inventoryApi } from '@/api/inventory'
import { Plus, Folder, FileText, ChevronRight, ChevronDown, Edit2, Trash2 } from '@lucide/vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSpinner from '@/components/ui/UiSpinner.vue'
import type { ProductCategory } from '@/types/inventory'

const queryClient = useQueryClient()
const { data: categories, isLoading } = useQuery({
  queryKey: ['inventory', 'categories'],
  queryFn: () => inventoryApi.getCategories().then(res => res.data)
})

const expandedIds = ref<Set<number>>(new Set())

const toggleExpand = (id: number) => {
  if (expandedIds.value.has(id)) {
    expandedIds.value.delete(id)
  } else {
    expandedIds.value.add(id)
  }
}

// Mocking some functionality for the UI
const isAdding = ref(false)
const newCategoryName = ref('')
const selectedCategoryId = ref<number | null>(null)

const handleAdd = () => {
  if (!newCategoryName.value) return
  // Implementation of add logic would go here
  isAdding.value = false
  newCategoryName.value = ''
}
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <h3 class="text-lg font-medium">Categories</h3>
      <UiButton size="sm" @click="isAdding = true">
        <Plus class="h-4 w-4 mr-1" />
        Add Category
      </UiButton>
    </div>

    <div v-if="isAdding" class="p-4 border rounded-lg bg-slate-50 space-y-3">
      <UiInput v-model="newCategoryName" label="Category Name" placeholder="e.g. Electronics" />
      <div class="flex justify-end space-x-2">
        <UiButton variant="ghost" size="sm" @click="isAdding = false">Cancel</UiButton>
        <UiButton size="sm" @click="handleAdd">Save Category</UiButton>
      </div>
    </div>

    <div v-if="isLoading" class="flex justify-center py-8">
      <UiSpinner size="md" />
    </div>

    <div v-else-if="categories && categories.length > 0" class="border rounded-lg overflow-hidden bg-white">
      <div class="divide-y divide-slate-100">
        <template v-for="category in categories" :key="category.id">
          <div class="group">
            <div 
              class="flex items-center px-4 py-3 hover:bg-slate-50 cursor-pointer"
              :class="{ 'bg-primary-50': selectedCategoryId === category.id }"
              @click="selectedCategoryId = category.id"
            >
              <button 
                v-if="category.children && category.children.length > 0"
                class="mr-2 text-slate-400 hover:text-slate-600"
                @click.stop="toggleExpand(category.id)"
              >
                <ChevronDown v-if="expandedIds.has(category.id)" class="h-4 w-4" />
                <ChevronRight v-else class="h-4 w-4" />
              </button>
              <div v-else class="w-6" />
              
              <Folder class="h-4 w-4 mr-2 text-slate-400" />
              <span class="text-sm font-medium text-slate-700 flex-1">{{ category.name }}</span>
              
              <div class="hidden group-hover:flex items-center space-x-1">
                <button class="p-1 text-slate-400 hover:text-primary-600 transition-colors">
                  <Edit2 class="h-3.5 w-3.5" />
                </button>
                <button class="p-1 text-slate-400 hover:text-red-600 transition-colors">
                  <Trash2 class="h-3.5 w-3.5" />
                </button>
              </div>
            </div>

            <!-- Children -->
            <div v-if="expandedIds.has(category.id) && category.children" class="bg-slate-50/50">
              <div 
                v-for="child in category.children" 
                :key="child.id"
                class="flex items-center pl-12 pr-4 py-2 hover:bg-slate-100 cursor-pointer text-sm text-slate-600"
              >
                <FileText class="h-4 w-4 mr-2 text-slate-400" />
                <span>{{ child.name }}</span>
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>
    <div v-else class="text-center py-8 text-slate-500 border rounded-lg border-dashed">
      No categories found.
    </div>
  </div>
</template>
