<script setup lang="ts">
import { ref, computed, markRaw } from 'vue'
import { useI18n } from 'vue-i18n'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { crmApi } from '@/api/crm'
import type { Lead, LeadStatus, LeadPriority } from '@/types/crm'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiStat from '@/components/ui/UiStat.vue'
import UiSpinner from '@/components/ui/UiSpinner.vue'
import { useToast } from '@/composables/useToast'
import {
  Users,
  Plus,
  Search,
  CheckCircle2,
  Clock,
  ArrowRight,
  TrendingUp,
  Mail,
  Phone,
  Building2,
  Trash2,
  Edit,
  Sparkles,
  DollarSign,
  Briefcase,
  Eye,
  FileText,
  Calendar,
  Layers,
} from '@lucide/vue'

const { t } = useI18n()
const queryClient = useQueryClient()
const toast = useToast()

const searchQuery = ref('')
const selectedStatus = ref<string>('all')
const isCreateModalOpen = ref(false)
const isConvertModalOpen = ref(false)
const isDetailDrawerOpen = ref(false)
const editingLead = ref<Lead | null>(null)
const convertingLead = ref<Lead | null>(null)
const selectedLead = ref<Lead | null>(null)

const leadForm = ref({
  name: '',
  company: '',
  title: '',
  email: '',
  phone: '',
  source: 'website',
  status: 'new' as LeadStatus,
  priority: 'medium' as LeadPriority,
  estimated_value: '',
  currency: 'USD',
  notes: '',
})

const convertForm = ref({
  deal_title: '',
  deal_amount: '',
  stage: 'qualification',
})

// Queries
const { data: leads, isLoading } = useQuery({
  queryKey: ['crm', 'leads'],
  queryFn: () => crmApi.getLeads().then((r) => r.data.data),
})

const filteredLeads = computed(() => {
  let list = leads.value || []
  if (selectedStatus.value !== 'all') {
    list = list.filter((l) => l.status === selectedStatus.value)
  }
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(
      (l) =>
        l.name.toLowerCase().includes(q) ||
        (l.company || '').toLowerCase().includes(q) ||
        (l.email || '').toLowerCase().includes(q) ||
        (l.phone || '').toLowerCase().includes(q)
    )
  }
  return list
})

// Stats
const stats = computed(() => {
  const list = leads.value || []
  const qualified = list.filter((l) => l.status === 'qualified').length
  const converted = list.filter((l) => l.status === 'converted').length
  const totalValue = list.reduce((sum, l) => sum + Number(l.estimated_value || 0), 0)

  return [
    {
      label: t('crm.leads.kpis.totalInbound'),
      value: list.length,
      icon: markRaw(Users),
    },
    {
      label: t('crm.leads.kpis.qualified'),
      value: qualified,
      icon: markRaw(Sparkles),
    },
    {
      label: t('crm.leads.kpis.converted'),
      value: converted,
      icon: markRaw(CheckCircle2),
    },
    {
      label: t('crm.leads.kpis.estValue'),
      value: `$${totalValue.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`,
      icon: markRaw(DollarSign),
    },
  ]
})

// Mutations
const saveMutation = useMutation({
  mutationFn: (payload: any) => {
    if (editingLead.value) {
      return crmApi.updateLead(editingLead.value.id, payload)
    }
    return crmApi.createLead(payload)
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['crm'] })
    isCreateModalOpen.value = false
    toast.success(editingLead.value ? t('crm.leads.messages.updated') : t('crm.leads.messages.created'))
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || t('crm.leads.messages.failedSave'))
  },
})

const convertMutation = useMutation({
  mutationFn: () => {
    return crmApi.convertLead(convertingLead.value!.id, {
      deal_title: convertForm.value.deal_title || undefined,
      deal_amount: convertForm.value.deal_amount ? Number(convertForm.value.deal_amount) : undefined,
      stage: convertForm.value.stage,
    })
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['crm'] })
    isConvertModalOpen.value = false
    toast.success(t('crm.leads.messages.converted'))
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || t('crm.leads.messages.failedConvert'))
  },
})

const deleteMutation = useMutation({
  mutationFn: (id: string) => crmApi.deleteLead(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['crm'] })
    toast.success(t('crm.leads.messages.deleted'))
  },
})

const openCreateModal = () => {
  editingLead.value = null
  leadForm.value = {
    name: '',
    company: '',
    title: '',
    email: '',
    phone: '',
    source: 'website',
    status: 'new',
    priority: 'medium',
    estimated_value: '',
    currency: 'USD',
    notes: '',
  }
  isCreateModalOpen.value = true
}

