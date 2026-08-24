<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import api from '@/api/client'
import { useToast } from '@/composables/useToast'
import {
  Sparkles,
  Plus,
  Trash2,
  ArrowUp,
  ArrowDown,
  ExternalLink,
  Save,
  Monitor,
  Tablet,
  Smartphone,
  Layers,
  Palette,
  Eye,
  ShoppingBag,
  Truck,
  ShieldCheck,
  RefreshCw,
  Star,
  Check,
  Copy,
} from '@lucide/vue'

interface Section {
  id: string
  type: 'hero' | 'features' | 'product_grid' | 'promo_banner' | 'testimonials' | 'footer'
  props: Record<string, any>
}

interface StorefrontPage {
  id: string
  slug: string
  title: string
  sections: Section[]
  is_published: boolean
}

interface Storefront {
  id: string
  name: string
  slug: string
  title: string | null
  description: string | null
  theme_config: Record<string, any>
  is_published: boolean
  pages?: StorefrontPage[]
}

const props = defineProps<{
  storefront: Storefront
}>()

const emit = defineEmits<{
  (e: 'updated', storefront: Storefront): void
  (e: 'close'): void
}>()

const toast = useToast()
const activePage = ref<StorefrontPage | null>(null)
const sections = ref<Section[]>([])
const selectedSectionId = ref<string | null>(null)
const viewMode = ref<'desktop' | 'tablet' | 'mobile'>('desktop')
const isSaving = ref(false)
const isAddingSection = ref(false)

// Sample demo products to render live inside product_grid blocks
const catalogProducts = ref([
  { id: '1', name: 'Premium Leather Backpack', sku: 'BAG-01', price_cents: 8900, category: 'Accessories' },
  { id: '2', name: 'Minimalist Wireless Headphones', sku: 'AUDIO-02', price_cents: 14900, category: 'Electronics' },
  { id: '3', name: 'Ergonomic Desk Mat (XL)', sku: 'MAT-03', price_cents: 3500, category: 'Office' },
  { id: '4', name: 'Ceramic Pour-Over Coffee Dripper', sku: 'COF-04', price_cents: 2800, category: 'Home' },
])

const availableBlockTypes = [
  {
    type: 'hero' as const,
    label: 'Hero Banner',
    description: 'Prominent headline, subtext, and call-to-action button.',
    icon: Sparkles,
  },
  {
    type: 'product_grid' as const,
    label: 'Product Catalog Grid',
    description: 'Showcase store products with live add-to-cart buttons.',
    icon: ShoppingBag,
  },
  {
    type: 'features' as const,
    label: 'Value Props & Perks',
    description: 'Highlight fast shipping, money-back guarantees, etc.',
    icon: ShieldCheck,
  },
  {
    type: 'promo_banner' as const,
    label: 'Promo & Discount Banner',
    description: 'Highlight sales, discount codes, or special promotions.',
    icon: Palette,
  },
  {
    type: 'testimonials' as const,
    label: 'Customer Reviews',
    description: 'Social proof cards with customer ratings and quotes.',
    icon: Star,
  },
  {
    type: 'footer' as const,
    label: 'Store Footer',
    description: 'Store contact details, copyright, and brand tagline.',
    icon: Layers,
  },
]

const selectedSection = computed(() => {
  return sections.value.find(s => s.id === selectedSectionId.value) || null
})

onMounted(() => {
  const home = props.storefront.pages?.find(p => p.slug === 'home') || props.storefront.pages?.[0]
  if (home) {
    activePage.value = home
    sections.value = JSON.parse(JSON.stringify(home.sections || []))
    if (sections.value.length > 0 && sections.value[0]) {
      selectedSectionId.value = sections.value[0].id
    }
  }
})

