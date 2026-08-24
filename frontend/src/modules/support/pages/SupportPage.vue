<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/api/client'
import { Headphones, Send, Clock, User, Mail } from '@lucide/vue'

interface TicketMessage {
  id: string
  sender_name: string
  sender_type: string
  message: string
  is_internal: boolean
  created_at: string
}

interface Ticket {
  id: string
  ticket_number: string
  subject: string
  contact_name: string | null
  contact_email: string | null
  status: string
  priority: string
  channel: string
  created_at: string
  assignee?: { name: string; email: string }
  messages_count?: number
  messages?: TicketMessage[]
}

const tickets = ref<Ticket[]>([])
const selectedTicket = ref<Ticket | null>(null)
const loading = ref(true)
const replyText = ref('')
const isInternalNote = ref(false)

const statusColors: Record<string, string> = {
  open: 'bg-blue-50 text-blue-700 border-blue-200',
  in_progress: 'bg-amber-50 text-amber-700 border-amber-200',
  pending: 'bg-purple-50 text-purple-700 border-purple-200',
  resolved: 'bg-green-50 text-green-700 border-green-200',
  closed: 'bg-gray-100 text-gray-700 border-gray-200',
}

const priorityColors: Record<string, string> = {
  low: 'text-gray-500',
  normal: 'text-blue-600',
  high: 'text-amber-600',
  urgent: 'text-red-600 font-bold',
}

async function fetchTickets() {
  loading.value = true
  try {
    const res = await api.get('/support/tickets')
    tickets.value = res.data?.data?.data ?? res.data?.data ?? []
  } catch (e) {
    console.error('Failed to load tickets', e)
  } finally {
    loading.value = false
  }
}

async function selectTicket(t: Ticket) {
  try {
    const res = await api.get(`/support/tickets/${t.id}`)
    selectedTicket.value = res.data?.data ?? res.data
  } catch (e) {
    console.error('Failed to load ticket details', e)
  }
}

async function sendReply() {
  if (!selectedTicket.value || !replyText.value.trim()) return
  try {
    const res = await api.post(`/support/tickets/${selectedTicket.value.id}/reply`, {
      message: replyText.value,
      is_internal: isInternalNote.value,
    })
    if (!selectedTicket.value.messages) selectedTicket.value.messages = []
    selectedTicket.value.messages.push(res.data?.data ?? res.data)
    replyText.value = ''
    isInternalNote.value = false
  } catch (e) {
    console.error('Failed to send reply', e)
  }
}

onMounted(fetchTickets)
</script>

<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <div class="flex items-center space-x-3">
        <Headphones class="w-7 h-7 text-gray-700" />
        <h1 class="text-2xl font-bold text-gray-900">Support Desk</h1>
      </div>
    </div>

    <div v-if="loading" class="text-center py-12 text-gray-500">Loading support tickets…</div>

    <div v-else-if="tickets.length === 0" class="text-center py-16 bg-white rounded-lg border border-gray-200">
      <Headphones class="w-12 h-12 text-gray-300 mx-auto mb-3" />
      <p class="text-gray-500 font-medium">No tickets opened</p>
      <p class="text-sm text-gray-400 mt-1">Customer support inquiries and requests will appear here.</p>
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Tickets list -->
      <div class="lg:col-span-1 space-y-3">
        <div
          v-for="item in tickets"
          :key="item.id"
          @click="selectTicket(item)"
          :class="[
            'p-4 rounded-lg border cursor-pointer transition-all hover:shadow-sm',
            selectedTicket?.id === item.id ? 'bg-primary-50/50 border-primary-500 ring-1 ring-primary-500' : 'bg-white border-gray-200'
          ]"
        >
          <div class="flex items-center justify-between mb-1.5">
            <span class="text-xs font-mono font-medium text-gray-500">{{ item.ticket_number }}</span>
            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium border" :class="statusColors[item.status] ?? 'bg-gray-100'">
              {{ item.status }}
            </span>
          </div>
          <h3 class="text-sm font-semibold text-gray-900 line-clamp-1">{{ item.subject }}</h3>
          <div class="flex items-center justify-between text-xs text-gray-500 mt-3 pt-2 border-t border-gray-100">
            <span>{{ item.contact_name || item.contact_email || 'Customer' }}</span>
            <span :class="priorityColors[item.priority]">{{ item.priority }}</span>
          </div>
        </div>
      </div>

      <!-- Ticket thread detail -->
      <div class="lg:col-span-2">
        <div v-if="!selectedTicket" class="bg-white rounded-lg border border-gray-200 p-12 text-center text-gray-400">
          Select a ticket to view the conversation history and reply.
        </div>
        <div v-else class="bg-white rounded-lg border border-gray-200 p-6 space-y-6 flex flex-col h-[600px]">
          <div class="border-b border-gray-200 pb-4 flex items-start justify-between shrink-0">
            <div>
              <span class="text-xs font-mono text-gray-500">{{ selectedTicket.ticket_number }}</span>
              <h2 class="text-lg font-bold text-gray-900">{{ selectedTicket.subject }}</h2>
              <p class="text-xs text-gray-500 mt-0.5">From: {{ selectedTicket.contact_name }} ({{ selectedTicket.contact_email }})</p>
            </div>
            <span class="inline-flex px-3 py-1 rounded-full text-xs font-medium border" :class="statusColors[selectedTicket.status] ?? 'bg-gray-100'">
              {{ selectedTicket.status }}
            </span>
          </div>

          <!-- Messages thread -->
          <div class="flex-1 overflow-y-auto space-y-4 pr-2">
            <div
              v-for="msg in selectedTicket.messages"
              :key="msg.id"
              :class="[
                'p-4 rounded-lg text-sm space-y-1',
                msg.is_internal ? 'bg-amber-50 border border-amber-200' : (msg.sender_type === 'agent' ? 'bg-primary-50 border border-primary-100 ml-6' : 'bg-gray-50 border border-gray-200 mr-6')
              ]"
            >
              <div class="flex items-center justify-between text-xs font-medium text-gray-600 mb-1">
                <span :class="msg.is_internal ? 'text-amber-800 font-bold' : ''">
                  {{ msg.sender_name }} {{ msg.is_internal ? '(Internal Note)' : '' }}
                </span>
                <span class="text-gray-400">{{ msg.created_at?.substring(0, 16).replace('T', ' ') }}</span>
              </div>
              <p class="text-gray-800 whitespace-pre-wrap leading-relaxed">{{ msg.message }}</p>
            </div>
          </div>

          <!-- Reply Box -->
          <div class="border-t border-gray-200 pt-4 space-y-3 shrink-0">
            <div class="flex items-center justify-between">
              <label class="flex items-center space-x-2 text-xs text-gray-600 cursor-pointer">
                <input type="checkbox" v-model="isInternalNote" class="rounded text-amber-600 focus:ring-amber-500" />
                <span>Internal note (hidden from customer)</span>
              </label>
            </div>
            <div class="flex space-x-2">
              <textarea
                v-model="replyText"
                rows="2"
                placeholder="Type your response or internal note…"
                class="flex-1 text-sm border border-gray-300 rounded-md p-2.5 focus:outline-none focus:ring-1 focus:ring-primary-500"
              ></textarea>
              <button
                @click="sendReply"
                class="px-4 bg-primary-600 text-white rounded-md hover:bg-primary-700 font-medium text-sm flex items-center justify-center space-x-1"
              >
                <Send class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
