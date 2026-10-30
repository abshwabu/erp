<script setup lang="ts">
import { computed, ref, markRaw } from 'vue'
import { useI18n } from 'vue-i18n'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import {
  superAdminApi,
  type PlatformTenant,
  type PlatformPlan,
  type PlatformMetrics,
} from '@/api/superAdmin'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiStat from '@/components/ui/UiStat.vue'
import UiSpinner from '@/components/ui/UiSpinner.vue'
import {
  Building2,
  Crown,
  Plus,
  Search,
  ExternalLink,
  ShieldAlert,
  ShieldCheck,
  CreditCard,
  Layers,
  Users,
  CheckCircle2,
  AlertTriangle,
  Server,
  Zap,
  Edit2,
  Trash2,
  LogIn,
  Copy,
  Check,
  RefreshCw,
  Clock,
  Sparkles,
  Globe,
  Database,
  Lock,
  X,
  CheckCircle,
} from '@lucide/vue'
import { useToast } from '@/composables/useToast'
import { useAuthStore } from '@/stores/auth'

const queryClient = useQueryClient()
const toast = useToast()
const authStore = useAuthStore()
const { t } = useI18n()

const activeTab = ref<'tenants' | 'plans' | 'superadmins'>('tenants')
const searchQuery = ref('')
const selectedStatusFilter = ref<string>('all')
const selectedPlanFilter = ref<string>('all')

const isCreateModalOpen = ref(false)
const isEditModalOpen = ref(false)
const isDeleteModalOpen = ref(false)

const editingTenant = ref<PlatformTenant | null>(null)
const tenantToDelete = ref<PlatformTenant | null>(null)
const deleteConfirmationInput = ref('')

const moduleMatrix = [
  { key: 'sales', name: 'Sales & Invoicing', category: 'Revenue', basic: true, pro: true, enterprise: true, note: '100 inv/mo on Basic, Unlimited on Pro & Ent' },
  { key: 'crm', name: 'CRM & Lead Intake', category: 'Revenue', basic: true, pro: true, enterprise: true, note: 'Web forms, lead pipeline & deals' },
  { key: 'inventory', name: 'Inventory & Stock Control', category: 'Operations', basic: true, pro: true, enterprise: true, note: 'Single warehouse on Basic, Multi-warehouse on Pro/Ent' },
  { key: 'pos', name: 'POS Cashier Terminal', category: 'Operations', basic: true, pro: true, enterprise: true, note: 'Cashier sessions & barcode receipts' },
  { key: 'core', name: 'Core Settings & RBAC', category: 'Administration', basic: true, pro: true, enterprise: true, note: 'User preferences & role permissions' },
  { key: 'accounting', name: 'Double-Entry Accounting', category: 'Finance', basic: false, pro: true, enterprise: true, note: 'General ledger, journal entries, trial balance & aging' },
  { key: 'procurement', name: 'Procurement & Supplier POs', category: 'Operations', basic: false, pro: true, enterprise: true, note: 'Purchase orders, bills & vendor directory' },
  { key: 'hr', name: 'Human Resources & Attendance', category: 'People', basic: false, pro: true, enterprise: true, note: 'Staff directory, attendance & leave approvals' },
  { key: 'payroll', name: 'Payroll Runs & Pay Stubs', category: 'People', basic: false, pro: true, enterprise: true, note: 'Automated disbursement calculations & payslips' },
  { key: 'projects', name: 'Projects & Billable Time', category: 'Operations', basic: false, pro: true, enterprise: true, note: 'Task boards, milestones & timesheet logs' },
  { key: 'support', name: 'Support & Helpdesk', category: 'People', basic: false, pro: true, enterprise: true, note: 'Ticket queue, SLA routing & knowledge articles' },
  { key: 'assets', name: 'Fixed Asset Depreciation', category: 'Finance', basic: false, pro: true, enterprise: true, note: 'Asset registry & write-down schedules' },
  { key: 'manufacturing', name: 'Manufacturing & BOMs', category: 'Operations', basic: false, pro: false, enterprise: true, note: 'Production work orders & bill of materials' },
  { key: 'ecommerce', name: 'Multi-Storefront Ecommerce', category: 'Revenue', basic: false, pro: false, enterprise: true, note: 'Public storefronts & online catalog engine' },
  { key: 'integrations', name: 'Webhooks & API Connectors', category: 'Technology', basic: false, pro: false, enterprise: true, note: '12-gateway connectors & real-time webhook engine' },
]

// Forms
const createForm = ref({
  name: '',
  slug: '',
  custom_domain: '',
  plan_id: '',
  status: 'active',
  currency: 'ETB',
  timezone: 'Africa/Addis_Ababa',
  admin_name: '',
  admin_email: '',
  admin_password: '',
})

