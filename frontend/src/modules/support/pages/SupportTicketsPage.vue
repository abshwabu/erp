<script setup lang="ts">
import { ref, computed, markRaw } from 'vue'
import { useI18n } from 'vue-i18n'
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

const { t } = useI18n()
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
      label: t('support.tickets.kpis.openInquiries'),
      value: open,
      icon: markRaw(MessageSquare),
    },
    {
      label: t('support.tickets.kpis.inProgress'),
      value: inProg,
      icon: markRaw(Clock),
    },
    {
      label: t('support.tickets.kpis.resolvedInquiries'),
      value: resolved,
      icon: markRaw(CheckCircle2),
    },
    {
      label: t('support.tickets.kpis.urgentPriority'),
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
    toast.success(t('support.tickets.messages.created'))
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || t('support.tickets.messages.failedCreate'))
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
    toast.success(isInternalNote.value ? t('support.tickets.messages.internalNoteAdded') : t('support.tickets.messages.replySent'))
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || t('support.tickets.messages.failedReply'))
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
    toast.success(t('support.tickets.messages.statusUpdated'))
  },
})

const deleteTicketMutation = useMutation({
  mutationFn: (id: string) => supportApi.deleteTicket(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['support'] })
    if (selectedTicket.value) selectedTicket.value = null
    toast.success(t('support.tickets.messages.deleted'))
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
    toast.error(t('support.tickets.messages.requiredFields'))
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
    case 'open': return { label: t('support.common.statuses.open'), variant: 'danger' as const }
    case 'in_progress': return { label: t('support.common.statuses.in_progress'), variant: 'info' as const }
    case 'pending': return { label: t('support.common.statuses.pending'), variant: 'warning' as const }
    case 'resolved': return { label: t('support.common.statuses.resolved'), variant: 'success' as const }
    case 'closed': return { label: t('support.common.statuses.closed'), variant: 'default' as const }
    default: return { label: status, variant: 'default' as const }
  }
}

