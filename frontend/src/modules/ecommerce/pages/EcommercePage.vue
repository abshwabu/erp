<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/api/client'
import { useToast } from '@/composables/useToast'
import StorefrontBuilder from '../components/StorefrontBuilder.vue'
import {
  Globe,
  RefreshCw,
  ShoppingCart,
  CheckCircle,
  Package,
  Sparkles,
  ExternalLink,
  Edit3,
  Plus,
  Trash2,
  Layout,
  Store,
} from '@lucide/vue'

interface EcommerceOrder {
  id: string
  order_number: string
  customer_name: string
  customer_email: string | null
  total_cents: number
  currency: string
  payment_status: string
  fulfillment_status: string
  created_at: string
  channel?: { name: string; platform: string }
}

interface EcommerceChannel {
  id: string
  name: string
  platform: string
  store_url: string | null
  is_active: boolean
  last_sync_at: string | null
  orders_count?: number
}

interface Storefront {
  id: string
  name: string
  slug: string
  title: string | null
  description: string | null
  theme_config: Record<string, any>
  is_published: boolean
  pages?: any[]
  created_at?: string
}

const toast = useToast()
const channels = ref<EcommerceChannel[]>([])
const orders = ref<EcommerceOrder[]>([])
const storefronts = ref<Storefront[]>([])
const loading = ref(true)
const activeTab = ref<'storefronts' | 'orders' | 'channels'>('storefronts')

// Builder overlay state
const activeBuildingStorefront = ref<Storefront | null>(null)

// Create Storefront modal state
const isCreateModalOpen = ref(false)
const newStoreName = ref('')
const newStoreTitle = ref('')
const isCreatingStore = ref(false)

const fulfillmentColors: Record<string, string> = {
  unfulfilled: 'bg-amber-50 text-amber-700 border-amber-200',
  fulfilled: 'bg-green-50 text-green-700 border-green-200',
  cancelled: 'bg-red-50 text-red-700 border-red-200',
}

async function fetchData() {
  loading.value = true
  try {
    const [chanRes, ordRes, storeRes] = await Promise.all([
      api.get('/ecommerce/channels'),
      api.get('/ecommerce/orders'),
      api.get('/ecommerce/storefronts'),
    ])
    channels.value = chanRes.data?.data ?? chanRes.data ?? []
    orders.value = ordRes.data?.data?.data ?? ordRes.data?.data ?? []
    storefronts.value = storeRes.data?.data ?? storeRes.data ?? []
  } catch (e) {
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
    // Open builder immediately
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
    await fetchData()
    toast.success('Channel orders synced successfully.')
  } catch (e) {
    toast.error('Failed to sync channel.')
  }
}

function formatCents(cents: number, cur = 'USD') {
  return '$' + (cents / 100).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ' + cur
}

onMounted(fetchData)
</script>

