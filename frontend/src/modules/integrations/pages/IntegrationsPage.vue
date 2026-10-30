<script setup lang="ts">
import { computed, ref, onMounted, markRaw } from 'vue'
import { useI18n } from 'vue-i18n'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import {
  integrationsApi,
  type Integration,
  type IntegrationLog,
  type ConnectorCatalogItem,
} from '@/api/integrations'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiStat from '@/components/ui/UiStat.vue'
import UiSpinner from '@/components/ui/UiSpinner.vue'
import {
  Plug,
  Plus,
  RefreshCw,
  Send,
  Trash2,
  Settings,
  Terminal,
  Activity,
  CheckCircle2,
  AlertCircle,
  Clock,
  Layers,
  Search,
  ExternalLink,
  ShieldCheck,
  CreditCard,
  Smartphone,
  DollarSign,
  Calculator,
  BookOpen,
  MessageSquare,
  PhoneCall,
  Mail,
  ShoppingBag,
  Zap,
  Webhook,
  Copy,
  Check,
  Code,
  Eye,
  EyeOff,
} from '@lucide/vue'
import { useToast } from '@/composables/useToast'

const queryClient = useQueryClient()
const toast = useToast()
const { t } = useI18n()

const activeTab = ref<'active' | 'catalog' | 'logs'>('active')
const selectedCategory = ref<string>('all')
const catalogSearchQuery = ref('')

const selectedIntegrationId = ref<string | null>(null)
const isConnectModalOpen = ref(false)
const isEditModalOpen = ref(false)
const isTestEventModalOpen = ref(false)
const isDeleteModalOpen = ref(false)
const isPayloadModalOpen = ref(false)

const selectedCatalogItem = ref<ConnectorCatalogItem | null>(null)
const integrationToDelete = ref<Integration | null>(null)
const selectedLogForPayload = ref<IntegrationLog | null>(null)

// Forms
const connectForm = ref({
  name: '',
  provider: '',
  api_key: '',
  webhook_url: '',
  settings: {} as Record<string, any>,
  selected_events: [] as string[],
})

const testEventForm = ref({
  event: 'invoice.paid',
  custom_note: '',
})

// Provider Icon Mapping
const iconMap: Record<string, any> = {
  CreditCard: markRaw(CreditCard),
  Smartphone: markRaw(Smartphone),
  DollarSign: markRaw(DollarSign),
  Calculator: markRaw(Calculator),
  BookOpen: markRaw(BookOpen),
  MessageSquare: markRaw(MessageSquare),
  Send: markRaw(Send),
  PhoneCall: markRaw(PhoneCall),
  Mail: markRaw(Mail),
  ShoppingBag: markRaw(ShoppingBag),
  Zap: markRaw(Zap),
  Webhook: markRaw(Webhook),
}

const providerBadgeColors: Record<string, string> = {
  stripe: 'bg-indigo-50 text-indigo-700 border-indigo-200',
  chapa: 'bg-emerald-50 text-emerald-700 border-emerald-200',
  paypal: 'bg-sky-50 text-sky-700 border-sky-200',
  quickbooks: 'bg-green-50 text-green-700 border-green-200',
  xero: 'bg-cyan-50 text-cyan-700 border-cyan-200',
  slack: 'bg-purple-50 text-purple-700 border-purple-200',
  telegram: 'bg-blue-50 text-blue-700 border-blue-200',
  whatsapp: 'bg-teal-50 text-teal-700 border-teal-200',
  sendgrid: 'bg-slate-50 text-slate-700 border-slate-200',
  shopify: 'bg-lime-50 text-lime-700 border-lime-200',
  zapier: 'bg-orange-50 text-orange-700 border-orange-200',
  webhook: 'bg-amber-50 text-amber-700 border-amber-200',
}

// Queries
const { data: integrations, isLoading: isLoadingIntegrations } = useQuery({
  queryKey: ['integrations', 'list'],
  queryFn: () => integrationsApi.list().then((res) => res.data.data),
})

const { data: catalog } = useQuery({
  queryKey: ['integrations', 'catalog'],
  queryFn: () => integrationsApi.getCatalog().then((res) => res.data.data),
})

