<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import { inventoryApi } from '@/api/inventory'
import { shopsApi } from '@/api/shops'
import { usePosStore } from '../stores/posStore'
import { Search, Package, Check, Tag, Filter, Layers, RefreshCw, AlertCircle } from '@lucide/vue'

const posStore = usePosStore()
const searchQuery = ref('')
const selectedCategoryId = ref<string | null>(null)
const addedProductId = ref<string | null>(null)

const shopId = computed(() => posStore.selectedShopId)
const locationId = computed(() => posStore.selectedShop()?.stock_location_id || posStore.session?.terminal?.location_id || null)

// Null-safe categories query
const { data: categories, isLoading: isCategoriesLoading } = useQuery<any[]>({
  queryKey: ['inventory', 'categories'],
  queryFn: async () => {
    try {
      const res: any = await inventoryApi.getCategories()
      if (Array.isArray(res.data)) return res.data
      if (Array.isArray(res.data?.data)) return res.data.data
      return []
    } catch (e) {
      console.warn('POS: unable to fetch categories, defaulting to empty list', e)
      return []
    }
  },
  initialData: [],
})

// Null-safe shop stock query
const { data: shopStock, isLoading: isShopStockLoading } = useQuery<any[]>({
  queryKey: computed(() => ['shops', shopId.value, 'stock-pos']),
  queryFn: async () => {
    if (!shopId.value) return []
    try {
      const res: any = await shopsApi.getStock(shopId.value)
      if (Array.isArray(res.data)) return res.data
      if (Array.isArray(res.data?.data)) return res.data.data
      return []
    } catch (e) {
      console.warn('POS: unable to fetch shop stock, defaulting to empty list', e)
      return []
    }
  },
  enabled: computed(() => !!shopId.value),
  initialData: [],
})

// Null-safe direct inventory products query
const { data: products, isLoading: isProductsLoading } = useQuery<any[]>({
  queryKey: ['inventory', 'products-pos'],
  queryFn: async () => {
    try {
      const res: any = await inventoryApi.getProducts({ status: 'active' }, 1)
      if (Array.isArray(res.data?.data)) return res.data.data
      if (Array.isArray(res.data)) return res.data
      return []
    } catch (e) {
      console.warn('POS: unable to fetch catalog products, defaulting to empty list', e)
      return []
    }
  },
  enabled: computed(() => !shopId.value),
  initialData: [],
})

const isLoading = computed(() => isCategoriesLoading.value || isShopStockLoading.value || isProductsLoading.value)

// Safe Category List
const categoryList = computed(() => {
  const list = Array.isArray(categories.value) ? categories.value : []
  return [{ id: null, name: 'All Products' }, ...list]
})

// Defensive watch to map catalog
watch([shopStock, products, shopId], () => {
  if (shopId.value && Array.isArray(shopStock.value) && shopStock.value.length > 0) {
    posStore.catalog = shopStock.value
      .filter((p: any) => p && (p.available_quantity ?? p.stock ?? 0) > 0)
      .map((p: any) => ({
        id: p.product_id || p.id,
        name: p.name || 'Unnamed Product',
        price: typeof p.selling_price === 'number' ? p.selling_price / 100 : (Number(p.price) || 0),
        categoryId: p.category_id || p.categoryId || null,
        sku: p.sku || `SKU-${p.product_id || p.id}`,
        stock: p.available_quantity ?? p.stock ?? 0,
        locationId: p.location_id || locationId.value,
      }))
    return
  }

  if (Array.isArray(products.value)) {
    posStore.catalog = products.value.map((p: any) => {
      const rawStock = (typeof p?.available_quantity === 'number') ? p.available_quantity : (p?.stock ?? 0)
      return {
        id: p?.id || '',
        name: p?.name || 'Unnamed Product',
        price: typeof p?.selling_price === 'number' ? (p.selling_price / 100) : (Number(p?.price) || 0),
        categoryId: p?.category_id || p?.category?.id || null,
        sku: p?.sku || `SKU-${p?.id}`,
        stock: rawStock
      }
    })
  } else {
    posStore.catalog = []
  }
}, { immediate: true })

