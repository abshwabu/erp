<script setup lang="ts">
import { ref, reactive, watch, computed } from 'vue'
import { TabGroup, TabList, Tab, TabPanels, TabPanel } from '@headlessui/vue'
import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { inventoryApi } from '@/api/inventory'
import { X, Plus, Trash2, Image as ImageIcon, Loader2 } from '@lucide/vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import type { Product, ProductCategory, ProductType, ProductStatus } from '@/types/inventory'

interface Props {
  modelValue: boolean
  product?: Product | null
  categories: ProductCategory[]
}

const props = defineProps<Props>()
const emit = defineEmits(['update:modelValue', 'saved'])

const queryClient = useQueryClient()
const isEdit = computed(() => !!props.product)

const form = reactive({
  name: '',
  description: '',
  sku: '',
  type: 'stockable' as ProductType,
  status: 'active' as ProductStatus,
  categoryId: undefined as number | undefined,
  costPrice: 0,
  sellingPrice: 0,
  minSellingPrice: 0,
  maxSellingPrice: 0,
  hasVariants: false,
  variants: [] as any[],
  images: [] as any[],
  tags: [] as string[],
  autoGenerateSku: true
})

const errors = reactive<Record<string, string>>({})

watch(() => props.product, (newProduct) => {
  if (newProduct) {
    Object.assign(form, {
      ...newProduct,
      autoGenerateSku: false
    })
  } else {
    // Reset form
    Object.assign(form, {
      name: '',
      description: '',
      sku: '',
      type: 'stockable',
      status: 'active',
      categoryId: undefined,
      costPrice: 0,
      sellingPrice: 0,
      minSellingPrice: 0,
      maxSellingPrice: 0,
      hasVariants: false,
      variants: [],
      images: [],
      tags: [],
      autoGenerateSku: true
    })
  }
}, { immediate: true })

const validate = () => {
  Object.keys(errors).forEach(key => delete errors[key])
  
  if (!form.name) errors.name = 'Name is required'
  if (!form.sku && !form.autoGenerateSku) errors.sku = 'SKU is required'
  if (!form.categoryId) errors.categoryId = 'Category is required'
  if (form.sellingPrice <= 0) errors.sellingPrice = 'Selling price must be greater than 0'
  
  return Object.keys(errors).length === 0
}

const mutation = useMutation({
  mutationFn: (data: any) => {
    if (isEdit.value && props.product) {
      return inventoryApi.updateProduct(props.product.id, data)
    }
    return inventoryApi.createProduct(data)
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['inventory', 'products'] })
    emit('saved')
    emit('update:modelValue', false)
  }
})

const handleSubmit = () => {
  if (!validate()) return
  mutation.mutate(form)
}

const tabs = ['General', 'Pricing', 'Variants', 'Images']

const addVariant = () => {
  form.variants.push({
    sku: '',
    attributes: {},
    price: form.sellingPrice,
    stock: 0
  })
}

const removeVariant = (index: number) => {
  form.variants.splice(index, 1)
}
</script>