// Auto-select first integration
const activeIntegrations = computed(() => integrations.value || [])

const selectedIntegration = computed(() => {
  if (!activeIntegrations.value.length) return null
  if (!selectedIntegrationId.value) {
    return activeIntegrations.value[0]
  }
  return activeIntegrations.value.find((i) => i.id === selectedIntegrationId.value) || activeIntegrations.value[0]
})

// All logs combined
const allLogs = computed(() => {
  const logs: Array<IntegrationLog & { integrationName: string; provider: string }> = []
  activeIntegrations.value.forEach((item) => {
    ;(item.logs || []).forEach((log) => {
      logs.push({
        ...log,
        integrationName: item.name,
        provider: item.provider,
      })
    })
  })
  return logs.sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
})

// Catalog Filtered
const filteredCatalog = computed(() => {
  let list = catalog.value || []
  if (selectedCategory.value !== 'all') {
    list = list.filter((item) => item.category === selectedCategory.value)
  }
  if (catalogSearchQuery.value) {
    const q = catalogSearchQuery.value.toLowerCase()
    list = list.filter(
      (item) =>
        item.name.toLowerCase().includes(q) ||
        item.description.toLowerCase().includes(q) ||
        item.provider.toLowerCase().includes(q)
    )
  }
  return list
})

// Top Stats
const stats = computed(() => {
  const list = activeIntegrations.value
  const connected = list.filter((i) => i.status === 'connected').length
  const totalEvents = list.reduce((acc, curr) => acc + (curr.logs_count || curr.logs?.length || 0), 0)

  return [
    {
      label: t('integrations.stats.connectedGateways'),
      value: connected,
      icon: markRaw(Plug),
    },
    {
      label: t('integrations.stats.supportedIntegrations'),
      value: catalog.value?.length || 12,
      icon: markRaw(Layers),
    },
    {
      label: t('integrations.stats.totalProcessedEvents'),
      value: totalEvents,
      icon: markRaw(Activity),
    },
    {
      label: t('integrations.stats.webhookHealth'),
      value: connected > 0 ? t('integrations.stats.operational') : t('integrations.stats.readyToConnect'),
      icon: markRaw(ShieldCheck),
    },
  ]
})

// --- Mutations ---
const connectMutation = useMutation({
  mutationFn: (payload: any) => integrationsApi.create(payload),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['integrations', 'list'] })
    isConnectModalOpen.value = false
    toast.success(t('integrations.messages.initializedSuccess'))
    activeTab.value = 'active'
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || t('integrations.messages.failedDelete'))
  },
})

const testConnectionMutation = useMutation({
  mutationFn: (id: string) => integrationsApi.testConnection(id),
  onSuccess: (res) => {
    queryClient.invalidateQueries({ queryKey: ['integrations', 'list'] })
    toast.success(res.data.data?.message || t('integrations.messages.pingVerified'))
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || t('integrations.messages.diagnosticFailed'))
  },
})

const sendTestEventMutation = useMutation({
  mutationFn: ({ id, data }: { id: string; data: any }) => integrationsApi.sendTestEvent(id, data),
  onSuccess: (res) => {
    queryClient.invalidateQueries({ queryKey: ['integrations', 'list'] })
    isTestEventModalOpen.value = false
    toast.success(res.data.data?.message || t('integrations.messages.eventDispatched'))
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || t('integrations.messages.eventFailed'))
  },
})

const deleteMutation = useMutation({
  mutationFn: (id: string) => integrationsApi.delete(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['integrations', 'list'] })
    isDeleteModalOpen.value = false
    integrationToDelete.value = null
    toast.success(t('integrations.messages.disconnectedSuccess'))
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || t('integrations.messages.failedDelete'))
  },
})

// --- Action Handlers ---
function openConnectModal(catalogItem: ConnectorCatalogItem) {
  selectedCatalogItem.value = catalogItem
  connectForm.value = {
    name: catalogItem.name,
    provider: catalogItem.provider,
    api_key: '',
    webhook_url: '',
    settings: {},
    selected_events: [...catalogItem.supported_events],
  }
  isConnectModalOpen.value = true
}

