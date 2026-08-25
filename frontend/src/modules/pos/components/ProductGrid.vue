<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import { inventoryApi } from '@/api/inventory'
import { shopsApi } from '@/api/shops'
import { usePosStore } from '../stores/posStore'
import { Search, Package, Check, Layers } from '@lucide/vue'
import { resolveImageUrl } from '@/utils/format'
import SelectVariantModal from './SelectVariantModal.vue'

const posStore = usePosStore()
const searchQuery = ref('')
const selectedCategoryId = ref<string | null>(null)
const addedProductId = ref<string | null>(null)
const variantModalOpen = ref(false)
const activeModalProduct = ref<any>(null)

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
  staleTime: 1000 * 60 * 5,
  refetchOnWindowFocus: false,
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
  staleTime: 1000 * 60 * 5,
  refetchOnWindowFocus: false,
})

// Null-safe direct inventory products query (always fetched for complete catalog metadata)
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
  staleTime: 1000 * 60 * 5,
  refetchOnWindowFocus: false,
})

const isLoading = computed(() => isCategoriesLoading.value || isShopStockLoading.value || isProductsLoading.value)

// Safe Category List
const categoryList = computed(() => {
  const list = Array.isArray(categories.value) ? categories.value : []
  return [{ id: null, name: 'All Products' }, ...list]
})

// Declarative & Non-Flickering Catalog Computed Property
const catalogItems = computed(() => {
  const rawProducts = Array.isArray(products.value) ? products.value : []
  const rawStock = Array.isArray(shopStock.value) ? shopStock.value : []
  const hasActiveShop = Boolean(shopId.value || posStore.selectedShopId)

  // Map shop items by product ID
  const shopStockMap = new Map<string, any>()
  for (const s of rawStock) {
    if (s && (s.product_id || s.id)) {
      shopStockMap.set(String(s.product_id || s.id), s)
    }
  }

  // Combine items: start with products, fallback to shop stock if products not loaded yet
  const sourceList = rawProducts.length > 0 ? rawProducts : rawStock

  return sourceList.map((item: any) => {
    const pId = String(item.id || item.product_id || '')
    const shopItem = shopStockMap.get(pId) || (item.product_id ? item : null)
    const productItem = item.product_id ? rawProducts.find((p: any) => String(p.id) === pId) : item

    const hasVariants = Boolean(
      shopItem?.has_variants ||
      productItem?.has_variants ||
      (shopItem?.variants && shopItem.variants.length > 0) ||
      (productItem?.variants && productItem.variants.length > 0)
    )

    const rawVariants = (shopItem?.variants && shopItem.variants.length > 0)
      ? shopItem.variants
      : (productItem?.variants || [])

    // Map variants with store-specific stock
    const variants = rawVariants.map((v: any) => {
      const vStock = hasActiveShop
        ? Number(v.stock ?? v.available_quantity ?? 0)
        : Number(v.stock ?? v.available_quantity ?? 0)
      return {
        ...v,
        stock: vStock,
        available_quantity: vStock,
      }
    })

    const primaryImage =
      productItem?.primary_image_url ||
      shopItem?.primary_image_url ||
      productItem?.image ||
      shopItem?.image ||
      productItem?.images?.[0]?.url ||
      shopItem?.images?.[0]?.url ||
      null

    const price = typeof productItem?.selling_price === 'number'
      ? (productItem.selling_price / 100)
      : (typeof shopItem?.selling_price === 'number'
        ? (shopItem.selling_price / 100)
        : (Number(productItem?.price || shopItem?.price) || 0))

    const categoryId = productItem?.category_id || productItem?.category?.id || shopItem?.category_id || null

    // Stock for products with variants is the sum of in-store variant quantities
    let stock = 0
    if (hasVariants && variants.length > 0) {
      stock = variants.reduce((sum: number, v: any) => sum + (Number(v.stock) || 0), 0)
    } else if (hasActiveShop) {
      stock = shopItem ? Number(shopItem.available_quantity ?? shopItem.quantity_on_hand ?? 0) : 0
    } else {
      stock = typeof productItem?.available_quantity === 'number'
        ? productItem.available_quantity
        : (Number(shopItem?.available_quantity ?? productItem?.stock) || 0)
    }

    return {
      id: pId,
      productId: pId,
      name: productItem?.name || shopItem?.name || 'Unnamed Product',
      price,
      categoryId,
      sku: productItem?.sku || shopItem?.sku || `SKU-${pId}`,
      has_variants: hasVariants,
      variants,
      stock,
      locationId: shopItem?.location_id || locationId.value,
      image: primaryImage,
    }
  })
})

// Sync to Pinia store without blocking template reactivity
watch(catalogItems, (items) => {
  posStore.catalog = items
}, { immediate: true })

