<script setup lang="ts">
import { ref, computed, markRaw } from 'vue'
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
      label: 'Total Inbound Leads',
      value: list.length,
      icon: markRaw(Users),
    },
    {
      label: 'Qualified Prospects',
      value: qualified,
      icon: markRaw(Sparkles),
    },
    {
      label: 'Converted to Pipeline',
      value: converted,
      icon: markRaw(CheckCircle2),
    },
    {
      label: 'Total Inbound Est. Value',
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
    toast.success(editingLead.value ? 'Lead updated successfully' : 'New lead captured')
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || 'Failed to save lead')
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
    toast.success('Lead converted to Customer and Deal in pipeline! 🎉')
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || 'Failed to convert lead')
  },
})

const deleteMutation = useMutation({
  mutationFn: (id: string) => crmApi.deleteLead(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['crm'] })
    toast.success('Lead deleted')
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
    toast.error('Please enter the lead name')
    return
  }
  saveMutation.mutate({
    ...leadForm.value,
    estimated_value: leadForm.value.estimated_value ? Number(leadForm.value.estimated_value) : null,
  })
}

const getStatusBadge = (status: string) => {
  switch (status) {
    case 'new': return { label: 'New Lead', variant: 'info' as const }
    case 'contacted': return { label: 'Contacted', variant: 'warning' as const }
    case 'qualified': return { label: 'Qualified', variant: 'purple' as const }
    case 'unqualified': return { label: 'Unqualified', variant: 'danger' as const }
    case 'converted': return { label: 'Converted 🎉', variant: 'success' as const }
    default: return { label: status, variant: 'default' as const }
  }
}