const openEditModal = (lead: Lead) => {
  editingLead.value = lead
  leadForm.value = {
    name: lead.name,
    company: lead.company || '',
    title: lead.title || '',
    email: lead.email || '',
    phone: lead.phone || '',
    source: lead.source || 'website',
    status: lead.status,
    priority: lead.priority,
    estimated_value: lead.estimated_value != null ? String(lead.estimated_value) : '',
    currency: lead.currency || 'USD',
    notes: lead.notes || '',
  }
  isCreateModalOpen.value = true
}

const openConvertModal = (lead: Lead) => {
  convertingLead.value = lead
  convertForm.value = {
    deal_title: lead.company ? `${lead.company} - Deal` : `${lead.name} - Deal`,
    deal_amount: lead.estimated_value ? String(lead.estimated_value) : '',
    stage: 'qualification',
  }
  isConvertModalOpen.value = true
}

const openLeadDetail = (lead: Lead) => {
  selectedLead.value = lead
  isDetailDrawerOpen.value = true
}

const handleSave = () => {
  if (!leadForm.value.name) {
    toast.error(t('crm.leads.messages.nameRequired'))
    return
  }
  saveMutation.mutate({
    ...leadForm.value,
    estimated_value: leadForm.value.estimated_value ? Number(leadForm.value.estimated_value) : null,
  })
}

const getStatusBadge = (status: string) => {
  switch (status) {
    case 'new': return { label: t('crm.leads.statuses.new'), variant: 'info' as const }
    case 'contacted': return { label: t('crm.leads.statuses.contacted'), variant: 'warning' as const }
    case 'qualified': return { label: t('crm.leads.statuses.qualified'), variant: 'purple' as const }
    case 'unqualified': return { label: t('crm.leads.statuses.unqualified'), variant: 'danger' as const }
    case 'converted': return { label: t('crm.leads.statuses.converted'), variant: 'success' as const }
    default: return { label: status, variant: 'default' as const }
  }
}

