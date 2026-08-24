<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/api/client'
import { Landmark, Plus, Calculator, Wrench, ShieldCheck, Laptop, Truck, Building } from '@lucide/vue'

interface AssetDepreciation {
  id: string
  fiscal_year: number
  depreciation_amount_cents: number
  accumulated_depreciation_cents: number
  book_value_cents: number
}

interface Asset {
  id: string
  asset_tag: string
  name: string
  category: string
  serial_number: string | null
  purchase_date: string | null
  purchase_cost_cents: number
  salvage_value_cents: number
  useful_life_years: number
  depreciation_method: string
  status: string
  assigned_to: string | null
  assignee?: { name: string; email: string }
  depreciations?: AssetDepreciation[]
}

const assets = ref<Asset[]>([])
const selectedAsset = ref<Asset | null>(null)
const loading = ref(true)

const categoryIcons: Record<string, any> = {
  electronics: Laptop,
  vehicle: Truck,
  building: Building,
  equipment: Landmark,
  machinery: Wrench,
  furniture: Landmark,
}

const statusColors: Record<string, string> = {
  active: 'bg-green-50 text-green-700 border-green-200',
  maintenance: 'bg-amber-50 text-amber-700 border-amber-200',
  disposed: 'bg-red-50 text-red-700 border-red-200',
  retired: 'bg-gray-100 text-gray-700 border-gray-200',
}

async function fetchAssets() {
  loading.value = true
  try {
    const res = await api.get('/assets')
    assets.value = res.data?.data?.data ?? res.data?.data ?? []
  } catch (e) {
    console.error('Failed to load assets', e)
  } finally {
    loading.value = false
  }
}

async function selectAsset(a: Asset) {
  try {
    const res = await api.get(`/assets/${a.id}`)
    selectedAsset.value = res.data?.data ?? res.data
  } catch (e) {
    console.error('Failed to load asset details', e)
  }
}

async function generateSchedule(id: string) {
  try {
    const res = await api.post(`/assets/${id}/depreciation-schedule`)
    selectedAsset.value = res.data?.data ?? res.data
  } catch (e) {
    console.error('Failed to generate depreciation schedule', e)
  }
}

