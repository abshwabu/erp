<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/api/client'
import { Globe, RefreshCw, ShoppingCart, CheckCircle, Package } from '@lucide/vue'

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

const channels = ref<EcommerceChannel[]>([])
const orders = ref<EcommerceOrder[]>([])
const loading = ref(true)
const activeTab = ref<'channels' | 'orders'>('orders')

const fulfillmentColors: Record<string, string> = {
  unfulfilled: 'bg-amber-50 text-amber-700 border-amber-200',
  fulfilled: 'bg-green-50 text-green-700 border-green-200',
  cancelled: 'bg-red-50 text-red-700 border-red-200',
}

async function fetchData() {
  loading.value = true
  try {
    const [chanRes, ordRes] = await Promise.all([
      api.get('/ecommerce/channels'),
      api.get('/ecommerce/orders'),
    ])
    channels.value = chanRes.data?.data ?? chanRes.data ?? []
    orders.value = ordRes.data?.data?.data ?? ordRes.data?.data ?? []
  } catch (e) {
    console.error('Failed to load ecommerce data', e)
  } finally {
    loading.value = false
  }
}

async function triggerSync(id: string) {
  try {
    await api.post(`/ecommerce/channels/${id}/sync`)
    await fetchData()
  } catch (e) {
    console.error('Failed to sync channel', e)
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
        <Globe class="w-7 h-7 text-gray-700" />
        <h1 class="text-2xl font-bold text-gray-900">E-Commerce</h1>
      </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200">
      <nav class="-mb-px flex space-x-8">
        <button
          @click="activeTab = 'orders'"
          :class="[activeTab === 'orders' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm']"
        >
          Online Orders
        </button>
        <button
          @click="activeTab = 'channels'"
          :class="[activeTab === 'channels' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm']"
        >
          Store Channels ({{ channels.length }})
        </button>
      </nav>
    </div>

    <div v-if="loading" class="text-center py-12 text-gray-500">Loading e-commerce data…</div>

    <!-- Orders Tab -->
    <div v-else-if="activeTab === 'orders'">
      <div v-if="orders.length === 0" class="text-center py-16 bg-white rounded-lg border border-gray-200">
        <ShoppingCart class="w-12 h-12 text-gray-300 mx-auto mb-3" />
        <p class="text-gray-500 font-medium">No online orders synced yet</p>
        <p class="text-sm text-gray-400 mt-1">Connect your Shopify or WooCommerce store to ingest orders automatically.</p>
      </div>
      <div v-else class="bg-white shadow rounded-lg overflow-hidden border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order #</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Channel</th>
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
                  {{ ord.channel?.name ?? 'Store' }}
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

    <!-- Channels Tab -->
    <div v-else-if="activeTab === 'channels'">
      <div v-if="channels.length === 0" class="text-center py-16 bg-white rounded-lg border border-gray-200">
        <Globe class="w-12 h-12 text-gray-300 mx-auto mb-3" />
        <p class="text-gray-500 font-medium">No sales channels configured</p>
        <p class="text-sm text-gray-400 mt-1">Configure an online storefront connection.</p>
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
  </div>
</template>