const editForm = ref({
  name: '',
  slug: '',
  custom_domain: '',
  plan_id: '',
  status: 'active',
})

// Queries
const { data: metrics, isLoading: isLoadingMetrics } = useQuery({
  queryKey: ['super-admin', 'metrics'],
  queryFn: () => superAdminApi.getMetrics().then((res) => res.data.data),
})

const { data: tenants, isLoading: isLoadingTenants } = useQuery({
  queryKey: ['super-admin', 'tenants'],
  queryFn: () => superAdminApi.getTenants().then((res) => res.data.data),
})

const { data: plans } = useQuery({
  queryKey: ['super-admin', 'plans'],
  queryFn: () => superAdminApi.getPlans().then((res) => res.data.data),
})

// Filtered Tenants
const filteredTenants = computed(() => {
  let list = tenants.value || []

  if (selectedStatusFilter.value !== 'all') {
    list = list.filter((t) => t.status === selectedStatusFilter.value)
  }

  if (selectedPlanFilter.value !== 'all') {
    list = list.filter((t) => t.plan?.id === selectedPlanFilter.value || t.plan?.name === selectedPlanFilter.value)
  }

  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(
      (t) =>
        t.name.toLowerCase().includes(q) ||
        t.slug.toLowerCase().includes(q) ||
        (t.custom_domain && t.custom_domain.toLowerCase().includes(q))
    )
  }

  return list
})

// Stats Cards
const statCards = computed(() => {
  const m = metrics.value
  const mrrFormatted = m?.mrr_cents ? (m.mrr_cents / 100).toLocaleString() + ' Birr' : '0 Birr'
  const arrFormatted = m?.arr_cents ? (m.arr_cents / 100).toLocaleString() + ' Birr' : '0 Birr'

  return [
    {
      label: t('superadmin.stats.totalTenants'),
      value: m?.total_tenants || tenants.value?.length || 0,
      icon: markRaw(Building2),
    },
    {
      label: t('superadmin.stats.activeSubscriptions'),
      value: m?.active_tenants || 0,
      icon: markRaw(ShieldCheck),
    },
    {
      label: t('superadmin.stats.mrr'),
      value: mrrFormatted,
      icon: markRaw(CreditCard),
    },
    {
      label: t('superadmin.stats.trialWorkspaces'),
      value: m?.trial_tenants || 0,
      icon: markRaw(Clock),
    },
  ]
})

// --- Mutations ---
const createTenantMutation = useMutation({
  mutationFn: (payload: any) => superAdminApi.createTenant(payload),
  onSuccess: (res) => {
    queryClient.invalidateQueries({ queryKey: ['super-admin'] })
    isCreateModalOpen.value = false
    toast.success(res.data.data?.message || t('superadmin.messages.provisionSuccess'))
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || t('superadmin.messages.failedProvision'))
  },
})

const updateTenantMutation = useMutation({
  mutationFn: ({ id, data }: { id: string; data: any }) => superAdminApi.updateTenant(id, data),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['super-admin'] })
    isEditModalOpen.value = false
    toast.success(t('superadmin.messages.updateSuccess'))
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || t('superadmin.messages.failedUpdate'))
  },
})

const updateStatusMutation = useMutation({
  mutationFn: ({ id, status }: { id: string; status: string }) => superAdminApi.updateTenantStatus(id, status),
  onSuccess: (res) => {
    queryClient.invalidateQueries({ queryKey: ['super-admin'] })
    toast.success(res.data.data?.message || t('superadmin.messages.statusSuccess'))
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || t('superadmin.messages.failedStatus'))
  },
})

const impersonateMutation = useMutation({
  mutationFn: (id: string) => superAdminApi.impersonateTenant(id),
  onSuccess: async (res) => {
    try {
      const data = res.data.data
      if (data?.access_token) {
        await authStore.impersonateTenant(data.access_token, {
          id: data.tenant_id,
          name: data.tenant_name,
          domain: data.tenant_slug,
        })
        toast.success(t('superadmin.messages.switchedContext', { name: data.tenant_name }))
        setTimeout(() => {
          window.location.href = '/'
        }, 300)
      } else {
        toast.error(t('superadmin.messages.tokenMissing'))
      }
    } catch (err: any) {
      toast.error(err?.response?.data?.message || err?.message || t('superadmin.messages.impersonationFailed'))
    }
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || err?.message || t('superadmin.messages.impersonationFailed'))
  },
})

const deleteTenantMutation = useMutation({
  mutationFn: (id: string) => superAdminApi.deleteTenant(id),
  onSuccess: (res) => {
    queryClient.invalidateQueries({ queryKey: ['super-admin'] })
    isDeleteModalOpen.value = false
    tenantToDelete.value = null
    toast.success(res.data.data?.message || t('superadmin.messages.decommissionSuccess'))
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || t('superadmin.messages.failedDecommission'))
  },
})

