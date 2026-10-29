<script setup lang="ts">
import { ref, computed, markRaw } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useQuery } from '@tanstack/vue-query'
import { crmApi } from '@/api/crm'
import UiStat from '@/components/ui/UiStat.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiSpinner from '@/components/ui/UiSpinner.vue'
import {
  TrendingUp,
  DollarSign,
  Users,
  Target,
  CheckCircle2,
  Clock,
  Briefcase,
  PhoneCall,
  Calendar,
  Mail,
  Plus,
  ArrowRight,
  Sparkles,
  PieChart,
  Layers,
} from '@lucide/vue'

const { t } = useI18n()
const router = useRouter()

const { data: statsData, isLoading } = useQuery({
  queryKey: ['crm', 'dashboard', 'stats'],
  queryFn: () => crmApi.getStats().then((r) => r.data.data),
})

const stats = computed(() => statsData.value)

const statCards = computed(() => {
  const s = stats.value
  return [
    {
      label: t('crm.dashboard.kpis.activePipelineValue'),
      value: `$${Number(s?.total_pipeline_value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`,
      icon: markRaw(DollarSign),
    },
    {
      label: t('crm.dashboard.kpis.closedWonRevenue'),
      value: `$${Number(s?.won_deals_value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`,
      icon: markRaw(TrendingUp),
    },
    {
      label: t('crm.dashboard.kpis.winRate'),
      value: `${s?.win_rate || 0}%`,
      icon: markRaw(Target),
    },
    {
      label: t('crm.dashboard.kpis.activeLeads'),
      value: s?.active_leads_count ?? 0,
      icon: markRaw(Users),
    },
  ]
})

const stages = computed(() => [
  { key: 'qualification', label: t('crm.dashboard.stages.qualification'), color: 'bg-blue-500', text: 'text-blue-700' },
  { key: 'proposal', label: t('crm.dashboard.stages.proposal'), color: 'bg-indigo-500', text: 'text-indigo-700' },
  { key: 'negotiation', label: t('crm.dashboard.stages.negotiation'), color: 'bg-amber-500', text: 'text-amber-700' },
  { key: 'won', label: t('crm.dashboard.stages.won'), color: 'bg-emerald-500', text: 'text-emerald-700' },
  { key: 'lost', label: t('crm.dashboard.stages.lost'), color: 'bg-red-500', text: 'text-red-700' },
])