function addSection(type: Section['type']) {
  const newId = `${type}-${Date.now()}`
  let defaultProps: Record<string, any> = {}

  switch (type) {
    case 'hero':
      defaultProps = {
        headline: 'New Season Collection',
        subheadline: 'Crafted with premium materials and designed to last.',
        button_text: 'Shop Now',
        button_link: '#products',
        background_color: '#0f172a',
        text_color: '#ffffff',
        align: 'center',
      }
      break
    case 'product_grid':
      defaultProps = {
        title: 'Trending Products',
        subtitle: 'Our best-selling items picked for you',
        columns: 4,
        limit: 8,
        show_price: true,
        show_add_to_cart: true,
      }
      break
    case 'features':
      defaultProps = {
        title: 'The Quality Difference',
        items: [
          { icon: 'truck', title: 'Free Global Shipping', description: 'On orders over $50' },
          { icon: 'shield', title: '2-Year Warranty', description: 'Full replacement guarantee' },
          { icon: 'refresh-cw', title: 'Hassle-Free Returns', description: '30-day trial period' },
        ],
      }
      break
    case 'promo_banner':
      defaultProps = {
        headline: 'Flash Sale: 25% Off Storewide',
        badge: 'LIMITED TIME',
        code: 'FLASH25',
        description: 'Use coupon code during checkout for instant savings.',
        background_color: '#6366f1',
      }
      break
    case 'testimonials':
      defaultProps = {
        title: 'Customer Stories',
        items: [
          { quote: 'The best online shopping experience I have had all year!', author: 'Michael R.', rating: 5 },
          { quote: 'Exceptional craftsmanship. Exceeded all my expectations.', author: 'Emma T.', rating: 5 },
        ],
      }
      break
    case 'footer':
      defaultProps = {
        store_name: props.storefront.name,
        tagline: 'Your destination for premium goods.',
        contact_email: 'hello@store.test',
        show_socials: true,
      }
      break
  }

  sections.value.push({
    id: newId,
    type,
    props: defaultProps,
  })

  selectedSectionId.value = newId
  isAddingSection.value = false
}

function moveUp(index: number) {
  if (index <= 0) return
  const current = sections.value[index]
  const prev = sections.value[index - 1]
  if (!current || !prev) return
  sections.value[index] = prev
  sections.value[index - 1] = current
}

function moveDown(index: number) {
  if (index >= sections.value.length - 1) return
  const current = sections.value[index]
  const next = sections.value[index + 1]
  if (!current || !next) return
  sections.value[index] = next
  sections.value[index + 1] = current
}

function deleteSection(id: string) {
  sections.value = sections.value.filter(s => s.id !== id)
  if (selectedSectionId.value === id) {
    selectedSectionId.value = sections.value[0]?.id || null
  }
}

function duplicateSection(index: number) {
  const original = sections.value[index]
  if (!original) return
  const clone = JSON.parse(JSON.stringify(original))
  clone.id = `${original.type}-${Date.now()}`
  sections.value.splice(index + 1, 0, clone)
  selectedSectionId.value = clone.id
}

async function saveChanges() {
  if (!activePage.value) return
  isSaving.value = true
  try {
    const res = await api.put(
      `/ecommerce/storefronts/${props.storefront.id}/pages/${activePage.value.id}`,
      {
        sections: sections.value,
      }
    )
    toast.success('Storefront page layout saved successfully!')
  } catch (e: any) {
    toast.error(e?.response?.data?.message || 'Failed to save page layout.')
  } finally {
    isSaving.value = false
  }
}

function formatCents(cents: number) {
  return '$' + (cents / 100).toFixed(2)
}
</script>

