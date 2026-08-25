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
  Briefcase,
  Zap,
  CheckCircle2,
  QrCode,
  Hash,
  FileText,
  Percent,
  UploadCloud,
  Star,
  Loader2,
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

interface ImageItem {
  id?: string
  path?: string
  url: string
  is_primary: boolean
  isUploading?: boolean
  file?: File
}

const props = defineProps<Props>()
const emit = defineEmits(['update:modelValue', 'saved'])

const queryClient = useQueryClient()
const isEdit = computed(() => !!props.product)
const isCreateCategoryModalOpen = ref(false)
const fileInputRef = ref<HTMLInputElement | null>(null)
const isDragging = ref(false)

const categoryOptions = computed(() => {
  const list = Array.isArray(props.categories) ? props.categories : []
  return [
    { label: 'Uncategorized / General', value: '' },
    ...list.map((c) => ({ label: c.name, value: c.id })),
  ]
})

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
  images: [] as ImageItem[],
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

      const mappedImages: ImageItem[] = Array.isArray(rawProd.images) && rawProd.images.length > 0
        ? rawProd.images.map((img: any) => ({
            id: img.id,
            path: img.path || img.url,
            url: img.url || img.path,
            is_primary: !!img.is_primary,
          }))
        : (newProduct.primary_image_url ? [{ url: newProduct.primary_image_url, path: newProduct.primary_image_url, is_primary: true }] : [])

      Object.assign(form, {
        name: newProduct.name || '',
        description: newProduct.description || '',
        sku: newProduct.sku || '',
        type: newProduct.type || 'stockable',
        status: newProduct.status || 'active',
        categoryId: newProduct.category?.id || (newProduct as any).category_id || undefined,
        costPrice: typeof rawProd.cost_price === 'number' ? rawProd.cost_price / 100 : 0,
        sellingPrice: typeof rawProd.selling_price === 'number' ? rawProd.selling_price / 100 : 0,
        initialStock: Number(rawProd.available_quantity ?? rawProd.quantity_on_hand ?? rawProd.stock ?? 0),
        minStock: typeof rawProd.min_stock === 'number' ? rawProd.min_stock : 5,
        barcode: rawProd.barcode || '',
        trackSerialNumbers: !!rawProd.track_serial_numbers,
        trackLots: !!rawProd.track_lots,
        variants: mappedVariants,
        hasVariants: !!(rawProd.has_variants ?? newProduct.has_variants),
        images: mappedImages,
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
  if (!form.sku.trim() && !form.autoGenerateSku) errors.sku = 'SKU is required'
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

// ── Media Upload Handlers ───────────────────────────────────────────────────

function triggerFileInput() {
  fileInputRef.value?.click()
}

async function uploadFileItem(file: File) {
  // Create instant local preview
  const localPreview = URL.createObjectURL(file)
  const isFirstImage = form.images.length === 0

  const imageEntry = reactive<ImageItem>({
    url: localPreview,
    path: '',
    is_primary: isFirstImage,
    isUploading: true,
    file,
  })

  form.images.push(imageEntry)

  try {
    const response = await inventoryApi.uploadMedia(file)
    const uploadedData = response.data?.data || response.data
    if (uploadedData?.url || uploadedData?.path) {
      imageEntry.url = uploadedData.url || uploadedData.path
      imageEntry.path = uploadedData.path || uploadedData.url
    }
  } catch (error) {
    console.warn('Failed to upload file to backend, fallback to data URI', error)
    // Fallback to data URI so it still saves
    const reader = new FileReader()
    reader.onload = (e) => {
      if (e.target?.result) {
        imageEntry.url = e.target.result as string
        imageEntry.path = e.target.result as string
      }
    }
    reader.readAsDataURL(file)
  } finally {
    imageEntry.isUploading = false
  }
}

async function handleFileSelect(e: Event) {
  const target = e.target as HTMLInputElement
  if (!target.files || target.files.length === 0) return

  const files = Array.from(target.files)
  for (const file of files) {
    await uploadFileItem(file)
  }

  // Reset file input value so same file can be re-selected
  target.value = ''
}

async function handleDrop(e: DragEvent) {
  isDragging.value = false
  if (!e.dataTransfer?.files || e.dataTransfer.files.length === 0) return

  const files = Array.from(e.dataTransfer.files).filter((f) => f.type.startsWith('image/'))
  for (const file of files) {
    await uploadFileItem(file)
  }
}

function setPrimaryImage(index: number) {
  form.images.forEach((img, i) => {
    img.is_primary = i === index
  })
}

function removeImage(index: number) {
  const removed = form.images.splice(index, 1)[0]
  if (removed?.is_primary && form.images[0]) {
    form.images[0].is_primary = true
  }
}

const primaryImage = computed(() => {
  return form.images.find((img) => img.is_primary) || form.images[0]
})

const mutation = useMutation({
  mutationFn: () => {
    const primaryImg = primaryImage.value
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
      primary_image_url: primaryImg?.url || primaryImg?.path || undefined,
      images: form.images.map((img, idx) => ({
        path: img.path || img.url,
        url: img.url,
        is_primary: img.is_primary,
        sort_order: idx,
      })),
      initial_stock: !form.hasVariants
        ? Math.max(0, Math.floor(Number(form.initialStock || 0)))
        : undefined,
      stock: !form.hasVariants
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
            stock: Math.max(0, Math.floor(Number(v.stock || 0))),
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
  { id: 'general', label: 'General Info', icon: Package },
  { id: 'pricing', label: 'Pricing & Margins', icon: DollarSign },
  { id: 'variants', label: 'Variant Matrix', icon: Layers },
  { id: 'media', label: 'Media Assets', icon: ImageIcon },
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
    <form id="productForm" @submit.prevent="handleSubmit" class="space-y-4">
      <TabGroup>
        <div class="flex flex-col min-h-[480px]">
          <!-- Modern Tab Navigation -->
          <TabList class="flex space-x-1.5 rounded-2xl bg-slate-100/80 p-1 mb-5 shrink-0 border border-slate-200/60 overflow-x-auto no-scrollbar">
            <Tab
              v-for="tab in tabs"
              :key="tab.id"
              v-slot="{ selected }"
              as="template"
            >
              <button
                type="button"
                :class="[
                  'flex items-center justify-center gap-2 rounded-xl py-2 px-3.5 text-xs sm:text-sm font-bold transition-all duration-200 outline-none whitespace-nowrap flex-1',
                  selected
                    ? 'bg-white text-blue-600 shadow-sm border border-slate-200/80 ring-1 ring-black/5'
                    : 'text-slate-500 hover:text-slate-900 hover:bg-white/40'
                ]"
              >
                <component :is="tab.icon" class="w-4 h-4" />
                <span>{{ tab.label }}</span>
                <span
                  v-if="tab.id === 'variants' && form.hasVariants"
                  class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700"
                >
                  {{ form.variants.length }}
                </span>
                <span
                  v-if="tab.id === 'media' && form.images.length > 0"
                  class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700"
                >
                  {{ form.images.length }}
                </span>
              </button>
            </Tab>
          </TabList>

          <!-- Tab Panels Container -->
          <div class="flex-1 px-0.5">
            <TabPanels>
              <!-- 1. GENERAL TAB -->
              <TabPanel class="space-y-4 outline-none">
                <!-- Product Type Visual Segmented Cards -->
                <div class="space-y-1.5">
                  <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Product Classification</label>
                  <div class="grid grid-cols-3 gap-2.5">
                    <button
                      type="button"
                      @click="form.type = 'stockable'"
                      :class="[
                        'p-3 rounded-2xl border text-left transition-all relative flex flex-col justify-between',
                        form.type === 'stockable'
                          ? 'border-blue-600 bg-blue-50/40 ring-2 ring-blue-500/10 shadow-xs'
                          : 'border-slate-200 bg-white hover:border-slate-300'
                      ]"
                    >
                      <div class="flex items-center justify-between">
                        <Package :class="['w-4 h-4', form.type === 'stockable' ? 'text-blue-600' : 'text-slate-400']" />
                        <CheckCircle2 v-if="form.type === 'stockable'" class="w-4 h-4 text-blue-600" />
                      </div>
                      <div class="mt-2">
                        <div class="text-xs font-bold text-slate-900">Stockable</div>
                        <div class="text-[10px] text-slate-500 leading-tight">Physical inventory with stock tracking</div>
                      </div>
                    </button>

                    <button
                      type="button"
                      @click="form.type = 'consumable'"
                      :class="[
                        'p-3 rounded-2xl border text-left transition-all relative flex flex-col justify-between',
                        form.type === 'consumable'
                          ? 'border-blue-600 bg-blue-50/40 ring-2 ring-blue-500/10 shadow-xs'
                          : 'border-slate-200 bg-white hover:border-slate-300'
                      ]"
                    >
                      <div class="flex items-center justify-between">
                        <Boxes :class="['w-4 h-4', form.type === 'consumable' ? 'text-blue-600' : 'text-slate-400']" />
                        <CheckCircle2 v-if="form.type === 'consumable'" class="w-4 h-4 text-blue-600" />
                      </div>
                      <div class="mt-2">
                        <div class="text-xs font-bold text-slate-900">Consumable</div>
                        <div class="text-[10px] text-slate-500 leading-tight">Supplies used without count reorder</div>
                      </div>
                    </button>

                    <button
                      type="button"
                      @click="form.type = 'service'"
                      :class="[
                        'p-3 rounded-2xl border text-left transition-all relative flex flex-col justify-between',
                        form.type === 'service'
                          ? 'border-blue-600 bg-blue-50/40 ring-2 ring-blue-500/10 shadow-xs'
                          : 'border-slate-200 bg-white hover:border-slate-300'
                      ]"
                    >
                      <div class="flex items-center justify-between">
                        <Zap :class="['w-4 h-4', form.type === 'service' ? 'text-blue-600' : 'text-slate-400']" />
                        <CheckCircle2 v-if="form.type === 'service'" class="w-4 h-4 text-blue-600" />
                      </div>
                      <div class="mt-2">
                        <div class="text-xs font-bold text-slate-900">Service</div>
                        <div class="text-[10px] text-slate-500 leading-tight">Digital or labor (zero physical stock)</div>
                      </div>
                    </button>
                  </div>
                </div>

                <!-- Product Name & Category Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                  <UiInput
                    v-model="form.name"
                    label="Product Name"
                    placeholder="e.g. Ergonomic Standing Desk Pro"
                    :error="errors.name"
                    required
                  />

                  <div class="space-y-1.5">
                    <div class="flex justify-between items-center">
                      <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Category</label>
                      <button
                        type="button"
                        @click="isCreateCategoryModalOpen = true"
                        class="text-xs text-blue-600 hover:text-blue-700 font-bold inline-flex items-center gap-1 hover:underline"
                      >
                        <Plus class="w-3.5 h-3.5" /> New Category
                      </button>
                    </div>
                    <UiSelect
                      v-model="form.categoryId"
                      :options="categoryOptions"
                    />
                  </div>
                </div>

                <!-- SKU & Status -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <!-- SKU Input with Toggle -->
                  <div class="space-y-1.5">
                    <UiInput
                      v-model="form.sku"
                      label="SKU Identifier"
                      placeholder="Auto-generated if left blank"
                      :disabled="form.autoGenerateSku"
                      :error="errors.sku"
                    />
                    <div class="flex items-center justify-between pt-0.5 px-1">
                      <span class="text-[11px] text-slate-500">Auto-generate SKU</span>
                      <button
                        type="button"
                        @click="form.autoGenerateSku = !form.autoGenerateSku"
                        :class="[
                          'relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none',
                          form.autoGenerateSku ? 'bg-blue-600' : 'bg-slate-300'
                        ]"
                      >
                        <span
                          :class="[
                            'pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out',
                            form.autoGenerateSku ? 'translate-x-4' : 'translate-x-0'
                          ]"
                        />
                      </button>
                    </div>
                  </div>

                  <!-- Status Selector -->
                  <div class="space-y-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Publish Status</label>
                    <div class="grid grid-cols-3 gap-2">
                      <button
                        type="button"
                        @click="form.status = 'active'"
                        :class="[
                          'py-2 px-2.5 rounded-xl text-xs font-bold transition-all border text-center',
                          form.status === 'active'
                            ? 'bg-emerald-50 text-emerald-700 border-emerald-300 ring-2 ring-emerald-500/10'
                            : 'bg-slate-50 text-slate-500 border-slate-200 hover:bg-slate-100'
                        ]"
                      >
                        Active
                      </button>

                      <button
                        type="button"
                        @click="form.status = 'inactive'"
                        :class="[
                          'py-2 px-2.5 rounded-xl text-xs font-bold transition-all border text-center',
                          form.status === 'inactive'
                            ? 'bg-amber-50 text-amber-700 border-amber-300 ring-2 ring-amber-500/10'
                            : 'bg-slate-50 text-slate-500 border-slate-200 hover:bg-slate-100'
                        ]"
                      >
                        Draft
                      </button>

                      <button
                        type="button"
                        @click="form.status = 'archived'"
                        :class="[
                          'py-2 px-2.5 rounded-xl text-xs font-bold transition-all border text-center',
                          form.status === 'archived'
                            ? 'bg-slate-200 text-slate-800 border-slate-400 ring-2 ring-slate-500/10'
                            : 'bg-slate-50 text-slate-500 border-slate-200 hover:bg-slate-100'
                        ]"
                      >
                        Archived
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Description Input -->
                <div class="space-y-1.5">
                  <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Product Description</label>
                  <textarea
                    v-model="form.description"
                    rows="3"
                    class="block w-full rounded-2xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white text-sm p-3 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all shadow-2xs font-medium text-slate-900 placeholder:text-slate-400"
                    placeholder="Add detailed specifications, product features, or handling instructions..."
                  ></textarea>
                </div>
              </TabPanel>

              <!-- 2. PRICING & MARGINS TAB -->
              <TabPanel class="space-y-4 outline-none">
                <!-- Pricing Inputs -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div class="space-y-1.5">
                    <UiInput
                      v-model.number="form.costPrice"
                      type="number"
                      step="0.01"
                      min="0"
                      label="Cost Price ($)"
                      placeholder="0.00"
                    >
                      <template #prefix>
                        <span class="text-xs font-mono font-bold text-slate-400">$</span>
                      </template>
                    </UiInput>
                    <p class="text-[11px] text-slate-400 pl-1">Your purchase or manufacturing cost.</p>
                  </div>

                  <div class="space-y-1.5">
                    <UiInput
                      v-model.number="form.sellingPrice"
                      type="number"
                      step="0.01"
                      min="0.01"
                      label="Selling Price ($)"
                      placeholder="0.00"
                      :error="errors.sellingPrice"
                      required
                    >
                      <template #prefix>
                        <span class="text-xs font-mono font-bold text-slate-400">$</span>
                      </template>
                    </UiInput>
                    <p class="text-[11px] text-slate-400 pl-1">Standard price for POS checkouts and web storefronts.</p>
                  </div>
                </div>

                <!-- Live Margin & Markup Analytics Banner -->
                <div class="p-4 rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950 to-blue-950 text-white border border-slate-800 shadow-md">
                  <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center space-x-2 text-xs font-bold text-blue-300 uppercase tracking-wider">
                      <BarChart2 class="w-4 h-4 text-blue-400" />
                      <span>Live Margin Intelligence</span>
                    </div>
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-white/10 text-blue-200 font-mono">
                      Dynamic
                    </span>
                  </div>

                  <div class="grid grid-cols-3 gap-3 text-center">
                    <div class="p-2.5 rounded-xl bg-white/10 backdrop-blur-md border border-white/10">
                      <span class="text-[10px] text-slate-300 uppercase font-bold block">Profit / Unit</span>
                      <span class="text-base sm:text-lg font-black text-emerald-400">
                        ${{ profitPerUnit.toFixed(2) }}
                      </span>
                    </div>

                    <div class="p-2.5 rounded-xl bg-white/10 backdrop-blur-md border border-white/10">
                      <span class="text-[10px] text-slate-300 uppercase font-bold block">Profit Margin</span>
                      <span :class="['text-base sm:text-lg font-black', marginPct >= 20 ? 'text-emerald-400' : 'text-amber-400']">
                        {{ marginPct }}%
                      </span>
                    </div>

                    <div class="p-2.5 rounded-xl bg-white/10 backdrop-blur-md border border-white/10">
                      <span class="text-[10px] text-slate-300 uppercase font-bold block">Markup %</span>
                      <span class="text-base sm:text-lg font-black text-blue-300">
                        {{ markupPct }}%
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Stock Quantity on Hand (for non-variant products) -->
                <div v-if="!form.hasVariants" class="p-4 rounded-2xl border border-slate-200 bg-slate-50/70 space-y-2">
                  <div class="flex items-center space-x-2">
                    <Boxes class="w-4 h-4 text-blue-600" />
                    <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wide">
                      {{ isEdit ? 'Current Stock on Hand (Units)' : 'Initial Opening Stock (Units)' }}
                    </h4>
                  </div>
                  <UiInput
                    v-model.number="form.initialStock"
                    type="number"
                    min="0"
                    step="1"
                    placeholder="0"
                    :error="errors.initialStock"
                  />
                  <p class="text-[11px] text-slate-500 leading-normal">
                    {{ isEdit
                      ? 'Adjusting this count will log an inventory stock level adjustment for your main warehouse.'
                      : 'Initial stock units will automatically be credited to your default warehouse inventory upon saving.'
                    }}
                  </p>
                </div>
              </TabPanel>

              <!-- 3. VARIANTS TAB -->
              <TabPanel class="space-y-4 outline-none">
                <!-- Variant Matrix Switch -->
                <div class="flex items-center justify-between p-4 rounded-2xl bg-slate-50/80 border border-slate-200">
                  <div class="flex items-center space-x-3">
                    <button
                      type="button"
                      @click="form.hasVariants = !form.hasVariants"
                      :class="[
                        'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none',
                        form.hasVariants ? 'bg-blue-600' : 'bg-slate-300'
                      ]"
                    >
                      <span
                        :class="[
                          'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out',
                          form.hasVariants ? 'translate-x-5' : 'translate-x-0'
                        ]"
                      />
                    </button>
                    <div>
                      <label class="text-sm font-bold text-slate-900 cursor-pointer block">
                        Enable Multi-Variant Matrix
                      </label>
                      <p class="text-xs text-slate-500">Manage independent SKUs, prices, and inventory for different Sizes, Colors, or Specs.</p>
                    </div>
                  </div>

                  <UiButton
                    v-if="form.hasVariants"
                    type="button"
                    size="sm"
                    variant="outline"
                    @click="addVariantManually"
                  >
                    <Plus class="h-3.5 w-3.5 mr-1" /> Add SKU
                  </UiButton>
                </div>

                <div v-if="errors.variants" class="p-3 bg-red-50 text-red-700 text-xs font-semibold rounded-2xl border border-red-200 flex items-center gap-2">
                  <AlertCircle class="w-4 h-4 shrink-0 text-red-500" />
                  <span>{{ errors.variants }}</span>
                </div>

                <div v-if="form.hasVariants" class="space-y-4">
                  <!-- Variant Option Template Generator -->
                  <div class="p-4 border border-slate-200/90 rounded-2xl bg-white space-y-3 shadow-2xs">
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
                        class="flex gap-2.5 items-center bg-slate-50/80 p-2.5 rounded-xl border border-slate-200/80"
                      >
                        <div class="w-1/3">
                          <input
                            v-model="opt.name"
                            placeholder="Option (e.g. Size)"
                            class="w-full text-xs font-bold text-slate-900 bg-white border border-slate-200 rounded-lg p-2 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none"
                          />
                        </div>
                        <div class="flex-1">
                          <input
                            v-model="opt.values"
                            placeholder="Comma-separated values (e.g. S, M, L, XL)"
                            class="w-full text-xs text-slate-800 bg-white border border-slate-200 rounded-lg p-2 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none"
                          />
                        </div>
                        <button
                          type="button"
                          @click="removeOptionTemplate(idx)"
                          class="p-1.5 text-slate-400 hover:text-red-500 rounded-lg transition-colors"
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
                        class="p-3.5 flex flex-col md:flex-row gap-2.5 items-start md:items-center relative group hover:bg-slate-50/70 transition-colors"
                      >
                        <div class="w-full md:w-1/3">
                          <input
                            type="text"
                            v-model="v.name"
                            class="block w-full border border-slate-200 rounded-lg py-1.5 px-2.5 text-xs font-bold text-slate-900 bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none"
                            placeholder="Variant Name"
                            required
                          />
                        </div>
                        <div class="w-full md:flex-1 grid grid-cols-2 sm:grid-cols-4 gap-2">
                          <div>
                            <input
                              type="text"
                              v-model="v.sku"
                              class="block w-full border border-slate-200 rounded-lg py-1.5 px-2 text-xs font-mono bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none"
                              placeholder="SKU"
                              required
                            />
                          </div>
                          <div>
                            <input
                              type="number"
                              v-model.number="v.costPrice"
                              step="0.01"
                              class="block w-full border border-slate-200 rounded-lg py-1.5 px-2 text-xs bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none"
                              placeholder="Cost ($)"
                            />
                          </div>
                          <div>
                            <input
                              type="number"
                              v-model.number="v.sellingPrice"
                              step="0.01"
                              class="block w-full border border-slate-200 rounded-lg py-1.5 px-2 text-xs font-bold bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none"
                              placeholder="Sale ($)"
                              required
                            />
                          </div>
                          <div class="flex items-center space-x-1.5">
                            <input
                              type="number"
                              v-model.number="v.stock"
                              class="block w-full border border-slate-200 rounded-lg py-1.5 px-2 text-xs bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none"
                              placeholder="Stock"
                            />
                            <button
                              type="button"
                              @click="removeVariant(index)"
                              class="p-1.5 text-slate-400 hover:text-red-500 rounded-lg shrink-0 transition-colors"
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

              <!-- 4. MEDIA ASSETS TAB -->
              <TabPanel class="space-y-5 outline-none">
                <!-- Hidden Real File Input -->
                <input
                  ref="fileInputRef"
                  type="file"
                  accept="image/png,image/jpeg,image/webp,image/gif,image/svg+xml"
                  multiple
                  class="hidden"
                  @change="handleFileSelect"
                />

                <!-- Drag and Drop Upload Area -->
                <div
                  @dragover.prevent="isDragging = true"
                  @dragleave.prevent="isDragging = false"
                  @drop.prevent="handleDrop"
                  @click="triggerFileInput"
                  :class="[
                    'border-2 border-dashed rounded-3xl p-8 text-center cursor-pointer transition-all duration-200 flex flex-col items-center justify-center space-y-3',
                    isDragging
                      ? 'border-blue-500 bg-blue-50/50 scale-[0.99]'
                      : 'border-slate-200 bg-slate-50/50 hover:bg-white hover:border-blue-400 shadow-2xs'
                  ]"
                >
                  <div class="p-3.5 bg-white rounded-2xl shadow-xs border border-slate-100 text-blue-600">
                    <UploadCloud class="w-7 h-7" />
                  </div>
                  <div>
                    <h4 class="text-sm font-bold text-slate-800">
                      Upload Product Media
                    </h4>
                    <p class="text-xs text-slate-500 mt-1">
                      Drag & drop images here, or <span class="text-blue-600 font-bold underline">browse files</span>
                    </p>
                    <p class="text-[11px] text-slate-400 mt-1">
                      PNG, JPG, WebP, GIF up to 10MB each
                    </p>
                  </div>
                </div>

                <!-- Uploaded Images Gallery Preview -->
                <div v-if="form.images.length > 0" class="space-y-3">
                  <div class="flex items-center justify-between">
                    <h5 class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                      Product Gallery ({{ form.images.length }})
                    </h5>
                    <span class="text-[11px] text-slate-400">Click star to set main cover image</span>
                  </div>

                  <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
                    <div
                      v-for="(img, idx) in form.images"
                      :key="idx"
                      :class="[
                        'relative aspect-square rounded-2xl border-2 overflow-hidden bg-slate-100 group transition-all',
                        img.is_primary ? 'border-blue-600 ring-2 ring-blue-500/20 shadow-md' : 'border-slate-200'
                      ]"
                    >
                      <!-- Image Element -->
                      <img
                        :src="img.url"
                        alt="Product preview"
                        class="w-full h-full object-cover"
                      />

                      <!-- Uploading Overlay -->
                      <div
                        v-if="img.isUploading"
                        class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center text-white text-xs font-bold gap-1.5"
                      >
                        <Loader2 class="w-4 h-4 animate-spin" />
                        <span>Uploading...</span>
                      </div>

                      <!-- Primary Badge -->
                      <div
                        v-if="img.is_primary"
                        class="absolute top-2 left-2 px-2 py-0.5 rounded-full bg-blue-600 text-white text-[10px] font-bold shadow-xs flex items-center gap-1"
                      >
                        <Star class="w-3 h-3 fill-current" /> Cover
                      </div>

                      <!-- Action Buttons Hover Overlay -->
                      <div
                        v-if="!img.isUploading"
                        class="absolute inset-0 bg-slate-950/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2"
                      >
                        <button
                          type="button"
                          v-if="!img.is_primary"
                          @click.stop="setPrimaryImage(idx)"
                          title="Set as Cover Image"
                          class="p-2 bg-white/90 hover:bg-white text-slate-800 rounded-xl transition-transform transform hover:scale-110 shadow-md text-xs font-bold flex items-center gap-1"
                        >
                          <Star class="w-3.5 h-3.5 text-amber-500" />
                        </button>

                        <button
                          type="button"
                          @click.stop="removeImage(idx)"
                          title="Delete Image"
                          class="p-2 bg-red-600/90 hover:bg-red-600 text-white rounded-xl transition-transform transform hover:scale-110 shadow-md"
                        >
                          <Trash2 class="w-3.5 h-3.5" />
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </TabPanel>
            </TabPanels>
          </div>
        </div>
      </TabGroup>
    </form>

    <template #footer>
      <div class="flex items-center justify-between w-full">
        <div class="text-xs text-slate-400 font-medium">
          <span v-if="form.sku" class="font-mono bg-slate-100 px-2 py-1 rounded-lg">SKU: {{ form.sku }}</span>
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
            class="shadow-sm font-bold"
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
