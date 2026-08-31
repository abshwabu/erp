<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { inventoryApi } from '@/api/inventory'
import {
  Plus,
  Search,
  MoreHorizontal,
  Edit2,
  Trash2,
  Upload,
  Package,
  Tag,
  AlertTriangle,
  Boxes,
  CheckCircle2,
  DollarSign,
  TrendingUp,
  LayoutGrid,
  List,
  Filter,
  X,
  RefreshCw,
  ExternalLink,
  ChevronRight,
} from '@lucide/vue'
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiTable from '@/components/ui/UiTable.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiPagination from '@/components/ui/UiPagination.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiModal from '@/components/ui/UiModal.vue'
import CreateEditProductModal from '../components/CreateEditProductModal.vue'
import CreateCategoryModal from '../components/CreateCategoryModal.vue'
import ImportModal from '../components/ImportModal.vue'
import { formatCurrency, resolveImageUrl } from '@/utils/format'
import { useToast } from '@/composables/useToast'
import type { Product, ProductCategory, ProductFilters, ProductStatus } from '@/types/inventory'

const queryClient = useQueryClient()
const toast = useToast()
const page = ref(1)
const viewMode = ref<'table' | 'grid'>('table')

const filters = reactive<ProductFilters>({
  search: '',
  category_id: undefined,
  status: undefined,
  type: undefined,
})

const debouncedSearch = ref('')
let debounceTimer: any = null

watch(
  () => filters.search,
  (val) => {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => {
      debouncedSearch.value = val || ''
      page.value = 1
    }, 200)
  }
)

watch(
  () => [filters.category_id, filters.status, filters.type],
  () => {
    page.value = 1
  }
)

// Safe products query with reactive computed queryKey
const { data, isLoading, refetch, isFetching } = useQuery({
  queryKey: computed(() => [
    'inventory',
    'products',
    page.value,
    debouncedSearch.value,
    filters.category_id || '',
    filters.status || '',
    filters.type || '',
  ]),
  queryFn: async () => {
    try {
      const res = await inventoryApi.getProducts(
        {
          search: debouncedSearch.value || undefined,
          category_id: filters.category_id || undefined,
          status: filters.status || undefined,
          type: filters.type || undefined,
        },
        page.value
      )
      return res.data
    } catch (e) {
      console.warn('Failed to load products', e)
      return { data: [], meta: { current_page: 1, last_page: 1, total: 0, from: 0, to: 0, per_page: 15 } }
    }
  },
})

// Safe categories query
const { data: categoriesRaw } = useQuery<any>({
  queryKey: ['inventory', 'categories'],
  queryFn: async () => {
    try {
      const res: any = await inventoryApi.getCategories()
      if (Array.isArray(res.data)) return res.data
      if (Array.isArray(res.data?.data)) return res.data.data
      return []
    } catch {
      return []
    }
  },
  initialData: [],
})

const categoriesList = computed<ProductCategory[]>(() => {
  const raw = categoriesRaw.value
  if (Array.isArray(raw)) return raw
  if (Array.isArray((raw as any)?.data)) return (raw as any).data
  return []
})

const categoryFilterOptions = computed(() => {
  return [
    { label: 'All Categories', value: '' },
    ...categoriesList.value.map((c: any) => ({ label: c.name, value: c.id })),
  ]
})

const isCreateModalOpen = ref(false)
const isImportModalOpen = ref(false)
const isCreateCategoryModalOpen = ref(false)
const selectedProduct = ref<Product | null>(null)

// Summary KPI calculations
const productList = computed(() => (data.value?.data || []) as Product[])
const totalProductsCount = computed(() => data.value?.meta?.total ?? productList.value.length)

const lowStockCount = computed(() => {
  return productList.value.filter((p: any) => {
    const stock = Number(p.available_quantity ?? p.stock ?? 0)
    return stock > 0 && stock <= 5
  }).length
})

const outOfStockCount = computed(() => {
  return productList.value.filter((p: any) => {
    const stock = Number(p.available_quantity ?? p.stock ?? 0)
    return stock <= 0
  }).length
})