<template>
  <div class="fixed inset-0 z-50 bg-slate-900 flex flex-col h-screen overflow-hidden text-slate-100 font-sans">
    <!-- Top Action Bar -->
    <header class="h-16 px-6 bg-slate-950 border-b border-slate-800 flex items-center justify-between shrink-0">
      <div class="flex items-center space-x-4">
        <button
          @click="emit('close')"
          class="text-xs px-3 py-1.5 rounded-lg border border-slate-700 hover:bg-slate-800 text-slate-300 transition-colors"
        >
          &larr; Back to Dashboard
        </button>
        <div>
          <h1 class="text-base font-bold text-white flex items-center space-x-2">
            <span>{{ storefront.name }}</span>
            <span class="text-xs px-2 py-0.5 rounded bg-primary-500/20 text-primary-400 border border-primary-500/30">
              Page Builder
            </span>
          </h1>
          <p class="text-xs font-mono text-slate-400">
            Public URL: /store/{{ storefront.slug }}
          </p>
        </div>
      </div>

      <!-- Viewport Device Switcher -->
      <div class="hidden md:flex items-center space-x-1 bg-slate-900 p-1 rounded-lg border border-slate-800">
        <button
          @click="viewMode = 'desktop'"
          :class="[viewMode === 'desktop' ? 'bg-primary-600 text-white' : 'text-slate-400 hover:text-white', 'p-1.5 rounded transition-colors']"
          title="Desktop View"
        >
          <Monitor class="w-4 h-4" />
        </button>
        <button
          @click="viewMode = 'tablet'"
          :class="[viewMode === 'tablet' ? 'bg-primary-600 text-white' : 'text-slate-400 hover:text-white', 'p-1.5 rounded transition-colors']"
          title="Tablet View"
        >
          <Tablet class="w-4 h-4" />
        </button>
        <button
          @click="viewMode = 'mobile'"
          :class="[viewMode === 'mobile' ? 'bg-primary-600 text-white' : 'text-slate-400 hover:text-white', 'p-1.5 rounded transition-colors']"
          title="Mobile View"
        >
          <Smartphone class="w-4 h-4" />
        </button>
      </div>

      <!-- Action Buttons -->
      <div class="flex items-center space-x-3">
        <router-link
          :to="`/store/${storefront.slug}`"
          target="_blank"
          class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 inline-flex items-center space-x-1.5 transition-colors"
        >
          <ExternalLink class="w-3.5 h-3.5" />
          <span>View Live Store</span>
        </router-link>

        <button
          @click="saveChanges"
          :disabled="isSaving"
          class="px-4 py-1.5 text-xs font-semibold rounded-lg bg-primary-600 hover:bg-primary-500 text-white shadow-lg shadow-primary-600/30 inline-flex items-center space-x-1.5 transition-colors disabled:opacity-50"
        >
          <Save class="w-3.5 h-3.5" />
          <span>{{ isSaving ? 'Saving...' : 'Publish & Save' }}</span>
        </button>
      </div>
    </header>

    <!-- Main Workspace (3-Column Layout) -->
    <div class="flex-1 flex overflow-hidden">
      <!-- Left Sidebar: Section Outline & Palette -->
      <div class="w-72 bg-slate-950 border-r border-slate-800 flex flex-col shrink-0">
        <div class="p-4 border-b border-slate-800 flex items-center justify-between">
          <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Page Structure</span>
          <button
            @click="isAddingSection = true"
            class="text-xs px-2 py-1 rounded bg-primary-600 hover:bg-primary-500 text-white flex items-center space-x-1 font-medium"
          >
            <Plus class="w-3 h-3" />
            <span>Add Block</span>
          </button>
        </div>

        <!-- Section list / reordering -->
        <div class="flex-1 overflow-y-auto p-3 space-y-2">
          <div
            v-for="(sec, idx) in sections"
            :key="sec.id"
            @click="selectedSectionId = sec.id"
            :class="[
              'p-2.5 rounded-lg border text-xs cursor-pointer transition-all flex items-center justify-between group',
              selectedSectionId === sec.id
                ? 'bg-primary-600/20 border-primary-500 text-white'
                : 'bg-slate-900 border-slate-800 text-slate-300 hover:border-slate-700'
            ]"
          >
            <div class="flex items-center space-x-2 truncate">
              <span class="font-mono text-slate-500 w-4">{{ idx + 1 }}</span>
              <span class="font-semibold capitalize truncate">{{ sec.type.replace('_', ' ') }}</span>
            </div>

            <!-- Controls -->
            <div class="flex items-center space-x-1 opacity-60 group-hover:opacity-100">
              <button
                @click.stop="moveUp(idx)"
                :disabled="idx === 0"
                class="p-1 hover:bg-slate-800 rounded disabled:opacity-20"
                title="Move up"
              >
                <ArrowUp class="w-3 h-3" />
              </button>
              <button
                @click.stop="moveDown(idx)"
                :disabled="idx === sections.length - 1"
                class="p-1 hover:bg-slate-800 rounded disabled:opacity-20"
                title="Move down"
              >
                <ArrowDown class="w-3 h-3" />
              </button>
              <button
                @click.stop="duplicateSection(idx)"
                class="p-1 hover:bg-slate-800 rounded"
                title="Duplicate"
              >
                <Copy class="w-3 h-3" />
              </button>
              <button
                @click.stop="deleteSection(sec.id)"
                class="p-1 hover:bg-red-900/50 hover:text-red-400 rounded"
                title="Delete"
              >
                <Trash2 class="w-3 h-3" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Center Live Preview Canvas -->
      <div class="flex-1 bg-slate-900/80 p-6 overflow-y-auto flex items-start justify-center">
        <div
          :class="[
            'bg-white text-slate-900 shadow-2xl transition-all duration-300 min-h-full rounded-lg overflow-hidden border border-slate-700',
            viewMode === 'desktop' ? 'w-full max-w-5xl' : (viewMode === 'tablet' ? 'w-[768px]' : 'w-[375px]')
          ]"
        >
          <!-- Top Store Announcement Bar -->
          <div
            v-if="storefront.theme_config?.show_banner"
            class="bg-indigo-600 text-white text-xs text-center py-2 px-4 font-medium"
          >
            {{ storefront.theme_config?.banner_text || 'Welcome to our store!' }}
          </div>

          <!-- Dynamic Rendered Sections -->
          <div v-for="sec in sections" :key="sec.id" :class="[selectedSectionId === sec.id ? 'ring-2 ring-primary-500 relative' : '']">
            <!-- 1. Hero Block -->
            <section
              v-if="sec.type === 'hero'"
              :style="{ backgroundColor: sec.props.background_color || '#0f172a', color: sec.props.text_color || '#ffffff' }"
              class="py-16 px-8 text-center space-y-4"
            >
              <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight">{{ sec.props.headline }}</h2>
              <p class="text-sm max-w-xl mx-auto opacity-90 leading-relaxed">{{ sec.props.subheadline }}</p>
              <div class="pt-2">
                <a
                  :href="sec.props.button_link || '#'"
                  class="inline-block px-6 py-2.5 rounded-full font-semibold text-sm bg-primary-600 text-white shadow hover:bg-primary-700 transition-colors"
                >
                  {{ sec.props.button_text || 'Shop Now' }}
                </a>
              </div>
            </section>

            <!-- 2. Features Block -->
            <section v-else-if="sec.type === 'features'" class="py-12 px-8 bg-slate-50 border-y border-slate-100">
              <h3 v-if="sec.props.title" class="text-xl font-bold text-center text-slate-900 mb-8">{{ sec.props.title }}</h3>
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 text-center">
                <div v-for="(feat, i) in sec.props.items" :key="i" class="p-4 bg-white rounded-xl shadow-sm border border-slate-100 space-y-2">
                  <div class="w-10 h-10 rounded-full bg-primary-50 text-primary-600 flex items-center justify-center mx-auto">
                    <Truck v-if="feat.icon === 'truck'" class="w-5 h-5" />
                    <ShieldCheck v-else-if="feat.icon === 'shield'" class="w-5 h-5" />
                    <RefreshCw v-else class="w-5 h-5" />
                  </div>
                  <h4 class="font-semibold text-sm text-slate-900">{{ feat.title }}</h4>
                  <p class="text-xs text-slate-500">{{ feat.description }}</p>
                </div>
              </div>
            </section>

            <!-- 3. Product Grid Block -->
            <section v-else-if="sec.type === 'product_grid'" id="products" class="py-14 px-8">
              <div class="text-center mb-8 space-y-1">
                <h3 class="text-2xl font-bold text-slate-900">{{ sec.props.title }}</h3>
                <p v-if="sec.props.subtitle" class="text-xs text-slate-500">{{ sec.props.subtitle }}</p>
              </div>

              <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div
                  v-for="prod in catalogProducts"
                  :key="prod.id"
                  class="bg-white border border-slate-200 rounded-xl p-3.5 space-y-2.5 shadow-sm hover:shadow-md transition-shadow"
                >
                  <div class="aspect-square bg-slate-100 rounded-lg flex items-center justify-center text-slate-300 font-mono text-xs">
                    {{ prod.sku }}
                  </div>
                  <div>
                    <span class="text-[10px] uppercase font-semibold text-primary-600 tracking-wider">{{ prod.category }}</span>
                    <h4 class="font-bold text-xs text-slate-900 truncate">{{ prod.name }}</h4>
                  </div>
                  <div class="flex items-center justify-between pt-1">
                    <span class="font-bold text-sm text-slate-900">{{ formatCents(prod.price_cents) }}</span>
                    <button class="px-2.5 py-1 text-xs font-semibold rounded bg-slate-900 text-white hover:bg-slate-800">
                      Add
                    </button>
                  </div>
                </div>
              </div>
            </section>

            <!-- 4. Promo Banner -->
            <section
              v-else-if="sec.type === 'promo_banner'"
              :style="{ backgroundColor: sec.props.background_color || '#6366f1' }"
              class="py-10 px-8 text-white text-center space-y-2"
            >
              <span class="inline-block px-2.5 py-0.5 text-[10px] font-bold rounded-full bg-white/20 uppercase tracking-widest">
                {{ sec.props.badge || 'PROMO' }}
              </span>
              <h3 class="text-2xl font-extrabold">{{ sec.props.headline }}</h3>
              <p class="text-xs max-w-md mx-auto opacity-90">{{ sec.props.description }}</p>
              <div v-if="sec.props.code" class="pt-2">
                <span class="px-3 py-1 font-mono text-xs font-bold rounded bg-white text-slate-900">
                  CODE: {{ sec.props.code }}
                </span>
              </div>
            </section>

            <!-- 5. Testimonials -->
            <section v-else-if="sec.type === 'testimonials'" class="py-12 px-8 bg-slate-50">
              <h3 class="text-xl font-bold text-center text-slate-900 mb-6">{{ sec.props.title }}</h3>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div v-for="(t, idx) in sec.props.items" :key="idx" class="p-4 bg-white rounded-xl border border-slate-200 space-y-2">
                  <div class="flex text-amber-400">
                    <Star v-for="s in (t.rating || 5)" :key="s" class="w-3.5 h-3.5 fill-current" />
                  </div>
                  <p class="text-xs text-slate-700 italic">"{{ t.quote }}"</p>
                  <p class="text-xs font-bold text-slate-900">— {{ t.author }}</p>
                </div>
              </div>
            </section>

            <!-- 6. Footer -->
            <footer v-else-if="sec.type === 'footer'" class="py-8 px-8 bg-slate-900 text-slate-400 text-center text-xs space-y-2">
              <p class="font-bold text-white text-sm">{{ sec.props.store_name }}</p>
              <p>{{ sec.props.tagline }}</p>
              <p class="pt-2 text-[10px] text-slate-500">&copy; 2026 {{ sec.props.store_name }}. All rights reserved.</p>
            </footer>
          </div>
        </div>
      </div>

      <!-- Right Sidebar: Section Inspector / Properties -->
      <div class="w-80 bg-slate-950 border-l border-slate-800 flex flex-col shrink-0 overflow-y-auto p-4 space-y-4">
        <div v-if="!selectedSection" class="text-center py-16 text-slate-500 text-xs">
          Select any block from the left outline or center preview to customize its settings.
        </div>

        <div v-else class="space-y-4">
          <div class="border-b border-slate-800 pb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-primary-400">Block Settings</span>
            <h3 class="text-sm font-bold text-white capitalize">{{ selectedSection.type.replace('_', ' ') }}</h3>
          </div>

          <!-- Hero Inspector -->
          <div v-if="selectedSection.type === 'hero'" class="space-y-3 text-xs">
            <div class="space-y-1">
              <label class="font-semibold text-slate-300">Headline</label>
              <input v-model="selectedSection.props.headline" class="w-full px-2.5 py-1.5 bg-slate-900 border border-slate-700 rounded text-slate-200 text-xs" />
            </div>
            <div class="space-y-1">
              <label class="font-semibold text-slate-300">Subheadline</label>
              <textarea v-model="selectedSection.props.subheadline" rows="2" class="w-full px-2.5 py-1.5 bg-slate-900 border border-slate-700 rounded text-slate-200 text-xs"></textarea>
            </div>
            <div class="space-y-1">
              <label class="font-semibold text-slate-300">Button Text</label>
              <input v-model="selectedSection.props.button_text" class="w-full px-2.5 py-1.5 bg-slate-900 border border-slate-700 rounded text-slate-200 text-xs" />
            </div>
            <div class="space-y-1">
              <label class="font-semibold text-slate-300">Background Color</label>
              <input v-model="selectedSection.props.background_color" type="color" class="w-full h-8 bg-slate-900 border border-slate-700 rounded cursor-pointer" />
            </div>
          </div>

          <!-- Product Grid Inspector -->
          <div v-else-if="selectedSection.type === 'product_grid'" class="space-y-3 text-xs">
            <div class="space-y-1">
              <label class="font-semibold text-slate-300">Section Title</label>
              <input v-model="selectedSection.props.title" class="w-full px-2.5 py-1.5 bg-slate-900 border border-slate-700 rounded text-slate-200 text-xs" />
            </div>
            <div class="space-y-1">
              <label class="font-semibold text-slate-300">Subtitle</label>
              <input v-model="selectedSection.props.subtitle" class="w-full px-2.5 py-1.5 bg-slate-900 border border-slate-700 rounded text-slate-200 text-xs" />
            </div>
            <div class="space-y-1">
              <label class="font-semibold text-slate-300">Grid Columns</label>
              <select v-model.number="selectedSection.props.columns" class="w-full px-2.5 py-1.5 bg-slate-900 border border-slate-700 rounded text-slate-200 text-xs">
                <option :value="2">2 Columns</option>
                <option :value="3">3 Columns</option>
                <option :value="4">4 Columns</option>
              </select>
            </div>
          </div>

          <!-- Promo Banner Inspector -->
          <div v-else-if="selectedSection.type === 'promo_banner'" class="space-y-3 text-xs">
            <div class="space-y-1">
              <label class="font-semibold text-slate-300">Headline</label>
              <input v-model="selectedSection.props.headline" class="w-full px-2.5 py-1.5 bg-slate-900 border border-slate-700 rounded text-slate-200 text-xs" />
            </div>
            <div class="space-y-1">
              <label class="font-semibold text-slate-300">Coupon Code</label>
              <input v-model="selectedSection.props.code" class="w-full px-2.5 py-1.5 bg-slate-900 border border-slate-700 rounded text-slate-200 text-xs font-mono uppercase" />
            </div>
            <div class="space-y-1">
              <label class="font-semibold text-slate-300">Background Color</label>
              <input v-model="selectedSection.props.background_color" type="color" class="w-full h-8 bg-slate-900 border border-slate-700 rounded cursor-pointer" />
            </div>
          </div>

          <!-- Footer Inspector -->
          <div v-else-if="selectedSection.type === 'footer'" class="space-y-3 text-xs">
            <div class="space-y-1">
              <label class="font-semibold text-slate-300">Store Name</label>
              <input v-model="selectedSection.props.store_name" class="w-full px-2.5 py-1.5 bg-slate-900 border border-slate-700 rounded text-slate-200 text-xs" />
            </div>
            <div class="space-y-1">
              <label class="font-semibold text-slate-300">Brand Tagline</label>
              <input v-model="selectedSection.props.tagline" class="w-full px-2.5 py-1.5 bg-slate-900 border border-slate-700 rounded text-slate-200 text-xs" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Block Modal -->
    <div
      v-if="isAddingSection"
      class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4"
    >
      <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-lg w-full p-6 space-y-4 shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
          <h3 class="text-base font-bold text-white">Add Page Block</h3>
          <button @click="isAddingSection = false" class="text-slate-400 hover:text-white text-xs font-bold">✕</button>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div
            v-for="b in availableBlockTypes"
            :key="b.type"
            @click="addSection(b.type)"
            class="p-3.5 rounded-xl border border-slate-800 bg-slate-950 hover:border-primary-500 hover:bg-primary-600/10 cursor-pointer transition-all space-y-1.5 group text-left"
          >
            <div class="w-8 h-8 rounded-lg bg-primary-500/10 text-primary-400 flex items-center justify-center group-hover:scale-110 transition-transform">
              <component :is="b.icon" class="w-4 h-4" />
            </div>
            <h4 class="font-bold text-xs text-white">{{ b.label }}</h4>
            <p class="text-[10px] text-slate-400 leading-snug">{{ b.description }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
