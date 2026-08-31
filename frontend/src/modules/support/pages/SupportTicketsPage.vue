<script setup lang="ts">
import { ref, computed, markRaw } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { supportApi } from '@/api/support'
import { crmApi } from '@/api/crm'
import type { SupportTicket, TicketStatus, TicketPriority, TicketChannel } from '@/types/support'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiStat from '@/components/ui/UiStat.vue'
import UiSpinner from '@/components/ui/UiSpinner.vue'
import { useToast } from '@/composables/useToast'
import {
  MessageSquare,
  Plus,
  Search,
  CheckCircle2,
  Clock,
  Send,
  Lock,
  User,
  Building2,
  Trash2,
  Edit,
  Mail,
  Phone,
  Globe,
  Layers,
  ArrowRight,
  Headphones,
  Check,
  AlertCircle,
  HelpCircle,
} from '@lucide/vue'

const queryClient = useQueryClient()
const toast = useToast()

const searchQuery = ref('')
const selectedStatus = ref<string>('all')
const isCreateModalOpen = ref(false)
const selectedTicket = ref<SupportTicket | null>(null)
const replyText = ref('')
const isInternalNote = ref(false)

const newTicketForm = ref({
  subject: '',
  message: '',
  customer_id: '',
  contact_name: '',
  contact_email: '',
  priority: 'normal' as TicketPriority,
  channel: 'web' as TicketChannel,
})

// Queries
const { data: tickets, isLoading } = useQuery({
  queryKey: ['support', 'tickets'],
  queryFn: () => supportApi.getTickets().then((r) => r.data.data),
})

const { data: contacts } = useQuery({
  queryKey: ['crm', 'contacts'],
  queryFn: () => crmApi.getContacts().then((r) => r.data.data),
})

const filteredTickets = computed(() => {
  let list = tickets.value || []
  if (selectedStatus.value !== 'all') {
    list = list.filter((t) => t.status === selectedStatus.value)
  }
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(
      (t) =>
        t.ticket_number.toLowerCase().includes(q) ||
        t.subject.toLowerCase().includes(q) ||
        (t.contact_name || '').toLowerCase().includes(q) ||
        (t.contact_email || '').toLowerCase().includes(q) ||
        (t.customer?.name || '').toLowerCase().includes(q)
    )
  }
  return list
})

// Stats
const stats = computed(() => {
  const list = tickets.value || []
  const open = list.filter((t) => t.status === 'open').length
  const inProg = list.filter((t) => t.status === 'in_progress').length
  const resolved = list.filter((t) => t.status === 'resolved' || t.status === 'closed').length
  const urgent = list.filter((t) => t.priority === 'urgent' && t.status !== 'resolved' && t.status !== 'closed').length

  return [
    {
      label: 'Open Inquiries',
      value: open,
      icon: markRaw(MessageSquare),
    },
    {
      label: 'In Progress',
      value: inProg,
      icon: markRaw(Clock),
    },
    {
      label: 'Resolved Inquiries',
      value: resolved,
      icon: markRaw(CheckCircle2),
    },
    {
      label: 'Urgent Priority',
      value: urgent,
      icon: markRaw(AlertCircle),
    },
  ]
})

// Mutations
const createTicketMutation = useMutation({
  mutationFn: (payload: any) => supportApi.createTicket(payload),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['support'] })
    isCreateModalOpen.value = false
    toast.success('Support ticket created')
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || 'Failed to create ticket')
  },
})

const replyMutation = useMutation({
  mutationFn: ({ id, payload }: { id: string; payload: any }) =>
    supportApi.replyTicket(id, payload),
  onSuccess: async () => {
    if (selectedTicket.value) {
      const refreshed = await supportApi.getTicket(selectedTicket.value.id)
      selectedTicket.value = refreshed.data.data
    }
    queryClient.invalidateQueries({ queryKey: ['support'] })
    replyText.value = ''
    toast.success(isInternalNote.value ? 'Internal agent note added' : 'Reply sent to customer')
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || 'Failed to send message')
  },
})

const updateStatusMutation = useMutation({
  mutationFn: ({ id, status }: { id: string; status: TicketStatus }) =>
    supportApi.updateTicket(id, { status }),
  onSuccess: async () => {
    if (selectedTicket.value) {
      const refreshed = await supportApi.getTicket(selectedTicket.value.id)
      selectedTicket.value = refreshed.data.data
    }
    queryClient.invalidateQueries({ queryKey: ['support'] })
    toast.success('Ticket status updated')
  },
})

const deleteTicketMutation = useMutation({
  mutationFn: (id: string) => supportApi.deleteTicket(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['support'] })
    if (selectedTicket.value) selectedTicket.value = null
    toast.success('Ticket deleted')
  },
})

