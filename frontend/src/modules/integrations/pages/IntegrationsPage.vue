<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/api/client'
import { Plug, CheckCircle, AlertCircle, RefreshCw, Radio, Terminal, Settings } from '@lucide/vue'

interface IntegrationLog {
  id: string
  event: string
  direction: string
  status_code: number | null
  created_at: string
}

interface Integration {
  id: string
  provider: string
  name: string
  status: string
  webhook_url: string | null
  last_tested_at: string | null
  logs_count?: number
  logs?: IntegrationLog[]
}

const integrations = ref<Integration[]>([])
const selectedIntegration = ref<Integration | null>(null)
const loading = ref(true)

const providerColors: Record<string, string> = {
  stripe: 'bg-indigo-50 text-indigo-700 border-indigo-200',
  slack: 'bg-emerald-50 text-emerald-700 border-emerald-200',
  sendgrid: 'bg-blue-50 text-blue-700 border-blue-200',
  zapier: 'bg-orange-50 text-orange-700 border-orange-200',
  quickbooks: 'bg-green-50 text-green-700 border-green-200',
  webhook: 'bg-purple-50 text-purple-700 border-purple-200',
}

async function fetchIntegrations() {
  loading.value = true
  try {
    const res = await api.get('/integrations')
    integrations.value = res.data?.data ?? res.data ?? []
  } catch (e) {
    console.error('Failed to load integrations', e)
  } finally {
    loading.value = false
  }
}

async function selectIntegration(item: Integration) {
  try {
    const res = await api.get(`/integrations/${item.id}`)
    selectedIntegration.value = res.data?.data ?? res.data
  } catch (e) {
    console.error('Failed to load integration details', e)
  }
}

async function testConnection(id: string) {
  try {
    await api.post(`/integrations/${id}/test`)
    await fetchIntegrations()
    if (selectedIntegration.value?.id === id) {
      await selectIntegration(selectedIntegration.value)
    }
  } catch (e) {
    console.error('Failed to test connection', e)
  }
}

onMounted(fetchIntegrations)
</script>

<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <div class="flex items-center space-x-3">
        <Plug class="w-7 h-7 text-gray-700" />
        <h1 class="text-2xl font-bold text-gray-900">Integrations & Connectors</h1>
      </div>
    </div>

    <div v-if="loading" class="text-center py-12 text-gray-500">Loading integrations…</div>

    <div v-else-if="integrations.length === 0" class="text-center py-16 bg-white rounded-lg border border-gray-200">
      <Plug class="w-12 h-12 text-gray-300 mx-auto mb-3" />
      <p class="text-gray-500 font-medium">No integrations configured</p>
      <p class="text-sm text-gray-400 mt-1">Connect third-party gateways (Stripe, Slack, Webhooks, Quickbooks).</p>
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Connectors list -->
      <div class="lg:col-span-1 space-y-3">
        <div
          v-for="item in integrations"
          :key="item.id"
          @click="selectIntegration(item)"
          :class="[
            'p-4 rounded-lg border cursor-pointer transition-all hover:shadow-sm',
            selectedIntegration?.id === item.id ? 'bg-primary-50/50 border-primary-500 ring-1 ring-primary-500' : 'bg-white border-gray-200'
          ]"
        >
          <div class="flex items-center justify-between mb-1.5">
            <span class="inline-flex px-2 py-0.5 rounded text-xs font-semibold uppercase tracking-wider border" :class="providerColors[item.provider] ?? 'bg-gray-100'">
              {{ item.provider }}
            </span>
            <span
              class="inline-flex items-center space-x-1 text-xs font-medium"
              :class="item.status === 'connected' ? 'text-green-600' : 'text-gray-500'"
            >
              <span class="w-2 h-2 rounded-full" :class="item.status === 'connected' ? 'bg-green-500' : 'bg-gray-400'"></span>
              <span class="capitalize">{{ item.status }}</span>
            </span>
          </div>
          <h3 class="text-sm font-semibold text-gray-900">{{ item.name }}</h3>
          <div class="flex items-center justify-between text-xs text-gray-400 mt-3 pt-2 border-t border-gray-100">
            <span>{{ item.logs_count ?? 0 }} event logs</span>
            <span>{{ item.last_tested_at ? 'Tested' : 'Untested' }}</span>
          </div>
        </div>
      </div>

      <!-- Connector detail and audit log -->
      <div class="lg:col-span-2">
        <div v-if="!selectedIntegration" class="bg-white rounded-lg border border-gray-200 p-12 text-center text-gray-400">
          Select an integration to view status, webhook URLs, and recent audit logs.
        </div>
        <div v-else class="bg-white rounded-lg border border-gray-200 p-6 space-y-6">
          <div class="flex items-start justify-between">
            <div>
              <span class="text-xs uppercase font-bold text-gray-500 tracking-wider">{{ selectedIntegration.provider }}</span>
              <h2 class="text-xl font-bold text-gray-900">{{ selectedIntegration.name }}</h2>
            </div>
            <button
              @click="testConnection(selectedIntegration.id)"
              class="inline-flex items-center space-x-1.5 text-xs text-primary-600 hover:text-primary-800 font-medium px-3 py-1.5 rounded border border-primary-200 hover:bg-primary-50"
            >
              <RefreshCw class="w-3.5 h-3.5" />
              <span>Test Connection</span>
            </button>
          </div>

          <div class="bg-gray-50 p-4 rounded-lg text-sm space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-xs text-gray-500">Status</span>
              <span class="font-medium capitalize text-gray-900">{{ selectedIntegration.status }}</span>
            </div>
            <div v-if="selectedIntegration.webhook_url" class="flex items-center justify-between">
              <span class="text-xs text-gray-500">Webhook URL</span>
              <span class="font-mono text-xs text-gray-700 truncate max-w-sm">{{ selectedIntegration.webhook_url }}</span>
            </div>
            <div v-if="selectedIntegration.last_tested_at" class="flex items-center justify-between">
              <span class="text-xs text-gray-500">Last Health Check</span>
              <span class="text-xs text-gray-600">{{ selectedIntegration.last_tested_at.substring(0, 16).replace('T', ' ') }}</span>
            </div>
          </div>

          <!-- Logs -->
          <div class="space-y-3">
            <h3 class="text-sm font-semibold text-gray-900 flex items-center space-x-2">
              <Terminal class="w-4 h-4 text-gray-500" />
              <span>Recent Activity Logs</span>
            </h3>

            <div v-if="!selectedIntegration.logs || selectedIntegration.logs.length === 0" class="text-sm text-gray-400 py-6 text-center bg-gray-50 rounded-lg">
              No recent webhook or sync events logged.
            </div>

            <div v-else class="divide-y divide-gray-100 border border-gray-200 rounded-lg overflow-hidden text-xs">
              <div
                v-for="log in selectedIntegration.logs"
                :key="log.id"
                class="p-3 flex items-center justify-between hover:bg-gray-50"
              >
                <div class="space-y-0.5">
                  <span class="font-mono font-medium text-gray-900">{{ log.event }}</span>
                  <p class="text-gray-400 capitalize">{{ log.direction }} event</p>
                </div>
                <div class="text-right">
                  <span
                    class="inline-flex px-2 py-0.5 rounded font-mono font-bold"
                    :class="log.status_code && log.status_code < 400 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                  >
                    {{ log.status_code ?? 'OK' }}
                  </span>
                  <p class="text-gray-400 mt-0.5">{{ log.created_at?.substring(11, 19) }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