const getActivityIcon = (type: string) => {
  switch (type) {
    case 'call': return PhoneCall
    case 'meeting': return Calendar
    case 'email': return Mail
    default: return CheckCircle2
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">{{ t('crm.dashboard.title') }}</h1>
        <p class="text-xs sm:text-sm text-slate-500">
          {{ t('crm.dashboard.subtitle') }}
        </p>
      </div>
      <div class="flex items-center gap-2">
        <UiButton variant="outline" size="sm" @click="router.push('/crm/leads')">
          <Plus class="w-3.5 h-3.5 mr-1" /> {{ t('crm.dashboard.buttons.newLead') }}
        </UiButton>
        <UiButton size="sm" @click="router.push('/crm/deals')">
          <Plus class="w-3.5 h-3.5 mr-1" /> {{ t('crm.dashboard.buttons.newDeal') }}
        </UiButton>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="p-16 flex justify-center">
      <UiSpinner size="lg" />
    </div>

    <div v-else class="space-y-6">
      <!-- Executive KPI Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <UiStat
          v-for="card in statCards"
          :key="card.label"
          :label="card.label"
          :value="card.value"
          :icon="card.icon"
        />
      </div>

      <!-- Pipeline Stage Visual Breakdown -->
      <div class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-xs space-y-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <Layers class="w-4 h-4 text-primary-600" />
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">{{ t('crm.dashboard.stageDistribution') }}</h2>
          </div>
          <button
            type="button"
            @click="router.push('/crm/deals')"
            class="text-xs text-primary-600 hover:text-primary-700 font-bold inline-flex items-center gap-1 hover:underline cursor-pointer"
          >
            {{ t('crm.dashboard.openKanban') }} <ArrowRight class="w-3.5 h-3.5" />
          </button>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
          <div
            v-for="st in stages"
            :key="st.key"
            @click="router.push('/crm/deals')"
            class="p-4 bg-slate-50/80 hover:bg-slate-100/80 rounded-xl border border-slate-200 transition-all cursor-pointer space-y-2"
          >
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-slate-700">{{ st.label }}</span>
              <span class="w-2.5 h-2.5 rounded-full" :class="st.color"></span>
            </div>
            <p class="text-lg font-black font-mono text-slate-900">
              ${{ Number(stats?.deals_by_stage?.[st.key]?.total_amount || 0).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}
            </p>
            <p class="text-[11px] text-slate-500 font-semibold">
              {{ stats?.deals_by_stage?.[st.key]?.count || 0 }} {{ t('crm.dashboard.opportunities') }}
            </p>
          </div>
        </div>
      </div>

      <!-- Two Column Grid: Top Opportunities & Recent Activities -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Top Active Deals -->
        <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
          <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">{{ t('crm.dashboard.topDeals') }}</h3>
            <button
              type="button"
              @click="router.push('/crm/deals')"
              class="text-xs text-primary-600 hover:text-primary-700 font-bold hover:underline cursor-pointer"
            >
              {{ t('crm.dashboard.viewAllDeals') }}
            </button>
          </div>

          <div v-if="stats?.top_deals?.length" class="divide-y divide-slate-100">
            <div
              v-for="deal in stats.top_deals"
              :key="deal.id"
              class="p-4 hover:bg-slate-50 transition-colors flex items-center justify-between gap-4"
            >
              <div class="space-y-1">
                <h4 class="font-bold text-slate-900 text-sm">{{ deal.title }}</h4>
                <p class="text-xs text-slate-500">
                  {{ deal.customer?.company || deal.customer?.name || t('crm.leads.detailsModal.individualProspect') }} •
                  <span class="capitalize font-semibold text-slate-700">{{ t(`crm.deals.stages.${deal.stage}`, deal.stage) }}</span>
                </p>
              </div>

              <div class="text-right">
                <span class="font-mono font-black text-slate-900 text-sm">
                  ${{ Number(deal.amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                </span>
                <p class="text-[10px] text-slate-400 font-semibold">{{ deal.probability }}% {{ t('crm.dashboard.winProbability') }}</p>
              </div>
            </div>
          </div>
          <div v-else class="p-10 text-center text-xs text-slate-400">
            {{ t('crm.dashboard.noDeals') }}
          </div>
        </div>

        <!-- Recent Activities -->
        <div class="lg:col-span-5 bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
          <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">{{ t('crm.dashboard.recentActivities') }}</h3>
            <button
              type="button"
              @click="router.push('/crm/activities')"
              class="text-xs text-primary-600 hover:text-primary-700 font-bold hover:underline cursor-pointer"
            >
              {{ t('crm.dashboard.viewAllActivities') }}
            </button>
          </div>

          <div v-if="stats?.recent_activities?.length" class="divide-y divide-slate-100">
            <div
              v-for="act in stats.recent_activities"
              :key="act.id"
              class="p-3.5 hover:bg-slate-50 transition-colors flex items-start gap-3"
            >
              <div class="w-8 h-8 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center shrink-0 mt-0.5">
                <component :is="getActivityIcon(act.type)" class="w-4 h-4" />
              </div>
              <div class="space-y-0.5 flex-1 min-w-0">
                <div class="flex items-center justify-between gap-2">
                  <h5 class="text-xs font-bold text-slate-900 truncate">{{ act.title }}</h5>
                  <UiBadge :variant="act.status === 'completed' ? 'success' : 'warning'" class="text-[9px] font-bold capitalize">
                    {{ t(`crm.activities.filters.${act.status}`, act.status) }}
                  </UiBadge>
                </div>
                <p class="text-[11px] text-slate-500 truncate">
                  {{ act.customer?.name || act.lead?.name || act.deal?.title || t('crm.activities.types.note') }}
                </p>
              </div>
            </div>
          </div>
          <div v-else class="p-10 text-center text-xs text-slate-400">
            {{ t('crm.dashboard.noActivities') }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