<template>
  <UiModal
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
    :title="isEdit ? 'Edit Product' : 'Create New Product'"
    size="2xl"
  >
    <form id="productForm" @submit.prevent="handleSubmit">
      <TabGroup>
        <div class="flex flex-col h-[600px]">
          <TabList class="flex space-x-1 rounded-xl bg-slate-100 p-1 mb-6">
            <Tab
              v-for="tab in tabs"
              :key="tab"
              v-slot="{ selected }"
              as="template"
            >
              <button
                :class="[
                  'w-full rounded-lg py-2.5 text-sm font-medium leading-5 transition-all',
                  'ring-white ring-opacity-60 ring-offset-2 ring-offset-primary-400 focus:outline-none focus:ring-2',
                  selected
                    ? 'bg-white text-primary-700 shadow'
                    : 'text-slate-600 hover:bg-white/[0.12] hover:text-slate-800'
                ]"
              >
                {{ tab }}
              </button>
            </Tab>
          </TabList>

          <div class="flex-1 overflow-y-auto px-1 custom-scrollbar">
            <TabPanels>
              <!-- General Tab -->
              <TabPanel class="space-y-4 outline-none">
                <div class="grid grid-cols-2 gap-4">
                  <UiInput
                    v-model="form.name"
                    label="Product Name"
                    placeholder="e.g. Wireless Mouse"
                    :error="errors.name"
                    required
                  />
                  <UiSelect
                    v-model="form.categoryId"
                    label="Category"
                    :options="Array.isArray(categories) ? categories.map(c => ({ label: c.name, value: c.id })) : []"
                    :error="errors.categoryId"
                    required
                  />
                </div>

                <div class="grid grid-cols-2 gap-4">
                  <div class="space-y-2">
                    <UiInput
                      v-model="form.sku"
                      label="SKU"
                      placeholder="Auto-generated if empty"
                      :disabled="form.autoGenerateSku"
                      :error="errors.sku"
                    />
                    <label class="flex items-center text-xs text-slate-500">
                      <input type="checkbox" v-model="form.autoGenerateSku" class="mr-2 rounded border-slate-300 text-primary-600 focus:ring-primary-500" />
                      Auto-generate SKU
                    </label>
                  </div>
                  <UiSelect
                    v-model="form.type"
                    label="Product Type"
                    :options="[
                      { label: 'Stockable', value: 'stockable' },
                      { label: 'Consumable', value: 'consumable' },
                      { label: 'Service', value: 'service' }
                    ]"
                  />
                </div>

                <div class="space-y-1">
                  <label class="block text-sm font-medium text-slate-700">Description</label>
                  <textarea
                    v-model="form.description"
                    rows="4"
                    class="block w-full rounded-md border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                    placeholder="Product details..."
                  ></textarea>
                </div>

                <UiSelect
                  v-model="form.status"
                  label="Status"
                  :options="[
                    { label: 'Active', value: 'active' },
                    { label: 'Inactive', value: 'inactive' },
                    { label: 'Archived', value: 'archived' }
                  ]"
                />
              </TabPanel>

              <!-- Pricing Tab -->
              <TabPanel class="space-y-4 outline-none">
                <div class="grid grid-cols-2 gap-4">
                  <UiInput
                    v-model.number="form.costPrice"
                    type="number"
                    step="0.01"
                    label="Cost Price"
                    placeholder="0.00"
                  />
                  <UiInput
                    v-model.number="form.sellingPrice"
                    type="number"
                    step="0.01"
                    label="Selling Price"
                    placeholder="0.00"
                    :error="errors.sellingPrice"
                    required
                  />
                </div>
                <div class="grid grid-cols-2 gap-4">
                  <UiInput
                    v-model.number="form.minSellingPrice"
                    type="number"
                    step="0.01"
                    label="Min Selling Price"
                  />
                  <UiInput
                    v-model.number="form.maxSellingPrice"
                    type="number"
                    step="0.01"
                    label="Max Selling Price"
                  />
                </div>
              </TabPanel>

              <!-- Variants Tab -->
              <TabPanel class="space-y-4 outline-none">
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-2">
                    <input type="checkbox" v-model="form.hasVariants" id="hasVariants" class="rounded border-slate-300 text-primary-600 focus:ring-primary-500" />
                    <label for="hasVariants" class="text-sm font-medium text-slate-700">This product has variants</label>
                  </div>
                  <UiButton v-if="form.hasVariants" type="button" size="sm" variant="outline" @click="addVariant">
                    <Plus class="h-4 w-4 mr-1" /> Add Variant
                  </UiButton>
                </div>

                <div v-if="form.hasVariants" class="space-y-3">
                  <div v-for="(variant, index) in form.variants" :key="index" class="p-4 border rounded-lg bg-slate-50 relative group">
                    <button @click="removeVariant(index)" class="absolute top-2 right-2 text-slate-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity">
                      <Trash2 class="h-4 w-4" />
                    </button>
                    <div class="grid grid-cols-3 gap-4">
                      <UiInput v-model="variant.sku" label="Variant SKU" size="sm" />
                      <UiInput v-model.number="variant.price" type="number" label="Price Override" size="sm" />
                      <UiInput v-model.number="variant.stock" type="number" label="Initial Stock" size="sm" />
                    </div>
                  </div>
                  <div v-if="form.variants.length === 0" class="text-center py-8 text-slate-500 border border-dashed rounded-lg">
                    No variants added yet.
                  </div>
                </div>
                <div v-else class="bg-blue-50 p-4 rounded-lg text-sm text-blue-700">
                  Variants allow you to offer different versions of the same product, such as different sizes or colors.
                </div>
              </TabPanel>

              <!-- Images Tab -->
              <TabPanel class="space-y-4 outline-none">
                <div class="grid grid-cols-4 gap-4">
                  <div 
                    v-for="(image, index) in form.images" 
                    :key="index"
                    class="aspect-square rounded-lg border-2 border-slate-200 overflow-hidden relative group"
                  >
                    <img :src="image.url" class="w-full h-full object-cover" />
                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                      <button type="button" class="p-1.5 bg-red-600 text-white rounded-full hover:bg-red-700">
                        <Trash2 class="h-4 w-4" />
                      </button>
                    </div>
                  </div>
                  <button 
                    type="button"
                    class="aspect-square rounded-lg border-2 border-dashed border-slate-300 flex flex-col items-center justify-center text-slate-400 hover:border-primary-400 hover:text-primary-500 transition-colors"
                  >
                    <ImageIcon class="h-8 w-8 mb-1" />
                    <span class="text-xs font-medium">Add Image</span>
                  </button>
                </div>
              </TabPanel>
            </TabPanels>
          </div>
        </div>
      </TabGroup>
    </form>
    <template #footer>
      <UiButton variant="ghost" @click="emit('update:modelValue', false)" class="mr-2">Cancel</UiButton>
      <UiButton type="submit" form="productForm" :loading="mutation.isPending.value">
        {{ isEdit ? 'Update Product' : 'Create Product' }}
      </UiButton>
    </template>
  </UiModal>
</template>