const totalInventoryValuation = computed(() => {
  return productList.value.reduce((acc: number, p: any) => {
    const stock = Number(p.available_quantity ?? p.stock ?? 0)
    const priceCents = typeof p.selling_price === 'number' ? p.selling_price : 0
    return acc + (stock * (priceCents / 100))
  }, 0)
})

const columns = [
  { key: 'image', label: '', align: 'left' as const },
  { key: 'name', label: 'Product & SKU', sortable: true },
  { key: 'category', label: 'Category' },
  { key: 'selling_price', label: 'Selling Price', align: 'right' as const },
  { key: 'cost_price', label: 'Cost Price', align: 'right' as const },
  { key: 'available_quantity', label: 'Stock Level', align: 'center' as const },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: '', align: 'right' as const },
]

const openCreateModal = () => {
  selectedProduct.value = null
  isCreateModalOpen.value = true
}

const handleEdit = (product: Product) => {
  selectedProduct.value = product
  isCreateModalOpen.value = true
}

const isDeleteModalOpen = ref(false)
const productToDelete = ref<Product | null>(null)

const deleteMutation = useMutation({
  mutationFn: (id: string) => inventoryApi.deleteProduct(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['inventory', 'products'] })
    queryClient.invalidateQueries({ queryKey: ['inventory', 'stock-summary'] })
    isDeleteModalOpen.value = false
    productToDelete.value = null
    toast.success('Product deleted successfully')
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || 'Failed to delete product')
  },
})

const openDeleteConfirm = (product: Product) => {
  productToDelete.value = product
  isDeleteModalOpen.value = true
}

const confirmDelete = () => {
  if (productToDelete.value) {
    deleteMutation.mutate(productToDelete.value.id)
  }
}

const getStatusVariant = (status: ProductStatus) => {
  switch (status) {
    case 'active':
      return 'success'
    case 'inactive':
      return 'warning'
    case 'archived':
      return 'danger'
    default:
      return 'default'
  }
}

const money = (cents: number) => formatCurrency((cents || 0) / 100)

function resetFilters() {
  filters.search = ''
  debouncedSearch.value = ''
  filters.category_id = undefined
  filters.status = undefined
  filters.type = undefined
  page.value = 1
}

const hasActiveFilters = computed(() => {
  return (
    !!filters.search ||
    !!debouncedSearch.value ||
    (filters.category_id !== undefined && filters.category_id !== '') ||
    (filters.status !== undefined && filters.status !== '') ||
    (filters.type !== undefined && filters.type !== '')
  )
})
</script>

