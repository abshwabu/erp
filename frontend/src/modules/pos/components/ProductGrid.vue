<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import { inventoryApi } from '@/api/inventory'
import { shopsApi } from '@/api/shops'
import { usePosStore } from '../stores/posStore'
import { Search, Package, Check, Tag, Filter, Layers } from '@lucide/vue'

const posStore = usePosStore()
const searchQuery = ref('')
const selectedCategoryId = ref<string | null>(null)
const addedProductId = ref<string | null>(null)

const shopId = computed(() => posStore.selectedShopId)
const locationId = computed(() => posStore.selectedShop()?.stock_location_id || posStore.session?.terminal?.location_id || null)

const { data: categories } = useQuery({
  queryKey: ['inventory', 'categories'],
  queryFn: () => inventoryApi.getCategories().then(res => res.data)
})

const { data: shopStock } = useQuery({
  queryKey: computed(() => ['shops', shopId.value, 'stock-pos']),
  queryFn: () => shopsApi.getStock(shopId.value!).then(res => res.data),
  enabled: computed(() => !!shopId.value)
})

const { data: products } = useQuery({
  queryKey: ['inventory', 'products-pos'],
  queryFn: () => inventoryApi.getProducts({ status: 'active' }, 1).then(res => res.data.data),
  enabled: computed(() => !shopId.value)
})

const categoryList = computed(() => {
  const list = Array.isArray(categories.value) ? categories.value : []
  return [{ id: null, name: 'All Products' }, ...list]
})

watch([shopStock, products, shopId], () => {
  if (shopId.value && Array.isArray(shopStock.value)) {
    posStore.catalog = shopStock.value
      .filter((p: any) => (p.available_quantity ?? 0) > 0)
      .map((p: any) => ({
        id: p.product_id,
        name: p.name,
        price: typeof p.selling_price === 'number' ? p.selling_price / 100 : 0,
        categoryId: null,
        sku: p.sku || `SKU-${p.product_id}`,
        stock: p.available_quantity ?? 0,
        locationId: p.location_id || locationId.value,
      }))
    return
  }

  if (Array.isArray(products.value)) {
    posStore.catalog = products.value.map((p: any) => {
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

    <div class="flex gap-2 overflow-x-auto pb-3 mb-2 shrink-0">
      <button
        v-for="cat in categoryList"
        :key="String(cat.id)"
        @click="selectedCategoryId = cat.id"
        class="px-3 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap border transition-colors"
        :class="selectedCategoryId === cat.id
          ? 'bg-blue-600 text-white border-blue-600'
          : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300'"
      >
        {{ cat.name }}
      </button>
    </div>

    <div class="flex-1 overflow-y-auto">
      <div v-if="filteredProducts.length === 0" class="h-full flex flex-col items-center justify-center text-slate-400 py-16">
        <Package class="h-10 w-10 mb-3 opacity-40" />
        <p class="text-sm font-medium">No products with stock at this shop</p>
      </div>

      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
        <button
          v-for="product in filteredProducts"
          :key="product.id"
          type="button"
          @click="handleAddProduct(product)"
          class="relative text-left bg-white border border-slate-200 rounded-xl p-3 hover:border-blue-400 hover:shadow-md transition-all active:scale-[0.98]"
        >
          <div
            v-if="addedProductId === product.id"
            class="absolute inset-0 bg-emerald-500/10 rounded-xl flex items-center justify-center"
          >
            <Check class="h-8 w-8 text-emerald-600" />
          </div>
          <div class="h-10 w-10 rounded-lg bg-slate-100 flex items-center justify-center mb-2">
            <Package class="h-5 w-5 text-slate-400" />
          </div>
          <div class="font-semibold text-sm text-slate-900 line-clamp-2 min-h-[2.5rem]">{{ product.name }}</div>
          <div class="text-[11px] text-slate-400 font-mono mt-1">{{ product.sku }}</div>
          <div class="flex items-center justify-between mt-2">
            <span class="font-bold text-slate-900">${{ Number(product.price).toFixed(2) }}</span>
            <span class="text-[11px] font-medium text-slate-500">{{ product.stock }} left</span>
          </div>
        </button>
      </div>
    </div>
  </div>
</template>
