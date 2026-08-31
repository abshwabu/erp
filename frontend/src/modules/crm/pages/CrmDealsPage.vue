<script setup lang="ts">
import { ref, computed, markRaw } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { crmApi } from '@/api/crm'
import type { Deal, DealStage } from '@/types/crm'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiStat from '@/components/ui/UiStat.vue'
import UiSpinner from '@/components/ui/UiSpinner.vue'
import { useToast } from '@/composables/useToast'
import {
  DollarSign,
  TrendingUp,
  Plus,
  Search,
  LayoutGrid,
  List,
  CheckCircle2,
  XCircle,
  Clock,
  ArrowRight,
  ArrowLeft,
  Calendar,
  Building2,
  Trash2,
  Edit,
  Sparkles,
  Layers,
  ChevronRight,
  ChevronLeft,
} from '@lucide/vue'

const queryClient = useQueryClient()
const toast = useToast()

const viewMode = ref<'kanban' | 'list'>('kanban')
const searchQuery = ref('')
const isModalOpen = ref(false)
const editingDeal = ref<Deal | null>(null)

const dealForm = ref({
  title: '',
  customer_id: '',
  stage: 'qualification' as DealStage,
  amount: '',
  currency: 'USD',
  probability: 20,
  expected_close_date: '',
  notes: '',
})

// Queries
const { data: deals, isLoading } = useQuery({
  queryKey: ['crm', 'deals'],
  queryFn: () => crmApi.getDeals().then((r) => r.data.data),
})

const { data: contacts } = useQuery({
  queryKey: ['crm', 'contacts'],
  queryFn: () => crmApi.getContacts().then((r) => r.data.data),
})

const stages: Array<{ key: DealStage; label: string; color: string; border: string }> = [
  { key: 'qualification', label: 'Qualification', color: 'bg-blue-50 text-blue-800', border: 'border-blue-200' },
  { key: 'proposal', label: 'Proposal Sent', color: 'bg-indigo-50 text-indigo-800', border: 'border-indigo-200' },
  { key: 'negotiation', label: 'Negotiation', color: 'bg-amber-50 text-amber-800', border: 'border-amber-200' },
  { key: 'won', label: 'Closed Won 🎉', color: 'bg-emerald-50 text-emerald-800', border: 'border-emerald-200' },
  { key: 'lost', label: 'Closed Lost ❌', color: 'bg-red-50 text-red-800', border: 'border-red-200' },
]

const filteredDeals = computed(() => {
  let list = deals.value || []
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(
      (d) =>
        d.title.toLowerCase().includes(q) ||
        (d.customer?.name || '').toLowerCase().includes(q) ||
        (d.customer?.company || '').toLowerCase().includes(q)
    )
  }
  return list
})

const dealsByStage = computed(() => {
  const map: Record<DealStage, Deal[]> = {
    qualification: [],
    proposal: [],
    negotiation: [],
    won: [],
    lost: [],
  }
  for (const deal of filteredDeals.value) {
    if (map[deal.stage]) {
      map[deal.stage].push(deal)
    }
  }
  return map
})

const stageTotals = computed(() => {
  const map: Record<DealStage, number> = {
    qualification: 0,
    proposal: 0,
    negotiation: 0,
    won: 0,
    lost: 0,
  }
  for (const deal of filteredDeals.value) {
    if (map[deal.stage] !== undefined) {
      map[deal.stage] += Number(deal.amount || 0)
    }
  }
  return map
})

// Stats
const stats = computed(() => {
  const list = deals.value || []
  const active = list.filter((d) => !['won', 'lost'].includes(d.stage))
  const won = list.filter((d) => d.stage === 'won')
  const totalActiveVal = active.reduce((sum, d) => sum + Number(d.amount || 0), 0)
  const totalWonVal = won.reduce((sum, d) => sum + Number(d.amount || 0), 0)

  return [
    {
      label: 'Active Opportunities',
      value: active.length,
      icon: markRaw(Layers),
    },
    {
      label: 'Active Pipeline Value',
      value: `$${totalActiveVal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`,
      icon: markRaw(DollarSign),
    },
    {
      label: 'Closed Won Deals',
      value: won.length,
      icon: markRaw(CheckCircle2),
    },
    {
      label: 'Total Won Revenue',
      value: `$${totalWonVal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`,
      icon: markRaw(TrendingUp),
    },
  ]
})