// --- Handlers ---
function openCreateModal() {
  const defaultPlan = plans.value?.[0]?.id || ''
  createForm.value = {
    name: '',
    slug: '',
    custom_domain: '',
    plan_id: defaultPlan,
    status: 'active',
    currency: 'USD',
    timezone: 'UTC',
    admin_name: '',
    admin_email: '',
    admin_password: '',
  }
  isCreateModalOpen.value = true
}

function handleNameInput(name: string) {
  createForm.value.name = name
  if (!createForm.value.slug || createForm.value.slug === '') {
    createForm.value.slug = name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '')
  }
}

function handleCreateSubmit() {
  if (!createForm.value.name || !createForm.value.admin_email || !createForm.value.admin_password) {
    toast.error(t('superadmin.messages.requiredFields'))
    return
  }

  createTenantMutation.mutate(createForm.value)
}

function openEditModal(tenant: PlatformTenant) {
  editingTenant.value = tenant
  editForm.value = {
    name: tenant.name,
    slug: tenant.slug,
    custom_domain: tenant.custom_domain || '',
    plan_id: tenant.plan?.id || '',
    status: tenant.status,
  }
  isEditModalOpen.value = true
}

function handleEditSubmit() {
  if (!editingTenant.value) return
  updateTenantMutation.mutate({
    id: editingTenant.value.id,
    data: editForm.value,
  })
}

function openDeleteModal(tenant: PlatformTenant) {
  tenantToDelete.value = tenant
  deleteConfirmationInput.value = ''
  isDeleteModalOpen.value = true
}

function handleDeleteSubmit() {
  if (!tenantToDelete.value) return
  deleteTenantMutation.mutate(tenantToDelete.value.id)
}

const copiedSlug = ref<string | null>(null)
function copyDomain(text: string, id: string) {
  navigator.clipboard.writeText(text)
  copiedSlug.value = id
  setTimeout(() => {
    copiedSlug.value = null
  }, 2000)
}
</script>

