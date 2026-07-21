<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import { inventoryApi } from '@/api/inventory'
import { usePosStore } from '../stores/posStore'
import { Search, Package, Check, Tag, Filter, Layers } from '@lucide/vue'

const posStore = usePosStore()
const searchQuery = ref('')
const selectedCategoryId = ref<number | null>(null)
const addedProductId = ref<number | null>(null)

const { data: categories } = useQuery({
  queryKey: ['inventory', 'categories'],
  queryFn: () => inventoryApi.getCategories().then(res => res.data)
})

const { data: products } = useQuery({
  queryKey: ['inventory', 'products-pos'],
  queryFn: () => inventoryApi.getProducts({ status: 'active' }, 1).then(res => res.data.data)
})

const categoryList = computed(() => {
  const list = Array.isArray(categories.value) ? categories.value : []
  return [{ id: null, name: 'All Products' }, ...list]
})

// Initialize posStore.catalog when products are fetched
watch(products, (newVal) => {
  if (Array.isArray(newVal)) {
    posStore.catalog = newVal.map((p: any) => {
      // Use real available_quantity from API; default to 0 if none
      const rawStock = (typeof p.available_quantity === 'number') ? p.available_quantity : 0
      return {
        id: p.id,
        name: p.name,
        price: typeof p.selling_price === 'number' ? (p.selling_price / 100) : (p.price || 0),
        categoryId: p.category_id || p.category?.id || null,
        sku: p.sku || `SKU-${p.id}`,
        stock: rawStock
      }
    })
  }
}, { immediate: true })

// Only show items with stock > 0
const filteredProducts = computed(() => {
  return posStore.catalog.filter(p => {
    const hasStock = typeof p.stock === 'number' ? p.stock > 0 : true
    const matchesCategory = selectedCategoryId.value === null || p.categoryId === selectedCategoryId.value
    const matchesSearch = p.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                          p.sku.toLowerCase().includes(searchQuery.value.toLowerCase())
    return hasStock && matchesCategory && matchesSearch
  })
})

function handleAddProduct(product: any) {
  if (product.stock <= 0) return

  // Check how many of this item are already in cart
  const cartItem = posStore.cart.find(item => String(item.id) === String(product.id))
  const currentInCart = cartItem ? cartItem.quantity : 0

  if (currentInCart >= product.stock) {
    alert(`Cannot add more ${product.name}. Only ${product.stock} unit(s) available in stock!`)
    return
  }

  posStore.addToCart(product)
  addedProductId.value = product.id
  setTimeout(() => {
    if (addedProductId.value === product.id) {
      addedProductId.value = null
    }
  }, 600)
}
</script>

<template>
  <div class="flex flex-col h-full w-full p-4 md:p-6 overflow-hidden">
    <!-- Header Bar: Search & Quick Info -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 shrink-0">
      <div class="relative flex-1">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
          <Search class="h-4 w-4" />
        </div>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search products by name or SKU..."
          class="w-full pl-9 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-sm"
        />
        <button
          v-if="searchQuery"
          @click="searchQuery = ''"
          class="absolute inset-y-0 right-0 pr-3 flex items-center text-xs font-semibold text-slate-400 hover:text-slate-600"
        >
          Clear
        </button>
      </div>

      <div class="text-xs text-slate-500 font-medium hidden sm:block shrink-0">
        Available: <span class="font-bold text-emerald-600">{{ filteredProducts.length }}</span> items (Stock &gt; 0)
      </div>
    </div>

    <!-- Category Chips Bar -->
    <div class="flex items-center gap-2 mb-4 overflow-x-auto pb-1 shrink-0 scrollbar-none">
      <span class="text-slate-400 text-xs font-medium shrink-0 flex items-center gap-1">
        <Filter class="w-3 h-3" />
      </span>
      <button
        v-for="cat in categoryList"
        :key="cat.id ?? 'all'"
        @click="selectedCategoryId = cat.id"
        class="px-3 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-all border shrink-0"
        :class="[
          selectedCategoryId === cat.id
            ? 'bg-blue-600 text-white border-blue-600 shadow-sm'
            : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:border-slate-300'
        ]"
      >
        {{ cat.name }}
      </button>
    </div>

    <!-- Products Grid -->
    <div class="flex-1 overflow-y-auto pr-1">
      <div
        v-if="filteredProducts.length > 0"
        class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 md:gap-4"
      >
        <div
          v-for="product in filteredProducts"
          :key="product.id"
          @click="handleAddProduct(product)"
          class="group bg-white p-3.5 rounded-2xl border border-slate-200/90 hover:border-blue-500 hover:shadow-md cursor-pointer transition-all duration-150 flex flex-col justify-between relative overflow-hidden"
        >
          <!-- Added Feedback Overlay -->
          <div
            v-if="addedProductId === product.id"
            class="absolute inset-0 bg-blue-600/95 text-white flex items-center justify-center gap-1.5 font-bold text-xs z-10 animate-fade-in"
          >
            <Check class="w-4 h-4" /> Added to Order!
          </div>

          <div>
            <!-- Product Placeholder Image Icon & Stock Badge -->
            <div class="h-24 bg-gradient-to-br from-slate-50 to-slate-100 mb-3 rounded-xl flex flex-col items-center justify-center border border-slate-100 group-hover:scale-98 transition-transform relative">
              <Package class="w-8 h-8 text-slate-300 group-hover:text-blue-500 transition-colors" />
              
              <!-- Stock Badge -->
              <span
                class="absolute bottom-2 right-2 text-[10px] font-bold px-2 py-0.5 rounded-md border backdrop-blur-md shadow-2xs"
                :class="[
                  product.stock <= 5
                    ? 'bg-amber-500/10 text-amber-700 border-amber-300'
                    : 'bg-emerald-500/10 text-emerald-700 border-emerald-300'
                ]"
              >
                {{ product.stock }} in stock
              </span>
            </div>

            <div class="font-bold text-xs sm:text-sm text-slate-900 line-clamp-2 leading-tight mb-1">
              {{ product.name }}
            </div>
            <div class="text-[11px] font-mono text-slate-400 flex items-center gap-1 mb-2">
              <Tag class="w-3 h-3 text-slate-300" />
              {{ product.sku }}
            </div>
          </div>

          <div class="flex items-center justify-between pt-2 border-t border-slate-100">
            <span class="text-sm font-bold text-blue-600">
              ${{ product.price.toFixed(2) }}
            </span>
            <span class="text-[10px] font-semibold text-slate-500 bg-slate-100 group-hover:bg-blue-50 group-hover:text-blue-600 px-2.5 py-1 rounded-lg transition-colors">
              + Add
            </span>
          </div>
        </div>
      </div>

      <!-- Empty Filter State -->
      <div v-else class="h-64 flex flex-col items-center justify-center text-slate-400">
        <Package class="w-12 h-12 stroke-[1.5] text-slate-300 mb-2" />
        <p class="text-sm font-semibold text-slate-600">No in-stock products found</p>
        <p class="text-xs text-slate-400">Items with 0 stock are automatically hidden from POS.</p>
      </div>
    </div>
  </div>
</template>