function formatCents(cents: number) {
  return '$' + (cents / 100).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

onMounted(fetchAssets)
</script>

<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <div class="flex items-center space-x-3">
        <Landmark class="w-7 h-7 text-gray-700" />
        <h1 class="text-2xl font-bold text-gray-900">Fixed Assets</h1>
      </div>
    </div>

    <div v-if="loading" class="text-center py-12 text-gray-500">Loading assets…</div>

    <div v-else-if="assets.length === 0" class="text-center py-16 bg-white rounded-lg border border-gray-200">
      <Landmark class="w-12 h-12 text-gray-300 mx-auto mb-3" />
      <p class="text-gray-500 font-medium">No assets registered</p>
      <p class="text-sm text-gray-400 mt-1">Track capital equipment, vehicles, and automate depreciation.</p>
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Asset List -->
      <div class="lg:col-span-1 space-y-3">
        <div
          v-for="item in assets"
          :key="item.id"
          @click="selectAsset(item)"
          :class="[
            'p-4 rounded-lg border cursor-pointer transition-all hover:shadow-sm',
            selectedAsset?.id === item.id ? 'bg-primary-50/50 border-primary-500 ring-1 ring-primary-500' : 'bg-white border-gray-200'
          ]"
        >
          <div class="flex items-center justify-between mb-1.5">
            <span class="text-xs font-mono font-medium text-gray-500">{{ item.asset_tag }}</span>
            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium border" :class="statusColors[item.status] ?? 'bg-gray-100'">
              {{ item.status }}
            </span>
          </div>
          <div class="flex items-center space-x-2">
            <component :is="categoryIcons[item.category] ?? Landmark" class="w-4 h-4 text-gray-400 shrink-0" />
            <h3 class="text-sm font-semibold text-gray-900 truncate">{{ item.name }}</h3>
          </div>
          <div class="flex items-center justify-between text-xs text-gray-500 mt-3 pt-2 border-t border-gray-100">
            <span class="capitalize">{{ item.category }}</span>
            <span class="font-medium text-gray-900">{{ formatCents(item.purchase_cost_cents) }}</span>
          </div>
        </div>
      </div>

      <!-- Asset Detail & Depreciation Table -->
      <div class="lg:col-span-2">
        <div v-if="!selectedAsset" class="bg-white rounded-lg border border-gray-200 p-12 text-center text-gray-400">
          Select an asset from the list to view specifications and depreciation schedules.
        </div>
        <div v-else class="bg-white rounded-lg border border-gray-200 p-6 space-y-6">
          <div class="flex items-start justify-between">
            <div>
              <span class="text-xs font-mono text-gray-500">{{ selectedAsset.asset_tag }}</span>
              <h2 class="text-xl font-bold text-gray-900">{{ selectedAsset.name }}</h2>
              <p class="text-xs text-gray-500 uppercase mt-0.5">Category: {{ selectedAsset.category }}</p>
            </div>
            <span class="inline-flex px-3 py-1 rounded-full text-xs font-medium border" :class="statusColors[selectedAsset.status] ?? 'bg-gray-100'">
              {{ selectedAsset.status }}
            </span>
          </div>

          <!-- Key Metrics -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 p-4 bg-gray-50 rounded-lg text-sm">
            <div>
              <span class="text-xs text-gray-500 block">Purchase Cost</span>
              <span class="font-semibold text-gray-900">{{ formatCents(selectedAsset.purchase_cost_cents) }}</span>
            </div>
            <div>
              <span class="text-xs text-gray-500 block">Salvage Value</span>
              <span class="font-medium text-gray-900">{{ formatCents(selectedAsset.salvage_value_cents) }}</span>
            </div>
            <div>
              <span class="text-xs text-gray-500 block">Useful Life</span>
              <span class="font-medium text-gray-900">{{ selectedAsset.useful_life_years }} years</span>
            </div>
            <div>
              <span class="text-xs text-gray-500 block">Method</span>
              <span class="font-medium text-gray-900 capitalize">{{ selectedAsset.depreciation_method.replace('_', ' ') }}</span>
            </div>
          </div>

          <!-- Depreciation Schedule -->
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-semibold text-gray-900">Depreciation Schedule</h3>
              <button
                @click="generateSchedule(selectedAsset.id)"
                class="inline-flex items-center space-x-1.5 text-xs text-primary-600 hover:text-primary-800 font-medium px-2.5 py-1 rounded border border-primary-200 hover:bg-primary-50"
              >
                <Calculator class="w-3.5 h-3.5" />
                <span>Calculate Schedule</span>
              </button>
            </div>

            <div v-if="!selectedAsset.depreciations || selectedAsset.depreciations.length === 0" class="text-sm text-gray-400 py-6 text-center bg-gray-50 rounded-lg">
              No schedule generated yet. Click "Calculate Schedule" to project annual depreciation.
            </div>

            <div v-else class="border border-gray-200 rounded-lg overflow-hidden">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                  <tr>
                    <th class="px-4 py-2.5 text-left">Fiscal Year</th>
                    <th class="px-4 py-2.5 text-right">Depreciation</th>
                    <th class="px-4 py-2.5 text-right">Accumulated</th>
                    <th class="px-4 py-2.5 text-right">Book Value</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-sm">
                  <tr v-for="d in selectedAsset.depreciations" :key="d.id" class="hover:bg-gray-50">
                    <td class="px-4 py-2.5 font-medium text-gray-900">{{ d.fiscal_year }}</td>
                    <td class="px-4 py-2.5 text-right text-gray-700">{{ formatCents(d.depreciation_amount_cents) }}</td>
                    <td class="px-4 py-2.5 text-right text-gray-500">{{ formatCents(d.accumulated_depreciation_cents) }}</td>
                    <td class="px-4 py-2.5 text-right font-semibold text-gray-900">{{ formatCents(d.book_value_cents) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
