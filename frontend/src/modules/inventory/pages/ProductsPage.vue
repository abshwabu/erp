<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { inventoryApi } from '@/api/inventory'
import { 
  Plus, 
  Search, 
  MoreHorizontal, 
  Edit2, 
  Trash2, 
  Eye, 
  Download, 
  Upload,
  Package
} from '@lucide/vue'
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiTable from '@/components/ui/UiTable.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiPagination from '@/components/ui/UiPagination.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import CreateEditProductModal from '../components/CreateEditProductModal.vue'
import ImportModal from '../components/ImportModal.vue'
import type { Product, ProductFilters, ProductStatus, ProductType } from '@/types/inventory'

const queryClient = useQueryClient()
const page = ref(1)

const filters = reactive<ProductFilters>({
  search: '',
  categoryId: undefined,
  status: undefined,
  type: undefined
})

const { data, isLoading } = useQuery({
  queryKey: ['inventory', 'products', { page, ...filters }],
  queryFn: () => inventoryApi.getProducts(filters, page.value).then(res => res.data)
})

const { data: categories } = useQuery({
  queryKey: ['inventory', 'categories'],
  queryFn: () => inventoryApi.getCategories().then(res => res.data)
})

const isCreateModalOpen = ref(false)
const isImportModalOpen = ref(false)
const selectedProduct = ref<Product | null>(null)

const columns = [
  { key: 'image', label: '', align: 'left' as const },
  { key: 'name', label: 'Product', sortable: true },
  { key: 'sku', label: 'SKU', sortable: true },
  { key: 'category', label: 'Category' },
  { key: 'sellingPrice', label: 'Price', align: 'right' as const },
  { key: 'stock', label: 'Stock', align: 'center' as const },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: '', align: 'right' as const }
]

const openCreateModal = () => {
  selectedProduct.value = null
  isCreateModalOpen.value = true
}

const handleEdit = (product: Product) => {
  selectedProduct.value = product
  isCreateModalOpen.value = true
}

const deleteMutation = useMutation({
  mutationFn: (id: number) => inventoryApi.deleteProduct(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['inventory', 'products'] })
  }
})

const handleDelete = (id: number) => {
  if (confirm('Are you sure you want to delete this product?')) {
    deleteMutation.mutate(id)
  }
}