const filteredProducts = computed(() => {
  const list = catalogItems.value
  return list.filter(p => {
    if (!p) return false
    const matchesCategory = selectedCategoryId.value === null || String(p.categoryId) === String(selectedCategoryId.value)
    const nameStr = (p.name || '').toLowerCase()
    const skuStr = (p.sku || '').toLowerCase()
    const query = (searchQuery.value || '').toLowerCase().trim()
    const matchesSearch = !query || nameStr.includes(query) || skuStr.includes(query)
    return matchesCategory && matchesSearch
  })
})

function handleAddProduct(product: any) {
  if (!product) return

  // If product has variants, open variant selection dialog
  if (product.has_variants && Array.isArray(product.variants) && product.variants.length > 0) {
    activeModalProduct.value = product
    variantModalOpen.value = true
    return
  }

  if (product.stock <= 0) {
    alert(`${product.name} is currently out of stock at this store.`)
    return
  }

  const cartItem = (posStore.cart || []).find(item => String(item.id) === String(product.id))
  const currentInCart = cartItem ? cartItem.quantity : 0

  if (currentInCart >= product.stock) {
    alert(`Cannot add more ${product.name}. Only ${product.stock} unit(s) available in this store!`)
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

function handleVariantSelect(variant: any, quantity: number) {
  if (!activeModalProduct.value || !variant) return

  posStore.addToCart(activeModalProduct.value, quantity, variant)
  addedProductId.value = activeModalProduct.value.id
  setTimeout(() => {
    if (addedProductId.value === activeModalProduct.value?.id) {
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
        <h4 class="text-sm font-semibold text-slate-700 mb-1">No products found</h4>
        <p class="text-xs text-slate-400 max-w-sm">
          {{ searchQuery ? `No products match "${searchQuery}". Try a different keyword.` : 'Products will appear here once items are created in your inventory catalog.' }}
        </p>
      </div>

      <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
        <button
          v-for="product in filteredProducts"
          :key="product.id"
          type="button"
          @click="handleAddProduct(product)"
          class="relative text-left bg-white border rounded-xl p-3 transition-all active:scale-[0.98] group flex flex-col justify-between"
          :class="product.stock > 0
            ? 'border-slate-200 hover:border-blue-400 hover:shadow-md cursor-pointer'
            : 'border-slate-200/60 bg-slate-50/60 opacity-75 cursor-not-allowed'"
        >
          <div
            v-if="addedProductId === product.id"
            class="absolute inset-0 bg-emerald-500/10 backdrop-blur-[1px] rounded-xl flex items-center justify-center z-10 animate-fade-in"
          >
            <Check class="h-8 w-8 text-emerald-600" />
          </div>

          <div>
            <div class="h-16 w-full rounded-lg bg-slate-100 group-hover:bg-blue-50 transition-colors flex items-center justify-center mb-2 overflow-hidden border border-slate-100 relative">
              <img
                v-if="resolveImageUrl(product.image)"
                :src="resolveImageUrl(product.image)!"
                :alt="product.name"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform"
              />
              <Package v-else class="h-6 w-6 text-slate-400 group-hover:text-blue-600 transition-colors" />

              <span
                v-if="product.has_variants && product.variants?.length > 0"
                class="absolute top-1.5 right-1.5 bg-slate-900/80 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-md backdrop-blur-sm flex items-center gap-1 shadow-sm"
              >
                <Layers class="w-3 h-3" />
                {{ product.variants.length }}
              </span>
            </div>

            <div class="font-semibold text-xs sm:text-sm text-slate-900 line-clamp-2 min-h-[2.25rem] leading-snug">{{ product.name }}</div>
            <div class="text-[11px] text-slate-400 font-mono mt-1 truncate">{{ product.sku }}</div>
          </div>

          <div class="flex items-center justify-between mt-2.5 pt-2 border-t border-slate-100 w-full">
            <span class="font-bold text-xs sm:text-sm text-slate-900">${{ Number(product.price || 0).toFixed(2) }}</span>
            <span
              class="text-[11px] font-medium px-1.5 py-0.5 rounded"
              :class="product.stock > 0 ? 'text-slate-600 bg-slate-100' : 'text-amber-700 bg-amber-100 font-semibold'"
            >
              {{ product.stock > 0 ? `${product.stock} in store` : 'Out of stock' }}
            </span>
          </div>
        </button>
      </div>
    </div>

    <!-- Variant Selection Modal -->
    <SelectVariantModal
      :is-open="variantModalOpen"
      :product="activeModalProduct"
      @close="variantModalOpen = false"
      @select="handleVariantSelect"
    />
  </div>
</template>