<template>
  <div class="space-y-6 max-w-7xl font-sans pb-12">
    <!-- Header Banner -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-gradient-to-r from-slate-900 via-slate-800 to-indigo-950 text-white p-6 rounded-3xl shadow-md border border-slate-700">
      <div class="space-y-1">
        <div class="flex items-center gap-2">
          <Crown class="w-6 h-6 text-amber-400" />
          <h1 class="text-2xl sm:text-3xl font-black tracking-tight">{{ t('superadmin.header.title') }}</h1>
          <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-400/20 text-amber-300 border border-amber-400/30">
            {{ t('superadmin.header.badge') }}
          </span>
        </div>
        <p class="text-xs sm:text-sm text-slate-300">
          {{ t('superadmin.header.subtitle') }}
        </p>
      </div>

      <div class="flex items-center gap-2">
        <UiButton variant="primary" class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-black" @click="openCreateModal">
          <Plus class="w-4 h-4 mr-1.5" /> {{ t('superadmin.header.provisionButton') }}
        </UiButton>
      </div>
    </div>

    <!-- Top Platform Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <UiStat
        v-for="stat in statCards"
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
        @click="activeTab = 'tenants'"
        class="px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-2 whitespace-nowrap"
        :class="activeTab === 'tenants' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
      >
        <Building2 class="w-3.5 h-3.5" /> {{ t('superadmin.tabs.tenants') }} ({{ tenants?.length || 0 }})
      </button>

      <button
        type="button"
        @click="activeTab = 'plans'"
        class="px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-2 whitespace-nowrap"
        :class="activeTab === 'plans' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
      >
        <CreditCard class="w-3.5 h-3.5" /> {{ t('superadmin.tabs.plans') }} ({{ plans?.length || 3 }})
      </button>

      <button
        type="button"
        @click="activeTab = 'superadmins'"
        class="px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-2 whitespace-nowrap"
        :class="activeTab === 'superadmins' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
      >
        <ShieldAlert class="w-3.5 h-3.5" /> {{ t('superadmin.tabs.superadmins') }}
      </button>
    </div>

    <!-- 1. Tenants Management Tab -->
    <div v-if="activeTab === 'tenants'" class="space-y-4">
      <!-- Filter Toolbar -->
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 bg-white p-3.5 rounded-2xl border border-slate-200 shadow-xs">
        <div class="flex flex-wrap items-center gap-2">
          <!-- Status Filters -->
          <div class="flex items-center gap-1 bg-slate-50 p-1 rounded-xl border border-slate-200">
            <button
              v-for="st in ['all', 'active', 'trial', 'suspended']"
              :key="st"
              type="button"
              @click="selectedStatusFilter = st"
              class="px-3 py-1 rounded-lg text-xs font-bold capitalize transition-all cursor-pointer"
              :class="selectedStatusFilter === st ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900'"
            >
              {{ st === 'all' ? t('superadmin.filters.allStatus') : st }}
            </button>
          </div>

          <!-- Plan Filter -->
          <UiSelect
            v-model="selectedPlanFilter"
            size="sm"
            class="w-44"
            :options="[
              { label: t('superadmin.filters.allPlans'), value: 'all' },
              ...(plans || []).map(p => ({ label: p.name + ' Plan', value: p.id }))
            ]"
          />
        </div>

        <UiInput
          v-model="searchQuery"
          :placeholder="t('superadmin.filters.searchPlaceholder')"
          size="sm"
          class="w-full lg:w-72"
        >
          <template #prefix><Search class="w-3.5 h-3.5 text-slate-400" /></template>
        </UiInput>
      </div>

      <!-- Tenants Table -->
      <div v-if="isLoadingTenants" class="p-16 flex justify-center">
        <UiSpinner size="lg" />
      </div>

      <div v-else class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div v-if="filteredTenants.length" class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-100 text-xs">
            <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
              <tr>
                <th class="px-4 py-3 text-left">{{ t('superadmin.table.tenantOrg') }}</th>
                <th class="px-4 py-3 text-left">{{ t('superadmin.table.subdomain') }}</th>
                <th class="px-4 py-3 text-left">{{ t('superadmin.table.plan') }}</th>
                <th class="px-4 py-3 text-center">{{ t('superadmin.table.status') }}</th>
                <th class="px-4 py-3 text-center">{{ t('superadmin.table.members') }}</th>
                <th class="px-4 py-3 text-right">{{ t('superadmin.table.createdDate') }}</th>
                <th class="px-4 py-3 text-right">{{ t('superadmin.table.actions') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr v-for="tenant in filteredTenants" :key="tenant.id" class="hover:bg-slate-50/80 transition-colors">
                <td class="px-4 py-3.5">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-slate-900 to-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow-xs shrink-0">
                      {{ tenant.name.charAt(0).toUpperCase() }}
                    </div>
                    <div>
                      <div class="font-bold text-slate-900 text-sm flex items-center gap-1.5">
                        {{ tenant.name }}
                        <span v-if="tenant.slug === 'demo'" class="text-[10px] bg-indigo-50 text-indigo-700 px-1.5 py-0.2 rounded font-bold border border-indigo-100">
                          {{ t('superadmin.table.primaryDemo') }}
                        </span>
                      </div>
                      <div class="text-[11px] text-slate-400 font-mono mt-0.5">
                        UUID: {{ tenant.id.substring(0, 13) }}...
                      </div>
                    </div>
                  </div>
                </td>

                <td class="px-4 py-3.5">
                  <div class="space-y-1">
                    <div class="flex items-center gap-1.5 font-mono text-[11px] text-blue-700 font-bold">
                      <span>{{ tenant.slug }}.erp.local</span>
                      <button
                        type="button"
                        @click="copyDomain(tenant.slug + '.erp.local', tenant.id)"
                        class="text-slate-400 hover:text-slate-600 cursor-pointer"
                        title="Copy Subdomain"
                      >
                        <Check v-if="copiedSlug === tenant.id" class="w-3 h-3 text-emerald-600" />
                        <Copy v-else class="w-3 h-3" />
                      </button>
                    </div>
                    <div v-if="tenant.custom_domain" class="text-[10px] text-slate-500 flex items-center gap-1 font-mono">
                      <Globe class="w-3 h-3 text-slate-400" />
                      <span>{{ tenant.custom_domain }}</span>
                    </div>
                  </div>
                </td>

                <td class="px-4 py-3.5">
                  <span
                    class="px-2.5 py-1 rounded-lg text-xs font-bold inline-flex items-center gap-1 border"
                    :class="[
                      tenant.plan?.name === 'Enterprise' ? 'bg-purple-50 text-purple-700 border-purple-200' :
                      tenant.plan?.name === 'Professional' ? 'bg-blue-50 text-blue-700 border-blue-200' :
                      'bg-slate-50 text-slate-700 border-slate-200'
                    ]"
                  >
                    <Crown v-if="tenant.plan?.name === 'Enterprise'" class="w-3 h-3 text-amber-500" />
                    {{ tenant.plan?.name || 'Custom Plan' }}
                  </span>
                </td>

                <td class="px-4 py-3.5 text-center">
                  <button
                    type="button"
                    @click="updateStatusMutation.mutate({ id: tenant.id, status: tenant.status === 'active' ? 'suspended' : 'active' })"
                    class="inline-flex items-center gap-1 font-bold text-[10px] px-2.5 py-0.5 rounded-full transition-all cursor-pointer"
                    :class="[
                      tenant.status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' :
                      tenant.status === 'trial' ? 'bg-blue-50 text-blue-700 border border-blue-200' :
                      'bg-red-50 text-red-700 border border-red-200'
                    ]"
                    :title="t('superadmin.table.clickToggleStatus')"
                  >
                    <span class="w-1.5 h-1.5 rounded-full" :class="tenant.status === 'active' ? 'bg-emerald-500' : tenant.status === 'trial' ? 'bg-blue-500' : 'bg-red-500'"></span>
                    <span class="capitalize">{{ tenant.status }}</span>
                  </button>
                </td>

                <td class="px-4 py-3.5 text-center">
                  <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-bold text-xs">
                    {{ t('superadmin.table.usersCount', { count: tenant.users_count || 1 }) }}
                  </span>
                </td>

                <td class="px-4 py-3.5 text-right font-mono text-slate-500">
                  {{ new Date(tenant.created_at).toLocaleDateString() }}
                </td>

                <td class="px-4 py-3.5 text-right whitespace-nowrap space-x-1">
                  <UiButton
                    variant="outline"
                    size="sm"
                    class="text-primary-700 hover:bg-primary-50"
                    :loading="impersonateMutation.isPending.value"
                    @click="impersonateMutation.mutate(tenant.id)"
                    title="Switch / Impersonate into Workspace"
                  >
                    <LogIn class="w-3.5 h-3.5 mr-1" /> {{ t('superadmin.table.switch') }}
                  </UiButton>

                  <UiButton variant="ghost" size="sm" @click="openEditModal(tenant)" title="Edit Tenant">
                    <Edit2 class="w-3.5 h-3.5" />
                  </UiButton>

                  <UiButton
                    v-if="tenant.slug !== 'demo'"
                    variant="ghost"
                    size="sm"
                    class="text-red-500 hover:text-red-700 hover:bg-red-50"
                    @click="openDeleteModal(tenant)"
                    title="Decommission Tenant"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                  </UiButton>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="p-12 text-center text-slate-400 text-xs">
          {{ t('superadmin.table.noTenants') }}
        </div>
      </div>
    </div>

    <!-- 2. Subscription Plans Tab -->
    <div v-else-if="activeTab === 'plans'" class="space-y-8">
      <!-- Tier Pricing & Perks Cards -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div
          v-for="plan in plans"
          :key="plan.id"
          class="bg-white rounded-3xl border p-6 shadow-xs flex flex-col justify-between space-y-6 relative overflow-hidden transition-all duration-200 hover:shadow-md"
          :class="[
            plan.slug === 'enterprise' ? 'border-purple-300 ring-2 ring-purple-600/30' :
            plan.slug === 'professional' ? 'border-blue-300 ring-2 ring-blue-600/30' :
            'border-slate-200'
          ]"
        >
          <!-- Top Badge -->
          <div
            v-if="plan.badge"
            class="absolute top-0 right-0 font-bold text-[10px] px-3 py-1 rounded-bl-xl uppercase tracking-wider text-white"
            :class="[
              plan.slug === 'enterprise' ? 'bg-gradient-to-r from-purple-600 to-indigo-600' :
              plan.slug === 'professional' ? 'bg-gradient-to-r from-blue-600 to-cyan-600' :
              'bg-slate-700'
            ]"
          >
            {{ plan.badge }}
          </div>

          <div class="space-y-4">
            <div>
              <div class="flex items-center gap-2">
                <Crown v-if="plan.slug === 'enterprise'" class="w-5 h-5 text-amber-500" />
                <Zap v-else-if="plan.slug === 'professional'" class="w-5 h-5 text-blue-500" />
                <Layers v-else class="w-5 h-5 text-slate-500" />
                <h3 class="text-xl font-black text-slate-900">{{ plan.name }} {{ t('superadmin.plansTab.tier') }}</h3>
              </div>
              <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">{{ plan.tagline || plan.description }}</p>
            </div>

            <!-- Price -->
            <div class="p-4 bg-slate-50/80 rounded-2xl border border-slate-100 space-y-1">
              <div class="flex items-baseline gap-1.5">
                <span class="text-3xl font-black text-slate-900">{{ (plan.price_monthly / 100).toLocaleString() }}</span>
                <span class="text-sm font-bold text-slate-700">Birr</span>
                <span class="text-xs text-slate-500 font-medium">{{ t('superadmin.plansTab.perMonth') }}</span>
              </div>
              <div class="text-[11px] text-slate-400 font-medium flex items-center justify-between">
                <span>{{ t('superadmin.plansTab.orYearly', { amount: (plan.price_annually / 100).toLocaleString() }) }}</span>
                <span class="font-bold text-slate-700 bg-white px-2 py-0.5 rounded-md border border-slate-200">
                  {{ t('superadmin.plansTab.activeTenantsCount', { count: plan.tenants_count || 0 }) }}
                </span>
              </div>
            </div>

            <!-- Capacity Highlights -->
            <div class="grid grid-cols-2 gap-2 text-[11px]">
              <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200 flex flex-col">
                <span class="text-slate-400 font-semibold text-[10px] uppercase">{{ t('superadmin.plansTab.userCapacity') }}</span>
                <span class="font-bold text-slate-800">
                  {{ (plan.limits?.users_limit === -1 || plan.slug === 'enterprise') ? t('superadmin.plansTab.unlimitedUsers') : t('superadmin.plansTab.userSeats', { count: plan.limits?.users_limit || 5 }) }}
                </span>
              </div>
              <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200 flex flex-col">
                <span class="text-slate-400 font-semibold text-[10px] uppercase">{{ t('superadmin.plansTab.fileStorage') }}</span>
                <span class="font-bold text-slate-800">
                  {{ t('superadmin.plansTab.cloudStorage', { count: plan.limits?.storage_gb || (plan.slug === 'enterprise' ? 500 : plan.slug === 'professional' ? 50 : 5) }) }}
                </span>
              </div>
            </div>

            <!-- Module Access Pills -->
            <div class="space-y-2 pt-2 border-t border-slate-100">
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">
                {{ t('superadmin.plansTab.moduleEntitlements', { count: plan.allowed_modules?.includes('*') ? 15 : (plan.allowed_modules?.length || 5) }) }}
              </span>
              <div class="flex flex-wrap gap-1.5">
                <span
                  v-if="plan.allowed_modules?.includes('*')"
                  class="px-2.5 py-0.5 rounded-lg text-[11px] font-bold bg-purple-50 text-purple-700 border border-purple-200 flex items-center gap-1"
                >
                  <Sparkles class="w-3 h-3 text-purple-600" /> {{ t('superadmin.plansTab.allModulesUnlocked') }}
                </span>
                <template v-else>
                  <span
                    v-for="mod in plan.allowed_modules"
                    :key="mod"
                    class="px-2 py-0.5 rounded-lg text-[10px] font-bold capitalize bg-slate-100 text-slate-700 border border-slate-200"
                  >
                    {{ mod }}
                  </span>
                </template>
              </div>
            </div>

            <!-- Perks Checklist -->
            <div class="space-y-2 pt-2 border-t border-slate-100">
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">{{ t('superadmin.plansTab.exclusivePerks') }}</span>
              <ul class="space-y-1.5 text-xs text-slate-700">
                <li
                  v-for="(perk, i) in plan.perks"
                  :key="i"
                  class="flex items-start gap-2 leading-snug"
                >
                  <CheckCircle2 class="w-3.5 h-3.5 text-emerald-600 shrink-0 mt-0.5" />
                  <span :class="perk.startsWith('Everything in') ? 'font-bold text-indigo-700' : ''">{{ perk }}</span>
                </li>
              </ul>
            </div>
          </div>

          <div class="pt-4 border-t border-slate-100">
            <div class="text-[11px] text-slate-500 font-mono flex items-center justify-between">
              <span>{{ t('superadmin.plansTab.slaLevel') }}</span>
              <span class="font-bold text-slate-800">{{ plan.limits?.support_sla || (plan.slug === 'enterprise' ? '24/7 1h SLA' : plan.slug === 'professional' ? '12h Business SLA' : '48h Standard') }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Comprehensive Module Entitlements Matrix -->
      <div class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden space-y-4 p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-4">
          <div>
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
              <Layers class="w-4 h-4 text-indigo-600" /> {{ t('superadmin.plansTab.matrixTitle') }}
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">
              {{ t('superadmin.plansTab.matrixSubtitle') }}
            </p>
          </div>
          <span class="text-xs font-mono text-slate-400 bg-slate-50 px-2.5 py-1 rounded-lg border border-slate-200">
            {{ t('superadmin.plansTab.modulesEvaluated') }}
          </span>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-100 text-xs">
            <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
              <tr>
                <th class="px-4 py-3 text-left w-1/4">{{ t('superadmin.plansTab.tableModule') }}</th>
                <th class="px-4 py-3 text-left w-1/6">{{ t('superadmin.plansTab.tableCategory') }}</th>
                <th class="px-4 py-3 text-center w-1/6">
                  <div class="font-bold text-slate-800">Basic</div>
                  <div class="text-[9px] text-slate-400 font-normal lowercase">1,000 Birr/month</div>
                </th>
                <th class="px-4 py-3 text-center w-1/6">
                  <div class="font-bold text-blue-700">Professional</div>
                  <div class="text-[9px] text-slate-400 font-normal lowercase">2,500 Birr/month</div>
                </th>
                <th class="px-4 py-3 text-center w-1/6">
                  <div class="font-bold text-purple-700 flex items-center justify-center gap-1">
                    <Crown class="w-3 h-3 text-amber-500" /> Enterprise
                  </div>
                  <div class="text-[9px] text-slate-400 font-normal lowercase">5,000 Birr/month</div>
                </th>
                <th class="px-4 py-3 text-left">{{ t('superadmin.plansTab.tableDifferences') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr
                v-for="row in moduleMatrix"
                :key="row.key"
                class="hover:bg-slate-50/70 transition-colors"
              >
                <td class="px-4 py-3 font-bold text-slate-900">
                  {{ row.name }}
                </td>

                <td class="px-4 py-3">
                  <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-600">
                    {{ row.category }}
                  </span>
                </td>

                <!-- Basic Column -->
                <td class="px-4 py-3 text-center">
                  <div v-if="row.basic" class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-50 text-emerald-600">
                    <CheckCircle2 class="w-4 h-4" />
                  </div>
                  <div v-else class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-slate-400" :title="t('superadmin.plansTab.lockedBasic')">
                    <Lock class="w-3 h-3 text-slate-400" />
                  </div>
                </td>

                <!-- Professional Column -->
                <td class="px-4 py-3 text-center">
                  <div v-if="row.pro" class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-50 text-emerald-600">
                    <CheckCircle2 class="w-4 h-4" />
                  </div>
                  <div v-else class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-slate-400" :title="t('superadmin.plansTab.lockedPro')">
                    <Lock class="w-3 h-3 text-slate-400" />
                  </div>
                </td>

                <!-- Enterprise Column -->
                <td class="px-4 py-3 text-center">
                  <div v-if="row.enterprise" class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-purple-50 text-purple-700">
                    <CheckCircle2 class="w-4 h-4" />
                  </div>
                </td>

                <!-- Notes -->
                <td class="px-4 py-3 text-slate-500 text-[11px]">
                  {{ row.note }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- 3. Super Admins Tab -->
    <div v-else-if="activeTab === 'superadmins'" class="space-y-6">
      <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-xs space-y-6">
        <div class="space-y-1 border-b border-slate-100 pb-4">
          <div class="flex items-center gap-2">
            <ShieldAlert class="w-5 h-5 text-amber-500" />
            <h2 class="text-base font-bold text-slate-900">{{ t('superadmin.credentialsTab.title') }}</h2>
          </div>
          <p class="text-xs text-slate-500">
            {{ t('superadmin.credentialsTab.subtitle') }}
          </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
          <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-2">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ t('superadmin.credentialsTab.loginEmail') }}</span>
            <p class="font-mono text-slate-900 font-bold text-sm">superadmin@erp.local</p>
            <p class="text-slate-500 text-[11px]">{{ t('superadmin.credentialsTab.primaryOwner') }}</p>
          </div>

          <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-2">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ t('superadmin.credentialsTab.seededPassword') }}</span>
            <p class="font-mono text-slate-900 font-bold text-sm">password123</p>
            <p class="text-slate-500 text-[11px]">{{ t('superadmin.credentialsTab.passwordNote') }}</p>
          </div>
        </div>

        <div class="p-4 bg-blue-50 border border-blue-200 rounded-2xl flex items-start gap-3">
          <Database class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" />
          <div class="text-xs text-blue-900 space-y-1">
            <h4 class="font-bold">{{ t('superadmin.credentialsTab.dbArchitectureTitle') }}</h4>
            <p class="leading-relaxed">
              {{ t('superadmin.credentialsTab.dbArchitectureDesc') }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal: Provision New Tenant -->
    <UiModal v-model="isCreateModalOpen" :title="t('superadmin.createModal.title')" size="lg">
      <div class="space-y-5">
        <div class="p-3.5 bg-blue-50 border border-blue-200 rounded-2xl flex items-start gap-3">
          <Building2 class="w-5 h-5 text-blue-600 mt-0.5 shrink-0" />
          <p class="text-xs text-blue-900 leading-relaxed">
            {{ t('superadmin.createModal.description') }}
          </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UiInput
            :model-value="createForm.name"
            @update:model-value="handleNameInput"
            :label="t('superadmin.createModal.orgName')"
            :placeholder="t('superadmin.createModal.orgNamePlaceholder')"
            required
          />

          <UiInput
            v-model="createForm.slug"
            :label="t('superadmin.createModal.slug')"
            :placeholder="t('superadmin.createModal.slugPlaceholder')"
            required
          />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UiInput
            v-model="createForm.custom_domain"
            :label="t('superadmin.createModal.customDomain')"
            :placeholder="t('superadmin.createModal.customDomainPlaceholder')"
          />

          <UiSelect
            v-model="createForm.plan_id"
            :label="t('superadmin.createModal.plan')"
            :options="(plans || []).map(p => ({ label: `${p.name} (${(p.price_monthly / 100).toLocaleString()} Birr/mo)`, value: p.id }))"
          />
        </div>

        <div class="border-t border-slate-100 pt-4 space-y-3">
          <span class="text-xs font-bold uppercase tracking-wider text-slate-800 block">{{ t('superadmin.createModal.adminSection') }}</span>
          
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <UiInput
              v-model="createForm.admin_name"
              :label="t('superadmin.createModal.adminName')"
              :placeholder="t('superadmin.createModal.adminNamePlaceholder')"
              required
            />
            <UiInput
              v-model="createForm.admin_email"
              :label="t('superadmin.createModal.adminEmail')"
              type="email"
              :placeholder="t('superadmin.createModal.adminEmailPlaceholder')"
              required
            />
            <UiInput
              v-model="createForm.admin_password"
              :label="t('superadmin.createModal.adminPassword')"
              type="password"
              :placeholder="t('superadmin.createModal.adminPasswordPlaceholder')"
              required
            />
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
          <UiButton variant="outline" type="button" @click="isCreateModalOpen = false">{{ t('superadmin.createModal.cancel') }}</UiButton>
          <UiButton :loading="createTenantMutation.isPending.value" @click="handleCreateSubmit">
            {{ t('superadmin.createModal.submit') }}
          </UiButton>
        </div>
      </div>
    </UiModal>

    <!-- Modal: Edit Tenant -->
    <UiModal v-model="isEditModalOpen" :title="t('superadmin.editModal.title')" size="md">
      <div v-if="editingTenant" class="space-y-4">
        <UiInput v-model="editForm.name" :label="t('superadmin.editModal.orgName')" required />
        <UiInput v-model="editForm.slug" :label="t('superadmin.editModal.slug')" required />
        <UiInput v-model="editForm.custom_domain" :label="t('superadmin.editModal.customDomain')" :placeholder="t('superadmin.editModal.customDomainPlaceholder')" />

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <UiSelect
            v-model="editForm.plan_id"
            :label="t('superadmin.editModal.plan')"
            :options="(plans || []).map(p => ({ label: p.name, value: p.id }))"
          />

          <UiSelect
            v-model="editForm.status"
            :label="t('superadmin.editModal.status')"
            :options="[
              { label: t('superadmin.editModal.statusActive'), value: 'active' },
              { label: t('superadmin.editModal.statusTrial'), value: 'trial' },
              { label: t('superadmin.editModal.statusSuspended'), value: 'suspended' },
            ]"
          />
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
          <UiButton variant="outline" type="button" @click="isEditModalOpen = false">{{ t('superadmin.editModal.cancel') }}</UiButton>
          <UiButton :loading="updateTenantMutation.isPending.value" @click="handleEditSubmit">
            {{ t('superadmin.editModal.submit') }}
          </UiButton>
        </div>
      </div>
    </UiModal>

    <!-- Modal: Delete / Decommission Tenant -->
    <UiModal v-model="isDeleteModalOpen" :title="t('superadmin.deleteModal.title')" size="sm">
      <div v-if="tenantToDelete" class="space-y-4">
        <div class="p-3.5 bg-red-50 border border-red-200 rounded-2xl flex items-start gap-3">
          <Trash2 class="w-5 h-5 text-red-600 mt-0.5 shrink-0" />
          <div class="space-y-1 text-xs text-red-900">
            <h4 class="font-bold">{{ t('superadmin.deleteModal.confirmTitle') }}</h4>
            <p class="leading-relaxed">
              {{ t('superadmin.deleteModal.confirmMessage', { name: tenantToDelete.name, slug: tenantToDelete.slug }) }}
            </p>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
          <UiButton variant="outline" size="sm" type="button" @click="isDeleteModalOpen = false">{{ t('superadmin.deleteModal.cancel') }}</UiButton>
          <UiButton
            variant="danger"
            size="sm"
            :loading="deleteTenantMutation.isPending.value"
            @click="handleDeleteSubmit"
          >
            <Trash2 class="w-3.5 h-3.5 mr-1" /> {{ t('superadmin.deleteModal.submit') }}
          </UiButton>
        </div>
      </div>
    </UiModal>
  </div>
</template>