const getPriorityBadge = (priority: string) => {
  switch (priority) {
    case 'urgent': return { label: 'Urgent', variant: 'danger' as const }
    case 'high': return { label: 'High', variant: 'warning' as const }
    case 'medium': return { label: 'Medium', variant: 'default' as const }
    default: return { label: 'Low', variant: 'default' as const }
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Leads & Inbound Inquiries</h1>
        <p class="text-xs sm:text-sm text-slate-500">Capture inbound leads, qualify opportunities, review form responses, and convert to sales pipeline.</p>
      </div>
      <UiButton @click="openCreateModal">
        <Plus class="w-4 h-4 mr-2" /> Capture New Lead
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
            {{ st === 'all' ? `All (${leads?.length || 0})` : st }}
          </button>
        </div>

        <UiInput
          v-model="searchQuery"
          placeholder="Search name, company, email..."
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
              <th class="px-4 py-3 text-left">Lead Contact & Company</th>
              <th class="px-4 py-3 text-left">Status</th>
              <th class="px-4 py-3 text-left">Priority</th>
              <th class="px-4 py-3 text-left">Channel / Source</th>
              <th class="px-4 py-3 text-right">Est. Value (Budget)</th>
              <th class="px-4 py-3 text-right">Actions</th>
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
                    title="Submitted via Lead Form"
                  >
                    Form Responses
                  </span>
                </div>
                <div class="text-[11px] text-slate-500 flex items-center gap-2 mt-0.5">
                  <span v-if="lead.company" class="font-semibold text-slate-700">{{ lead.company }}</span>
                  <span v-if="lead.company && (lead.email || lead.phone)">•</span>
                  <span>{{ lead.email || lead.phone || 'No contact info' }}</span>
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
                {{ lead.source.replace('_', ' ') }}
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
                  title="Convert Lead to Customer & Deal"
                >
                  <Sparkles class="w-3.5 h-3.5 mr-1 text-primary-600" /> Convert
                </UiButton>

                <UiButton variant="ghost" size="sm" @click="openLeadDetail(lead)" title="View Form Responses">
                  <Eye class="w-3.5 h-3.5 text-slate-600" />
                </UiButton>

                <UiButton variant="ghost" size="sm" @click="openEditModal(lead)" title="Edit Lead">
                  <Edit class="w-3.5 h-3.5 text-slate-600" />
                </UiButton>

                <UiButton
                  variant="ghost"
                  size="sm"
                  class="text-red-500 hover:text-red-700 hover:bg-red-50"
                  @click="deleteMutation.mutate(lead.id)"
                  title="Delete Lead"
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
        <h4 class="font-bold text-slate-700 text-sm">No leads found</h4>
        <p class="text-xs text-slate-400 max-w-sm mx-auto">
          Capture prospect inquiries or share your lead intake wizard to build your sales funnel.
        </p>
        <UiButton size="sm" @click="openCreateModal">Capture First Lead</UiButton>
      </div>
    </div>

    <!-- Lead Details & Questionnaire Responses Modal -->
    <UiModal v-model="isDetailDrawerOpen" title="Lead Inquiry Details" size="lg">
      <div v-if="selectedLead" class="space-y-5">
        <!-- Lead Header -->
        <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div class="space-y-1">
            <h3 class="text-base font-black text-slate-900">{{ selectedLead.name }}</h3>
            <p class="text-xs text-slate-500 font-medium">
              {{ selectedLead.title ? `${selectedLead.title} at ` : '' }}
              <strong class="text-slate-800">{{ selectedLead.company || 'Individual Prospect' }}</strong>
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
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Est. Value / Budget</span>
            <p class="font-mono font-black text-primary-700 text-sm">
              {{ selectedLead.estimated_value ? `$${Number(selectedLead.estimated_value).toLocaleString(undefined, { minimumFractionDigits: 2 })}` : '—' }}
            </p>
          </div>
          <div class="p-3 bg-white border border-slate-200 rounded-xl space-y-0.5">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Channel</span>
            <p class="font-bold text-slate-800 capitalize">{{ selectedLead.source.replace('_', ' ') }}</p>
          </div>
          <div class="p-3 bg-white border border-slate-200 rounded-xl space-y-0.5">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Email</span>
            <p class="font-mono font-bold text-slate-800 truncate">{{ selectedLead.email || '—' }}</p>
          </div>
          <div class="p-3 bg-white border border-slate-200 rounded-xl space-y-0.5">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Phone</span>
            <p class="font-mono font-bold text-slate-800 truncate">{{ selectedLead.phone || '—' }}</p>
          </div>
        </div>

        <!-- Submitted Form Questionnaire Responses -->
        <div v-if="selectedLead.custom_form_responses && Object.keys(selectedLead.custom_form_responses).length" class="space-y-3">
          <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800 border-b border-slate-100 pb-2">
            Submitted Questionnaire Responses
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
          <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800">Additional Inquiry Notes</h4>
          <p class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 text-xs text-slate-700 leading-relaxed">
            {{ selectedLead.notes }}
          </p>
        </div>

        <!-- Footer Actions -->
        <div class="flex items-center justify-between pt-3 border-t border-slate-100">
          <UiButton variant="outline" size="sm" @click="isDetailDrawerOpen = false">Close</UiButton>
          <UiButton
            v-if="selectedLead.status !== 'converted'"
            size="sm"
            @click="isDetailDrawerOpen = false; openConvertModal(selectedLead)"
          >
            <Sparkles class="w-3.5 h-3.5 mr-1" /> Convert to Pipeline Deal
          </UiButton>
        </div>
      </div>
    </UiModal>

    <!-- Create / Edit Lead Modal -->
    <UiModal v-model="isCreateModalOpen" :title="editingLead ? 'Edit Lead Details' : 'Capture New Lead'" size="lg">
      <div class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UiInput v-model="leadForm.name" label="Contact Name" placeholder="Jane Smith" required />
          <UiInput v-model="leadForm.company" label="Company / Organization" placeholder="Acme Inc." />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <UiInput v-model="leadForm.title" label="Job Title" placeholder="VP Operations" />
          <UiInput v-model="leadForm.email" label="Email Address" type="email" placeholder="jane@acme.com" />
          <UiInput v-model="leadForm.phone" label="Phone Number" placeholder="+1..." />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
          <UiSelect
            v-model="leadForm.source"
            label="Lead Source"
            :options="[
              { label: 'Website / Organic', value: 'website' },
              { label: 'Agency Referral', value: 'agency' },
              { label: 'Social Media', value: 'social_media' },
              { label: 'Google / Paid Ads', value: 'google_ads' },
              { label: 'Client Referral', value: 'referral' },
              { label: 'Cold Outreach', value: 'outreach' },
              { label: 'Event / Trade Show', value: 'event' },
              { label: 'Other', value: 'other' },
            ]"
          />
          <UiSelect
            v-model="leadForm.status"
            label="Status"
            :options="[
              { label: 'New Lead', value: 'new' },
              { label: 'Contacted', value: 'contacted' },
              { label: 'Qualified', value: 'qualified' },
              { label: 'Unqualified', value: 'unqualified' },
              { label: 'Converted', value: 'converted' },
            ]"
          />
          <UiSelect
            v-model="leadForm.priority"
            label="Priority"
            :options="[
              { label: 'Low', value: 'low' },
              { label: 'Medium', value: 'medium' },
              { label: 'High', value: 'high' },
              { label: 'Urgent', value: 'urgent' },
            ]"
          />
          <UiInput v-model="leadForm.estimated_value" label="Est. Value / Budget ($)" type="number" placeholder="5000" />
        </div>

        <div class="space-y-1">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Notes & Context</label>
          <textarea
            v-model="leadForm.notes"
            rows="3"
            placeholder="Key discussion points, customer requirements, timeline..."
            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500"
          ></textarea>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
          <UiButton variant="outline" type="button" @click="isCreateModalOpen = false">Cancel</UiButton>
          <UiButton :loading="saveMutation.isPending.value" @click="handleSave">
            {{ editingLead ? 'Update Lead' : 'Save Lead' }}
          </UiButton>
        </div>
      </div>
    </UiModal>

    <!-- Convert Lead to Deal Modal -->
    <UiModal v-model="isConvertModalOpen" title="Convert Lead to Customer & Pipeline Deal" size="md">
      <div v-if="convertingLead" class="space-y-5">
        <div class="p-4 bg-primary-50 border border-primary-100 rounded-xl space-y-1">
          <h4 class="text-xs font-bold uppercase tracking-wider text-primary-900">Lead to be converted</h4>
          <p class="text-sm font-black text-primary-950">{{ convertingLead.name }} ({{ convertingLead.company || 'Individual' }})</p>
          <p class="text-xs text-primary-700">Converting will automatically register an active Customer and create an opportunity in your Deals pipeline.</p>
        </div>

        <div class="space-y-4">
          <UiInput v-model="convertForm.deal_title" label="Deal Title" placeholder="e.g. Enterprise License" required />
          <UiInput v-model="convertForm.deal_amount" label="Deal Amount ($)" type="number" placeholder="10000" />
          <UiSelect
            v-model="convertForm.stage"
            label="Initial Pipeline Stage"
            :options="[
              { label: 'Qualification', value: 'qualification' },
              { label: 'Proposal Sent', value: 'proposal' },
              { label: 'Negotiation', value: 'negotiation' },
              { label: 'Closed Won', value: 'won' },
            ]"
          />
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
          <UiButton variant="outline" type="button" @click="isConvertModalOpen = false">Cancel</UiButton>
          <UiButton :loading="convertMutation.isPending.value" @click="convertMutation.mutate()">
            Confirm Conversion
          </UiButton>
        </div>
      </div>
    </UiModal>
  </div>
</template>