<template>
  <div class="space-y-6 pb-12 font-sans max-w-7xl mx-auto px-4 sm:px-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="flex items-center space-x-2">
          <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Products Catalog</h1>
          <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-100">
            {{ totalProductsCount }} Items
          </span>
        </div>
        <p class="text-slate-500 text-xs sm:text-sm mt-1">
          Manage product SKUs, stock levels, sales pricing, and category classifications.
        </p>
      </div>

      <!-- Action Buttons -->
      <div class="flex flex-wrap items-center gap-2">
        <button
          type="button"
          @click="isCreateCategoryModalOpen = true"
          class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs sm:text-sm font-semibold shadow-sm transition-all"
        >
          <Tag class="h-4 w-4 text-slate-400" />
          <span>Categories</span>
        </button>

        <button
          type="button"
          @click="isImportModalOpen = true"
          class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs sm:text-sm font-semibold shadow-sm transition-all"
        >
          <Upload class="h-4 w-4 text-slate-400" />
          <span>Import CSV</span>
        </button>

        <button
          type="button"
          @click="openCreateModal"
          class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs sm:text-sm font-bold shadow-md shadow-blue-600/30 transition-all transform hover:-translate-y-0.5 active:translate-y-0"
        >
          <Plus class="h-4 w-4" />
          <span>New Product</span>
        </button>
      </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
      <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
        <div>
          <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Catalog</p>
          <h3 class="text-xl sm:text-2xl font-black text-slate-900 mt-1">{{ totalProductsCount }}</h3>
        </div>
        <div class="p-2.5 bg-blue-50 text-blue-600 rounded-xl">
          <Package class="w-5 h-5" />
        </div>
      </div>

      <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
        <div>
          <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Low Stock Alert</p>
          <h3 class="text-xl sm:text-2xl font-black text-amber-600 mt-1">{{ lowStockCount }}</h3>
        </div>
        <div class="p-2.5 bg-amber-50 text-amber-600 rounded-xl">
          <AlertTriangle class="w-5 h-5" />
        </div>
      </div>

      <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
        <div>
          <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Out of Stock</p>
          <h3 class="text-xl sm:text-2xl font-black text-red-600 mt-1">{{ outOfStockCount }}</h3>
        </div>
        <div class="p-2.5 bg-red-50 text-red-600 rounded-xl">
          <Boxes class="w-5 h-5" />
        </div>
      </div>

      <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
        <div>
          <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Inventory Valuation</p>
          <h3 class="text-xl sm:text-2xl font-black text-emerald-600 mt-1">${{ totalInventoryValuation.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}</h3>
        </div>
        <div class="p-2.5 bg-emerald-50 text-emerald-600 rounded-xl">
          <DollarSign class="w-5 h-5" />
        </div>
      </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm space-y-3">
      <div class="flex flex-col md:flex-row items-center gap-3">
        <!-- Search Input -->
        <div class="relative flex-1 w-full">
          <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
          <input
            v-model="filters.search"
            type="text"
            placeholder="Search by product name, SKU, or description..."
            class="w-full pl-10 pr-10 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 transition-colors"
          />
          <button
            v-if="filters.search"
            @click="filters.search = ''"
            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
          >
            <X class="w-4 h-4" />
          </button>
        </div>

        <!-- Filters Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 w-full md:w-auto">
          <!-- Category Select -->
          <div class="w-full sm:w-44">
            <UiSelect
              v-model="filters.category_id"
              :options="categoryFilterOptions"
            />
          </div>

          <!-- Status Select -->
          <div class="w-full sm:w-36">
            <UiSelect
              v-model="filters.status"
              :options="[
                { label: 'All Status', value: '' },
                { label: 'Active', value: 'active' },
                { label: 'Inactive', value: 'inactive' },
                { label: 'Archived', value: 'archived' },
              ]"
            />
          </div>

          <!-- Type Select -->
          <div class="w-full sm:w-36">
            <UiSelect
              v-model="filters.type"
              :options="[
                { label: 'All Types', value: '' },
                { label: 'Stockable', value: 'stockable' },
                { label: 'Consumable', value: 'consumable' },
                { label: 'Service', value: 'service' },
              ]"
            />
          </div>
        </div>

        <!-- View Mode & Reset -->
        <div class="flex items-center space-x-2 self-end md:self-center">
          <button
            v-if="hasActiveFilters"
            @click="resetFilters"
            class="text-xs text-blue-600 hover:text-blue-700 font-semibold px-2.5 py-1.5 rounded-lg hover:bg-blue-50 transition-colors"
          >
            Reset
          </button>

          <div class="flex items-center bg-slate-100 p-1 rounded-xl border border-slate-200">
            <button
              @click="viewMode = 'table'"
              :class="['p-1.5 rounded-lg transition-colors', viewMode === 'table' ? 'bg-white shadow-sm text-blue-600 font-bold' : 'text-slate-400 hover:text-slate-700']"
              title="Table View"
            >
              <List class="w-4 h-4" />
            </button>
            <button
              @click="viewMode = 'grid'"
              :class="['p-1.5 rounded-lg transition-colors', viewMode === 'grid' ? 'bg-white shadow-sm text-blue-600 font-bold' : 'text-slate-400 hover:text-slate-700']"
              title="Grid Card View"
            >
              <LayoutGrid class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Table View Mode -->
    <div v-if="viewMode === 'table'" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
      <UiTable
        :columns="columns"
        :data="productList"
        :loading="isLoading"
        empty-title="No products found"
        empty-description="Create your first catalog product or adjust your active search filters."
      >
        <template #cell(image)="{ item }">
          <div class="h-11 w-11 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center overflow-hidden shrink-0">
            <img
              v-if="resolveImageUrl(item.primary_image_url || item.images?.[0]?.url || item.images?.[0]?.path)"
              :src="resolveImageUrl(item.primary_image_url || item.images?.[0]?.url || item.images?.[0]?.path)!"
              :alt="item.name"
              class="h-full w-full object-cover"
            />
            <Package v-else class="h-5 w-5 text-slate-400" />
          </div>
        </template>

        <template #cell(name)="{ item }">
          <div class="py-1">
            <div class="font-bold text-slate-900 hover:text-blue-600 cursor-pointer transition-colors" @click="handleEdit(item)">
              {{ item.name }}
            </div>
            <div class="flex items-center space-x-2 mt-0.5">
              <span class="font-mono text-[11px] text-slate-500 font-medium">{{ item.sku }}</span>
              <span class="text-[10px] text-slate-300">&bull;</span>
              <span class="text-[11px] capitalize text-slate-400 font-medium">{{ item.type }}</span>
            </div>
          </div>
        </template>

        <template #cell(category)="{ item }">
          <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-slate-100 text-slate-700">
            {{ item.category?.name || 'General' }}
          </span>
        </template>

        <template #cell(selling_price)="{ value }">
          <span class="font-black text-slate-900 text-sm">{{ money(value) }}</span>
        </template>

        <template #cell(cost_price)="{ value }">
          <span class="font-medium text-slate-500 text-xs">{{ money(value) }}</span>
        </template>

        <template #cell(available_quantity)="{ item }">
          <div class="inline-flex items-center">
            <span
              :class="[
                'px-2.5 py-1 rounded-full text-xs font-bold',
                (item.available_quantity ?? item.stock ?? 0) <= 0
                  ? 'bg-red-50 text-red-600 border border-red-200'
                  : (item.available_quantity ?? item.stock ?? 0) <= 5
                  ? 'bg-amber-50 text-amber-600 border border-amber-200'
                  : 'bg-emerald-50 text-emerald-600 border border-emerald-200'
              ]"
            >
              {{ item.available_quantity ?? item.stock ?? 0 }} Units
            </span>
          </div>
        </template>

        <template #cell(status)="{ value }">
          <UiBadge :variant="getStatusVariant(value)">{{ value }}</UiBadge>
        </template>

        <template #cell(actions)="{ item }">
          <div class="flex items-center justify-end space-x-1">
            <button
              type="button"
              @click.stop="handleEdit(item)"
              title="Edit Product"
              class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-all"
            >
              <Edit2 class="h-4 w-4" />
            </button>
            <button
              type="button"
              @click.stop="openDeleteConfirm(item)"
              title="Delete Product"
              class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all cursor-pointer"
            >
              <Trash2 class="h-4 w-4" />
            </button>
          </div>
        </template>
      </UiTable>
    </div>

    <!-- Grid Card View Mode -->
    <div v-else>
      <div v-if="isLoading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div v-for="i in 8" :key="i" class="bg-white rounded-2xl p-4 border border-slate-200 animate-pulse space-y-3">
          <div class="h-32 bg-slate-100 rounded-xl"></div>
          <div class="h-4 bg-slate-200 rounded w-3/4"></div>
          <div class="h-3 bg-slate-100 rounded w-1/2"></div>
        </div>
      </div>

      <div v-else-if="productList.length === 0" class="bg-white rounded-2xl border border-slate-200/80 p-12 text-center">
        <Package class="h-12 w-12 text-slate-300 mx-auto mb-3" />
        <h3 class="text-base font-bold text-slate-800">No products found</h3>
        <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">Create a product or modify your search criteria.</p>
      </div>

      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <div
          v-for="product in productList"
          :key="product.id"
          class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm hover:shadow-md transition-all flex flex-col justify-between group"
        >
          <div>
            <div class="relative h-40 rounded-xl bg-slate-50 border border-slate-100 overflow-hidden flex items-center justify-center mb-3">
              <img
                v-if="resolveImageUrl(product.primary_image_url || (product as any).images?.[0]?.url || (product as any).images?.[0]?.path)"
                :src="resolveImageUrl(product.primary_image_url || (product as any).images?.[0]?.url || (product as any).images?.[0]?.path)!"
                :alt="product.name"
                class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-300"
              />
              <Package v-else class="h-10 w-10 text-slate-300" />

              <span
                :class="[
                  'absolute top-2.5 right-2.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider',
                  (product.available_quantity ?? (product as any).stock ?? 0) <= 0
                    ? 'bg-red-500 text-white'
                    : (product.available_quantity ?? (product as any).stock ?? 0) <= 5
                    ? 'bg-amber-500 text-white'
                    : 'bg-emerald-500 text-white'
                ]"
              >
                {{ (product.available_quantity ?? (product as any).stock ?? 0) }} In Stock
              </span>
            </div>

            <div class="flex items-start justify-between gap-2">
              <div>
                <span class="text-[10px] font-bold text-blue-600 uppercase tracking-wider">
                  {{ product.category?.name || 'General' }}
                </span>
                <h3 class="font-bold text-sm text-slate-900 line-clamp-1 group-hover:text-blue-600 transition-colors">
                  {{ product.name }}
                </h3>
                <p class="font-mono text-xs text-slate-400 mt-0.5">{{ product.sku }}</p>
              </div>

              <UiBadge :variant="getStatusVariant(product.status)">{{ product.status }}</UiBadge>
            </div>
          </div>

          <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
            <div>
              <span class="text-[10px] text-slate-400 block font-medium">Selling Price</span>
              <span class="font-black text-slate-900 text-base">{{ money(product.selling_price) }}</span>
            </div>

            <div class="flex items-center space-x-1">
              <button
                @click="handleEdit(product)"
                class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                title="Edit Product"
              >
                <Edit2 class="w-4 h-4" />
              </button>
              <button
                type="button"
                @click.stop="openDeleteConfirm(product)"
                class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors cursor-pointer"
                title="Delete Product"
              >
                <Trash2 class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination Toolbar -->
    <div v-if="data?.meta && data.meta.total > 0" class="flex flex-col sm:flex-row justify-between items-center gap-3 pt-2">
      <p class="text-xs font-medium text-slate-500">
        Showing <span class="font-bold text-slate-700">{{ data.meta.from ?? 0 }}</span>–<span class="font-bold text-slate-700">{{ data.meta.to ?? 0 }}</span> of <span class="font-bold text-slate-700">{{ data.meta.total }}</span> products
      </p>
      <UiPagination
        :current-page="page"
        @update:current-page="page = $event"
        :total-pages="data.meta.last_page || 1"
        :has-next-page="data.meta.current_page < data.meta.last_page"
        :has-prev-page="data.meta.current_page > 1"
      />
    </div>

    <!-- Modals -->
    <CreateEditProductModal
      v-model="isCreateModalOpen"
      :product="selectedProduct"
      :categories="categoriesList"
      @saved="refetch"
    />

    <CreateCategoryModal
      v-model="isCreateCategoryModalOpen"
      @saved="() => queryClient.invalidateQueries({ queryKey: ['inventory', 'categories'] })"
    />

    <ImportModal
      v-model="isImportModalOpen"
      @imported="refetch"
    />

    <!-- Custom Delete Confirmation Modal -->
    <UiModal v-model="isDeleteModalOpen" title="Delete Product" size="sm">
      <div v-if="productToDelete" class="space-y-4">
        <div class="flex items-start gap-3.5 p-3.5 bg-red-50/80 border border-red-200 rounded-2xl">
          <div class="p-2 bg-red-100 text-red-600 rounded-xl shrink-0 mt-0.5">
            <Trash2 class="w-5 h-5" />
          </div>
          <div class="space-y-1">
            <h4 class="text-sm font-bold text-red-950">Confirm Product Deletion</h4>
            <p class="text-xs text-red-800 leading-relaxed">
              Are you sure you want to permanently delete <strong class="font-bold">{{ productToDelete.name }}</strong> (SKU: <span class="font-mono font-semibold">{{ productToDelete.sku }}</span>)?
            </p>
          </div>
        </div>

        <p class="text-xs text-slate-500 leading-relaxed">
          This action will remove the product and its inventory specifications from the active catalog.
        </p>

        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
          <UiButton variant="outline" size="sm" type="button" @click="isDeleteModalOpen = false">
            Cancel
          </UiButton>
          <UiButton
            variant="danger"
            size="sm"
            :loading="deleteMutation.isPending.value"
            @click="confirmDelete"
          >
            <Trash2 class="w-3.5 h-3.5 mr-1" />
            Delete Product
          </UiButton>
        </div>
      </div>
    </UiModal>
  </div>
</template>