const getPriorityBadge = (priority: string) => {
  switch (priority) {
    case 'urgent': return { label: t('crm.leads.priorities.urgent'), variant: 'danger' as const }
    case 'high': return { label: t('crm.leads.priorities.high'), variant: 'warning' as const }
    case 'medium': return { label: t('crm.leads.priorities.medium'), variant: 'default' as const }
    default: return { label: t('crm.leads.priorities.low'), variant: 'default' as const }
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">{{ t('crm.leads.title') }}</h1>
        <p class="text-xs sm:text-sm text-slate-500">{{ t('crm.leads.subtitle') }}</p>
      </div>
      <UiButton @click="openCreateModal">
        <Plus class="w-4 h-4 mr-2" /> {{ t('crm.leads.captureNewLead') }}
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

    <!-- Filters & Table -->
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden space-y-4">
      <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1">
          <button
            v-for="st in ['all', 'new', 'contacted', 'qualified', 'unqualified', 'converted']"
            :key="st"
            type="button"
            @click="selectedStatus = st"
            class="px-3 py-1.5 rounded-xl text-xs font-bold capitalize transition-all cursor-pointer"
            :class="selectedStatus === st ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
          >
            {{ st === 'all' ? `${t('crm.leads.statusFilters.all')} (${leads?.length || 0})` : t(`crm.leads.statusFilters.${st}`, st) }}
          </button>
        </div>

        <UiInput
          v-model="searchQuery"
          :placeholder="t('crm.deals.searchPlaceholder')"
          size="sm"
          class="w-full sm:w-64"
        >
          <template #prefix><Search class="w-3.5 h-3.5 text-slate-400" /></template>
        </UiInput>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="p-12 flex justify-center">
        <UiSpinner size="md" />
      </div>

      <!-- Leads Table -->
      <div v-else-if="filteredLeads.length" class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100 text-xs">
          <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
            <tr>
              <th class="px-4 py-3 text-left">{{ t('crm.leads.table.contactCompany') }}</th>
              <th class="px-4 py-3 text-left">{{ t('crm.leads.table.status') }}</th>
              <th class="px-4 py-3 text-left">{{ t('crm.leads.table.priority') }}</th>
              <th class="px-4 py-3 text-left">{{ t('crm.leads.table.channel') }}</th>
              <th class="px-4 py-3 text-right">{{ t('crm.leads.table.estValue') }}</th>
              <th class="px-4 py-3 text-right">{{ t('crm.leads.table.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-slate-700">
            <tr
              v-for="lead in filteredLeads"
              :key="lead.id"
              class="hover:bg-slate-50/70 transition-colors cursor-pointer"
              @click="openLeadDetail(lead)"
            >
              <td class="px-4 py-3">
                <div class="font-bold text-slate-900 text-sm flex items-center gap-2">
                  <span>{{ lead.name }}</span>
                  <span
                    v-if="lead.custom_form_responses && Object.keys(lead.custom_form_responses).length"
                    class="px-1.5 py-0.5 rounded bg-primary-50 text-primary-700 font-semibold text-[10px] border border-primary-100"
                    :title="t('crm.leads.table.formResponsesBadge')"
                  >
                    {{ t('crm.leads.table.formResponsesBadge') }}
                  </span>
                </div>
                <div class="text-[11px] text-slate-500 flex items-center gap-2 mt-0.5">
                  <span v-if="lead.company" class="font-semibold text-slate-700">{{ lead.company }}</span>
                  <span v-if="lead.company && (lead.email || lead.phone)">•</span>
                  <span>{{ lead.email || lead.phone || t('crm.leads.table.noContactInfo') }}</span>
                </div>
              </td>

              <td class="px-4 py-3">
                <UiBadge :variant="getStatusBadge(lead.status).variant" class="text-[10px] font-bold">
                  {{ getStatusBadge(lead.status).label }}
                </UiBadge>
              </td>

              <td class="px-4 py-3">
                <UiBadge :variant="getPriorityBadge(lead.priority).variant" class="text-[10px] font-bold">
                  {{ getPriorityBadge(lead.priority).label }}
                </UiBadge>
              </td>

              <td class="px-4 py-3 capitalize text-slate-600 font-medium">
                {{ t(`crm.leads.sources.${lead.source}`, lead.source.replace('_', ' ')) }}
              </td>

              <!-- Est. Value (Budget extracted from form answers or default) -->
              <td class="px-4 py-3 text-right font-mono font-bold text-slate-900">
                <span v-if="lead.estimated_value">
                  ${{ Number(lead.estimated_value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                </span>
                <span v-else class="text-slate-400 font-normal">—</span>
              </td>

              <td class="px-4 py-3 text-right space-x-1 whitespace-nowrap" @click.stop>
                <UiButton
                  v-if="lead.status !== 'converted'"
                  size="sm"
                  variant="outline"
                  @click="openConvertModal(lead)"
                  :title="t('crm.leads.detailsModal.convertToDeal')"
                >
                  <Sparkles class="w-3.5 h-3.5 mr-1 text-primary-600" /> {{ t('crm.leads.table.convert') }}
                </UiButton>

                <UiButton variant="ghost" size="sm" @click="openLeadDetail(lead)" :title="t('crm.leads.detailsModal.title')">
                  <Eye class="w-3.5 h-3.5 text-slate-600" />
                </UiButton>

                <UiButton variant="ghost" size="sm" @click="openEditModal(lead)" :title="t('crm.leads.formModal.editTitle')">
                  <Edit class="w-3.5 h-3.5 text-slate-600" />
                </UiButton>

                <UiButton
                  variant="ghost"
                  size="sm"
                  class="text-red-500 hover:text-red-700 hover:bg-red-50"
                  @click="deleteMutation.mutate(lead.id)"
                  :title="t('crm.common.delete')"
                >
                  <Trash2 class="w-3.5 h-3.5" />
                </UiButton>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Empty State -->
      <div v-else class="p-12 text-center text-slate-400 space-y-3">
        <Users class="w-10 h-10 mx-auto text-slate-300" />
        <h4 class="font-bold text-slate-700 text-sm">{{ t('crm.leads.table.emptyTitle') }}</h4>
        <p class="text-xs text-slate-400 max-w-sm mx-auto">
          {{ t('crm.leads.table.emptyText') }}
        </p>
        <UiButton size="sm" @click="openCreateModal">{{ t('crm.leads.table.captureFirst') }}</UiButton>
      </div>
    </div>

    <!-- Lead Details & Questionnaire Responses Modal -->
    <UiModal v-model="isDetailDrawerOpen" :title="t('crm.leads.detailsModal.title')" size="lg">
      <div v-if="selectedLead" class="space-y-5">
        <!-- Lead Header -->
        <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div class="space-y-1">
            <h3 class="text-base font-black text-slate-900">{{ selectedLead.name }}</h3>
            <p class="text-xs text-slate-500 font-medium">
              {{ selectedLead.title ? `${selectedLead.title} at ` : '' }}
              <strong class="text-slate-800">{{ selectedLead.company || t('crm.leads.detailsModal.individualProspect') }}</strong>
            </p>
          </div>

          <div class="flex items-center gap-2">
            <UiBadge :variant="getStatusBadge(selectedLead.status).variant" class="font-bold">
              {{ getStatusBadge(selectedLead.status).label }}
            </UiBadge>
            <UiBadge :variant="getPriorityBadge(selectedLead.priority).variant" class="font-bold">
              {{ getPriorityBadge(selectedLead.priority).label }}
            </UiBadge>
          </div>
        </div>

        <!-- Contact & Value Summary Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
          <div class="p-3 bg-white border border-slate-200 rounded-xl space-y-0.5">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ t('crm.leads.detailsModal.estBudget') }}</span>
            <p class="font-mono font-black text-primary-700 text-sm">
              {{ selectedLead.estimated_value ? `$${Number(selectedLead.estimated_value).toLocaleString(undefined, { minimumFractionDigits: 2 })}` : '—' }}
            </p>
          </div>
          <div class="p-3 bg-white border border-slate-200 rounded-xl space-y-0.5">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ t('crm.leads.detailsModal.channel') }}</span>
            <p class="font-bold text-slate-800 capitalize">{{ t(`crm.leads.sources.${selectedLead.source}`, selectedLead.source.replace('_', ' ')) }}</p>
          </div>
          <div class="p-3 bg-white border border-slate-200 rounded-xl space-y-0.5">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ t('crm.leads.detailsModal.email') }}</span>
            <p class="font-mono font-bold text-slate-800 truncate">{{ selectedLead.email || '—' }}</p>
          </div>
          <div class="p-3 bg-white border border-slate-200 rounded-xl space-y-0.5">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ t('crm.leads.detailsModal.phone') }}</span>
            <p class="font-mono font-bold text-slate-800 truncate">{{ selectedLead.phone || '—' }}</p>
          </div>
        </div>

        <!-- Submitted Form Questionnaire Responses -->
        <div v-if="selectedLead.custom_form_responses && Object.keys(selectedLead.custom_form_responses).length" class="space-y-3">
          <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800 border-b border-slate-100 pb-2">
            {{ t('crm.leads.detailsModal.questionnaireResponses') }}
          </h4>

          <div class="space-y-2.5">
            <div
              v-for="(val, key) in selectedLead.custom_form_responses"
              :key="key"
              class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 text-xs space-y-1"
            >
              <span class="font-bold uppercase text-[10px] tracking-wider text-slate-400">
                {{ String(key).replace(/_/g, ' ') }}
              </span>
              <p class="text-slate-900 font-semibold leading-relaxed">
                {{ Array.isArray(val) ? val.join(', ') : val }}
              </p>
            </div>
          </div>
        </div>

        <!-- Notes -->
        <div v-if="selectedLead.notes" class="space-y-1.5 pt-1">
          <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800">{{ t('crm.leads.detailsModal.notes') }}</h4>
          <p class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 text-xs text-slate-700 leading-relaxed">
            {{ selectedLead.notes }}
          </p>
        </div>

        <!-- Footer Actions -->
        <div class="flex items-center justify-between pt-3 border-t border-slate-100">
          <UiButton variant="outline" size="sm" @click="isDetailDrawerOpen = false">{{ t('crm.common.close') }}</UiButton>
          <UiButton
            v-if="selectedLead.status !== 'converted'"
            size="sm"
            @click="isDetailDrawerOpen = false; openConvertModal(selectedLead)"
          >
            <Sparkles class="w-3.5 h-3.5 mr-1" /> {{ t('crm.leads.detailsModal.convertToDeal') }}
          </UiButton>
        </div>
      </div>
    </UiModal>

    <!-- Create / Edit Lead Modal -->
    <UiModal v-model="isCreateModalOpen" :title="editingLead ? t('crm.leads.formModal.editTitle') : t('crm.leads.formModal.createTitle')" size="lg">
      <div class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UiInput v-model="leadForm.name" :label="t('crm.leads.formModal.contactName')" placeholder="Jane Smith" required />
          <UiInput v-model="leadForm.company" :label="t('crm.leads.formModal.company')" placeholder="Acme Inc." />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <UiInput v-model="leadForm.title" :label="t('crm.leads.formModal.jobTitle')" placeholder="VP Operations" />
          <UiInput v-model="leadForm.email" :label="t('crm.leads.formModal.email')" type="email" placeholder="jane@acme.com" />
          <UiInput v-model="leadForm.phone" :label="t('crm.leads.formModal.phone')" placeholder="+1..." />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
          <UiSelect
            v-model="leadForm.source"
            :label="t('crm.leads.formModal.source')"
            :options="[
              { label: t('crm.leads.sources.website'), value: 'website' },
              { label: t('crm.leads.sources.agency'), value: 'agency' },
              { label: t('crm.leads.sources.social_media'), value: 'social_media' },
              { label: t('crm.leads.sources.google_ads'), value: 'google_ads' },
              { label: t('crm.leads.sources.referral'), value: 'referral' },
              { label: t('crm.leads.sources.outreach'), value: 'outreach' },
              { label: t('crm.leads.sources.event'), value: 'event' },
              { label: t('crm.leads.sources.other'), value: 'other' },
            ]"
          />
          <UiSelect
            v-model="leadForm.status"
            :label="t('crm.leads.formModal.status')"
            :options="[
              { label: t('crm.leads.statuses.new'), value: 'new' },
              { label: t('crm.leads.statuses.contacted'), value: 'contacted' },
              { label: t('crm.leads.statuses.qualified'), value: 'qualified' },
              { label: t('crm.leads.statuses.unqualified'), value: 'unqualified' },
              { label: t('crm.leads.statuses.converted'), value: 'converted' },
            ]"
          />
          <UiSelect
            v-model="leadForm.priority"
            :label="t('crm.leads.formModal.priority')"
            :options="[
              { label: t('crm.leads.priorities.low'), value: 'low' },
              { label: t('crm.leads.priorities.medium'), value: 'medium' },
              { label: t('crm.leads.priorities.high'), value: 'high' },
              { label: t('crm.leads.priorities.urgent'), value: 'urgent' },
            ]"
          />
          <UiInput v-model="leadForm.estimated_value" :label="t('crm.leads.formModal.estValue')" type="number" placeholder="5000" />
        </div>

        <div class="space-y-1">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">{{ t('crm.leads.formModal.notesLabel') }}</label>
          <textarea
            v-model="leadForm.notes"
            rows="3"
            :placeholder="t('crm.leads.formModal.notesPlaceholder')"
            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500"
          ></textarea>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
          <UiButton variant="outline" type="button" @click="isCreateModalOpen = false">{{ t('crm.common.cancel') }}</UiButton>
          <UiButton :loading="saveMutation.isPending.value" @click="handleSave">
            {{ editingLead ? t('crm.leads.formModal.updateBtn') : t('crm.leads.formModal.saveBtn') }}
          </UiButton>
        </div>
      </div>
    </UiModal>

    <!-- Convert Lead to Deal Modal -->
    <UiModal v-model="isConvertModalOpen" :title="t('crm.leads.convertModal.title')" size="md">
      <div v-if="convertingLead" class="space-y-5">
        <div class="p-4 bg-primary-50 border border-primary-100 rounded-xl space-y-1">
          <h4 class="text-xs font-bold uppercase tracking-wider text-primary-900">{{ t('crm.leads.convertModal.leadToBeConverted') }}</h4>
          <p class="text-sm font-black text-primary-950">{{ convertingLead.name }} ({{ convertingLead.company || t('crm.leads.detailsModal.individualProspect') }})</p>
          <p class="text-xs text-primary-700">{{ t('crm.leads.convertModal.conversionNotice') }}</p>
        </div>

        <div class="space-y-4">
          <UiInput v-model="convertForm.deal_title" :label="t('crm.leads.convertModal.dealTitle')" :placeholder="t('crm.leads.convertModal.dealTitlePlaceholder')" required />
          <UiInput v-model="convertForm.deal_amount" :label="t('crm.leads.convertModal.dealAmount')" type="number" placeholder="10000" />
          <UiSelect
            v-model="convertForm.stage"
            :label="t('crm.leads.convertModal.initialStage')"
            :options="[
              { label: t('crm.deals.stages.qualification'), value: 'qualification' },
              { label: t('crm.deals.stages.proposal'), value: 'proposal' },
              { label: t('crm.deals.stages.negotiation'), value: 'negotiation' },
              { label: t('crm.deals.stages.won'), value: 'won' },
            ]"
          />
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
          <UiButton variant="outline" type="button" @click="isConvertModalOpen = false">{{ t('crm.common.cancel') }}</UiButton>
          <UiButton :loading="convertMutation.isPending.value" @click="convertMutation.mutate()">
            {{ t('crm.leads.convertModal.confirmBtn') }}
          </UiButton>
        </div>
      </div>
    </UiModal>
  </div>
</template>