function handleConnectSubmit() {
  if (!connectForm.value.name) {
    toast.error(t('integrations.messages.nameRequired'))
    return
  }

  const payload: any = {
    name: connectForm.value.name,
    provider: connectForm.value.provider,
    api_key: connectForm.value.api_key || null,
    webhook_url: connectForm.value.webhook_url || null,
    settings: {
      ...connectForm.value.settings,
      events: connectForm.value.selected_events,
    },
  }

  connectMutation.mutate(payload)
}

function openTestEventModal(integration: Integration) {
  testEventForm.value = {
    event: integration.settings?.events?.[0] || 'invoice.paid',
    custom_note: '',
  }
  isTestEventModalOpen.value = true
}

function handleSendTestEvent() {
  if (!selectedIntegration.value) return
  sendTestEventMutation.mutate({
    id: selectedIntegration.value.id,
    data: {
      event: testEventForm.value.event,
    },
  })
}

function openDeleteConfirm(integration: Integration) {
  integrationToDelete.value = integration
  isDeleteModalOpen.value = true
}

function openPayloadInspector(log: IntegrationLog) {
  selectedLogForPayload.value = log
  isPayloadModalOpen.value = true
}

const copiedWebhook = ref(false)
function copyToClipboard(text: string) {
  navigator.clipboard.writeText(text)
  copiedWebhook.value = true
  setTimeout(() => {
    copiedWebhook.value = false
  }, 2000)
}
</script>