const openTicketDetails = async (t: SupportTicket) => {
  try {
    const res = await supportApi.getTicket(t.id)
    selectedTicket.value = res.data.data
  } catch (e) {
    selectedTicket.value = t
  }
}

const handleCreateTicket = () => {
  if (!newTicketForm.value.subject || !newTicketForm.value.message) {
    toast.error('Subject and message description are required')
    return
  }
  createTicketMutation.mutate({
    ...newTicketForm.value,
    customer_id: newTicketForm.value.customer_id || null,
  })
}

const handleSendReply = () => {
  if (!selectedTicket.value || !replyText.value.trim()) return
  replyMutation.mutate({
    id: selectedTicket.value.id,
    payload: {
      message: replyText.value.trim(),
      is_internal: isInternalNote.value,
      sender_type: 'agent',
    },
  })
}

const getStatusBadge = (status: string) => {
  switch (status) {
    case 'open': return { label: 'Open', variant: 'danger' as const }
    case 'in_progress': return { label: 'In Progress', variant: 'info' as const }
    case 'pending': return { label: 'Pending Client', variant: 'warning' as const }
    case 'resolved': return { label: 'Resolved 🎉', variant: 'success' as const }
    case 'closed': return { label: 'Closed', variant: 'default' as const }
    default: return { label: status, variant: 'default' as const }
  }
}

