<script setup lang="ts">
import { computed, markRaw } from 'vue'
import { useRouter } from 'vue-router'
import { useQuery } from '@tanstack/vue-query'
import { supportApi } from '@/api/support'
import type { SupportTicket } from '@/types/support'
import UiButton from '@/components/ui/UiButton.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiStat from '@/components/ui/UiStat.vue'
import UiSpinner from '@/components/ui/UiSpinner.vue'
import {
  Headphones,
  Plus,
  CheckCircle2,
  Clock,
  AlertCircle,
  AlertTriangle,
  MessageSquare,
  BookOpen,
  ArrowRight,
  Globe,
  Mail,
  Phone,
  Layers,
  Sparkles,
} from '@lucide/vue'

const router = useRouter()

const { data: statsData, isLoading } = useQuery({
  queryKey: ['support', 'dashboard', 'stats'],
  queryFn: () => supportApi.getStats().then((r) => r.data.data),
})

const stats = computed(() => {
  const s = statsData.value
  return [
    {
      label: 'Open Inquiries',
      value: s?.open_tickets ?? 0,
      icon: markRaw(MessageSquare),
    },
    {
      label: 'In Progress Tickets',
      value: s?.in_progress_tickets ?? 0,
      icon: markRaw(Clock),
    },
    {
      label: 'Resolution Rate',
      value: `${s?.resolution_rate ?? 0}%`,
      icon: markRaw(CheckCircle2),
    },
    {
      label: 'Urgent SLA Inquiries',
      value: s?.urgent_tickets ?? 0,
      icon: markRaw(AlertTriangle),
    },
  ]
})

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
        <h1 class="text-2xl font-bold text-slate-900">Helpdesk & Support Center</h1>
        <p class="text-xs sm:text-sm text-slate-500">Manage customer tickets, SLA resolution rates, communication logs, and knowledge base resources.</p>
      </div>
      <div class="flex items-center gap-2">
        <UiButton variant="outline" size="sm" @click="router.push('/support/knowledge-base')">
          <BookOpen class="w-3.5 h-3.5 mr-1.5" /> Knowledge Base
        </UiButton>
        <UiButton size="sm" @click="router.push('/support/tickets')">
          <MessageSquare class="w-3.5 h-3.5 mr-1.5" /> All Tickets Queue
        </UiButton>
      </div>
    </div>

    <!-- Executive Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <UiStat
        v-for="stat in stats"
        :key="stat.label"
        :label="stat.label"
        :value="stat.value"
        :icon="stat.icon"
      />
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="p-16 flex justify-center">
      <UiSpinner size="lg" />
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Recent Tickets Queue -->
      <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center gap-2">
            <Headphones class="w-5 h-5 text-primary-600" />
            <h2 class="font-bold text-slate-900 text-base">Recent Support Tickets</h2>
          </div>
          <button
            type="button"
            @click="router.push('/support/tickets')"
            class="text-xs font-bold text-primary-600 hover:text-primary-800 flex items-center gap-1 cursor-pointer"
          >
            View Ticket Queue <ArrowRight class="w-3 h-3" />
          </button>
        </div>

        <div v-if="statsData?.recent_tickets?.length" class="space-y-3">
          <div
            v-for="ticket in statsData.recent_tickets"
            :key="ticket.id"
            @click="router.push('/support/tickets')"
            class="p-4 rounded-xl border border-slate-200 hover:border-slate-300 hover:bg-slate-50/50 transition-all cursor-pointer flex items-start justify-between gap-3"
          >
            <div class="space-y-1">
              <div class="flex items-center gap-2">
                <span class="text-xs font-mono font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded">
                  {{ ticket.ticket_number }}
                </span>
                <UiBadge :variant="getStatusBadge(ticket.status).variant" class="text-[10px] font-bold">
                  {{ getStatusBadge(ticket.status).label }}
                </UiBadge>
                <UiBadge :variant="getPriorityBadge(ticket.priority).variant" class="text-[9px] font-bold">
                  {{ getPriorityBadge(ticket.priority).label }}
                </UiBadge>
              </div>

              <h4 class="font-bold text-slate-900 text-sm leading-snug">
                {{ ticket.subject }}
              </h4>

              <div class="flex items-center gap-3 text-xs text-slate-400 pt-0.5">
                <span v-if="ticket.contact_name" class="font-semibold text-slate-700">
                  {{ ticket.contact_name }}
                </span>
                <span v-if="ticket.customer" class="text-slate-500">
                  ({{ ticket.customer.company || ticket.customer.name }})
                </span>
                <span>• {{ ticket.messages_count ?? 0 }} replies</span>
              </div>
            </div>

            <div class="text-right text-[11px] text-slate-400 font-mono shrink-0">
              {{ new Date(ticket.created_at).toLocaleDateString() }}
            </div>
          </div>
        </div>
        <div v-else class="p-12 text-center text-slate-400 text-xs">
          No customer tickets logged yet.
        </div>
      </div>

      <!-- Channels & Knowledge Base Quick Launch -->
      <div class="space-y-6">
        <!-- Inbound Channels Breakdown -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-4">
          <h3 class="font-bold text-slate-900 text-sm">Tickets by Channel</h3>
          <div class="space-y-2.5">
            <div class="flex items-center justify-between p-2.5 bg-slate-50 rounded-xl text-xs">
              <div class="flex items-center gap-2 text-slate-700 font-semibold">
                <Globe class="w-4 h-4 text-blue-600" /> Web Inquiries
              </div>
              <span class="font-mono font-bold text-slate-900">{{ statsData?.tickets_by_channel?.web ?? 0 }}</span>
            </div>

            <div class="flex items-center justify-between p-2.5 bg-slate-50 rounded-xl text-xs">
              <div class="flex items-center gap-2 text-slate-700 font-semibold">
                <Mail class="w-4 h-4 text-amber-600" /> Email Tickets
              </div>
              <span class="font-mono font-bold text-slate-900">{{ statsData?.tickets_by_channel?.email ?? 0 }}</span>
            </div>

            <div class="flex items-center justify-between p-2.5 bg-slate-50 rounded-xl text-xs">
              <div class="flex items-center gap-2 text-slate-700 font-semibold">
                <Phone class="w-4 h-4 text-emerald-600" /> Phone Support
              </div>
              <span class="font-mono font-bold text-slate-900">{{ statsData?.tickets_by_channel?.phone ?? 0 }}</span>
            </div>

            <div class="flex items-center justify-between p-2.5 bg-slate-50 rounded-xl text-xs">
              <div class="flex items-center gap-2 text-slate-700 font-semibold">
                <Layers class="w-4 h-4 text-purple-600" /> Client Portal
              </div>
              <span class="font-mono font-bold text-slate-900">{{ statsData?.tickets_by_channel?.portal ?? 0 }}</span>
            </div>
          </div>
        </div>

        <!-- Knowledge Base Quick Card -->
        <div class="bg-linear-to-br from-primary-900 to-indigo-950 rounded-2xl p-5 text-white space-y-3 shadow-sm">
          <div class="flex items-center gap-2 text-primary-300">
            <BookOpen class="w-5 h-5" />
            <h3 class="font-bold text-sm text-white">Knowledge Base & FAQs</h3>
          </div>
          <p class="text-xs text-primary-100 leading-relaxed">
            Empower your support team with standard operating procedures and FAQs to resolve client issues faster.
          </p>
          <div class="pt-1">
            <UiButton variant="secondary" size="sm" class="w-full justify-center" @click="router.push('/support/knowledge-base')">
              Browse Articles ({{ statsData?.total_articles ?? 0 }}) →
            </UiButton>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
