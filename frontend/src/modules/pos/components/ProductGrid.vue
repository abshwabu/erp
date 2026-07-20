<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import { inventoryApi } from '@/api/inventory'
import { usePosStore } from '../stores/posStore'
import UiInput from '@/components/ui/UiInput.vue'
import UiButton from '@/components/ui/UiButton.vue'
import { Search } from '@lucide/vue'

const posStore = usePosStore()
const searchQuery = ref('')

const { data: categories } = useQuery({
  queryKey: ['inventory', 'categories'],
  queryFn: () => inventoryApi.getCategories().then(res => res.data)
})

const { data: products } = useQuery({
  queryKey: ['inventory', 'products-pos'],
  queryFn: () => inventoryApi.getProducts({ status: 'active' }, 1).then(res => res.data.data)
})

const selectedCategoryId = ref<string | null>(null)

const categoryList = computed(() => {
  return [{ id: null, name: 'All' }, ...(Array.isArray(categories.value) ? categories.value : [])]
})

const mappedProducts = computed(() => {
  if (!products.value) return []
  return products.value.map((p: any) => ({
    id: p.id,
    name: p.name,
    price: p.selling_price / 100, // Convert cents to dollars
    categoryId: p.category?.id || null,
    sku: p.sku
  }))
})

// Keep posStore.catalog updated with fetched products
watch(mappedProducts, (newVal) => {
  posStore.catalog = newVal
}, { immediate: true })

const filteredProducts = computed(() => {
  return mappedProducts.value.filter(p => 
    (selectedCategoryId.value === null || p.categoryId === selectedCategoryId.value) &&
    p.name.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})
</script>

<template>
  <div class="flex flex-col h-full w-full p-6">
    <div class="mb-6">
      <UiInput v-model="searchQuery" placeholder="Search products..." class="w-full">
        <template #prefix><Search class="h-4 w-4 text-slate-400" /></template>
      </UiInput>
    </div>
    
    <div class="flex gap-2 mb-6 overflow-x-auto pb-2">
      <UiButton v-for="cat in categoryList" :key="cat.id" 
        @click="selectedCategoryId = cat.id" 
        :variant="selectedCategoryId === cat.id ? 'primary' : 'outline'"
        size="sm">
        {{ cat.name }}
      </UiButton>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 overflow-y-auto pr-2">
      <div v-for="product in filteredProducts" :key="product.id" 
        @click="posStore.addToCart(product)"
        class="bg-white p-4 rounded-lg border border-slate-200 hover:border-primary-500 hover:shadow-md cursor-pointer transition-all">
        <div class="h-24 bg-slate-100 mb-3 rounded flex items-center justify-center text-slate-400">
          <span class="text-xs">Image</span>
        </div>
        <div class="font-medium text-slate-900 mb-1 truncate">{{ product.name }}</div>
        <div class="text-primary-600 font-semibold">${{ product.price }}</div>
      </div>
    </div>
  </div>
</template>
