<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import api from '@/api/client'
import { useToast } from '@/composables/useToast'
import StorefrontBuilder from '../components/StorefrontBuilder.vue'
import FulfillmentModal from '../components/FulfillmentModal.vue'
import {
  Globe,
  ShoppingCart,
  RefreshCw,
  Plus,
  ExternalLink,
  Trash2,
  Sliders,
  CheckCircle,
  Clock,
  Sparkles,
  Layout,
  Layers,
  Palette,
  Eye,
  Truck,
} from '@lucide/vue'

interface EcommerceChannel {
  id: string
  name: string
  platform: 'shopify' | 'woocommerce' | 'amazon' | 'custom'
  is_active: boolean
  last_sync_at: string | null
  created_at: string
}

interface EcommerceOrder {
  id: string
  order_number: string
  customer_name: string
  customer_email: string
  total_cents: number
  currency: string
  payment_status: string
  fulfillment_status: string
  tracking_number?: string
  shipping_carrier?: string
  notes?: string
  created_at: string
  channel?: { name: string }
}

interface Storefront {
  id: string
  name: string
  slug: string
  title: string | null
  description: string | null
  theme_config: Record<string, any>
  is_published: boolean
  created_at: string
  pages?: any[]
}

const toast = useToast()
const channels = ref<EcommerceChannel[]>([])
const orders = ref<EcommerceOrder[]>([])
const storefronts = ref<Storefront[]>([])
const loading = ref(true)
const activeTab = ref<'storefronts' | 'orders' | 'channels'>('storefronts')

// Active storefront in builder modal
const activeBuildingStorefront = ref<Storefront | null>(null)

// Create store modal
const isCreateModalOpen = ref(false)
const isCreatingStore = ref(false)
const newStoreName = ref('')
const newStoreTitle = ref('')

// Connect channel modal
const isConnectModalOpen = ref(false)
const isConnecting = ref(false)
const newChannel = ref({
  name: '',
  platform: 'shopify' as 'shopify' | 'woocommerce' | 'amazon' | 'custom',
  store_url: '',
  api_key: '',
  api_secret: '',
})

// Fulfillment modal
const isFulfillmentModalOpen = ref(false)
const selectedOrder = ref<EcommerceOrder | null>(null)

function openFulfillmentModal(order: EcommerceOrder) {
  selectedOrder.value = order
  isFulfillmentModalOpen.value = true
}

async function onFulfilled() {
  await fetchData()
}

async function fetchData() {
  loading.value = true
  try {
    const [channelsRes, ordersRes, storefrontsRes] = await Promise.all([
      api.get('/ecommerce/channels'),
      api.get('/ecommerce/orders'),
      api.get('/ecommerce/storefronts'),
    ])
    channels.value = channelsRes.data?.data || channelsRes.data || []
    const rawOrders = ordersRes.data?.data || ordersRes.data || []
    orders.value = Array.isArray(rawOrders) ? rawOrders : rawOrders.data || []
    storefronts.value = storefrontsRes.data?.data || storefrontsRes.data || []
  } catch (e: any) {
    console.error('Failed to load ecommerce data', e)
  } finally {
    loading.value = false
  }
}

async function createStorefront() {
  if (!newStoreName.value) return
  isCreatingStore.value = true
  try {
    const res = await api.post('/ecommerce/storefronts', {
      name: newStoreName.value,
      title: newStoreTitle.value || newStoreName.value,
    })
    toast.success('Storefront site created!')
    newStoreName.value = ''
    newStoreTitle.value = ''
    isCreateModalOpen.value = false
    await fetchData()
    const created = res.data?.data || res.data
    if (created) {
      activeBuildingStorefront.value = created
    }
  } catch (e: any) {
    toast.error(e?.response?.data?.message || 'Failed to create storefront.')
  } finally {
    isCreatingStore.value = false
  }
}

async function togglePublish(store: Storefront) {
  try {
    await api.put(`/ecommerce/storefronts/${store.id}`, {
      is_published: !store.is_published,
    })
    store.is_published = !store.is_published
    toast.success(store.is_published ? 'Store published live!' : 'Store unpublished.')
  } catch (e: any) {
    toast.error('Failed to update storefront status.')
  }
}

async function deleteStorefront(id: string) {
  if (!confirm('Are you sure you want to delete this storefront website?')) return
  try {
    await api.delete(`/ecommerce/storefronts/${id}`)
    toast.success('Storefront deleted.')
    await fetchData()
  } catch (e: any) {
    toast.error('Failed to delete storefront.')
  }
}

