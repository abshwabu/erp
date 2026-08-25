<script setup lang="ts">
import { ref, reactive, watch, computed } from 'vue'
import { TabGroup, TabList, Tab, TabPanels, TabPanel } from '@headlessui/vue'
import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { inventoryApi } from '@/api/inventory'
import {
  X,
  Plus,
  Trash2,
  Image as ImageIcon,
  Sparkles,
  Check,
  AlertCircle,
  Package,
  DollarSign,
  Layers,
  BarChart2,
  Sliders,
  Tag,
  Boxes,
  ShieldCheck,
} from '@lucide/vue'
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
  categoryId: undefined as string | undefined,
  costPrice: 0,
  sellingPrice: 0,
  minSellingPrice: 0,
  maxSellingPrice: 0,
  initialStock: 0,
  minStock: 5,
  barcode: '',
  trackSerialNumbers: false,
  trackLots: false,
  hasVariants: false,
  variants: [] as any[],
  images: [] as any[],
  tags: [] as string[],
  autoGenerateSku: true,
})

const errors = reactive<Record<string, string>>({})

// local attributes for variant generator
const optionTemplates = ref([
  { name: 'Size', values: 'S, M, L, XL' },
  { name: 'Color', values: 'Black, White, Navy' },
])

// Real-time financial calculations
const profitPerUnit = computed(() => {
  const sell = Number(form.sellingPrice) || 0
  const cost = Number(form.costPrice) || 0
  return Math.max(0, sell - cost)
})

const marginPct = computed(() => {
  const sell = Number(form.sellingPrice) || 0
  const cost = Number(form.costPrice) || 0
  if (sell <= 0) return 0
  return Math.round(((sell - cost) / sell) * 100 * 10) / 10
})

const markupPct = computed(() => {
  const sell = Number(form.sellingPrice) || 0
  const cost = Number(form.costPrice) || 0
  if (cost <= 0) return 0
  return Math.round(((sell - cost) / cost) * 100 * 10) / 10
})

watch(
  () => props.product,
  (newProduct) => {
    if (newProduct) {
      const rawProd = newProduct as any
      const mappedVariants = Array.isArray(rawProd.variants)
        ? rawProd.variants.map((v: any) => ({
            id: v.id,
            sku: v.sku,
            name: v.name,
            costPrice:
              typeof v.cost_price === 'number'
                ? v.cost_price / 100
                : typeof v.costPrice === 'number'
                ? v.costPrice
                : 0,
            sellingPrice:
              typeof v.selling_price === 'number'
                ? v.selling_price / 100
                : typeof v.sellingPrice === 'number'
                ? v.sellingPrice
                : 0,
            attribute_value_ids: v.attribute_value_ids || [],
            is_active: v.is_active !== false,
            stock: v.stock || 0,
          }))
        : []

      Object.assign(form, {
        name: newProduct.name || '',
        description: newProduct.description || '',
        sku: newProduct.sku || '',
        type: newProduct.type || 'stockable',
        status: newProduct.status || 'active',
        categoryId: newProduct.category?.id || (newProduct as any).category_id || undefined,
        costPrice: typeof rawProd.cost_price === 'number' ? rawProd.cost_price / 100 : 0,
        sellingPrice: typeof rawProd.selling_price === 'number' ? rawProd.selling_price / 100 : 0,
        minSellingPrice: typeof rawProd.min_selling_price === 'number' ? rawProd.min_selling_price / 100 : 0,
        maxSellingPrice: typeof rawProd.max_selling_price === 'number' ? rawProd.max_selling_price / 100 : 0,
        initialStock: 0,
        minStock: typeof rawProd.min_stock === 'number' ? rawProd.min_stock : 5,
        barcode: rawProd.barcode || '',
        trackSerialNumbers: !!rawProd.track_serial_numbers,
        trackLots: !!rawProd.track_lots,
        variants: mappedVariants,
        hasVariants: !!(rawProd.has_variants ?? newProduct.has_variants),
        images: [],
        tags: [],
        autoGenerateSku: false,
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
        initialStock: 0,
        minStock: 5,
        barcode: '',
        trackSerialNumbers: false,
        trackLots: false,
        hasVariants: false,
        variants: [],
        images: [],
        tags: [],
        autoGenerateSku: true,
      })
      optionTemplates.value = [
        { name: 'Size', values: 'S, M, L, XL' },
        { name: 'Color', values: 'Black, White, Navy' },
      ]
    }
  },
  { immediate: true }
)

