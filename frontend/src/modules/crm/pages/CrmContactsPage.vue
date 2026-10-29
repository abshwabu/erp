<script setup lang="ts">
import { ref, computed, markRaw } from 'vue'
import { useI18n } from 'vue-i18n'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { crmApi } from '@/api/crm'
import type { CrmContact } from '@/types/crm'
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
  Building2,
  Mail,
  Phone,
  Globe,
  MapPin,
  FileText,
  DollarSign,
  TrendingUp,
  Trash2,
  Edit,
  Eye,
  Briefcase,
  Layers,
} from '@lucide/vue'

const { t } = useI18n()
const queryClient = useQueryClient()
const toast = useToast()

const searchQuery = ref('')
const selectedStatus = ref<string>('all')
const isModalOpen = ref(false)
const isDrawerOpen = ref(false)
const editingContact = ref<CrmContact | null>(null)
const selectedContactId = ref<string | null>(null)

const contactForm = ref({
  name: '',
  company: '',
  job_title: '',
  email: '',
  phone: '',
  status: 'customer' as 'lead' | 'customer' | 'partner' | 'churned',
  source: '',
  address: '',
  city: '',
  country: '',
  website: '',
  notes: '',
})

// Queries
const { data: contacts, isLoading } = useQuery({
  queryKey: ['crm', 'contacts'],
  queryFn: () => crmApi.getContacts().then((r) => r.data.data),
})

const { data: selectedContact, isLoading: isLoadingContactDetail } = useQuery({
  queryKey: ['crm', 'contacts', selectedContactId],
  queryFn: () => selectedContactId.value ? crmApi.getContact(selectedContactId.value).then(r => r.data.data) : null,
  enabled: () => !!selectedContactId.value,
})

const filteredContacts = computed(() => {
  let list = contacts.value || []
  if (selectedStatus.value !== 'all') {
    list = list.filter((c) => c.status === selectedStatus.value)
  }
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(
      (c) =>
        c.name.toLowerCase().includes(q) ||
        (c.company || '').toLowerCase().includes(q) ||
        (c.email || '').toLowerCase().includes(q) ||
        (c.phone || '').toLowerCase().includes(q)
    )
  }
  return list
})

// Stats
const stats = computed(() => {
  const list = contacts.value || []
  const customers = list.filter((c) => c.status === 'customer').length
  const leads = list.filter((c) => c.status === 'lead').length
  const partners = list.filter((c) => c.status === 'partner').length

  return [
    {
      label: t('crm.contacts.kpis.totalContacts'),
      value: list.length,
      icon: markRaw(Users),
    },
    {
      label: t('crm.contacts.kpis.activeCustomers'),
      value: customers,
      icon: markRaw(Building2),
    },
    {
      label: t('crm.contacts.kpis.leads'),
      value: leads,
      icon: markRaw(TrendingUp),
    },
    {
      label: t('crm.contacts.kpis.partners'),
      value: partners,
      icon: markRaw(Briefcase),
    },
  ]
})

// Mutations
const saveMutation = useMutation({
  mutationFn: (payload: any) => {
    if (editingContact.value) {
      return crmApi.updateContact(editingContact.value.id, payload)
    }
    return crmApi.createContact(payload)
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['crm', 'contacts'] })
    isModalOpen.value = false
    toast.success(editingContact.value ? t('crm.contacts.messages.updated') : t('crm.contacts.messages.created'))
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || t('crm.contacts.messages.failedSave'))
  },
})

const deleteMutation = useMutation({
  mutationFn: (id: string) => crmApi.deleteContact(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['crm', 'contacts'] })
    isDrawerOpen.value = false
    toast.success(t('crm.contacts.messages.deleted'))
  },
})

const openCreateModal = () => {
  editingContact.value = null
  contactForm.value = {
    name: '',
    company: '',
    job_title: '',
    email: '',
    phone: '',
    status: 'customer',
    source: '',
    address: '',
    city: '',
    country: '',
    website: '',
    notes: '',
  }
  isModalOpen.value = true
}

const openEditModal = (contact: CrmContact) => {
  editingContact.value = contact
  contactForm.value = {
    name: contact.name,
    company: contact.company || '',
    job_title: contact.job_title || '',
    email: contact.email || '',
    phone: contact.phone || '',
    status: contact.status || 'customer',
    source: contact.source || '',
    address: contact.address || '',
    city: contact.city || '',
    country: contact.country || '',
    website: contact.website || '',
    notes: contact.notes || '',
  }
  isModalOpen.value = true
}

const viewContactDetail = (contact: CrmContact) => {
  selectedContactId.value = contact.id
  isDrawerOpen.value = true
}

const handleSave = () => {
  if (!contactForm.value.name) {
    toast.error(t('crm.contacts.messages.nameRequired'))
    return
  }
  saveMutation.mutate(contactForm.value)
}