<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <div class="flex items-center space-x-3">
        <Globe class="w-7 h-7 text-primary-600" />
        <div>
          <h1 class="text-2xl font-bold text-gray-900">E-Commerce & Online Store Builder</h1>
          <p class="text-xs text-gray-500">Create, customize, and manage customer-facing online stores with drag-and-drop pages</p>
        </div>
      </div>

      <button
        v-if="activeTab === 'storefronts'"
        @click="isCreateModalOpen = true"
        class="inline-flex items-center space-x-2 px-4 py-2 bg-primary-600 hover:bg-primary-500 text-white text-xs font-bold rounded-lg shadow-sm transition-colors"
      >
        <Plus class="w-4 h-4" />
        <span>Create Storefront Site</span>
      </button>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200">
      <nav class="-mb-px flex space-x-8">
        <button
          @click="activeTab = 'storefronts'"
          :class="[activeTab === 'storefronts' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center space-x-2']"
        >
          <Store class="w-4 h-4" />
          <span>My Storefront Sites ({{ storefronts.length }})</span>
        </button>
        <button
          @click="activeTab = 'orders'"
          :class="[activeTab === 'orders' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center space-x-2']"
        >
          <ShoppingCart class="w-4 h-4" />
          <span>Customer Orders ({{ orders.length }})</span>
        </button>
        <button
          @click="activeTab = 'channels'"
          :class="[activeTab === 'channels' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center space-x-2']"
        >
          <Globe class="w-4 h-4" />
          <span>External Connectors ({{ channels.length }})</span>
        </button>
      </nav>
    </div>

    <div v-if="loading" class="text-center py-12 text-gray-500">Loading e-commerce data…</div>

    <!-- 1. Storefronts Builder Tab -->
    <div v-else-if="activeTab === 'storefronts'" class="space-y-6">
      <div v-if="storefronts.length === 0" class="text-center py-16 bg-white rounded-2xl border border-gray-200 space-y-4">
        <div class="w-16 h-16 rounded-2xl bg-primary-50 text-primary-600 flex items-center justify-center mx-auto">
          <Sparkles class="w-8 h-8" />
        </div>
        <div class="space-y-1">
          <h3 class="text-lg font-bold text-gray-900">Build Your First Online Store</h3>
          <p class="text-sm text-gray-500 max-w-md mx-auto">
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

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="store in storefronts"
          :key="store.id"
          class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-sm hover:shadow-md transition-all flex flex-col justify-between"
        >
          <!-- Card Header -->
          <div class="p-6 space-y-4">
            <div class="flex items-start justify-between">
              <div class="space-y-1">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-700">
                  /store/{{ store.slug }}
                </span>
                <h3 class="text-lg font-bold text-gray-900">{{ store.name }}</h3>
              </div>
              <button
                @click="togglePublish(store)"
                :class="[
                  'px-2.5 py-1 rounded-full text-xs font-bold transition-colors',
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
          <div class="p-4 bg-slate-50 border-t border-gray-100 flex items-center justify-between">
            <button
              @click="activeBuildingStorefront = store"
              class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-lg bg-primary-600 hover:bg-primary-500 text-white text-xs font-bold shadow-sm transition-colors"
            >
              <Layout class="w-3.5 h-3.5" />
              <span>Edit Pages (Builder)</span>
            </button>

            <div class="flex items-center space-x-2">
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
      <div v-if="orders.length === 0" class="text-center py-16 bg-white rounded-lg border border-gray-200">
        <ShoppingCart class="w-12 h-12 text-gray-300 mx-auto mb-3" />
        <p class="text-gray-500 font-medium">No online orders placed yet</p>
        <p class="text-sm text-gray-400 mt-1">Orders placed through your public storefronts will appear here automatically.</p>
      </div>
      <div v-else class="bg-white shadow rounded-lg overflow-hidden border border-gray-200">
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
                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium border" :class="fulfillmentColors[ord.fulfillment_status] ?? 'bg-gray-100'">
                  {{ ord.fulfillment_status }}
                </span>
              </td>
              <td class="px-6 py-4 text-gray-400">{{ ord.created_at?.substring(0, 10) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- 3. External Channels Tab -->
    <div v-else-if="activeTab === 'channels'">
      <div v-if="channels.length === 0" class="text-center py-16 bg-white rounded-lg border border-gray-200">
        <Globe class="w-12 h-12 text-gray-300 mx-auto mb-3" />
        <p class="text-gray-500 font-medium">No external sales channels connected</p>
        <p class="text-sm text-gray-400 mt-1">Connect Shopify, WooCommerce, or custom API channels.</p>
      </div>
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="chan in channels"
          :key="chan.id"
          class="bg-white p-5 rounded-lg border border-gray-200 space-y-4 hover:shadow-sm transition-all"
        >
          <div class="flex items-start justify-between">
            <div>
              <h3 class="font-bold text-gray-900">{{ chan.name }}</h3>
              <p class="text-xs text-gray-500 uppercase mt-0.5">Platform: {{ chan.platform }}</p>
            </div>
            <span
              class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium"
              :class="chan.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'"
            >
              {{ chan.is_active ? 'Active' : 'Paused' }}
            </span>
          </div>

          <div class="text-xs text-gray-500 space-y-1">
            <p v-if="chan.store_url" class="truncate font-mono">{{ chan.store_url }}</p>
            <p>Synced Orders: <span class="font-semibold text-gray-800">{{ chan.orders_count ?? 0 }}</span></p>
            <p v-if="chan.last_sync_at">Last Sync: {{ chan.last_sync_at.substring(0, 16).replace('T', ' ') }}</p>
          </div>

          <div class="pt-2 border-t border-gray-100 flex justify-end">
            <button
              @click="triggerSync(chan.id)"
              class="inline-flex items-center space-x-1 text-xs text-primary-600 hover:text-primary-800 font-medium px-2.5 py-1 rounded border border-primary-200 hover:bg-primary-50"
            >
              <RefreshCw class="w-3.5 h-3.5" />
              <span>Sync Now</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Storefront Modal -->
    <div
      v-if="isCreateModalOpen"
      class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4"
    >
      <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-5 shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="text-base font-bold text-gray-900">Create New Storefront Site</h3>
          <button @click="isCreateModalOpen = false" class="text-gray-400 hover:text-gray-700">✕</button>
        </div>

        <form @submit.prevent="createStorefront" class="space-y-4 text-xs">
          <div class="space-y-1">
            <label class="font-semibold text-gray-700">Store Name *</label>
            <input
              v-model="newStoreName"
              required
              placeholder="e.g. Modern Outfitters"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
            />
          </div>

          <div class="space-y-1">
            <label class="font-semibold text-gray-700">Store Title / Tagline</label>
            <input
              v-model="newStoreTitle"
              placeholder="e.g. Premium handcrafted apparel & gear"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
            />
          </div>

          <button
            type="submit"
            :disabled="isCreatingStore"
            class="w-full py-2.5 rounded-xl bg-primary-600 hover:bg-primary-500 text-white font-bold text-sm shadow-md transition-all disabled:opacity-50"
          >
            {{ isCreatingStore ? 'Creating Storefront...' : 'Create & Open Visual Builder' }}
          </button>
        </form>
      </div>
    </div>

    <!-- Fullscreen Drag & Drop Page Builder Overlay -->
    <StorefrontBuilder
      v-if="activeBuildingStorefront"
      :storefront="activeBuildingStorefront"
      @close="activeBuildingStorefront = null; fetchData()"
    />
  </div>
</template>