const getPriorityBadge = (priority: string) => {
  switch (priority) {
    case 'urgent': return { label: 'Urgent', variant: 'danger' as const }
    case 'high': return { label: 'High', variant: 'warning' as const }
    case 'normal': return { label: 'Normal', variant: 'default' as const }
    default: return { label: 'Low', variant: 'default' as const }
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Support Ticket Queue</h1>
        <p class="text-xs sm:text-sm text-slate-500">Track and respond to client inquiries, communicate through thread histories, and post internal agent notes.</p>
      </div>
      <UiButton @click="isCreateModalOpen = true">
        <Plus class="w-4 h-4 mr-2" /> New Support Ticket
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

    <!-- Layout: Queue List on Left / Conversation Thread on Right -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
      <!-- Left: Ticket Queue (5 cols on lg) -->
      <div class="lg:col-span-5 space-y-4">
        <!-- Filter Tabs & Search -->
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-xs space-y-3">
          <div class="flex items-center gap-1 overflow-x-auto pb-1">
            <button
              v-for="st in ['all', 'open', 'in_progress', 'pending', 'resolved', 'closed']"
              :key="st"
              type="button"
              @click="selectedStatus = st"
              class="px-2.5 py-1 rounded-lg text-xs font-bold capitalize transition-all cursor-pointer whitespace-nowrap"
              :class="selectedStatus === st ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
            >
              {{ st === 'all' ? `All (${tickets?.length || 0})` : st.replace('_', ' ') }}
            </button>
          </div>

          <UiInput
            v-model="searchQuery"
            placeholder="Search tickets, customers, emails..."
            size="sm"
          >
            <template #prefix><Search class="w-3.5 h-3.5 text-slate-400" /></template>
          </UiInput>
        </div>

        <!-- Ticket Cards -->
        <div v-if="isLoading" class="p-12 flex justify-center">
          <UiSpinner size="lg" />
        </div>

        <div v-else-if="filteredTickets.length" class="space-y-2.5 max-h-[700px] overflow-y-auto pr-1">
          <div
            v-for="ticket in filteredTickets"
            :key="ticket.id"
            @click="openTicketDetails(ticket)"
            class="p-4 rounded-2xl border transition-all cursor-pointer space-y-2.5 shadow-2xs hover:shadow-xs"
            :class="[
              selectedTicket?.id === ticket.id
                ? 'bg-primary-50/50 border-primary-500 ring-1 ring-primary-500'
                : 'bg-white border-slate-200 hover:border-slate-300'
            ]"
          >
            <div class="flex items-start justify-between gap-2">
              <div class="flex items-center gap-2">
                <span class="text-xs font-mono font-bold text-slate-600 bg-slate-100 px-2 py-0.5 rounded">
                  {{ ticket.ticket_number }}
                </span>
                <UiBadge :variant="getStatusBadge(ticket.status).variant" class="text-[10px] font-bold">
                  {{ getStatusBadge(ticket.status).label }}
                </UiBadge>
              </div>

              <UiBadge :variant="getPriorityBadge(ticket.priority).variant" class="text-[9px] font-bold">
                {{ getPriorityBadge(ticket.priority).label }}
              </UiBadge>
            </div>

            <h3 class="font-bold text-slate-900 text-xs leading-snug line-clamp-2">
              {{ ticket.subject }}
            </h3>

            <div class="flex items-center justify-between text-xs text-slate-500 pt-1 border-t border-slate-100">
              <span class="truncate font-semibold text-slate-700">
                {{ ticket.contact_name || ticket.customer?.name || 'Customer' }}
              </span>
              <span class="text-[11px] text-slate-400 font-mono">
                {{ new Date(ticket.created_at).toLocaleDateString() }}
              </span>
            </div>
          </div>
        </div>

        <div v-else class="p-12 bg-white rounded-2xl border border-slate-200 text-center text-slate-400 text-xs">
          No tickets found matching your filter.
        </div>
      </div>

      <!-- Right: Ticket Conversation Thread (7 cols on lg) -->
      <div class="lg:col-span-7">
        <div v-if="!selectedTicket" class="bg-white rounded-2xl border border-slate-200 p-16 text-center space-y-3 text-slate-400">
          <Headphones class="w-12 h-12 mx-auto text-slate-300" />
          <h3 class="font-bold text-slate-700 text-sm">Select a ticket from the queue</h3>
          <p class="text-xs max-w-sm mx-auto">
            Click any ticket on the left to inspect conversation history, send replies, or add internal notes.
          </p>
        </div>

        <div v-else class="bg-white rounded-2xl border border-slate-200 shadow-xs flex flex-col min-h-[650px] overflow-hidden">
          <!-- Ticket Header -->
          <div class="p-4 sm:p-5 bg-slate-50/80 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="space-y-1">
              <div class="flex items-center gap-2">
                <span class="text-xs font-mono font-bold bg-white px-2 py-0.5 rounded border border-slate-200 text-slate-700">
                  {{ selectedTicket.ticket_number }}
                </span>
                <UiBadge :variant="getStatusBadge(selectedTicket.status).variant" class="font-bold text-xs">
                  {{ getStatusBadge(selectedTicket.status).label }}
                </UiBadge>
                <UiBadge :variant="getPriorityBadge(selectedTicket.priority).variant" class="font-bold text-[10px]">
                  {{ getPriorityBadge(selectedTicket.priority).label }}
                </UiBadge>
              </div>

              <h2 class="font-black text-slate-900 text-base sm:text-lg leading-snug">
                {{ selectedTicket.subject }}
              </h2>

              <div class="text-xs text-slate-500 flex flex-wrap items-center gap-2">
                <span class="font-semibold text-slate-800">{{ selectedTicket.contact_name }}</span>
                <span v-if="selectedTicket.contact_email">({{ selectedTicket.contact_email }})</span>
                <span v-if="selectedTicket.customer">• {{ selectedTicket.customer.company || selectedTicket.customer.name }}</span>
              </div>
            </div>

            <!-- Quick Status Change Actions -->
            <div class="flex items-center gap-1.5 shrink-0 self-end sm:self-center">
              <button
                v-if="selectedTicket.status !== 'resolved'"
                type="button"
                @click="updateStatusMutation.mutate({ id: selectedTicket.id, status: 'resolved' })"
                class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold flex items-center gap-1 transition-all cursor-pointer shadow-2xs"
              >
                <Check class="w-3.5 h-3.5" /> Resolve
              </button>

              <button
                v-if="selectedTicket.status === 'resolved'"
                type="button"
                @click="updateStatusMutation.mutate({ id: selectedTicket.id, status: 'in_progress' })"
                class="px-2.5 py-1.5 bg-slate-900 hover:bg-black text-white rounded-xl text-xs font-bold transition-all cursor-pointer shadow-2xs"
              >
                Re-open Ticket
              </button>

              <button
                type="button"
                @click="deleteTicketMutation.mutate(selectedTicket.id)"
                class="p-1.5 text-slate-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition-colors cursor-pointer"
                title="Delete Ticket"
              >
                <Trash2 class="w-4 h-4" />
              </button>
            </div>
          </div>

          <!-- Message Thread List -->
          <div class="p-5 flex-1 space-y-4 overflow-y-auto max-h-[420px] bg-slate-50/30">
            <div
              v-for="msg in selectedTicket.messages"
              :key="msg.id"
              class="rounded-2xl p-4 space-y-2 text-xs transition-all shadow-2xs"
              :class="[
                msg.is_internal
                  ? 'bg-amber-50/90 border border-amber-200 text-amber-950 ml-4'
                  : msg.sender_type === 'agent'
                    ? 'bg-primary-50/70 border border-primary-100 text-slate-900 ml-6'
                    : 'bg-white border border-slate-200 text-slate-800 mr-6'
              ]"
            >
              <div class="flex items-center justify-between font-bold">
                <div class="flex items-center gap-1.5">
                  <Lock v-if="msg.is_internal" class="w-3.5 h-3.5 text-amber-600" />
                  <span :class="msg.is_internal ? 'text-amber-800' : msg.sender_type === 'agent' ? 'text-primary-700' : 'text-slate-900'">
                    {{ msg.sender_name }}
                  </span>
                  <span v-if="msg.is_internal" class="text-[10px] font-bold bg-amber-200 text-amber-800 px-1.5 py-0.2 rounded">
                    INTERNAL NOTE
                  </span>
                  <span v-else-if="msg.sender_type === 'agent'" class="text-[10px] font-bold bg-primary-100 text-primary-800 px-1.5 py-0.2 rounded">
                    SUPPORT AGENT
                  </span>
                </div>

                <span class="text-[10px] text-slate-400 font-mono font-normal">
                  {{ new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}
                </span>
              </div>

              <div class="leading-relaxed whitespace-pre-wrap">
                {{ msg.message }}
              </div>
            </div>
          </div>

          <!-- Quick Reply / Internal Note Input Box -->
          <div class="p-4 bg-white border-t border-slate-200 space-y-3">
            <div class="flex items-center justify-between text-xs">
              <div class="flex items-center gap-2">
                <button
                  type="button"
                  @click="isInternalNote = false"
                  class="px-2.5 py-1 rounded-lg font-bold transition-all cursor-pointer"
                  :class="!isInternalNote ? 'bg-primary-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                >
                  Public Reply
                </button>
                <button
                  type="button"
                  @click="isInternalNote = true"
                  class="px-2.5 py-1 rounded-lg font-bold transition-all cursor-pointer flex items-center gap-1"
                  :class="isInternalNote ? 'bg-amber-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                >
                  <Lock class="w-3 h-3" /> Internal Agent Note
                </button>
              </div>

              <span class="text-[11px] text-slate-400">Press send to post</span>
            </div>

            <div class="relative">
              <textarea
                v-model="replyText"
                rows="3"
                :placeholder="isInternalNote ? 'Write an internal note visible only to your support team...' : 'Type your reply to the customer...'"
                class="w-full px-3.5 py-2.5 rounded-xl border text-xs text-slate-900 focus:outline-none transition-all"
                :class="isInternalNote ? 'bg-amber-50/50 border-amber-300 focus:ring-1 focus:ring-amber-500' : 'bg-slate-50 border-slate-300 focus:bg-white focus:ring-1 focus:ring-primary-500'"
              ></textarea>
            </div>

            <div class="flex justify-end">
              <UiButton
                size="sm"
                :variant="isInternalNote ? 'warning' : 'primary'"
                :loading="replyMutation.isPending.value"
                @click="handleSendReply"
              >
                <Send class="w-3.5 h-3.5 mr-1.5" />
                {{ isInternalNote ? 'Post Internal Note' : 'Send Reply' }}
              </UiButton>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Ticket Modal -->
    <UiModal v-model="isCreateModalOpen" title="Create Customer Support Ticket" size="md">
      <div class="space-y-4">
        <UiInput v-model="newTicketForm.subject" label="Subject / Problem Summary" placeholder="e.g. Invoicing calculation mismatch" required />

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UiSelect
            v-model="newTicketForm.customer_id"
            label="Linked Client Account (Optional)"
            :options="[{ label: 'None (Direct Inquirer)', value: '' }, ...(contacts?.map(c => ({ label: c.company ? `${c.name} (${c.company})` : c.name, value: c.id })) || [])]"
          />
          <UiSelect
            v-model="newTicketForm.priority"
            label="Priority Level"
            :options="[
              { label: 'Low', value: 'low' },
              { label: 'Normal', value: 'normal' },
              { label: 'High', value: 'high' },
              { label: 'Urgent (SLA)', value: 'urgent' },
            ]"
          />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UiInput v-model="newTicketForm.contact_name" label="Contact Name" placeholder="Jane Doe" />
          <UiInput v-model="newTicketForm.contact_email" label="Contact Email" type="email" placeholder="jane@client.com" />
        </div>

        <UiSelect
          v-model="newTicketForm.channel"
          label="Inbound Channel"
          :options="[
            { label: 'Web Helpdesk', value: 'web' },
            { label: 'Email', value: 'email' },
            { label: 'Phone Call', value: 'phone' },
            { label: 'Client Portal', value: 'portal' },
          ]"
        />

        <div class="space-y-1">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Initial Inquiry / Message</label>
          <textarea
            v-model="newTicketForm.message"
            rows="4"
            placeholder="Detailed description of the customer inquiry, error logs, or request..."
            class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500"
            required
          ></textarea>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
          <UiButton variant="outline" type="button" @click="isCreateModalOpen = false">Cancel</UiButton>
          <UiButton :loading="createTicketMutation.isPending.value" @click="handleCreateTicket">
            Create Ticket
          </UiButton>
        </div>
      </div>
    </UiModal>
  </div>
</template>