// Mutations
const saveMutation = useMutation({
  mutationFn: (payload: any) => {
    if (editingDeal.value) {
      return crmApi.updateDeal(editingDeal.value.id, payload)
    }
    return crmApi.createDeal(payload)
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['crm'] })
    isModalOpen.value = false
    toast.success(editingDeal.value ? 'Deal updated' : 'Deal added to pipeline')
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || 'Failed to save deal')
  },
})

const stageMutation = useMutation({
  mutationFn: ({ id, stage }: { id: string; stage: DealStage }) =>
    crmApi.updateDealStage(id, { stage }),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['crm'] })
    toast.success('Deal stage updated')
  },
})

const deleteMutation = useMutation({
  mutationFn: (id: string) => crmApi.deleteDeal(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['crm'] })
    toast.success('Deal removed')
  },
})

const openCreateModal = () => {
  editingDeal.value = null
  dealForm.value = {
    title: '',
    customer_id: '',
    stage: 'qualification',
    amount: '',
    currency: 'USD',
    probability: 20,
    expected_close_date: '',
    notes: '',
  }
  isModalOpen.value = true
}

const openEditModal = (deal: Deal) => {
  editingDeal.value = deal
  dealForm.value = {
    title: deal.title,
    customer_id: deal.customer_id || '',
    stage: deal.stage,
    amount: String(deal.amount),
    currency: deal.currency || 'USD',
    probability: deal.probability || 20,
    expected_close_date: deal.expected_close_date ? String(deal.expected_close_date).slice(0, 10) : '',
    notes: deal.notes || '',
  }
  isModalOpen.value = true
}

const handleSave = () => {
  if (!dealForm.value.title) {
    toast.error('Please enter a deal title')
    return
  }
  saveMutation.mutate({
    ...dealForm.value,
    customer_id: dealForm.value.customer_id || null,
    amount: Number(dealForm.value.amount || 0),
    probability: Number(dealForm.value.probability || 0),
    expected_close_date: dealForm.value.expected_close_date || null,
  })
}

const moveToStage = (deal: Deal, newStage: DealStage) => {
  stageMutation.mutate({ id: deal.id, stage: newStage })
}

const getNextStage = (curr: DealStage): DealStage | null => {
  const order: DealStage[] = ['qualification', 'proposal', 'negotiation', 'won']
  const idx = order.indexOf(curr)
  return idx >= 0 && idx < order.length - 1 ? order[idx + 1] : null
}

