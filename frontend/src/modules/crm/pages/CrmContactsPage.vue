<script setup lang="ts">
import { ref, computed, markRaw } from 'vue'
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
      label: 'Total Directory Contacts',
      value: list.length,
      icon: markRaw(Users),
    },
    {
      label: 'Active Customers',
      value: customers,
      icon: markRaw(Building2),
    },
    {
      label: 'Prospects / Leads',
      value: leads,
      icon: markRaw(TrendingUp),
    },
    {
      label: 'Strategic Partners',
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
    toast.success(editingContact.value ? 'Contact updated' : 'Contact created')
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || 'Failed to save contact')
  },
})

const deleteMutation = useMutation({
  mutationFn: (id: string) => crmApi.deleteContact(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['crm', 'contacts'] })
    isDrawerOpen.value = false
    toast.success('Contact deleted')
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
    toast.error('Please provide a contact name')
    return
  }
  saveMutation.mutate(contactForm.value)
}

const getStatusBadge = (status: string) => {
  switch (status) {
    case 'customer': return { label: 'Customer', variant: 'success' as const }
    case 'lead': return { label: 'Lead', variant: 'info' as const }
    case 'partner': return { label: 'Partner', variant: 'purple' as const }
    case 'churned': return { label: 'Churned', variant: 'danger' as const }
    default: return { label: status, variant: 'default' as const }
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Contacts & Accounts</h1>
        <p class="text-xs sm:text-sm text-slate-500">Directory of enterprise accounts, prospective clients, and key stakeholders.</p>
      </div>
      <UiButton @click="openCreateModal">
        <Plus class="w-4 h-4 mr-2" /> Add Contact
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
            {{ st === 'all' ? `All Contacts (${contacts?.length || 0})` : st }}
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

      <!-- Contacts Table -->
      <div v-else-if="filteredContacts.length" class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100 text-xs">
          <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
            <tr>
              <th class="px-4 py-3 text-left">Contact & Organization</th>
              <th class="px-4 py-3 text-left">Contact Details</th>
              <th class="px-4 py-3 text-left">Status</th>
              <th class="px-4 py-3 text-left">Location</th>
              <th class="px-4 py-3 text-center">Deals</th>
              <th class="px-4 py-3 text-right">Actions</th>
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
                  <span class="font-semibold text-slate-700">{{ contact.company || 'Individual' }}</span>
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
                <UiButton variant="ghost" size="sm" @click="viewContactDetail(contact)" title="View Profile">
                  <Eye class="w-3.5 h-3.5 text-slate-600" />
                </UiButton>
                <UiButton variant="ghost" size="sm" @click="openEditModal(contact)" title="Edit Contact">
                  <Edit class="w-3.5 h-3.5 text-slate-600" />
                </UiButton>
                <UiButton
                  variant="ghost"
                  size="sm"
                  class="text-red-500 hover:text-red-700 hover:bg-red-50"
                  @click="deleteMutation.mutate(contact.id)"
                  title="Delete Contact"
                >
                  <Trash2 class="w-3.5 h-3.5" />
                </UiButton>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else class="p-12 text-center text-slate-400 text-xs">
        No contacts found matching your criteria.
      </div>
    </div>

    <!-- Create / Edit Contact Modal -->
    <UiModal v-model="isModalOpen" :title="editingContact ? 'Edit Contact' : 'Add New Contact'" size="lg">
      <div class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UiInput v-model="contactForm.name" label="Full Name" placeholder="Johnathan Miller" required />
          <UiInput v-model="contactForm.company" label="Company Name" placeholder="Miller Technologies" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <UiInput v-model="contactForm.job_title" label="Job Title" placeholder="Director of Sales" />
          <UiInput v-model="contactForm.email" label="Email Address" type="email" placeholder="john@miller.com" />
          <UiInput v-model="contactForm.phone" label="Phone Number" placeholder="+1 (555) 000-0000" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <UiSelect
            v-model="contactForm.status"
            label="Relationship Status"
            :options="[
              { label: 'Customer', value: 'customer' },
              { label: 'Lead / Prospect', value: 'lead' },
              { label: 'Partner', value: 'partner' },
              { label: 'Churned', value: 'churned' },
            ]"
          />
          <UiInput v-model="contactForm.city" label="City" placeholder="Addis Ababa" />
          <UiInput v-model="contactForm.country" label="Country" placeholder="Ethiopia" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UiInput v-model="contactForm.website" label="Website URL" placeholder="https://miller.com" />
          <UiInput v-model="contactForm.source" label="Acquisition Channel" placeholder="Referral, Inbound, etc." />
        </div>

        <div class="space-y-1">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Account Notes</label>
          <textarea
            v-model="contactForm.notes"
            rows="3"
            placeholder="Account preferences, billing notes, background..."
            class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500"
          ></textarea>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
          <UiButton variant="outline" type="button" @click="isModalOpen = false">Cancel</UiButton>
          <UiButton :loading="saveMutation.isPending.value" @click="handleSave">
            {{ editingContact ? 'Save Changes' : 'Create Contact' }}
          </UiButton>
        </div>
      </div>
    </UiModal>

    <!-- Detailed Contact Profile Modal -->
    <UiModal v-model="isDrawerOpen" title="Account & Contact Profile" size="lg">
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
                <strong class="text-slate-800">{{ selectedContact.company || 'Individual Account' }}</strong>
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
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Email</span>
            <p class="font-mono text-slate-800 font-bold truncate">{{ selectedContact.email || '—' }}</p>
          </div>
          <div class="p-3 bg-white border border-slate-200 rounded-xl space-y-0.5">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Phone</span>
            <p class="font-mono text-slate-800 font-bold truncate">{{ selectedContact.phone || '—' }}</p>
          </div>
          <div class="p-3 bg-white border border-slate-200 rounded-xl space-y-0.5">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Location</span>
            <p class="text-slate-800 font-bold truncate">{{ [selectedContact.city, selectedContact.country].filter(Boolean).join(', ') || '—' }}</p>
          </div>
        </div>

        <!-- Deals Associated -->
        <div class="space-y-3">
          <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800 border-b border-slate-100 pb-2">
            Pipeline Deals ({{ selectedContact.deals?.length || 0 }})
          </h4>

          <div v-if="selectedContact.deals?.length" class="space-y-2">
            <div
              v-for="d in selectedContact.deals"
              :key="d.id"
              class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between text-xs"
            >
              <div>
                <span class="font-bold text-slate-900">{{ d.title }}</span>
                <span class="text-slate-400 ml-2 capitalize">({{ d.stage }})</span>
              </div>
              <span class="font-mono font-bold text-slate-900">${{ Number(d.amount).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</span>
            </div>
          </div>
          <p v-else class="text-xs text-slate-400 italic">No deals linked to this account yet.</p>
        </div>

        <div class="flex justify-end pt-3 border-t border-slate-100">
          <UiButton size="sm" @click="isDrawerOpen = false">Close</UiButton>
        </div>
      </div>
    </UiModal>
  </div>
</template>
