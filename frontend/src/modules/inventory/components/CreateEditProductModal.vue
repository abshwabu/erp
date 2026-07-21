<script setup lang="ts">
import { ref, reactive, watch, computed } from 'vue'
import { TabGroup, TabList, Tab, TabPanels, TabPanel } from '@headlessui/vue'
import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { inventoryApi } from '@/api/inventory'
import { X, Plus, Trash2, Image as ImageIcon, Sparkles, Check, AlertCircle } from '@lucide/vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import CreateCategoryModal from './CreateCategoryModal.vue'
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
const isCreateCategoryModalOpen = ref(false)

const form = reactive({
  name: '',
  description: '',
  sku: '',
  type: 'stockable' as ProductType,
  status: 'active' as ProductStatus,
  categoryId: undefined as number | string | undefined,
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

// local attributes for variant generator
const optionTemplates = ref([
  { name: 'Size', values: '' },
  { name: 'Color', values: '' }
])

watch(() => props.product, (newProduct) => {
  if (newProduct) {
    const rawProd = newProduct as any
    const mappedVariants = Array.isArray(rawProd.variants)
      ? rawProd.variants.map((v: any) => ({
          id: v.id,
          sku: v.sku,
          name: v.name,
          costPrice: typeof v.cost_price === 'number' ? (v.cost_price / 100) : (typeof v.costPrice === 'number' ? v.costPrice : 0),
          sellingPrice: typeof v.selling_price === 'number' ? (v.selling_price / 100) : (typeof v.sellingPrice === 'number' ? v.sellingPrice : 0),
          attribute_value_ids: v.attribute_value_ids || [],
          is_active: v.is_active !== false,
          stock: v.stock || 0
        }))
      : []

    Object.assign(form, {
      ...newProduct,
      categoryId: newProduct.category?.id || newProduct.categoryId || undefined,
      costPrice: typeof rawProd.cost_price === 'number' ? (rawProd.cost_price / 100) : (typeof rawProd.costPrice === 'number' ? rawProd.costPrice : 0),
      sellingPrice: typeof rawProd.selling_price === 'number' ? (rawProd.selling_price / 100) : (typeof rawProd.sellingPrice === 'number' ? rawProd.sellingPrice : 0),
      minSellingPrice: typeof rawProd.min_selling_price === 'number' ? (rawProd.min_selling_price / 100) : (typeof rawProd.minSellingPrice === 'number' ? rawProd.minSellingPrice : 0),
      maxSellingPrice: typeof rawProd.max_selling_price === 'number' ? (rawProd.max_selling_price / 100) : (typeof rawProd.maxSellingPrice === 'number' ? rawProd.maxSellingPrice : 0),
      variants: mappedVariants,
      hasVariants: !!(rawProd.has_variants ?? newProduct.hasVariants),
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
      categoryId: props.categories?.[0]?.id || undefined,
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
    optionTemplates.value = [
      { name: 'Size', values: '' },
      { name: 'Color', values: '' }
    ]
  }
}, { immediate: true })

const validate = () => {
  Object.keys(errors).forEach(key => delete errors[key])
  
  if (!form.name) errors.name = 'Name is required'
  if (!form.sku && !form.autoGenerateSku) errors.sku = 'SKU is required'
  if (!form.categoryId) errors.categoryId = 'Category is required'
  if (form.sellingPrice <= 0) errors.sellingPrice = 'Selling price must be greater than 0'
  
  if (form.hasVariants && form.variants.length === 0) {
    errors.variants = 'Please add at least one variant configuration.'
  }
  
  return Object.keys(errors).length === 0
}

const addOptionTemplate = () => {
  optionTemplates.value.push({ name: '', values: '' })
}

const removeOptionTemplate = (idx: number) => {
  optionTemplates.value.splice(idx, 1)
}

const generateVariants = () => {
  // Filter options that have a name and non-empty values
  const activeOptions = optionTemplates.value.filter(o => o.name.trim() && o.values.trim())
  if (activeOptions.length === 0) return

  // cartesian helper
  const cartesian = (sets: string[][]): string[][] => {
    return sets.reduce<string[][]>((acc, set) => {
      return acc.flatMap(x => set.map(y => [...x, y]))
    }, [[]])
  }

  const optionSets = activeOptions.map(o => 
    o.values.split(',').map(v => v.trim()).filter(Boolean)
  )

  if (optionSets.some(s => s.length === 0)) return

  const combos = cartesian(optionSets)
  const baseSku = form.sku || form.name.substring(0, 3).toUpperCase().replace(/\s+/g, '')

  const generated = combos.map((combo) => {
    const nameSuffix = combo.join(' / ')
    const skuSuffix = combo.join('-').toUpperCase().replace(/[^A-Z0-9-]/g, '')
    const generatedSku = `${baseSku}-${skuSuffix}`
    
    return {
      sku: generatedSku,
      name: `${form.name} - ${nameSuffix}`,
      costPrice: form.costPrice,
      sellingPrice: form.sellingPrice,
      is_active: true,
      stock: 0,
      attribute_value_ids: combo
    }
  })

  // Merge or replace
  form.variants = [...form.variants, ...generated]
}

const addVariantManually = () => {
  const baseSku = form.sku || form.name.substring(0, 3).toUpperCase().replace(/\s+/g, '')
  const randomSuffix = Math.random().toString(36).substring(2, 6).toUpperCase()
  form.variants.push({
    sku: `${baseSku}-${randomSuffix}`,
    name: `${form.name} - Custom`,
    costPrice: form.costPrice,
    sellingPrice: form.sellingPrice,
    is_active: true,
    stock: 0,
    attribute_value_ids: []
  })
}

const removeVariant = (index: number) => {
  form.variants.splice(index, 1)
}

const mutation = useMutation({
  mutationFn: (data: any) => {
    // Convert decimal pricing to cents
    const payload = {
      ...data,
      sku: data.sku || undefined, // let backend autogenerate if empty
      cost_price: Math.round(data.costPrice * 100),
      selling_price: Math.round(data.sellingPrice * 100),
      min_selling_price: Math.round((data.minSellingPrice || 0) * 100),
      max_selling_price: Math.round((data.maxSellingPrice || 0) * 100),
      variants: data.hasVariants
        ? data.variants.map((v: any) => ({
            id: v.id || undefined,
            sku: v.sku,
            name: v.name,
            cost_price: Math.round((v.costPrice || 0) * 100),
            selling_price: Math.round((v.sellingPrice || 0) * 100),
            is_active: v.is_active !== false,
            stock: v.stock || 0,
            attribute_value_ids: v.attribute_value_ids || []
          }))
        : []
    }

    if (isEdit.value && props.product) {
      return inventoryApi.updateProduct(props.product.id, payload)
    }
    return inventoryApi.createProduct(payload)
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

function handleCategoryCreated(newCat: any) {
  if (newCat?.id) {
    form.categoryId = newCat.id
  }
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
        <div class="flex flex-col h-[620px]">
          <TabList class="flex space-x-1 rounded-xl bg-slate-100 p-1 mb-6 shrink-0">
            <Tab
              v-for="tab in tabs"
              :key="tab"
              v-slot="{ selected }"
              as="template"
            >
              <button
                type="button"
                :class="[
                  'w-full rounded-lg py-2 text-sm font-semibold transition-all duration-200 outline-none',
                  selected
                    ? 'bg-white text-blue-600 shadow-sm border border-slate-200'
                    : 'text-slate-500 hover:text-slate-800'
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
                  
                  <div class="space-y-1">
                    <div class="flex justify-between items-center">
                      <label class="block text-sm font-medium text-slate-700">Category <span class="text-red-500">*</span></label>
                      <button
                        type="button"
                        @click="isCreateCategoryModalOpen = true"
                        class="text-xs text-blue-600 hover:text-blue-700 font-semibold inline-flex items-center gap-0.5"
                      >
                        <Plus class="w-3 h-3" /> Add Category
                      </button>
                    </div>
                    <UiSelect
                      v-model="form.categoryId"
                      :options="Array.isArray(categories) ? categories.map(c => ({ label: c.name, value: c.id })) : []"
                      :error="errors.categoryId"
                      required
                    />
                  </div>
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
                    <label class="flex items-center text-xs text-slate-500 cursor-pointer">
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
              <TabPanel class="space-y-6 outline-none">
                <div class="flex items-center justify-between border-b pb-4">
                  <div class="flex items-center space-x-2">
                    <input type="checkbox" v-model="form.hasVariants" id="hasVariants" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-4 h-4 cursor-pointer" />
                    <label for="hasVariants" class="text-sm font-semibold text-slate-800 cursor-pointer">This product has variants</label>
                  </div>
                  <div v-if="form.hasVariants" class="flex items-center gap-2">
                    <UiButton type="button" size="sm" variant="ghost" @click="addVariantManually">
                      <Plus class="h-4 w-4 mr-1" /> Add Manually
                    </UiButton>
                  </div>
                </div>

                <div v-if="errors.variants" class="p-3 bg-red-50 text-red-700 text-xs font-semibold rounded-lg border border-red-200 flex items-center gap-1.5">
                  <AlertCircle class="w-4 h-4 shrink-0" />
                  {{ errors.variants }}
                </div>

                <div v-if="form.hasVariants" class="space-y-6">
                  <!-- Variant Configurator / Generator -->
                  <div class="p-4 border border-slate-200 rounded-xl bg-slate-50/50 space-y-4">
                    <div class="flex justify-between items-center">
                      <h4 class="text-xs font-bold text-slate-700 tracking-wide uppercase">Variant Option Generator</h4>
                      <UiButton type="button" size="sm" variant="outline" @click="addOptionTemplate">
                        <Plus class="w-3.5 h-3.5 mr-1" /> Add Option
                      </UiButton>
                    </div>

                    <div class="space-y-3">
                      <div v-for="(opt, idx) in optionTemplates" :key="idx" class="flex gap-4 items-end">
                        <div class="w-1/3">
                          <UiInput v-model="opt.name" label="Option Name" placeholder="e.g. Size" size="sm" />
                        </div>
                        <div class="flex-1">
                          <UiInput v-model="opt.values" label="Values (separated by comma)" placeholder="e.g. S, M, L" size="sm" />
                        </div>
                        <button type="button" @click="removeOptionTemplate(idx)" class="p-2 text-slate-400 hover:text-red-500">
                          <Trash2 class="w-4 h-4" />
                        </button>
                      </div>
                    </div>

                    <div class="flex justify-end pt-2">
                      <UiButton type="button" size="sm" variant="secondary" @click="generateVariants" class="shadow-sm">
                        <Sparkles class="w-4 h-4 mr-1.5 text-blue-500" /> Generate Combinations
                      </UiButton>
                    </div>
                  </div>

                  <!-- Variants Grid List -->
                  <div class="border border-slate-200 rounded-xl overflow-hidden shadow-sm bg-white">
                    <div class="bg-slate-50 border-b border-slate-200 px-4 py-2 flex items-center justify-between">
                      <span class="text-xs font-bold text-slate-700 tracking-wide uppercase">Variants Configuration ({{ form.variants.length }})</span>
                    </div>

                    <div class="divide-y divide-slate-100 max-h-[300px] overflow-y-auto custom-scrollbar">
                      <div v-for="(v, index) in form.variants" :key="index" class="p-4 flex flex-col md:flex-row gap-4 items-start md:items-end relative group hover:bg-slate-50/40">
                        <button type="button" @click="removeVariant(index)" class="absolute top-2 right-2 text-slate-400 hover:text-red-500">
                          <Trash2 class="h-4 w-4" />
                        </button>
                        
                        <div class="w-full md:w-1/3 space-y-1">
                          <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide">Variant Details</label>
                          <input type="text" v-model="v.name" class="block w-full border border-slate-300 rounded-md py-1.5 px-3 text-sm focus:ring-blue-500 focus:border-blue-500 bg-white" placeholder="Name" required />
                        </div>
                        <div class="w-full md:flex-1 grid grid-cols-2 lg:grid-cols-4 gap-3">
                          <div class="space-y-1">
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide">SKU</label>
                            <input type="text" v-model="v.sku" class="block w-full border border-slate-300 rounded-md py-1.5 px-3 text-sm focus:ring-blue-500 focus:border-blue-500 bg-white font-mono" placeholder="SKU" required />
                          </div>
                          <div class="space-y-1">
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide">Cost Price</label>
                            <input type="number" v-model.number="v.costPrice" step="0.01" class="block w-full border border-slate-300 rounded-md py-1.5 px-3 text-sm focus:ring-blue-500 focus:border-blue-500 bg-white" placeholder="0.00" />
                          </div>
                          <div class="space-y-1">
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide">Sale Price</label>
                            <input type="number" v-model.number="v.sellingPrice" step="0.01" class="block w-full border border-slate-300 rounded-md py-1.5 px-3 text-sm focus:ring-blue-500 focus:border-blue-500 bg-white" placeholder="0.00" required />
                          </div>
                          <div class="space-y-1">
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide">Initial Stock</label>
                            <input type="number" v-model.number="v.stock" class="block w-full border border-slate-300 rounded-md py-1.5 px-3 text-sm focus:ring-blue-500 focus:border-blue-500 bg-white" placeholder="0" />
                          </div>
                        </div>
                      </div>

                      <div v-if="form.variants.length === 0" class="text-center py-12 text-slate-400">
                        <Sparkles class="w-8 h-8 mx-auto mb-2 text-slate-300 animate-pulse" />
                        <span class="text-sm font-medium">No variants configured yet. Generate combinations or add manually!</span>
                      </div>
                    </div>
                  </div>
                </div>

                <div v-else class="bg-blue-50/50 p-4 border border-blue-100 rounded-xl flex items-start gap-3">
                  <Sparkles class="w-5 h-5 text-blue-500 shrink-0 mt-0.5" />
                  <div class="text-sm text-blue-800">
                    <p class="font-semibold">Looking to configure variants?</p>
                    <p class="text-xs text-blue-700/80 mt-1">Variants enable you to track inventory for different attributes of this item (e.g. Size, Color, Weight, Material) with independent SKUs, pricing overrides, and stock levels.</p>
                  </div>
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

  <!-- Create Category Quick Modal -->
  <CreateCategoryModal
    v-model="isCreateCategoryModalOpen"
    @created="handleCategoryCreated"
  />
</template>