async function triggerSync(id: string) {
  try {
    await api.post(`/ecommerce/channels/${id}/sync`)
    toast.success('Order sync triggered successfully')
    await fetchData()
  } catch (e: any) {
    toast.error(e?.response?.data?.message || 'Sync failed')
  }
}

async function connectChannel() {
  if (!newChannel.value.name) return
  isConnecting.value = true
  try {
    await api.post('/ecommerce/channels', newChannel.value)
    toast.success('Channel connected successfully')
    isConnectModalOpen.value = false
    newChannel.value = { name: '', platform: 'shopify', store_url: '', api_key: '', api_secret: '' }
    await fetchData()
  } catch (e: any) {
    toast.error(e?.response?.data?.message || 'Failed to connect channel')
  } finally {
    isConnecting.value = false
  }
}

function formatCents(cents: number, currency: string = 'USD') {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currency || 'USD',
  }).format(cents / 100)
}

const fulfillmentColors: Record<string, string> = {
  unfulfilled: 'bg-yellow-50 text-yellow-700 border-yellow-200',
  fulfilled: 'bg-green-50 text-green-700 border-green-200',
  shipped: 'bg-blue-50 text-blue-700 border-blue-200',
  cancelled: 'bg-red-50 text-red-700 border-red-200',
}

onMounted(fetchData)
</script>