const getPriorityBadge = (priority: string) => {
  switch (priority) {
    case 'urgent': return { label: t('support.common.priorities.urgent'), variant: 'danger' as const }
    case 'high': return { label: t('support.common.priorities.high'), variant: 'warning' as const }
    case 'normal': return { label: t('support.common.priorities.normal'), variant: 'default' as const }
    default: return { label: t('support.common.priorities.low'), variant: 'default' as const }
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">{{ t('support.tickets.title') }}</h1>
        <p class="text-xs sm:text-sm text-slate-500">{{ t('support.tickets.subtitle') }}</p>
      </div>
      <UiButton @click="isCreateModalOpen = true">
        <Plus class="w-4 h-4 mr-2" /> {{ t('support.tickets.newTicket') }}
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
              {{ st === 'all' ? `${t('support.tickets.filters.all')} (${tickets?.length || 0})` : t(`support.common.statuses.${st}`) }}
            </button>
          </div>

          <UiInput
            v-model="searchQuery"
            :placeholder="t('support.tickets.filters.searchPlaceholder')"
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
                {{ ticket.contact_name || ticket.customer?.name || t('support.tickets.thread.customerFallback') }}
              </span>
              <span class="text-[11px] text-slate-400 font-mono">
                {{ new Date(ticket.created_at).toLocaleDateString() }}
              </span>
            </div>
          </div>
        </div>

        <div v-else class="p-12 bg-white rounded-2xl border border-slate-200 text-center text-slate-400 text-xs">
          {{ t('support.tickets.filters.emptyList') }}
        </div>
      </div>

      <!-- Right: Ticket Conversation Thread (7 cols on lg) -->
      <div class="lg:col-span-7">
        <div v-if="!selectedTicket" class="bg-white rounded-2xl border border-slate-200 p-16 text-center space-y-3 text-slate-400">
          <Headphones class="w-12 h-12 mx-auto text-slate-300" />
          <h3 class="font-bold text-slate-700 text-sm">{{ t('support.tickets.thread.noSelectedTitle') }}</h3>
          <p class="text-xs max-w-sm mx-auto">
            {{ t('support.tickets.thread.noSelectedText') }}
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
                <Check class="w-3.5 h-3.5" /> {{ t('support.tickets.thread.resolveBtn') }}
              </button>

              <button
                v-if="selectedTicket.status === 'resolved'"
                type="button"
                @click="updateStatusMutation.mutate({ id: selectedTicket.id, status: 'in_progress' })"
                class="px-2.5 py-1.5 bg-slate-900 hover:bg-black text-white rounded-xl text-xs font-bold transition-all cursor-pointer shadow-2xs"
              >
                {{ t('support.tickets.thread.reopenBtn') }}
              </button>

              <button
                type="button"
                @click="deleteTicketMutation.mutate(selectedTicket.id)"
                class="p-1.5 text-slate-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition-colors cursor-pointer"
                :title="t('support.tickets.thread.deleteTicketTitle')"
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
                    {{ t('support.tickets.thread.internalNoteBadge') }}
                  </span>
                  <span v-else-if="msg.sender_type === 'agent'" class="text-[10px] font-bold bg-primary-100 text-primary-800 px-1.5 py-0.2 rounded">
                    {{ t('support.tickets.thread.supportAgentBadge') }}
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
                  {{ t('support.tickets.thread.publicReply') }}
                </button>
                <button
                  type="button"
                  @click="isInternalNote = true"
                  class="px-2.5 py-1 rounded-lg font-bold transition-all cursor-pointer flex items-center gap-1"
                  :class="isInternalNote ? 'bg-amber-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                >
                  <Lock class="w-3 h-3" /> {{ t('support.tickets.thread.internalAgentNote') }}
                </button>
              </div>

              <span class="text-[11px] text-slate-400">{{ t('support.tickets.thread.pressSendHint') }}</span>
            </div>

            <div class="relative">
              <textarea
                v-model="replyText"
                rows="3"
                :placeholder="isInternalNote ? t('support.tickets.thread.internalPlaceholder') : t('support.tickets.thread.publicPlaceholder')"
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
                {{ isInternalNote ? t('support.tickets.thread.postInternalNoteBtn') : t('support.tickets.thread.sendReplyBtn') }}
              </UiButton>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Ticket Modal -->
    <UiModal v-model="isCreateModalOpen" :title="t('support.tickets.modal.createTitle')" size="md">
      <div class="space-y-4">
        <UiInput v-model="newTicketForm.subject" :label="t('support.tickets.modal.subjectLabel')" :placeholder="t('support.tickets.modal.subjectPlaceholder')" required />

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UiSelect
            v-model="newTicketForm.customer_id"
            :label="t('support.tickets.modal.clientLabel')"
            :options="[{ label: t('support.tickets.modal.directInquirer'), value: '' }, ...(contacts?.map(c => ({ label: c.company ? `${c.name} (${c.company})` : c.name, value: c.id })) || [])]"
          />
          <UiSelect
            v-model="newTicketForm.priority"
            :label="t('support.tickets.modal.priorityLabel')"
            :options="[
              { label: t('support.common.priorities.low'), value: 'low' },
              { label: t('support.common.priorities.normal'), value: 'normal' },
              { label: t('support.common.priorities.high'), value: 'high' },
              { label: t('support.common.priorities.urgent'), value: 'urgent' },
            ]"
          />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UiInput v-model="newTicketForm.contact_name" :label="t('support.tickets.modal.contactNameLabel')" placeholder="Jane Doe" />
          <UiInput v-model="newTicketForm.contact_email" :label="t('support.tickets.modal.contactEmailLabel')" type="email" placeholder="jane@client.com" />
        </div>

        <UiSelect
          v-model="newTicketForm.channel"
          :label="t('support.tickets.modal.channelLabel')"
          :options="[
            { label: t('support.common.channels.web'), value: 'web' },
            { label: t('support.common.channels.email'), value: 'email' },
            { label: t('support.common.channels.phone'), value: 'phone' },
            { label: t('support.common.channels.portal'), value: 'portal' },
          ]"
        />

        <div class="space-y-1">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">{{ t('support.tickets.modal.messageLabel') }}</label>
          <textarea
            v-model="newTicketForm.message"
            rows="4"
            :placeholder="t('support.tickets.modal.messagePlaceholder')"
            class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500"
            required
          ></textarea>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
          <UiButton variant="outline" type="button" @click="isCreateModalOpen = false">{{ t('support.common.cancel') }}</UiButton>
          <UiButton :loading="createTicketMutation.isPending.value" @click="handleCreateTicket">
            {{ t('support.tickets.modal.createBtn') }}
          </UiButton>
        </div>
      </div>
    </UiModal>
  </div>
</template>
