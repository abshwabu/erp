<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/api/client'

const authStore = useAuthStore()

interface WorkOrder {
  id: string
  number: string
  quantity: number
  status: string
  priority: string
  planned_start: string | null
  planned_end: string | null
  started_at: string | null
  completed_at: string | null
  bom?: { id: string; name: string; product?: { name: string; sku: string } }
}

interface Bom {
  id: string
  name: string
  status: string
  output_quantity: number
  product?: { name: string; sku: string }
  lines?: Array<{ material_id: string; quantity: number; material?: { name: string; sku: string } }>
}

const workOrders = ref<WorkOrder[]>([])
const boms = ref<Bom[]>([])
const loading = ref(true)
const activeTab = ref<'work-orders' | 'boms'>('work-orders')

const statusColors: Record<string, string> = {
  draft: 'bg-gray-100 text-gray-700',
  in_progress: 'bg-blue-100 text-blue-700',
  completed: 'bg-green-100 text-green-700',
  cancelled: 'bg-red-100 text-red-700',
  active: 'bg-green-100 text-green-700',
  archived: 'bg-gray-100 text-gray-500',
}

const priorityColors: Record<string, string> = {
  low: 'text-gray-500',
  normal: 'text-blue-600',
  high: 'text-orange-600',
  urgent: 'text-red-600 font-bold',
}

async function fetchData() {
  loading.value = true
  try {
    const [woRes, bomRes] = await Promise.all([
      api.get('/manufacturing/work-orders'),
      api.get('/manufacturing/boms'),
    ])
    workOrders.value = woRes.data?.data?.data ?? woRes.data?.data ?? []
    boms.value = bomRes.data?.data?.data ?? bomRes.data?.data ?? []
  } catch (e) {
    console.error('Failed to load manufacturing data', e)
  } finally {
    loading.value = false
  }
}

onMounted(fetchData)
</script>

<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-900">Manufacturing</h1>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200">
      <nav class="-mb-px flex space-x-8">
        <button
          @click="activeTab = 'work-orders'"
          :class="[activeTab === 'work-orders' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm']"
        >
          Work Orders
        </button>
        <button
          @click="activeTab = 'boms'"
          :class="[activeTab === 'boms' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm']"
        >
          Bills of Material
        </button>
      </nav>
    </div>

    <div v-if="loading" class="text-center py-12 text-gray-500">Loading…</div>

    <!-- Work Orders Tab -->
    <div v-else-if="activeTab === 'work-orders'">
      <div v-if="workOrders.length === 0" class="text-center py-12 text-gray-400">
        No work orders yet. Create your first BOM and then schedule a work order.
      </div>
      <div v-else class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Number</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">BOM / Product</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Priority</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Planned</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="wo in workOrders" :key="wo.id">
              <td class="px-6 py-4 text-sm font-mono font-medium text-gray-900">{{ wo.number }}</td>
              <td class="px-6 py-4 text-sm text-gray-700">
                {{ wo.bom?.name ?? '—' }}
                <span v-if="wo.bom?.product" class="text-gray-400 ml-1">({{ wo.bom.product.sku }})</span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-700">{{ wo.quantity }}</td>
              <td class="px-6 py-4 text-sm" :class="priorityColors[wo.priority] ?? ''">{{ wo.priority }}</td>
              <td class="px-6 py-4">
                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium" :class="statusColors[wo.status] ?? 'bg-gray-100'">
                  {{ wo.status.replace('_', ' ') }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-500">
                {{ wo.planned_start ? wo.planned_start.substring(0, 10) : '—' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- BOMs Tab -->
    <div v-else-if="activeTab === 'boms'">
      <div v-if="boms.length === 0" class="text-center py-12 text-gray-400">
        No Bills of Material defined yet.
      </div>
      <div v-else class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Output Qty</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Materials</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="bom in boms" :key="bom.id">
              <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ bom.name }}</td>
              <td class="px-6 py-4 text-sm text-gray-700">
                {{ bom.product?.name ?? '—' }}
                <span v-if="bom.product" class="text-gray-400 ml-1">({{ bom.product.sku }})</span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-700">{{ bom.output_quantity }}</td>
              <td class="px-6 py-4 text-sm text-gray-500">{{ bom.lines?.length ?? 0 }} items</td>
              <td class="px-6 py-4">
                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium" :class="statusColors[bom.status] ?? 'bg-gray-100'">
                  {{ bom.status }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