const validate = () => {
  Object.keys(errors).forEach((key) => delete errors[key])

  if (!form.name.trim()) errors.name = 'Product name is required'
  if (!form.sku.trim() && !form.autoGenerateSku) errors.sku = 'SKU is required or toggle Auto-generate'
  if (Number(form.sellingPrice) <= 0) errors.sellingPrice = 'Selling price must be greater than 0'
  if (Number(form.initialStock) < 0) errors.initialStock = 'Opening stock cannot be negative'

  if (form.hasVariants && form.variants.length === 0) {
    errors.variants = 'Please generate or add at least one variant combination.'
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
  const activeOptions = optionTemplates.value.filter((o) => o.name.trim() && o.values.trim())
  if (activeOptions.length === 0) return

  const cartesian = (sets: string[][]): string[][] => {
    return sets.reduce<string[][]>(
      (acc, set) => {
        return acc.flatMap((x) => set.map((y) => [...x, y]))
      },
      [[]]
    )
  }

  const optionSets = activeOptions.map((o) =>
    o.values
      .split(',')
      .map((v) => v.trim())
      .filter(Boolean)
  )

  if (optionSets.some((s) => s.length === 0)) return

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
      attribute_value_ids: combo,
    }
  })

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
    attribute_value_ids: [],
  })
}

const removeVariant = (index: number) => {
  form.variants.splice(index, 1)
}

const mutation = useMutation({
  mutationFn: () => {
    const payload = {
      name: form.name,
      description: form.description || undefined,
      sku: form.autoGenerateSku ? undefined : form.sku || undefined,
      type: form.type,
      status: form.status,
      category_id: form.categoryId || null,
      cost_price: Math.round(Number(form.costPrice || 0) * 100),
      selling_price: Math.round(Number(form.sellingPrice || 0) * 100),
      has_variants: form.hasVariants,
      initial_stock:
        !form.hasVariants && !isEdit.value
          ? Math.max(0, Math.floor(Number(form.initialStock || 0)))
          : undefined,
      variants: form.hasVariants
        ? form.variants.map((v: any) => ({
            id: v.id || undefined,
            sku: v.sku,
            name: v.name,
            cost_price: Math.round(Number(v.costPrice || 0) * 100),
            selling_price: Math.round(Number(v.sellingPrice || 0) * 100),
            is_active: v.is_active !== false,
            stock: isEdit.value ? undefined : v.stock || 0,
            attribute_value_ids: v.attribute_value_ids || [],
          }))
        : [],
    }

    if (isEdit.value && props.product) {
      return inventoryApi.updateProduct(props.product.id, payload)
    }
    return inventoryApi.createProduct(payload)
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['inventory'] })
    emit('saved')
    emit('update:modelValue', false)
  },
})

const handleSubmit = () => {
  if (!validate()) return
  mutation.mutate()
}