const getStatusVariant = (status: ProductStatus) => {
  switch (status) {
    case 'active': return 'success'
    case 'inactive': return 'warning'
    case 'archived': return 'danger'
    default: return 'default'
  }
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Products</h1>
        <p class="text-slate-500 text-sm">Manage your inventory products and services.</p>
      </div>
      <div class="flex items-center space-x-2">
        <UiButton variant="outline" size="sm" @click="isImportModalOpen = true">
          <Upload class="h-4 w-4 mr-2" /> Import
        </UiButton>
        <UiButton variant="outline" size="sm">
          <Download class="h-4 w-4 mr-2" /> Export
        </UiButton>
        <UiButton size="sm" @click="openCreateModal">
          <Plus class="h-4 w-4 mr-2" /> Add Product
        </UiButton>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm flex flex-wrap gap-4">
      <div class="flex-1 min-w-[240px]">
        <UiInput v-model="filters.search" placeholder="Search products..." class="w-full">
          <template #prefix>
            <Search class="h-4 w-4 text-slate-400" />
          </template>
        </UiInput>
      </div>
      <div class="w-48">
        <UiSelect
          v-model="filters.categoryId"
          :options="[
            { label: 'All Categories', value: '' },
            ...(Array.isArray(categories) ? categories.map((c: any) => ({ label: c.name, value: c.id })) : [])
          ]"
        />
      </div>
      <div class="w-40">
        <UiSelect
          v-model="filters.status"
          :options="[
            { label: 'All Status', value: '' },
            { label: 'Active', value: 'active' },
            { label: 'Inactive', value: 'inactive' },
            { label: 'Archived', value: 'archived' }
          ]"
        />
      </div>
      <div class="w-40">
        <UiSelect
          v-model="filters.type"
          :options="[
            { label: 'All Types', value: '' },
            { label: 'Stockable', value: 'stockable' },
            { label: 'Consumable', value: 'consumable' },
            { label: 'Service', value: 'service' }
          ]"
        />
      </div>
    </div>

    <!-- Table -->
    <UiTable
      :columns="columns"
      :data="data?.data || []"
      :loading="isLoading"
      empty-title="No products found"
      empty-description="Try adjusting your filters or create a new product."
    >
      <template #cell(image)="{ item }">
        <div class="h-10 w-10 rounded bg-slate-100 flex items-center justify-center overflow-hidden">
          <img v-if="item.images?.[0]" :src="item.images[0].url" :alt="item.name" class="h-full w-full object-cover" />
          <Package v-else :size="20" class="h-5 w-5 text-slate-400" />
        </div>
      </template>
      
      <template #cell(name)="{ item }">
        <div>
          <div class="font-medium text-slate-900">{{ item.name }}</div>
          <div class="text-xs text-slate-500">{{ item.type }}</div>
        </div>
      </template>

      <template #cell(category)="{ item }">
        <span class="text-slate-600">{{ item.category?.name || 'Uncategorized' }}</span>
      </template>

      <template #cell(sellingPrice)="{ value }">
        <span class="font-medium">${{ value?.toFixed(2) || '0.00' }}</span>
      </template>

      <template #cell(stock)="{ item }">
        <div class="text-center">
          <span :class="item.stock <= 5 ? 'text-red-600 font-bold' : 'text-slate-600'">
            {{ item.stock || 0 }}
          </span>
        </div>
      </template>

      <template #cell(status)="{ value }">
        <UiBadge :variant="getStatusVariant(value)">{{ value }}</UiBadge>
      </template>

      <template #cell(actions)="{ item }">
        <Menu as="div" class="relative inline-block text-left">
          <MenuButton class="p-2 hover:bg-slate-100 rounded-full transition-colors">
            <MoreHorizontal :size="16" class="h-4 w-4 text-slate-500" />
          </MenuButton>
          <transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
          >
            <MenuItems class="absolute right-0 mt-2 w-48 origin-top-right divide-y divide-slate-100 rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-10">
              <div class="px-1 py-1">
                <MenuItem v-slot="{ active }">
                  <button :class="[active ? 'bg-primary-50 text-primary-700' : 'text-slate-700', 'group flex w-full items-center rounded-md px-2 py-2 text-sm']">
                    <Eye :size="16" class="mr-2 h-4 w-4 text-slate-400" /> View Details
                  </button>
                </MenuItem>
                <MenuItem v-slot="{ active }">
                  <button @click="handleEdit(item)" :class="[active ? 'bg-primary-50 text-primary-700' : 'text-slate-700', 'group flex w-full items-center rounded-md px-2 py-2 text-sm']">
                    <Edit2 :size="16" class="mr-2 h-4 w-4 text-slate-400" /> Edit Product
                  </button>
                </MenuItem>
              </div>
              <div class="px-1 py-1">
                <MenuItem v-slot="{ active }">
                  <button @click="handleDelete(item.id)" :class="[active ? 'bg-red-50 text-red-700' : 'text-slate-700', 'group flex w-full items-center rounded-md px-2 py-2 text-sm']">
                    <Trash2 :size="16" class="mr-2 h-4 w-4 text-slate-400 group-hover:text-red-500" /> Delete
                  </button>
                </MenuItem>
              </div>
            </MenuItems>
          </transition>
        </Menu>
      </template>
    </UiTable>

    <!-- Pagination -->
    <div v-if="data?.meta" class="flex justify-between items-center">
      <p class="text-sm text-slate-500">
        Showing {{ (data.meta.currentPage - 1) * data.meta.perPage + 1 }} to {{ Math.min(data.meta.currentPage * data.meta.perPage, data.meta.total) }} of {{ data.meta.total }} products
      </p>
      <UiPagination
        :current-page="page"
        @update:current-page="page = $event"
        :total-pages="data.meta.lastPage"
        :has-next-page="data.meta.currentPage < data.meta.lastPage"
        :has-prev-page="data.meta.currentPage > 1"
      />
    </div>

    <!-- Create/Edit Modal -->
    <CreateEditProductModal
      v-model="isCreateModalOpen"
      :product="selectedProduct"
      :categories="categories || []"
    />

    <ImportModal
      v-model="isImportModalOpen"
    />
  </div>
</template>