<template>
  <div class="space-y-6 p-4 sm:p-6 max-w-7xl mx-auto">
    <!-- Header Section (Mobile Responsive) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900">E-Commerce & Storefronts</h1>
        <p class="text-xs sm:text-sm text-gray-500 mt-0.5">
          Build drag-and-drop customer websites, manage multi-channel orders, and sync integrations.
        </p>
      </div>

      <div class="flex items-center space-x-2 sm:space-x-3 shrink-0">
        <button
          v-if="activeTab === 'storefronts'"
          @click="isCreateModalOpen = true"
          class="w-full sm:w-auto px-4 py-2 bg-primary-600 hover:bg-primary-500 text-white font-semibold text-xs sm:text-sm rounded-xl shadow-sm transition-all flex items-center justify-center space-x-2"
        >
          <Plus class="w-4 h-4" />
          <span>New Storefront Site</span>
        </button>

        <button
          v-if="activeTab === 'channels'"
          @click="isConnectModalOpen = true"
          class="w-full sm:w-auto px-4 py-2 bg-primary-600 hover:bg-primary-500 text-white font-semibold text-xs sm:text-sm rounded-xl shadow-sm transition-all flex items-center justify-center space-x-2"
        >
          <Plus class="w-4 h-4" />
          <span>Connect Channel</span>
        </button>
      </div>
    </div>

    <!-- Navigation Tabs (Mobile Horizontally Scrollable) -->
    <div class="border-b border-gray-200 overflow-x-auto scrollbar-none">
      <nav class="-mb-px flex space-x-6 sm:space-x-8 min-w-max">
        <button
          @click="activeTab = 'storefronts'"
          :class="[activeTab === 'storefronts' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'whitespace-nowrap py-3 sm:py-4 px-1 border-b-2 font-semibold text-xs sm:text-sm flex items-center space-x-2']"
        >
          <Sparkles class="w-4 h-4" />
          <span>My Storefront Sites ({{ storefronts.length }})</span>
        </button>

        <button
          @click="activeTab = 'orders'"
          :class="[activeTab === 'orders' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'whitespace-nowrap py-3 sm:py-4 px-1 border-b-2 font-semibold text-xs sm:text-sm flex items-center space-x-2']"
        >
          <ShoppingCart class="w-4 h-4" />
          <span>Orders ({{ orders.length }})</span>
        </button>

        <button
          @click="activeTab = 'channels'"
          :class="[activeTab === 'channels' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'whitespace-nowrap py-3 sm:py-4 px-1 border-b-2 font-semibold text-xs sm:text-sm flex items-center space-x-2']"
        >
          <Globe class="w-4 h-4" />
          <span>External Connectors ({{ channels.length }})</span>
        </button>
      </nav>
    </div>

    <div v-if="loading" class="text-center py-12 text-gray-500 text-sm">Loading e-commerce data…</div>

    <!-- 1. Storefronts Builder Tab -->
    <div v-else-if="activeTab === 'storefronts'" class="space-y-6">
      <div v-if="storefronts.length === 0" class="text-center py-12 sm:py-16 bg-white rounded-2xl border border-gray-200 p-6 space-y-4">
        <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-primary-50 text-primary-600 flex items-center justify-center mx-auto">
          <Sparkles class="w-7 h-7 sm:w-8 sm:h-8" />
        </div>
        <div class="space-y-1">
          <h3 class="text-base sm:text-lg font-bold text-gray-900">Build Your First Online Store</h3>
          <p class="text-xs sm:text-sm text-gray-500 max-w-md mx-auto">
            Design a public e-commerce store with drag-and-drop editable blocks (Hero banners, product grids, promo deals, testimonials).
          </p>
        </div>
        <button
          @click="isCreateModalOpen = true"
          class="px-5 py-2.5 bg-primary-600 hover:bg-primary-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-primary-600/30 transition-all inline-flex items-center space-x-2"
        >
          <Plus class="w-4 h-4" />
          <span>Launch Drag-and-Drop Storefront</span>
        </button>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
        <div
          v-for="store in storefronts"
          :key="store.id"
          class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-sm hover:shadow-md transition-all flex flex-col justify-between"
        >
          <!-- Card Header -->
          <div class="p-5 sm:p-6 space-y-3 sm:space-y-4">
            <div class="flex items-start justify-between gap-2">
              <div class="space-y-1 min-w-0">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-700 truncate max-w-full">
                  /store/{{ store.slug }}
                </span>
                <h3 class="text-base sm:text-lg font-bold text-gray-900 truncate">{{ store.name }}</h3>
              </div>
              <button
                @click="togglePublish(store)"
                :class="[
                  'px-2.5 py-1 rounded-full text-xs font-bold transition-colors shrink-0',
                  store.is_published ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600'
                ]"
              >
                {{ store.is_published ? '● Live' : '○ Draft' }}
              </button>
            </div>

            <p class="text-xs text-gray-500 line-clamp-2">
              {{ store.description || 'Custom drag-and-drop online storefront for catalog products.' }}
            </p>

            <div class="p-3 bg-slate-50 rounded-xl space-y-1.5 text-xs text-slate-600">
              <div class="flex items-center justify-between">
                <span class="text-slate-400">Pages:</span>
                <span class="font-bold">{{ store.pages?.length || 1 }} (Home)</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-slate-400">Theme:</span>
                <span class="font-mono text-xs">{{ store.theme_config?.primary_color || '#4f46e5' }}</span>
              </div>
            </div>
          </div>

          <!-- Card Actions -->
          <div class="p-3 sm:p-4 bg-slate-50 border-t border-gray-100 flex items-center justify-between gap-2">
            <button
              @click="activeBuildingStorefront = store"
              class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-lg bg-primary-600 hover:bg-primary-500 text-white text-xs font-bold shadow-sm transition-colors"
            >
              <Layout class="w-3.5 h-3.5" />
              <span>Edit Pages (Builder)</span>
            </button>

            <div class="flex items-center space-x-1.5 sm:space-x-2">
              <router-link
                :to="`/store/${store.slug}`"
                target="_blank"
                class="p-2 rounded-lg border border-gray-200 hover:bg-white text-gray-600 hover:text-gray-900 transition-colors"
                title="View live store"
              >
                <ExternalLink class="w-4 h-4" />
              </router-link>

              <button
                @click="deleteStorefront(store.id)"
                class="p-2 rounded-lg border border-gray-200 hover:bg-red-50 text-gray-400 hover:text-red-600 transition-colors"
                title="Delete"
              >
                <Trash2 class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 2. Orders Tab -->
    <div v-else-if="activeTab === 'orders'">
      <div v-if="orders.length === 0" class="text-center py-12 sm:py-16 bg-white rounded-2xl border border-gray-200 p-6">
        <ShoppingCart class="w-12 h-12 text-gray-300 mx-auto mb-3" />
        <p class="text-gray-600 font-medium text-sm">No online orders placed yet</p>
        <p class="text-xs text-gray-400 mt-1">Orders placed through your public storefronts will appear here automatically.</p>
      </div>

      <!-- Mobile Card List (< md) -->
      <div class="md:hidden space-y-3">
        <div
          v-for="ord in orders"
          :key="ord.id"
          class="bg-white rounded-xl border border-gray-200 p-4 space-y-3 shadow-sm"
        >
          <div class="flex items-center justify-between">
            <span class="font-mono font-bold text-xs text-gray-900">{{ ord.order_number }}</span>
            <span class="text-[10px] text-gray-400">{{ ord.created_at?.substring(0, 10) }}</span>
          </div>

          <div class="flex items-center justify-between text-xs">
            <div>
              <p class="font-semibold text-gray-900">{{ ord.customer_name }}</p>
              <p class="text-[10px] text-gray-500">{{ ord.customer_email }}</p>
            </div>
            <span class="text-sm font-extrabold text-gray-900">{{ formatCents(ord.total_cents, ord.currency) }}</span>
          </div>

          <div class="flex items-center justify-between pt-2 border-t border-gray-100 text-[10px]">
            <div class="flex items-center gap-1.5">
              <span class="px-2 py-0.5 rounded-full bg-green-50 text-green-700 font-semibold uppercase">
                {{ ord.payment_status }}
              </span>
              <span class="px-2 py-0.5 rounded-full font-semibold border uppercase" :class="fulfillmentColors[ord.fulfillment_status] ?? 'bg-gray-100'">
                {{ ord.fulfillment_status }}
              </span>
            </div>
            <button
              @click="openFulfillmentModal(ord)"
              class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 text-[10px] font-bold transition-colors"
            >
              <Truck class="w-3 h-3" />
              Fulfill
            </button>
          </div>
        </div>
      </div>

      <!-- Desktop Table (md+) -->
      <div class="hidden md:block bg-white shadow-sm rounded-xl overflow-hidden border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order #</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Channel / Store</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fulfillment</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 text-sm">
            <tr v-for="ord in orders" :key="ord.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 font-mono font-medium text-gray-900">{{ ord.order_number }}</td>
              <td class="px-6 py-4 text-gray-600">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-gray-100 font-medium">
                  {{ ord.channel?.name ?? 'Storefront' }}
                </span>
              </td>
              <td class="px-6 py-4">
                <p class="font-medium text-gray-900">{{ ord.customer_name }}</p>
                <p v-if="ord.customer_email" class="text-xs text-gray-400">{{ ord.customer_email }}</p>
              </td>
              <td class="px-6 py-4 font-semibold text-gray-900">{{ formatCents(ord.total_cents, ord.currency) }}</td>
              <td class="px-6 py-4">
                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700">
                  {{ ord.payment_status }}
                </span>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-1.5">
                  <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium border" :class="fulfillmentColors[ord.fulfillment_status] ?? 'bg-gray-100'">
                    {{ ord.fulfillment_status }}
                  </span>
                  <span v-if="ord.tracking_number" class="text-[10px] text-gray-400 font-mono" :title="'Tracking: ' + ord.tracking_number">
                    📦 {{ ord.tracking_number.substring(0, 12) }}{{ ord.tracking_number.length > 12 ? '…' : '' }}
                  </span>
                </div>
              </td>
              <td class="px-6 py-4 text-gray-400">{{ ord.created_at?.substring(0, 10) }}</td>
              <td class="px-6 py-4 text-right">
                <button
                  @click="openFulfillmentModal(ord)"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-colors"
                  :class="[
                    ord.fulfillment_status === 'fulfilled'
                      ? 'bg-gray-100 text-gray-500 hover:bg-gray-200'
                      : 'bg-blue-50 text-blue-700 hover:bg-blue-100'
                  ]"
                >
                  <Truck class="w-3.5 h-3.5" />
                  {{ ord.fulfillment_status === 'fulfilled' ? 'Update' : 'Fulfill' }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- 3. External Channels Tab -->
    <div v-else-if="activeTab === 'channels'">
      <div v-if="channels.length === 0" class="text-center py-12 sm:py-16 bg-white rounded-2xl border border-gray-200 p-6">
        <Globe class="w-12 h-12 text-gray-300 mx-auto mb-3" />
        <p class="text-gray-600 font-medium text-sm">No external marketplace channels connected</p>
        <p class="text-xs text-gray-400 mt-1 mb-4">Connect external channels like Shopify, WooCommerce, or Amazon to sync inventory & orders.</p>
        <button
          @click="isConnectModalOpen = true"
          class="px-4 py-2 bg-primary-600 hover:bg-primary-500 text-white font-medium text-xs rounded-xl shadow transition-colors"
        >
          Connect Shopify or WooCommerce
        </button>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
        <div
          v-for="ch in channels"
          :key="ch.id"
          class="bg-white rounded-2xl border border-gray-200 p-5 sm:p-6 flex flex-col justify-between shadow-sm space-y-4"
        >
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold uppercase tracking-wider text-primary-600">{{ ch.platform }}</span>
              <span
                :class="[ch.is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-600', 'px-2 py-0.5 rounded-full text-[10px] font-bold']"
              >
                {{ ch.is_active ? 'Active' : 'Inactive' }}
              </span>
            </div>
            <h3 class="text-base font-bold text-gray-900">{{ ch.name }}</h3>
            <p class="text-xs text-gray-400">
              Last synced: {{ ch.last_sync_at ? new Date(ch.last_sync_at).toLocaleString() : 'Never' }}
            </p>
          </div>

          <div class="pt-3 border-t border-gray-100 flex items-center justify-between">
            <button
              @click="triggerSync(ch.id)"
              class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-lg border border-gray-200 hover:bg-gray-50 text-gray-700 text-xs font-semibold"
            >
              <RefreshCw class="w-3.5 h-3.5" />
              <span>Sync Orders</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Storefront Modal (Mobile Responsive) -->
    <div
      v-if="isCreateModalOpen"
      class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4 overflow-y-auto"
    >
      <div class="bg-white rounded-2xl max-w-md w-full p-5 sm:p-6 space-y-4 shadow-2xl my-auto">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
          <h3 class="text-base font-bold text-gray-900">Create New Storefront</h3>
          <button @click="isCreateModalOpen = false" class="text-gray-400 hover:text-gray-700 p-1">✕</button>
        </div>

        <form @submit.prevent="createStorefront" class="space-y-3.5 text-xs sm:text-sm">
          <div class="space-y-1">
            <label class="font-semibold text-gray-700">Storefront Brand Name *</label>
            <input
              v-model="newStoreName"
              required
              placeholder="e.g. Apex Apparel Store"
              class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500/20 text-xs sm:text-sm"
            />
          </div>

          <div class="space-y-1">
            <label class="font-semibold text-gray-700">Headline Title</label>
            <input
              v-model="newStoreTitle"
              placeholder="e.g. Premium Sustainable Clothing"
              class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500/20 text-xs sm:text-sm"
            />
          </div>

          <button
            type="submit"
            :disabled="isCreatingStore"
            class="w-full py-2.5 rounded-xl bg-primary-600 hover:bg-primary-500 text-white font-bold text-xs sm:text-sm shadow-md transition-all disabled:opacity-50"
          >
            {{ isCreatingStore ? 'Creating Storefront...' : 'Create & Open Visual Builder' }}
          </button>
        </form>
      </div>
    </div>

    <!-- Connect Channel Modal (Mobile Responsive) -->
    <div
      v-if="isConnectModalOpen"
      class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4 overflow-y-auto"
    >
      <div class="bg-white rounded-2xl max-w-md w-full p-5 sm:p-6 space-y-4 shadow-2xl my-auto">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
          <h3 class="text-base font-bold text-gray-900">Connect Marketplace Channel</h3>
          <button @click="isConnectModalOpen = false" class="text-gray-400 hover:text-gray-700 p-1">✕</button>
        </div>

        <form @submit.prevent="connectChannel" class="space-y-3 text-xs sm:text-sm">
          <div class="space-y-1">
            <label class="font-semibold text-gray-700">Platform</label>
            <select v-model="newChannel.platform" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs sm:text-sm">
              <option value="shopify">Shopify</option>
              <option value="woocommerce">WooCommerce</option>
              <option value="amazon">Amazon</option>
              <option value="custom">Custom Webhook</option>
            </select>
          </div>

          <div class="space-y-1">
            <label class="font-semibold text-gray-700">Channel Name *</label>
            <input v-model="newChannel.name" required placeholder="e.g. US Shopify Store" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs sm:text-sm" />
          </div>

          <div class="space-y-1">
            <label class="font-semibold text-gray-700">Store URL</label>
            <input v-model="newChannel.store_url" placeholder="https://store.myshopify.com" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs sm:text-sm" />
          </div>

          <button
            type="submit"
            :disabled="isConnecting"
            class="w-full py-2.5 rounded-xl bg-primary-600 hover:bg-primary-500 text-white font-bold text-xs sm:text-sm shadow-md transition-all disabled:opacity-50"
          >
            {{ isConnecting ? 'Connecting...' : 'Save & Connect Channel' }}
          </button>
        </form>
      </div>
    </div>

    <!-- Fulfillment Modal -->
    <FulfillmentModal
      v-model="isFulfillmentModalOpen"
      :order="selectedOrder"
      @fulfilled="onFulfilled"
    />

    <!-- Fullscreen Drag & Drop Page Builder Overlay -->
    <StorefrontBuilder
      v-if="activeBuildingStorefront"
      :storefront="activeBuildingStorefront"
      @updated="fetchData"
      @close="activeBuildingStorefront = null; fetchData()"
    />
  </div>
</template>