const tabs = [
  { id: 'general', label: 'General', icon: Package },
  { id: 'pricing', label: 'Pricing & Margins', icon: DollarSign },
  { id: 'variants', label: 'Variants & Options', icon: Layers },
  { id: 'media', label: 'Media', icon: ImageIcon },
]

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
    :title="isEdit ? 'Edit Product' : 'Add New Product'"
    size="2xl"
  >
    <form id="productForm" @submit.prevent="handleSubmit">
      <TabGroup>
        <div class="flex flex-col min-h-[520px] max-h-[640px]">
          <!-- Modern Tab Navigation -->
          <TabList class="flex space-x-1.5 rounded-2xl bg-slate-100 p-1.5 mb-5 shrink-0 border border-slate-200/60 overflow-x-auto no-scrollbar">
            <Tab
              v-for="tab in tabs"
              :key="tab.id"
              v-slot="{ selected }"
              as="template"
            >
              <button
                type="button"
                :class="[
                  'flex items-center justify-center gap-2 rounded-xl py-2 px-3 text-xs sm:text-sm font-bold transition-all duration-200 outline-none whitespace-nowrap flex-1',
                  selected
                    ? 'bg-white text-blue-600 shadow-sm border border-slate-200/80 ring-1 ring-black/5'
                    : 'text-slate-500 hover:text-slate-900 hover:bg-white/50'
                ]"
              >
                <component :is="tab.icon" class="w-4 h-4" />
                <span>{{ tab.label }}</span>
                <span
                  v-if="tab.id === 'variants' && form.hasVariants"
                  class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] bg-blue-100 text-blue-700"
                >
                  {{ form.variants.length }}
                </span>
              </button>
            </Tab>
          </TabList>

          <!-- Tab Panels Container -->
          <div class="flex-1 overflow-y-auto px-1 custom-scrollbar">
            <TabPanels>
              <!-- 1. GENERAL TAB -->
              <TabPanel class="space-y-4 outline-none">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <UiInput
                    v-model="form.name"
                    label="Product Name"
                    placeholder="e.g. Ergonomic Office Chair"
                    :error="errors.name"
                    required
                  />

                  <div class="space-y-1.5">
                    <div class="flex justify-between items-center">
                      <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Category</label>
                      <button
                        type="button"
                        @click="isCreateCategoryModalOpen = true"
                        class="text-xs text-blue-600 hover:text-blue-700 font-bold inline-flex items-center gap-1"
                      >
                        <Plus class="w-3.5 h-3.5" /> New Category
                      </button>
                    </div>
                    <UiSelect
                      v-model="form.categoryId"
                      :options="[
                        { label: 'Uncategorized / General', value: '' },
                        ...(Array.isArray(categories) ? categories.map((c) => ({ label: c.name, value: c.id })) : [])
                      ]"
                    />
                  </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                  <!-- SKU with Auto Generator -->
                  <div class="space-y-1.5 sm:col-span-1">
                    <UiInput
                      v-model="form.sku"
                      label="SKU Identifier"
                      placeholder="Auto-generated"
                      :disabled="form.autoGenerateSku"
                      :error="errors.sku"
                    />
                    <label class="flex items-center text-xs text-slate-500 cursor-pointer pt-0.5">
                      <input
                        type="checkbox"
                        v-model="form.autoGenerateSku"
                        class="mr-2 rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-3.5 h-3.5"
                      />
                      <span>Auto-generate SKU</span>
                    </label>
                  </div>

                  <!-- Product Type -->
                  <div class="space-y-1.5 sm:col-span-1">
                    <UiSelect
                      v-model="form.type"
                      label="Product Type"
                      :options="[
                        { label: 'Stockable (Inventory Tracked)', value: 'stockable' },
                        { label: 'Consumable (Office/Supplies)', value: 'consumable' },
                        { label: 'Service (Non-Physical)', value: 'service' }
                      ]"
                    />
                  </div>

                  <!-- Status -->
                  <div class="space-y-1.5 sm:col-span-1">
                    <UiSelect
                      v-model="form.status"
                      label="Status"
                      :options="[
                        { label: 'Active (Available for Sale)', value: 'active' },
                        { label: 'Inactive (Draft/Hidden)', value: 'inactive' },
                        { label: 'Archived (Discontinued)', value: 'archived' }
                      ]"
                    />
                  </div>
                </div>

                <!-- Description -->
                <div class="space-y-1.5">
                  <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Product Description</label>
                  <textarea
                    v-model="form.description"
                    rows="3"
                    class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white text-sm p-3 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 transition-colors shadow-2xs"
                    placeholder="Provide detailed specifications, features, or internal handling instructions..."
                  ></textarea>
                </div>
              </TabPanel>

              <!-- 2. PRICING & MARGINS TAB -->
              <TabPanel class="space-y-5 outline-none">
                <!-- Pricing Inputs -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <UiInput
                    v-model.number="form.costPrice"
                    type="number"
                    step="0.01"
                    min="0"
                    label="Cost Price ($)"
                    placeholder="0.00"
                  />

                  <UiInput
                    v-model.number="form.sellingPrice"
                    type="number"
                    step="0.01"
                    min="0.01"
                    label="Selling Price ($)"
                    placeholder="0.00"
                    :error="errors.sellingPrice"
                    required
                  />
                </div>

                <!-- Live Margin & Markup Analytics Banner -->
                <div class="p-4 rounded-2xl bg-gradient-to-r from-slate-900 to-indigo-950 text-white border border-slate-800 shadow-md">
                  <div class="flex items-center space-x-2 text-xs font-bold text-blue-300 uppercase tracking-wider mb-3">
                    <BarChart2 class="w-4 h-4 text-blue-400" />
                    <span>Real-Time Margin & Profit Analysis</span>
                  </div>

                  <div class="grid grid-cols-3 gap-4 text-center">
                    <div class="p-2.5 rounded-xl bg-white/10 backdrop-blur-md border border-white/10">
                      <span class="text-[10px] text-slate-300 uppercase font-bold block">Gross Profit</span>
                      <span class="text-lg font-black text-emerald-400">
                        ${{ profitPerUnit.toFixed(2) }}
                      </span>
                    </div>

                    <div class="p-2.5 rounded-xl bg-white/10 backdrop-blur-md border border-white/10">
                      <span class="text-[10px] text-slate-300 uppercase font-bold block">Profit Margin</span>
                      <span :class="['text-lg font-black', marginPct >= 20 ? 'text-emerald-400' : 'text-amber-400']">
                        {{ marginPct }}%
                      </span>
                    </div>

                    <div class="p-2.5 rounded-xl bg-white/10 backdrop-blur-md border border-white/10">
                      <span class="text-[10px] text-slate-300 uppercase font-bold block">Markup %</span>
                      <span class="text-lg font-black text-blue-300">
                        {{ markupPct }}%
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Opening Stock (for simple products on create) -->
                <div v-if="!isEdit && !form.hasVariants" class="p-4 rounded-xl border border-slate-200 bg-slate-50/60 space-y-2">
                  <div class="flex items-center space-x-2">
                    <Boxes class="w-4 h-4 text-blue-600" />
                    <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wide">Opening Stock Quantity</h4>
                  </div>
                  <UiInput
                    v-model.number="form.initialStock"
                    type="number"
                    min="0"
                    step="1"
                    placeholder="Enter available units on hand..."
                    :error="errors.initialStock"
                  />
                  <p class="text-[11px] text-slate-400">Stock will be credited to your default warehouse location immediately upon creation.</p>
                </div>
              </TabPanel>

              <!-- 3. VARIANTS TAB -->
              <TabPanel class="space-y-5 outline-none">
                <div class="flex items-center justify-between p-4 rounded-2xl bg-slate-50 border border-slate-200">
                  <div class="flex items-center space-x-3">
                    <input
                      type="checkbox"
                      v-model="form.hasVariants"
                      id="hasVariants"
                      class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-5 h-5 cursor-pointer"
                    />
                    <div>
                      <label for="hasVariants" class="text-sm font-bold text-slate-900 cursor-pointer block">
                        Enable Multi-Variant Matrix
                      </label>
                      <p class="text-xs text-slate-500">Configure multiple options such as Size, Color, Capacity, or Material.</p>
                    </div>
                  </div>

                  <UiButton
                    v-if="form.hasVariants"
                    type="button"
                    size="sm"
                    variant="outline"
                    @click="addVariantManually"
                  >
                    <Plus class="h-3.5 w-3.5 mr-1" /> Add Manual SKU
                  </UiButton>
                </div>

                <div v-if="errors.variants" class="p-3 bg-red-50 text-red-700 text-xs font-semibold rounded-xl border border-red-200 flex items-center gap-2">
                  <AlertCircle class="w-4 h-4 shrink-0 text-red-500" />
                  <span>{{ errors.variants }}</span>
                </div>

                <div v-if="form.hasVariants" class="space-y-4">
                  <!-- Variant Option Template Generator -->
                  <div class="p-4 border border-slate-200 rounded-2xl bg-white space-y-3 shadow-2xs">
                    <div class="flex justify-between items-center">
                      <h4 class="text-xs font-bold text-slate-700 tracking-wide uppercase">Option Attribute Rules</h4>
                      <button
                        type="button"
                        @click="addOptionTemplate"
                        class="text-xs text-blue-600 hover:text-blue-700 font-bold inline-flex items-center gap-1"
                      >
                        <Plus class="w-3.5 h-3.5" /> Add Attribute
                      </button>
                    </div>

                    <div class="space-y-2.5">
                      <div
                        v-for="(opt, idx) in optionTemplates"
                        :key="idx"
                        class="flex gap-3 items-center bg-slate-50 p-2 rounded-xl border border-slate-200"
                      >
                        <div class="w-1/3">
                          <input
                            v-model="opt.name"
                            placeholder="Option (e.g. Size)"
                            class="w-full text-xs font-bold text-slate-800 bg-white border border-slate-200 rounded-lg p-2 focus:ring-1 focus:ring-blue-500"
                          />
                        </div>
                        <div class="flex-1">
                          <input
                            v-model="opt.values"
                            placeholder="Comma-separated values (e.g. S, M, L, XL)"
                            class="w-full text-xs text-slate-700 bg-white border border-slate-200 rounded-lg p-2 focus:ring-1 focus:ring-blue-500"
                          />
                        </div>
                        <button
                          type="button"
                          @click="removeOptionTemplate(idx)"
                          class="p-1.5 text-slate-400 hover:text-red-500 rounded-lg"
                        >
                          <Trash2 class="w-4 h-4" />
                        </button>
                      </div>
                    </div>

                    <div class="flex justify-end pt-1">
                      <UiButton
                        type="button"
                        size="sm"
                        variant="secondary"
                        @click="generateVariants"
                        class="shadow-xs"
                      >
                        <Sparkles class="w-3.5 h-3.5 mr-1.5 text-blue-600" /> Generate Combinations
                      </UiButton>
                    </div>
                  </div>

                  <!-- Generated Variants List -->
                  <div class="border border-slate-200 rounded-2xl overflow-hidden shadow-2xs bg-white">
                    <div class="bg-slate-50 border-b border-slate-200 px-4 py-2.5 flex items-center justify-between">
                      <span class="text-xs font-bold text-slate-700 tracking-wide uppercase">
                        Configured Variants ({{ form.variants.length }})
                      </span>
                    </div>

                    <div class="divide-y divide-slate-100 max-h-[260px] overflow-y-auto custom-scrollbar">
                      <div
                        v-for="(v, index) in form.variants"
                        :key="index"
                        class="p-3.5 flex flex-col md:flex-row gap-3 items-start md:items-center relative group hover:bg-slate-50/70 transition-colors"
                      >
                        <div class="w-full md:w-1/3">
                          <input
                            type="text"
                            v-model="v.name"
                            class="block w-full border border-slate-200 rounded-lg py-1.5 px-2.5 text-xs font-bold text-slate-900 bg-white focus:ring-1 focus:ring-blue-500"
                            placeholder="Variant Name"
                            required
                          />
                        </div>
                        <div class="w-full md:flex-1 grid grid-cols-2 sm:grid-cols-4 gap-2">
                          <div>
                            <input
                              type="text"
                              v-model="v.sku"
                              class="block w-full border border-slate-200 rounded-lg py-1.5 px-2 text-xs font-mono bg-white focus:ring-1 focus:ring-blue-500"
                              placeholder="SKU"
                              required
                            />
                          </div>
                          <div>
                            <input
                              type="number"
                              v-model.number="v.costPrice"
                              step="0.01"
                              class="block w-full border border-slate-200 rounded-lg py-1.5 px-2 text-xs bg-white focus:ring-1 focus:ring-blue-500"
                              placeholder="Cost ($)"
                            />
                          </div>
                          <div>
                            <input
                              type="number"
                              v-model.number="v.sellingPrice"
                              step="0.01"
                              class="block w-full border border-slate-200 rounded-lg py-1.5 px-2 text-xs font-bold bg-white focus:ring-1 focus:ring-blue-500"
                              placeholder="Sale ($)"
                              required
                            />
                          </div>
                          <div class="flex items-center space-x-1.5">
                            <input
                              type="number"
                              v-model.number="v.stock"
                              class="block w-full border border-slate-200 rounded-lg py-1.5 px-2 text-xs bg-white focus:ring-1 focus:ring-blue-500"
                              placeholder="Stock"
                            />
                            <button
                              type="button"
                              @click="removeVariant(index)"
                              class="p-1.5 text-slate-400 hover:text-red-500 rounded-lg shrink-0"
                            >
                              <Trash2 class="h-3.5 w-3.5" />
                            </button>
                          </div>
                        </div>
                      </div>

                      <div v-if="form.variants.length === 0" class="text-center py-10 text-slate-400">
                        <Sparkles class="w-8 h-8 mx-auto mb-2 text-slate-300" />
                        <span class="text-xs font-medium">No variants configured yet. Click "Generate Combinations" above.</span>
                      </div>
                    </div>
                  </div>
                </div>
              </TabPanel>

              <!-- 4. MEDIA TAB -->
              <TabPanel class="space-y-4 outline-none">
                <div class="p-8 border-2 border-dashed border-slate-200 rounded-2xl text-center bg-slate-50/50 space-y-3">
                  <div class="p-3 bg-white rounded-full w-12 h-12 mx-auto shadow-2xs flex items-center justify-center">
                    <ImageIcon class="w-6 h-6 text-slate-400" />
                  </div>
                  <div>
                    <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wide">Product Imagery</h4>
                    <p class="text-xs text-slate-400 mt-0.5">Upload product showcase images for POS terminals and customer storefronts.</p>
                  </div>
                  <UiButton type="button" size="sm" variant="outline">
                    Browse Files
                  </UiButton>
                </div>
              </TabPanel>
            </TabPanels>
          </div>
        </div>
      </TabGroup>
    </form>

    <template #footer>
      <div class="flex items-center justify-between w-full pt-2">
        <div class="text-xs text-slate-400 font-medium">
          <span v-if="form.sku" class="font-mono">SKU: {{ form.sku }}</span>
        </div>

        <div class="flex items-center space-x-2">
          <UiButton variant="ghost" size="sm" @click="emit('update:modelValue', false)">
            Cancel
          </UiButton>
          <UiButton
            type="submit"
            form="productForm"
            size="sm"
            :loading="mutation.isPending.value"
            class="shadow-sm"
          >
            <Check class="w-4 h-4 mr-1.5" />
            {{ isEdit ? 'Save Changes' : 'Create Product' }}
          </UiButton>
        </div>
      </div>
    </template>
  </UiModal>

  <!-- Create Category Quick Modal -->
  <CreateCategoryModal
    v-model="isCreateCategoryModalOpen"
    @created="handleCategoryCreated"
  />
</template>