const filteredProducts = computed(() => {
  const catalog = Array.isArray(posStore.catalog) ? posStore.catalog : []
  return catalog.filter(p => {
    if (!p) return false
    const hasStock = typeof p.stock === 'number' ? p.stock > 0 : true
    const matchesCategory = selectedCategoryId.value === null || p.categoryId === selectedCategoryId.value
    const nameStr = (p.name || '').toLowerCase()
    const skuStr = (p.sku || '').toLowerCase()
    const query = (searchQuery.value || '').toLowerCase()
    const matchesSearch = !query || nameStr.includes(query) || skuStr.includes(query)
    return hasStock && matchesCategory && matchesSearch
  })
})

function handleAddProduct(product: any) {
  if (!product || product.stock <= 0) return

  const cartItem = (posStore.cart || []).find(item => String(item.id) === String(product.id))
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
    <!-- Top Search & Filter Bar -->
    <div class="flex flex-col sm:flex-row gap-3 mb-4 shrink-0">
      <div class="relative flex-1">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search products by name or SKU..."
          class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-400"
        />
      </div>
    </div>

    <!-- Category Pill Selector -->
    <div class="flex gap-2 overflow-x-auto pb-3 mb-2 shrink-0 no-scrollbar">
      <button
        v-for="cat in categoryList"
        :key="String(cat.id)"
        @click="selectedCategoryId = cat.id"
        class="px-3 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap border transition-colors shadow-sm"
        :class="selectedCategoryId === cat.id
          ? 'bg-blue-600 text-white border-blue-600'
          : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300'"
      >
        {{ cat.name }}
      </button>
    </div>

    <!-- Products Grid & Safe Empty/Loading States -->
    <div class="flex-1 overflow-y-auto">
      <div v-if="filteredProducts.length === 0" class="h-full min-h-[260px] flex flex-col items-center justify-center text-slate-400 py-12 px-4 text-center">
        <div class="p-3 bg-slate-100 rounded-full mb-3">
          <Package class="h-8 w-8 text-slate-400 opacity-60" />
        </div>
        <h4 class="text-sm font-semibold text-slate-700 mb-1">No products available in this view</h4>
        <p class="text-xs text-slate-400 max-w-sm">
          {{ searchQuery ? `No products match "${searchQuery}". Try a different keyword.` : 'Products will appear here once items are stocked in your inventory catalog.' }}
        </p>
      </div>

      <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
        <button
          v-for="product in filteredProducts"
          :key="product.id"
          type="button"
          @click="handleAddProduct(product)"
          class="relative text-left bg-white border border-slate-200 rounded-xl p-3 hover:border-blue-400 hover:shadow-md transition-all active:scale-[0.98] group"
        >
          <div
            v-if="addedProductId === product.id"
            class="absolute inset-0 bg-emerald-500/10 backdrop-blur-[1px] rounded-xl flex items-center justify-center z-10 animate-fade-in"
          >
            <Check class="h-8 w-8 text-emerald-600" />
          </div>
          <div class="h-10 w-10 rounded-lg bg-slate-100 group-hover:bg-blue-50 transition-colors flex items-center justify-center mb-2">
            <Package class="h-5 w-5 text-slate-400 group-hover:text-blue-600 transition-colors" />
          </div>
          <div class="font-semibold text-xs sm:text-sm text-slate-900 line-clamp-2 min-h-[2.25rem] leading-snug">{{ product.name }}</div>
          <div class="text-[11px] text-slate-400 font-mono mt-1 truncate">{{ product.sku }}</div>
          <div class="flex items-center justify-between mt-2.5 pt-2 border-t border-slate-100">
            <span class="font-bold text-xs sm:text-sm text-slate-900">${{ Number(product.price || 0).toFixed(2) }}</span>
            <span class="text-[11px] font-medium text-slate-500">{{ product.stock }} left</span>
          </div>
        </button>
      </div>
    </div>
  </div>
</template>