<template>
  <div class="space-y-6 max-w-7xl font-sans pb-12">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="flex items-center space-x-2">
          <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ t('integrations.title') }}</h1>
          <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-100">
            {{ t('integrations.connectedCount', { count: activeIntegrations.length }) }}
          </span>
        </div>
        <p class="text-slate-500 text-xs sm:text-sm mt-1">
          {{ t('integrations.subtitle') }}
        </p>
      </div>

      <UiButton @click="activeTab = 'catalog'">
        <Plus class="w-4 h-4 mr-1.5" /> {{ t('integrations.exploreCatalog') }}
      </UiButton>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <UiStat
        v-for="stat in stats"
        :key="stat.label"
        :label="stat.label"
        :value="stat.value"
        :icon="stat.icon"
      />
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-1.5 border-b border-slate-200 pb-2 overflow-x-auto">
      <button
        type="button"
        @click="activeTab = 'active'"
        class="px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-2 whitespace-nowrap"
        :class="activeTab === 'active' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
      >
        <Plug class="w-3.5 h-3.5" /> {{ t('integrations.tabs.activeConnectors') }} ({{ activeIntegrations.length }})
      </button>

      <button
        type="button"
        @click="activeTab = 'catalog'"
        class="px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-2 whitespace-nowrap"
        :class="activeTab === 'catalog' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
      >
        <Layers class="w-3.5 h-3.5" /> {{ t('integrations.tabs.marketplace') }} ({{ catalog?.length || 12 }})
      </button>

      <button
        type="button"
        @click="activeTab = 'logs'"
        class="px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-2 whitespace-nowrap"
        :class="activeTab === 'logs' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
      >
        <Terminal class="w-3.5 h-3.5" /> {{ t('integrations.tabs.logs') }} ({{ allLogs.length }})
      </button>
    </div>

    <!-- 1. Active Connectors Tab -->
    <div v-if="activeTab === 'active'">
      <div v-if="isLoadingIntegrations" class="p-16 flex justify-center">
        <UiSpinner size="lg" />
      </div>

      <div v-else-if="activeIntegrations.length === 0" class="text-center py-16 bg-white rounded-3xl border border-slate-200 shadow-xs space-y-4">
        <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mx-auto">
          <Plug class="w-8 h-8" />
        </div>
        <div class="space-y-1">
          <h3 class="text-base font-bold text-slate-900">{{ t('integrations.active.emptyTitle') }}</h3>
          <p class="text-xs text-slate-500 max-w-md mx-auto">
            {{ t('integrations.active.emptyDescription') }}
          </p>
        </div>
        <UiButton @click="activeTab = 'catalog'">
          <Plus class="w-4 h-4 mr-1.5" /> {{ t('integrations.active.browseConnectors') }}
        </UiButton>
      </div>

      <div v-else class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Sidebar List -->
        <div class="lg:col-span-5 space-y-3">
          <div
            v-for="item in activeIntegrations"
            :key="item.id"
            @click="selectedIntegrationId = item.id"
            class="p-4 rounded-2xl border transition-all cursor-pointer flex flex-col justify-between space-y-3"
            :class="[
              selectedIntegration?.id === item.id
                ? 'bg-blue-50/50 border-blue-500 shadow-sm ring-1 ring-blue-500/20'
                : 'bg-white border-slate-200 hover:border-slate-300 hover:shadow-xs'
            ]"
          >
            <div class="flex items-center justify-between">
              <span
                class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border"
                :class="providerBadgeColors[item.provider] || 'bg-slate-100 text-slate-700'"
              >
                {{ item.provider }}
              </span>

              <span class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-600">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                {{ t('integrations.active.connectedStatus') }}
              </span>
            </div>

            <div>
              <h3 class="font-bold text-sm text-slate-900 line-clamp-1">{{ item.name }}</h3>
              <p class="text-[11px] text-slate-500 font-mono mt-0.5 truncate">
                {{ item.webhook_url || 'Managed API Gateway Handshake' }}
              </p>
            </div>

            <div class="flex items-center justify-between text-[11px] text-slate-400 pt-2 border-t border-slate-100">
              <span class="flex items-center gap-1">
                <Activity class="w-3.5 h-3.5 text-slate-400" />
                {{ item.logs_count ?? (item.logs?.length || 0) }} events
              </span>
              <span class="font-mono">
                {{ item.last_tested_at ? 'Tested ' + new Date(item.last_tested_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : 'Untested' }}
              </span>
            </div>
          </div>
        </div>

        <!-- Detail Inspector Panel -->
        <div v-if="selectedIntegration" class="lg:col-span-7 space-y-6">
          <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
              <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                  {{ selectedIntegration.provider }} CONNECTOR
                </span>
                <h2 class="text-xl font-bold text-slate-900">{{ selectedIntegration.name }}</h2>
              </div>

              <div class="flex items-center gap-2">
                <UiButton
                  variant="outline"
                  size="sm"
                  :loading="testConnectionMutation.isPending.value"
                  @click="testConnectionMutation.mutate(selectedIntegration.id)"
                >
                  <RefreshCw class="w-3.5 h-3.5 mr-1" /> {{ t('integrations.active.pingDiagnostic') }}
                </UiButton>

                <UiButton size="sm" @click="openTestEventModal(selectedIntegration)">
                  <Send class="w-3.5 h-3.5 mr-1" /> {{ t('integrations.active.triggerEvent') }}
                </UiButton>
              </div>
            </div>

            <!-- Parameters Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
              <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-1">
                <span class="text-slate-500 font-bold uppercase text-[10px]">{{ t('integrations.active.connectionStatus') }}</span>
                <div class="flex items-center gap-2 text-emerald-700 font-bold">
                  <CheckCircle2 class="w-4 h-4 text-emerald-500" /> {{ t('integrations.active.operationalSynced') }}
                </div>
              </div>

              <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-1">
                <span class="text-slate-500 font-bold uppercase text-[10px]">{{ t('integrations.active.lastDiagnostic') }}</span>
                <div class="text-slate-700 font-mono">
                  {{ selectedIntegration.last_tested_at ? new Date(selectedIntegration.last_tested_at).toLocaleString() : t('integrations.active.notVerified') }}
                </div>
              </div>
            </div>

            <!-- Webhook URL Box -->
            <div v-if="selectedIntegration.webhook_url" class="space-y-1.5">
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">{{ t('integrations.active.webhookUrl') }}</label>
              <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 p-2.5 rounded-xl">
                <span class="font-mono text-xs text-slate-700 truncate flex-1">{{ selectedIntegration.webhook_url }}</span>
                <button
                  type="button"
                  @click="copyToClipboard(selectedIntegration.webhook_url)"
                  class="p-1.5 text-slate-500 hover:text-slate-800 hover:bg-slate-200 rounded-lg transition-colors cursor-pointer"
                  title="Copy URL"
                >
                  <Check v-if="copiedWebhook" class="w-4 h-4 text-emerald-600" />
                  <Copy v-else class="w-4 h-4" />
                </button>
              </div>
            </div>

            <!-- Subscribed Events -->
            <div v-if="selectedIntegration.settings?.events?.length" class="space-y-2">
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">{{ t('integrations.active.subscribedTopics') }}</label>
              <div class="flex flex-wrap gap-1.5">
                <span
                  v-for="ev in selectedIntegration.settings.events"
                  :key="ev"
                  class="px-2.5 py-1 rounded-lg text-[11px] font-mono bg-blue-50 text-blue-700 border border-blue-100"
                >
                  {{ ev }}
                </span>
              </div>
            </div>

            <!-- Recent Activity for this connector -->
            <div class="space-y-3 pt-2">
              <div class="flex items-center justify-between">
                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center gap-1.5">
                  <Terminal class="w-4 h-4 text-slate-500" /> {{ t('integrations.active.recentActivity') }}
                </h3>
                <span class="text-xs text-slate-400 font-mono">{{ t('integrations.active.loggedEvents', { count: selectedIntegration.logs?.length || 0 }) }}</span>
              </div>

              <div v-if="!selectedIntegration.logs?.length" class="p-8 text-center bg-slate-50 rounded-2xl border border-slate-200 text-xs text-slate-400">
                {{ t('integrations.active.noLogs') }}
              </div>

              <div v-else class="divide-y divide-slate-100 border border-slate-200 rounded-2xl overflow-hidden bg-white">
                <div
                  v-for="log in selectedIntegration.logs"
                  :key="log.id"
                  @click="openPayloadInspector(log)"
                  class="p-3.5 flex items-center justify-between hover:bg-slate-50 cursor-pointer transition-colors"
                >
                  <div class="space-y-0.5">
                    <div class="flex items-center gap-2">
                      <span class="font-mono font-bold text-slate-900 text-xs">{{ log.event }}</span>
                      <span class="px-1.5 py-0.2 rounded text-[9px] uppercase font-bold tracking-wider" :class="log.direction === 'inbound' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'">
                        {{ log.direction }}
                      </span>
                    </div>
                    <p class="text-[10px] text-slate-400 font-mono">
                      {{ new Date(log.created_at).toLocaleTimeString() }} • {{ t('integrations.active.clickInspect') }}
                    </p>
                  </div>

                  <div class="flex items-center gap-2">
                    <span
                      class="px-2 py-0.5 rounded-md font-mono text-[11px] font-bold"
                      :class="log.status_code && log.status_code < 400 ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'"
                    >
                      HTTP {{ log.status_code || 200 }}
                    </span>
                    <Code class="w-3.5 h-3.5 text-slate-400" />
                  </div>
                </div>
              </div>
            </div>

            <!-- Footer Actions -->
            <div class="flex justify-between items-center pt-4 border-t border-slate-100">
              <span class="text-xs text-slate-400">{{ t('integrations.active.uuid', { id: selectedIntegration.id }) }}</span>
              <UiButton variant="danger" size="sm" @click="openDeleteConfirm(selectedIntegration)">
                <Trash2 class="w-3.5 h-3.5 mr-1" /> {{ t('integrations.active.disconnectIntegration') }}
              </UiButton>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 2. Marketplace / Catalog Tab -->
    <div v-else-if="activeTab === 'catalog'" class="space-y-6">
      <!-- Filter Bar -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-3.5 rounded-2xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 max-w-2xl">
          <button
            type="button"
            @click="selectedCategory = 'all'"
            class="px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all cursor-pointer"
            :class="selectedCategory === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
          >
            {{ t('integrations.marketplaceView.allCategories') }}
          </button>
          <button
            v-for="cat in ['Payments & Billing', 'Accounting & ERP', 'Communication & Alerts', 'Automation & Custom Hooks', 'E-Commerce & Retail']"
            :key="cat"
            type="button"
            @click="selectedCategory = cat"
            class="px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all cursor-pointer"
            :class="selectedCategory === cat ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
          >
            {{ cat }}
          </button>
        </div>

        <UiInput
          v-model="catalogSearchQuery"
          :placeholder="t('integrations.marketplaceView.searchPlaceholder')"
          size="sm"
          class="w-full sm:w-60"
        >
          <template #prefix><Search class="w-3.5 h-3.5 text-slate-400" /></template>
        </UiInput>
      </div>

      <!-- Catalog Cards Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <div
          v-for="item in filteredCatalog"
          :key="item.provider"
          class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between space-y-4 hover:border-blue-300 hover:shadow-md transition-all group"
        >
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <div class="w-10 h-10 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-800 group-hover:bg-blue-50 group-hover:text-blue-600 transition-colors">
                <component :is="iconMap[item.icon] || Plug" class="w-5 h-5" />
              </div>

              <span
                v-if="item.badge"
                class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-100"
              >
                {{ item.badge }}
              </span>
            </div>

            <div>
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ item.category }}</span>
              <h3 class="text-base font-bold text-slate-900">{{ item.name }}</h3>
              <p class="text-xs text-slate-500 mt-1 leading-relaxed">{{ item.description }}</p>
            </div>

            <div class="pt-2 border-t border-slate-100">
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1.5">{{ t('integrations.marketplaceView.supportedEvents') }}</span>
              <div class="flex flex-wrap gap-1">
                <span
                  v-for="ev in item.supported_events.slice(0, 3)"
                  :key="ev"
                  class="text-[9px] font-mono bg-slate-50 text-slate-600 px-1.5 py-0.5 rounded border border-slate-200"
                >
                  {{ ev }}
                </span>
                <span v-if="item.supported_events.length > 3" class="text-[9px] font-bold text-slate-400 self-center">
                  +{{ item.supported_events.length - 3 }} more
                </span>
              </div>
            </div>
          </div>

          <div class="pt-3 border-t border-slate-100">
            <UiButton class="w-full" size="sm" @click="openConnectModal(item)">
              <Plus class="w-3.5 h-3.5 mr-1" /> {{ t('integrations.marketplaceView.connectGateway') }}
            </UiButton>
          </div>
        </div>
      </div>
    </div>

    <!-- 3. Webhook Activity Logs Tab -->
    <div v-else-if="activeTab === 'logs'" class="space-y-4">
      <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
          <div>
            <h3 class="text-sm font-bold text-slate-900">{{ t('integrations.logsView.title') }}</h3>
            <p class="text-xs text-slate-500">{{ t('integrations.logsView.subtitle') }}</p>
          </div>
          <span class="text-xs font-mono font-bold text-slate-600">{{ t('integrations.logsView.totalEvents', { count: allLogs.length }) }}</span>
        </div>

        <div v-if="!allLogs.length" class="p-16 text-center text-slate-400 text-xs">
          {{ t('integrations.logsView.noLogs') }}
        </div>

        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-100 text-xs">
            <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
              <tr>
                <th class="px-4 py-3 text-left">{{ t('integrations.logsView.timestamp') }}</th>
                <th class="px-4 py-3 text-left">{{ t('integrations.logsView.gatewayConnector') }}</th>
                <th class="px-4 py-3 text-left">{{ t('integrations.logsView.eventTopic') }}</th>
                <th class="px-4 py-3 text-center">{{ t('integrations.logsView.direction') }}</th>
                <th class="px-4 py-3 text-center">{{ t('integrations.logsView.status') }}</th>
                <th class="px-4 py-3 text-right">{{ t('integrations.logsView.payload') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr
                v-for="log in allLogs"
                :key="log.id"
                @click="openPayloadInspector(log)"
                class="hover:bg-slate-50/80 cursor-pointer transition-colors"
              >
                <td class="px-4 py-3 font-mono text-slate-500">
                  {{ new Date(log.created_at).toLocaleString() }}
                </td>
                <td class="px-4 py-3 font-bold text-slate-900">
                  {{ log.integrationName }}
                </td>
                <td class="px-4 py-3 font-mono font-semibold text-blue-700">
                  {{ log.event }}
                </td>
                <td class="px-4 py-3 text-center">
                  <span
                    class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider"
                    :class="log.direction === 'inbound' ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'bg-blue-50 text-blue-700 border border-blue-200'"
                  >
                    {{ log.direction }}
                  </span>
                </td>
                <td class="px-4 py-3 text-center">
                  <span
                    class="px-2 py-0.5 rounded font-mono font-bold text-[11px]"
                    :class="log.status_code && log.status_code < 400 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200'"
                  >
                    HTTP {{ log.status_code || 200 }}
                  </span>
                </td>
                <td class="px-4 py-3 text-right">
                  <span class="inline-flex items-center gap-1 font-bold text-primary-600 hover:text-primary-800 text-xs">
                    {{ t('integrations.logsView.inspect') }} <Code class="w-3 h-3" />
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal: Connect Gateway -->
    <UiModal
      v-model="isConnectModalOpen"
      :title="selectedCatalogItem ? t('integrations.connectModal.title', { name: selectedCatalogItem.name }) : t('integrations.connectModal.defaultTitle')"
      size="lg"
    >
      <div v-if="selectedCatalogItem" class="space-y-4">
        <div class="p-3 bg-blue-50 border border-blue-200 rounded-xl flex items-start gap-3">
          <Plug class="w-5 h-5 text-blue-600 mt-0.5 shrink-0" />
          <p class="text-xs text-blue-900 leading-relaxed">{{ selectedCatalogItem.description }}</p>
        </div>

        <UiInput
          v-model="connectForm.name"
          :label="t('integrations.connectModal.displayName')"
          :placeholder="t('integrations.connectModal.displayNamePlaceholder')"
          required
        />

        <!-- Dynamic Fields -->
        <div v-for="field in selectedCatalogItem.fields" :key="field.key" class="space-y-1">
          <UiInput
            v-if="field.key === 'api_key'"
            v-model="connectForm.api_key"
            :label="field.label"
            :type="field.type"
            :required="field.required"
          />
          <UiInput
            v-else-if="field.key === 'webhook_url'"
            v-model="connectForm.webhook_url"
            :label="field.label"
            :type="field.type"
            :required="field.required"
          />
          <UiInput
            v-else
            v-model="connectForm.settings[field.key]"
            :label="field.label"
            :type="field.type"
            :required="field.required"
          />
        </div>

        <!-- Event Topic Selection -->
        <div class="space-y-2">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">{{ t('integrations.connectModal.subscribedTriggers') }}</label>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 p-3 bg-slate-50 border border-slate-200 rounded-xl max-h-36 overflow-y-auto">
            <label
              v-for="ev in selectedCatalogItem.supported_events"
              :key="ev"
              class="flex items-center gap-2 text-xs font-mono text-slate-700 cursor-pointer hover:text-slate-900"
            >
              <input
                type="checkbox"
                class="rounded border-slate-300 text-primary-600 focus:ring-primary-500 cursor-pointer"
                :value="ev"
                v-model="connectForm.selected_events"
              />
              <span>{{ ev }}</span>
            </label>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
          <UiButton variant="outline" type="button" @click="isConnectModalOpen = false">{{ t('integrations.connectModal.cancel') }}</UiButton>
          <UiButton :loading="connectMutation.isPending.value" @click="handleConnectSubmit">
            {{ t('integrations.connectModal.completeAuthorize') }}
          </UiButton>
        </div>
      </div>
    </UiModal>

    <!-- Modal: Send Test Event -->
    <UiModal v-model="isTestEventModalOpen" :title="t('integrations.testEventModal.title')" size="md">
      <div v-if="selectedIntegration" class="space-y-4">
        <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs space-y-1">
          <span class="text-slate-500 uppercase text-[10px] font-bold">{{ t('integrations.testEventModal.targetGateway') }}</span>
          <p class="font-bold text-slate-900">{{ selectedIntegration.name }} ({{ selectedIntegration.provider }})</p>
        </div>

        <UiSelect
          v-model="testEventForm.event"
          :label="t('integrations.testEventModal.selectEvent')"
          :options="[
            { label: 'invoice.paid (Sales & Billing)', value: 'invoice.paid' },
            { label: 'lead.captured (CRM Prospect)', value: 'lead.captured' },
            { label: 'stock.low_warning (Inventory Alert)', value: 'stock.low_warning' },
            { label: 'ticket.created (Customer Support)', value: 'ticket.created' },
          ]"
        />

        <p class="text-xs text-slate-500 leading-relaxed">
          {{ t('integrations.testEventModal.hint') }}
        </p>

        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
          <UiButton variant="outline" type="button" @click="isTestEventModalOpen = false">{{ t('integrations.testEventModal.cancel') }}</UiButton>
          <UiButton :loading="sendTestEventMutation.isPending.value" @click="handleSendTestEvent">
            <Send class="w-3.5 h-3.5 mr-1" /> {{ t('integrations.testEventModal.transmitPayload') }}
          </UiButton>
        </div>
      </div>
    </UiModal>

    <!-- Modal: Payload Inspector -->
    <UiModal v-model="isPayloadModalOpen" :title="t('integrations.payloadModal.title')" size="lg">
      <div v-if="selectedLogForPayload" class="space-y-4">
        <div class="flex items-center justify-between text-xs p-3 bg-slate-50 border border-slate-200 rounded-xl">
          <div>
            <span class="font-mono font-bold text-slate-900">{{ selectedLogForPayload.event }}</span>
            <span class="text-slate-400 ml-2">({{ selectedLogForPayload.direction }})</span>
          </div>
          <span
            class="px-2 py-0.5 rounded font-mono font-bold"
            :class="selectedLogForPayload.status_code && selectedLogForPayload.status_code < 400 ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'"
          >
            HTTP {{ selectedLogForPayload.status_code || 200 }}
          </span>
        </div>

        <div class="space-y-1.5">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">{{ t('integrations.payloadModal.requestBody') }}</label>
          <pre class="p-4 bg-slate-950 text-emerald-400 rounded-xl text-xs font-mono overflow-x-auto max-h-60 custom-scrollbar">{{ JSON.stringify(selectedLogForPayload.payload, null, 2) }}</pre>
        </div>

        <div v-if="selectedLogForPayload.response" class="space-y-1.5">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">{{ t('integrations.payloadModal.responseBody') }}</label>
          <pre class="p-4 bg-slate-900 text-slate-200 rounded-xl text-xs font-mono overflow-x-auto max-h-40 custom-scrollbar">{{ JSON.stringify(selectedLogForPayload.response, null, 2) }}</pre>
        </div>

        <div class="flex justify-end pt-2">
          <UiButton variant="outline" size="sm" @click="isPayloadModalOpen = false">{{ t('integrations.payloadModal.close') }}</UiButton>
        </div>
      </div>
    </UiModal>

    <!-- Modal: Delete Confirmation -->
    <UiModal v-model="isDeleteModalOpen" :title="t('integrations.deleteModal.title')" size="sm">
      <div v-if="integrationToDelete" class="space-y-4">
        <div class="flex items-start gap-3.5 p-3.5 bg-red-50 border border-red-200 rounded-2xl">
          <div class="p-2 bg-red-100 text-red-600 rounded-xl shrink-0 mt-0.5">
            <Trash2 class="w-5 h-5" />
          </div>
          <div class="space-y-1">
            <h4 class="text-sm font-bold text-red-950">{{ t('integrations.deleteModal.confirmTitle', { name: integrationToDelete.name }) }}</h4>
            <p class="text-xs text-red-800 leading-relaxed">
              {{ t('integrations.deleteModal.confirmMessage') }}
            </p>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
          <UiButton variant="outline" size="sm" type="button" @click="isDeleteModalOpen = false">{{ t('integrations.deleteModal.cancel') }}</UiButton>
          <UiButton
            variant="danger"
            size="sm"
            :loading="deleteMutation.isPending.value"
            @click="deleteMutation.mutate(integrationToDelete.id)"
          >
            <Trash2 class="w-3.5 h-3.5 mr-1" /> {{ t('integrations.deleteModal.disconnect') }}
          </UiButton>
        </div>
      </div>
    </UiModal>
  </div>
</template>