const getStatusBadge = (status: string) => {
  switch (status) {
    case 'customer': return { label: t('crm.contacts.statuses.customer'), variant: 'success' as const }
    case 'lead': return { label: t('crm.contacts.statuses.lead'), variant: 'info' as const }
    case 'partner': return { label: t('crm.contacts.statuses.partner'), variant: 'purple' as const }
    case 'churned': return { label: t('crm.contacts.statuses.churned'), variant: 'danger' as const }
    default: return { label: status, variant: 'default' as const }
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">{{ t('crm.contacts.title') }}</h1>
        <p class="text-xs sm:text-sm text-slate-500">{{ t('crm.contacts.subtitle') }}</p>
      </div>
      <UiButton @click="openCreateModal">
        <Plus class="w-4 h-4 mr-2" /> {{ t('crm.contacts.addContact') }}
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

    <!-- Table Container -->
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden space-y-4">
      <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1">
          <button
            v-for="st in ['all', 'customer', 'lead', 'partner', 'churned']"
            :key="st"
            type="button"
            @click="selectedStatus = st"
            class="px-3.5 py-1.5 rounded-xl text-xs font-bold capitalize transition-all cursor-pointer"
            :class="selectedStatus === st ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
          >
            {{ st === 'all' ? `${t('crm.contacts.statusFilters.all')} (${contacts?.length || 0})` : t(`crm.contacts.statusFilters.${st}`, st) }}
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

      <!-- Contacts Table -->
      <div v-else-if="filteredContacts.length" class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100 text-xs">
          <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
            <tr>
              <th class="px-4 py-3 text-left">{{ t('crm.contacts.table.contactOrg') }}</th>
              <th class="px-4 py-3 text-left">{{ t('crm.contacts.table.contactDetails') }}</th>
              <th class="px-4 py-3 text-left">{{ t('crm.contacts.table.status') }}</th>
              <th class="px-4 py-3 text-left">{{ t('crm.contacts.table.location') }}</th>
              <th class="px-4 py-3 text-center">{{ t('crm.contacts.table.deals') }}</th>
              <th class="px-4 py-3 text-right">{{ t('crm.contacts.table.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-slate-700">
            <tr
              v-for="contact in filteredContacts"
              :key="contact.id"
              class="hover:bg-slate-50/70 transition-colors cursor-pointer"
              @click="viewContactDetail(contact)"
            >
              <td class="px-4 py-3">
                <div class="font-bold text-slate-900 text-sm">{{ contact.name }}</div>
                <div class="text-[11px] text-slate-500 font-medium mt-0.5">
                  {{ contact.job_title ? `${contact.job_title} at ` : '' }}
                  <span class="font-semibold text-slate-700">{{ contact.company || t('crm.contacts.table.individual') }}</span>
                </div>
              </td>

              <td class="px-4 py-3 text-slate-600 space-y-0.5">
                <div v-if="contact.email" class="flex items-center gap-1 font-mono">
                  <Mail class="w-3 h-3 text-slate-400" /> {{ contact.email }}
                </div>
                <div v-if="contact.phone" class="flex items-center gap-1 font-mono">
                  <Phone class="w-3 h-3 text-slate-400" /> {{ contact.phone }}
                </div>
              </td>

              <td class="px-4 py-3">
                <UiBadge :variant="getStatusBadge(contact.status).variant" class="text-[10px] font-bold">
                  {{ getStatusBadge(contact.status).label }}
                </UiBadge>
              </td>

              <td class="px-4 py-3 text-slate-600">
                {{ [contact.city, contact.country].filter(Boolean).join(', ') || '—' }}
              </td>

              <td class="px-4 py-3 text-center font-bold font-mono">
                <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 text-xs">
                  {{ contact.deals_count || 0 }}
                </span>
              </td>

              <td class="px-4 py-3 text-right space-x-1 whitespace-nowrap" @click.stop>
                <UiButton variant="ghost" size="sm" @click="viewContactDetail(contact)" :title="t('crm.contacts.profileModal.title')">
                  <Eye class="w-3.5 h-3.5 text-slate-600" />
                </UiButton>
                <UiButton variant="ghost" size="sm" @click="openEditModal(contact)" :title="t('crm.contacts.modal.editTitle')">
                  <Edit class="w-3.5 h-3.5 text-slate-600" />
                </UiButton>
                <UiButton
                  variant="ghost"
                  size="sm"
                  class="text-red-500 hover:text-red-700 hover:bg-red-50"
                  @click="deleteMutation.mutate(contact.id)"
                  :title="t('crm.common.delete')"
                >
                  <Trash2 class="w-3.5 h-3.5" />
                </UiButton>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else class="p-12 text-center text-slate-400 text-xs">
        {{ t('crm.contacts.table.emptySearch') }}
      </div>
    </div>

    <!-- Create / Edit Contact Modal -->
    <UiModal v-model="isModalOpen" :title="editingContact ? t('crm.contacts.modal.editTitle') : t('crm.contacts.modal.createTitle')" size="lg">
      <div class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UiInput v-model="contactForm.name" :label="t('crm.contacts.modal.fullName')" placeholder="Johnathan Miller" required />
          <UiInput v-model="contactForm.company" :label="t('crm.contacts.modal.companyName')" placeholder="Miller Technologies" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <UiInput v-model="contactForm.job_title" :label="t('crm.contacts.modal.jobTitle')" placeholder="Director of Sales" />
          <UiInput v-model="contactForm.email" :label="t('crm.contacts.modal.email')" type="email" placeholder="john@miller.com" />
          <UiInput v-model="contactForm.phone" :label="t('crm.contacts.modal.phone')" placeholder="+1 (555) 000-0000" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <UiSelect
            v-model="contactForm.status"
            :label="t('crm.contacts.modal.relationshipStatus')"
            :options="[
              { label: t('crm.contacts.statuses.customer'), value: 'customer' },
              { label: t('crm.contacts.statuses.lead'), value: 'lead' },
              { label: t('crm.contacts.statuses.partner'), value: 'partner' },
              { label: t('crm.contacts.statuses.churned'), value: 'churned' },
            ]"
          />
          <UiInput v-model="contactForm.city" :label="t('crm.contacts.modal.city')" placeholder="Addis Ababa" />
          <UiInput v-model="contactForm.country" :label="t('crm.contacts.modal.country')" placeholder="Ethiopia" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UiInput v-model="contactForm.website" :label="t('crm.contacts.modal.website')" placeholder="https://miller.com" />
          <UiInput v-model="contactForm.source" :label="t('crm.contacts.modal.acquisitionChannel')" placeholder="Referral, Inbound, etc." />
        </div>

        <div class="space-y-1">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">{{ t('crm.contacts.modal.notesLabel') }}</label>
          <textarea
            v-model="contactForm.notes"
            rows="3"
            :placeholder="t('crm.contacts.modal.notesPlaceholder')"
            class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500"
          ></textarea>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
          <UiButton variant="outline" type="button" @click="isModalOpen = false">{{ t('crm.common.cancel') }}</UiButton>
          <UiButton :loading="saveMutation.isPending.value" @click="handleSave">
            {{ editingContact ? t('crm.contacts.modal.saveBtn') : t('crm.contacts.modal.createBtn') }}
          </UiButton>
        </div>
      </div>
    </UiModal>

    <!-- Detailed Contact Profile Modal -->
    <UiModal v-model="isDrawerOpen" :title="t('crm.contacts.profileModal.title')" size="lg">
      <div v-if="selectedContact" class="space-y-6">
        <!-- Profile Header -->
        <div class="p-5 bg-slate-50 rounded-2xl border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-white border border-slate-200 flex items-center justify-center font-black text-slate-800 text-base shadow-2xs">
              {{ selectedContact.name[0] }}
            </div>
            <div>
              <h3 class="font-black text-slate-900 text-base">{{ selectedContact.name }}</h3>
              <p class="text-xs text-slate-500 font-medium">
                {{ selectedContact.job_title ? `${selectedContact.job_title} at ` : '' }}
                <strong class="text-slate-800">{{ selectedContact.company || t('crm.contacts.profileModal.individualAccount') }}</strong>
              </p>
            </div>
          </div>

          <UiBadge :variant="getStatusBadge(selectedContact.status).variant" class="font-bold">
            {{ getStatusBadge(selectedContact.status).label }}
          </UiBadge>
        </div>

        <!-- Contact Info Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
          <div class="p-3 bg-white border border-slate-200 rounded-xl space-y-0.5">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ t('crm.contacts.modal.email') }}</span>
            <p class="font-mono text-slate-800 font-bold truncate">{{ selectedContact.email || '—' }}</p>
          </div>
          <div class="p-3 bg-white border border-slate-200 rounded-xl space-y-0.5">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ t('crm.contacts.modal.phone') }}</span>
            <p class="font-mono text-slate-800 font-bold truncate">{{ selectedContact.phone || '—' }}</p>
          </div>
          <div class="p-3 bg-white border border-slate-200 rounded-xl space-y-0.5">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ t('crm.contacts.table.location') }}</span>
            <p class="text-slate-800 font-bold truncate">{{ [selectedContact.city, selectedContact.country].filter(Boolean).join(', ') || '—' }}</p>
          </div>
        </div>

        <!-- Deals Associated -->
        <div class="space-y-3">
          <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800 border-b border-slate-100 pb-2">
            {{ t('crm.contacts.profileModal.pipelineDeals') }} ({{ selectedContact.deals?.length || 0 }})
          </h4>

          <div v-if="selectedContact.deals?.length" class="space-y-2">
            <div
              v-for="d in selectedContact.deals"
              :key="d.id"
              class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between text-xs"
            >
              <div>
                <span class="font-bold text-slate-900">{{ d.title }}</span>
                <span class="text-slate-400 ml-2 capitalize">({{ t(`crm.deals.stages.${d.stage}`, d.stage) }})</span>
              </div>
              <span class="font-mono font-bold text-slate-900">${{ Number(d.amount).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</span>
            </div>
          </div>
          <p v-else class="text-xs text-slate-400 italic">{{ t('crm.contacts.profileModal.noDeals') }}</p>
        </div>

        <div class="flex justify-end pt-3 border-t border-slate-100">
          <UiButton size="sm" @click="isDrawerOpen = false">{{ t('crm.contacts.profileModal.close') }}</UiButton>
        </div>
      </div>
    </UiModal>
  </div>
</template>