const getPrevStage = (curr: DealStage): DealStage | null => {
  const order: DealStage[] = ['qualification', 'proposal', 'negotiation']
  const idx = order.indexOf(curr)
  return idx > 0 ? order[idx - 1] : null
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Deals & Sales Pipeline</h1>
        <p class="text-xs sm:text-sm text-slate-500">Track and advance high-value commercial opportunities from qualification to close.</p>
      </div>
      <div class="flex items-center gap-2">
        <div class="flex items-center bg-slate-100 p-1 rounded-xl">
          <button
            type="button"
            @click="viewMode = 'kanban'"
            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5"
            :class="viewMode === 'kanban' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900'"
          >
            <LayoutGrid class="w-3.5 h-3.5" /> Kanban
          </button>
          <button
            type="button"
            @click="viewMode = 'list'"
            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5"
            :class="viewMode === 'list' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900'"
          >
            <List class="w-3.5 h-3.5" /> Table
          </button>
        </div>
        <UiButton @click="openCreateModal">
          <Plus class="w-4 h-4 mr-1.5" /> New Deal
        </UiButton>
      </div>
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

    <!-- Search Bar -->
    <div class="flex items-center justify-between gap-3 bg-white p-3.5 rounded-2xl border border-slate-200 shadow-xs">
      <UiInput
        v-model="searchQuery"
        placeholder="Filter deals by title, company, customer..."
        size="sm"
        class="w-full sm:w-80"
      >
        <template #prefix><Search class="w-3.5 h-3.5 text-slate-400" /></template>
      </UiInput>

      <span class="text-xs font-bold text-slate-500">
        {{ filteredDeals.length }} Opportunities
      </span>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="p-16 flex justify-center">
      <UiSpinner size="lg" />
    </div>

    <!-- 1. Kanban Board View -->
    <div v-else-if="viewMode === 'kanban'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-start">
      <div
        v-for="st in stages"
        :key="st.key"
        class="bg-slate-50/70 border rounded-2xl p-3.5 space-y-3 min-h-[500px]"
        :class="st.border"
      >
        <!-- Column Header -->
        <div class="flex items-center justify-between pb-2 border-b border-slate-200">
          <div class="flex items-center gap-2">
            <h3 class="text-xs font-black uppercase tracking-wider text-slate-800">{{ st.label }}</h3>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white text-slate-700 shadow-2xs">
              {{ dealsByStage[st.key]?.length || 0 }}
            </span>
          </div>
          <span class="text-xs font-mono font-bold text-slate-900">
            ${{ Number(stageTotals[st.key] || 0).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}
          </span>
        </div>

        <!-- Deals Column Cards -->
        <div class="space-y-3">
          <div
            v-for="deal in dealsByStage[st.key]"
            :key="deal.id"
            class="p-4 bg-white rounded-xl border border-slate-200 hover:border-slate-300 shadow-2xs hover:shadow-xs transition-all space-y-2.5 group"
          >
            <div class="flex items-start justify-between gap-2">
              <h4 class="font-bold text-slate-900 text-xs leading-snug group-hover:text-primary-700 transition-colors">
                {{ deal.title }}
              </h4>
              <div class="flex items-center gap-0.5">
                <button
                  type="button"
                  @click="openEditModal(deal)"
                  class="text-slate-400 hover:text-slate-700 p-0.5 cursor-pointer"
                  title="Edit Deal"
                >
                  <Edit class="w-3 h-3" />
                </button>
                <button
                  type="button"
                  @click="deleteMutation.mutate(deal.id)"
                  class="text-slate-400 hover:text-red-600 p-0.5 cursor-pointer"
                  title="Delete Deal"
                >
                  <Trash2 class="w-3 h-3" />
                </button>
              </div>
            </div>

            <p v-if="deal.customer" class="text-[11px] text-slate-500 font-medium truncate flex items-center gap-1">
              <Building2 class="w-3 h-3 text-slate-400" />
              {{ deal.customer.company || deal.customer.name }}
            </p>

            <div class="flex items-center justify-between pt-1 text-xs">
              <span class="font-mono font-black text-slate-900">
                ${{ Number(deal.amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
              </span>
              <span class="text-[10px] font-bold text-slate-400">{{ deal.probability }}% prob</span>
            </div>

            <!-- Quick Stage Transition Buttons -->
            <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-[11px]">
              <button
                v-if="getPrevStage(deal.stage)"
                type="button"
                @click="moveToStage(deal, getPrevStage(deal.stage)!)"
                class="text-slate-400 hover:text-slate-700 font-bold inline-flex items-center cursor-pointer"
                title="Move Back"
              >
                <ChevronLeft class="w-3 h-3" /> Back
              </button>
              <div v-else></div>

              <div class="flex items-center gap-1">
                <button
                  v-if="deal.stage !== 'lost' && deal.stage !== 'won'"
                  type="button"
                  @click="moveToStage(deal, 'lost')"
                  class="text-red-500 hover:text-red-700 text-[10px] font-bold px-1.5 py-0.5 rounded hover:bg-red-50 cursor-pointer"
                >
                  Lost
                </button>
                <button
                  v-if="getNextStage(deal.stage)"
                  type="button"
                  @click="moveToStage(deal, getNextStage(deal.stage)!)"
                  class="text-primary-600 hover:text-primary-700 font-bold inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded hover:bg-primary-50 cursor-pointer"
                >
                  Advance <ChevronRight class="w-3 h-3" />
                </button>
              </div>
            </div>
          </div>

          <div v-if="!dealsByStage[st.key]?.length" class="p-6 text-center text-slate-300 text-xs italic">
            No deals in {{ st.label }}
          </div>
        </div>
      </div>
    </div>

    <!-- 2. Table List View -->
    <div v-else class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
      <div v-if="filteredDeals.length" class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100 text-xs">
          <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
            <tr>
              <th class="px-4 py-3 text-left">Deal Title</th>
              <th class="px-4 py-3 text-left">Customer / Company</th>
              <th class="px-4 py-3 text-left">Stage</th>
              <th class="px-4 py-3 text-right">Deal Value</th>
              <th class="px-4 py-3 text-right">Probability</th>
              <th class="px-4 py-3 text-right">Expected Close</th>
              <th class="px-4 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-slate-700">
            <tr v-for="deal in filteredDeals" :key="deal.id" class="hover:bg-slate-50/70 transition-colors">
              <td class="px-4 py-3 font-bold text-slate-900">{{ deal.title }}</td>
              <td class="px-4 py-3 font-medium text-slate-600">
                {{ deal.customer?.company || deal.customer?.name || '—' }}
              </td>
              <td class="px-4 py-3">
                <UiBadge
                  :variant="deal.stage === 'won' ? 'success' : deal.stage === 'lost' ? 'danger' : 'info'"
                  class="capitalize text-[10px] font-bold"
                >
                  {{ deal.stage }}
                </UiBadge>
              </td>
              <td class="px-4 py-3 text-right font-mono font-bold text-slate-900">
                ${{ Number(deal.amount).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
              </td>
              <td class="px-4 py-3 text-right font-semibold">{{ deal.probability }}%</td>
              <td class="px-4 py-3 text-right text-slate-500">
                {{ deal.expected_close_date ? new Date(deal.expected_close_date).toLocaleDateString() : '—' }}
              </td>
              <td class="px-4 py-3 text-right space-x-1 whitespace-nowrap">
                <UiButton variant="ghost" size="sm" @click="openEditModal(deal)">
                  <Edit class="w-3.5 h-3.5" />
                </UiButton>
                <UiButton variant="ghost" size="sm" class="text-red-500 hover:text-red-700" @click="deleteMutation.mutate(deal.id)">
                  <Trash2 class="w-3.5 h-3.5" />
                </UiButton>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="p-12 text-center text-slate-400 text-xs">
        No deals found matching your search.
      </div>
    </div>

    <!-- Create / Edit Deal Modal -->
    <UiModal v-model="isModalOpen" :title="editingDeal ? 'Edit Deal' : 'Add Deal to Pipeline'" size="md">
      <div class="space-y-4">
        <UiInput v-model="dealForm.title" label="Deal Title" placeholder="e.g. Enterprise License Expansion" required />

        <UiSelect
          v-model="dealForm.customer_id"
          label="Associated Customer / Company"
          :options="[{ label: 'Select Customer (Optional)', value: '' }, ...(contacts?.map(c => ({ label: c.company ? `${c.name} (${c.company})` : c.name, value: c.id })) || [])]"
        />

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UiInput v-model="dealForm.amount" label="Deal Amount ($)" type="number" placeholder="5000" required />
          <UiSelect
            v-model="dealForm.stage"
            label="Pipeline Stage"
            :options="[
              { label: 'Qualification', value: 'qualification' },
              { label: 'Proposal Sent', value: 'proposal' },
              { label: 'Negotiation', value: 'negotiation' },
              { label: 'Closed Won', value: 'won' },
              { label: 'Closed Lost', value: 'lost' },
            ]"
          />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UiInput v-model="dealForm.probability" label="Win Probability (%)" type="number" min="0" max="100" />
          <UiInput v-model="dealForm.expected_close_date" label="Expected Close Date" type="date" />
        </div>

        <div class="space-y-1">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Deal Notes</label>
          <textarea
            v-model="dealForm.notes"
            rows="3"
            placeholder="Contract terms, stakeholder notes, timeline..."
            class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500"
          ></textarea>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
          <UiButton variant="outline" type="button" @click="isModalOpen = false">Cancel</UiButton>
          <UiButton :loading="saveMutation.isPending.value" @click="handleSave">
            {{ editingDeal ? 'Save Changes' : 'Create Deal' }}
          </UiButton>
        </div>
      </div>
    </UiModal>
  </div>
</template>
