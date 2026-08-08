<script setup lang="ts">
import { ref, reactive, watch } from 'vue'
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
} from '@lucide/vue'
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiTable from '@/components/ui/UiTable.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiPagination from '@/components/ui/UiPagination.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import CreateEditProductModal from '../components/CreateEditProductModal.vue'
import CreateCategoryModal from '../components/CreateCategoryModal.vue'
import ImportModal from '../components/ImportModal.vue'
import { formatCurrency } from '@/utils/format'
import type { Product, ProductFilters, ProductStatus } from '@/types/inventory'

const queryClient = useQueryClient()
const page = ref(1)

const filters = reactive<ProductFilters>({
  search: '',
  category_id: undefined,
  status: undefined,
  type: undefined,
})

watch(
  () => [filters.search, filters.category_id, filters.status, filters.type],
  () => {
    page.value = 1
  }
)

const { data, isLoading } = useQuery({
  queryKey: ['inventory', 'products', page, filters],
  queryFn: () => inventoryApi.getProducts(filters, page.value).then((res) => res.data),
})

const { data: categories } = useQuery({
  queryKey: ['inventory', 'categories'],
  queryFn: () => inventoryApi.getCategories().then((res) => res.data),
})

const isCreateModalOpen = ref(false)
const isImportModalOpen = ref(false)
const isCreateCategoryModalOpen = ref(false)
const selectedProduct = ref<Product | null>(null)

const columns = [
  { key: 'image', label: '', align: 'left' as const },
  { key: 'name', label: 'Product', sortable: true },
  { key: 'sku', label: 'SKU', sortable: true },
  { key: 'category', label: 'Category' },
  { key: 'selling_price', label: 'Price', align: 'right' as const },
  { key: 'available_quantity', label: 'Available', align: 'center' as const },
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

const deleteMutation = useMutation({
  mutationFn: (id: string) => inventoryApi.deleteProduct(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['inventory', 'products'] })
    queryClient.invalidateQueries({ queryKey: ['inventory', 'stock-summary'] })
  },
})

const handleDelete = (id: string) => {
  if (confirm('Are you sure you want to delete this product?')) {
    deleteMutation.mutate(id)
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
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Products</h1>
        <p class="text-slate-500 text-sm">Manage your catalog and stocked items.</p>
      </div>
      <div class="flex items-center space-x-2">
        <UiButton variant="outline" size="sm" @click="isCreateCategoryModalOpen = true">
          <Tag class="h-4 w-4 mr-2" /> Add Category
        </UiButton>
        <UiButton variant="outline" size="sm" @click="isImportModalOpen = true">
          <Upload class="h-4 w-4 mr-2" /> Import
        </UiButton>
        <UiButton size="sm" @click="openCreateModal">
          <Plus class="h-4 w-4 mr-2" /> Add Product
        </UiButton>
      </div>
    </div>

    <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm flex flex-wrap gap-4">
      <div class="flex-1 min-w-[240px]">
        <UiInput v-model="filters.search" placeholder="Search by name or SKU..." class="w-full">
          <template #prefix>
            <Search class="h-4 w-4 text-slate-400" />
          </template>
        </UiInput>
      </div>
      <div class="w-48">
        <UiSelect
          v-model="filters.category_id"
          :options="[
            { label: 'All Categories', value: '' },
            ...(Array.isArray(categories) ? categories.map((c) => ({ label: c.name, value: c.id })) : []),
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
            { label: 'Archived', value: 'archived' },
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
            { label: 'Service', value: 'service' },
          ]"
        />
      </div>
    </div>

    <UiTable
      :columns="columns"
      :data="data?.data || []"
      :loading="isLoading"
      empty-title="No products yet"
      empty-description="Create your first product to start tracking inventory."
    >
      <template #cell(image)="{ item }">
        <div class="h-10 w-10 rounded bg-slate-100 flex items-center justify-center overflow-hidden">
          <img
            v-if="item.primary_image_url"
            :src="item.primary_image_url"
            :alt="item.name"
            class="h-full w-full object-cover"
          />
          <Package v-else :size="20" class="h-5 w-5 text-slate-400" />
        </div>
      </template>

      <template #cell(name)="{ item }">
        <div>
          <div class="font-medium text-slate-900">{{ item.name }}</div>
          <div class="text-xs text-slate-500 capitalize">{{ item.type }}</div>
        </div>
      </template>

      <template #cell(sku)="{ value }">
        <span class="font-mono text-xs text-slate-600">{{ value }}</span>
      </template>

      <template #cell(category)="{ item }">
        <span class="text-slate-600">{{ item.category?.name || 'Uncategorized' }}</span>
      </template>

      <template #cell(selling_price)="{ value }">
        <span class="font-medium">{{ money(value) }}</span>
      </template>

      <template #cell(available_quantity)="{ item }">
        <span :class="item.available_quantity <= 0 ? 'text-red-600 font-semibold' : 'text-slate-700'">
          {{ item.available_quantity ?? 0 }}
        </span>
      </template>

      <template #cell(status)="{ value }">
        <UiBadge :variant="getStatusVariant(value)">{{ value }}</UiBadge>
      </template>

      <template #cell(actions)="{ item }">
        <Menu as="div" class="relative inline-block text-left">
          <MenuButton class="p-2 hover:bg-slate-100 rounded-full transition-colors">
            <MoreHorizontal :size="16" class="h-4 w-4 text-slate-500" />
          </MenuButton>
          <MenuItems
            class="absolute right-0 mt-2 w-48 origin-top-right divide-y divide-slate-100 rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-10"
          >
            <div class="px-1 py-1">
              <MenuItem v-slot="{ active }">
                <button
                  type="button"
                  @click="handleEdit(item)"
                  :class="[
                    active ? 'bg-primary-50 text-primary-700' : 'text-slate-700',
                    'group flex w-full items-center rounded-md px-2 py-2 text-sm',
                  ]"
                >
                  <Edit2 :size="16" class="mr-2 h-4 w-4 text-slate-400" /> Edit Product
                </button>
              </MenuItem>
            </div>
            <div class="px-1 py-1">
              <MenuItem v-slot="{ active }">
                <button
                  type="button"
                  @click="handleDelete(item.id)"
                  :class="[
                    active ? 'bg-red-50 text-red-700' : 'text-slate-700',
                    'group flex w-full items-center rounded-md px-2 py-2 text-sm',
                  ]"
                >
                  <Trash2 :size="16" class="mr-2 h-4 w-4 text-slate-400 group-hover:text-red-500" /> Delete
                </button>
              </MenuItem>
            </div>
          </MenuItems>
        </Menu>
      </template>
    </UiTable>

    <div v-if="data?.meta" class="flex justify-between items-center">
      <p class="text-sm text-slate-500">
        Showing {{ data.meta.from ?? 0 }}–{{ data.meta.to ?? 0 }} of {{ data.meta.total }} products
      </p>
      <UiPagination
        :current-page="page"
        @update:current-page="page = $event"
        :total-pages="data.meta.last_page"
        :has-next-page="data.meta.current_page < data.meta.last_page"
        :has-prev-page="data.meta.current_page > 1"
      />
    </div>

    <CreateEditProductModal
      v-model="isCreateModalOpen"
      :product="selectedProduct"
      :categories="categories || []"
    />

    <CreateCategoryModal v-model="isCreateCategoryModalOpen" />

    <ImportModal v-model="isImportModalOpen" />
  </div>
</template>
