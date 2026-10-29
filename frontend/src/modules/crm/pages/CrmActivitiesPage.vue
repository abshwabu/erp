<script setup lang="ts">
import { ref, computed, markRaw } from 'vue'
import { useI18n } from 'vue-i18n'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { crmApi } from '@/api/crm'
import type { Activity, ActivityType, ActivityStatus } from '@/types/crm'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiStat from '@/components/ui/UiStat.vue'
import UiSpinner from '@/components/ui/UiSpinner.vue'
import { useToast } from '@/composables/useToast'
import {
  PhoneCall,
  Calendar,
  Mail,
  CheckSquare,
  Clock,
  Plus,
  Search,
  CheckCircle2,
  Trash2,
  Edit,
  Building2,
  DollarSign,
  Layers,
  MessageSquare,
} from '@lucide/vue'

const { t } = useI18n()
const queryClient = useQueryClient()
const toast = useToast()

const searchQuery = ref('')
const selectedType = ref<string>('all')
const selectedStatus = ref<string>('all')
const isModalOpen = ref(false)
const editingActivity = ref<Activity | null>(null)

const form = ref({
  type: 'call' as ActivityType,
  title: '',
  description: '',
  due_date: '',
  priority: 'medium' as 'low' | 'medium' | 'high',
  customer_id: '',
  deal_id: '',
  lead_id: '',
})

// Queries
const { data: activities, isLoading } = useQuery({
  queryKey: ['crm', 'activities'],
  queryFn: () => crmApi.getActivities().then((r) => r.data.data),
})

const { data: contacts } = useQuery({
  queryKey: ['crm', 'contacts'],
  queryFn: () => crmApi.getContacts().then((r) => r.data.data),
})

const { data: deals } = useQuery({
  queryKey: ['crm', 'deals'],
  queryFn: () => crmApi.getDeals().then((r) => r.data.data),
})

const filteredActivities = computed(() => {
  let list = activities.value || []
  if (selectedType.value !== 'all') {
    list = list.filter((a) => a.type === selectedType.value)
  }
  if (selectedStatus.value !== 'all') {
    list = list.filter((a) => a.status === selectedStatus.value)
  }
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(
      (a) =>
        a.title.toLowerCase().includes(q) ||
        (a.description || '').toLowerCase().includes(q) ||
        (a.customer?.name || '').toLowerCase().includes(q)
    )
  }
  return list
})

// Stats
const stats = computed(() => {
  const list = activities.value || []
  const pending = list.filter((a) => a.status === 'pending').length
  const completed = list.filter((a) => a.status === 'completed').length
  const calls = list.filter((a) => a.type === 'call').length

  return [
    {
      label: t('crm.activities.kpis.pendingTasks'),
      value: pending,
      icon: markRaw(Clock),
    },
    {
      label: t('crm.activities.kpis.completedActivities'),
      value: completed,
      icon: markRaw(CheckCircle2),
    },
    {
      label: t('crm.activities.kpis.loggedCalls'),
      value: calls,
      icon: markRaw(PhoneCall),
    },
    {
      label: t('crm.activities.kpis.totalHistory'),
      value: list.length,
      icon: markRaw(Layers),
    },
  ]
})

// Mutations
const saveMutation = useMutation({
  mutationFn: (payload: any) => {
    if (editingActivity.value) {
      return crmApi.updateActivity(editingActivity.value.id, payload)
    }
    return crmApi.createActivity(payload)
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['crm'] })
    isModalOpen.value = false
    toast.success(editingActivity.value ? t('crm.activities.messages.updated') : t('crm.activities.messages.scheduled'))
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || t('crm.activities.messages.failedSave'))
  },
})

const toggleMutation = useMutation({
  mutationFn: (id: string) => crmApi.toggleActivityComplete(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['crm'] })
    toast.success(t('crm.activities.messages.statusUpdated'))
  },
})

const deleteMutation = useMutation({
  mutationFn: (id: string) => crmApi.deleteActivity(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['crm'] })
    toast.success(t('crm.activities.messages.removed'))
  },
})

const openCreateModal = () => {
  editingActivity.value = null
  form.value = {
    type: 'call',
    title: '',
    description: '',
    due_date: new Date().toISOString().slice(0, 16),
    priority: 'medium',
    customer_id: '',
    deal_id: '',
    lead_id: '',
  }
  isModalOpen.value = true
}

const openEditModal = (act: Activity) => {
  editingActivity.value = act
  form.value = {
    type: act.type,
    title: act.title,
    description: act.description || '',
    due_date: act.due_date ? new Date(act.due_date).toISOString().slice(0, 16) : '',
    priority: act.priority || 'medium',
    customer_id: act.customer_id || '',
    deal_id: act.deal_id || '',
    lead_id: act.lead_id || '',
  }
  isModalOpen.value = true
}

const handleSave = () => {
  if (!form.value.title) {
    toast.error(t('crm.activities.messages.titleRequired'))
    return
  }
  saveMutation.mutate({
    ...form.value,
    customer_id: form.value.customer_id || null,
    deal_id: form.value.deal_id || null,
    lead_id: form.value.lead_id || null,
    due_date: form.value.due_date ? new Date(form.value.due_date).toISOString() : null,
  })
}

const getActivityIcon = (type: ActivityType) => {
  switch (type) {
    case 'call': return PhoneCall
    case 'meeting': return Calendar
    case 'email': return Mail
    case 'task': return CheckSquare
    case 'follow_up': return Clock
    default: return MessageSquare
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">{{ t('crm.activities.title') }}</h1>
        <p class="text-xs sm:text-sm text-slate-500">{{ t('crm.activities.subtitle') }}</p>
      </div>
      <UiButton @click="openCreateModal">
        <Plus class="w-4 h-4 mr-2" /> {{ t('crm.activities.scheduleActivity') }}
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
            v-for="st in ['all', 'pending', 'completed']"
            :key="st"
            type="button"
            @click="selectedStatus = st"
            class="px-3.5 py-1.5 rounded-xl text-xs font-bold capitalize transition-all cursor-pointer"
            :class="selectedStatus === st ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
          >
            {{ t(`crm.activities.filters.${st}`, st) }}
          </button>
        </div>

        <UiInput
          v-model="searchQuery"
          :placeholder="t('crm.activities.filters.searchPlaceholder')"
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

      <!-- Activities List -->
      <div v-else-if="filteredActivities.length" class="divide-y divide-slate-100">
        <div
          v-for="act in filteredActivities"
          :key="act.id"
          class="p-4 hover:bg-slate-50/70 transition-colors flex items-start justify-between gap-4"
        >
          <div class="flex items-start gap-3.5">
            <!-- Complete Checkbox -->
            <button
              type="button"
              @click="toggleMutation.mutate(act.id)"
              class="w-5 h-5 rounded-lg border flex items-center justify-center mt-0.5 transition-colors cursor-pointer"
              :class="act.status === 'completed' ? 'bg-emerald-600 border-emerald-600 text-white' : 'border-slate-300 hover:border-primary-500 bg-white'"
            >
              <CheckCircle2 v-if="act.status === 'completed'" class="w-3.5 h-3.5" />
            </button>

            <!-- Icon -->
            <div class="w-8 h-8 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center shrink-0">
              <component :is="getActivityIcon(act.type)" class="w-4 h-4" />
            </div>

            <!-- Content -->
            <div class="space-y-1">
              <div class="flex items-center gap-2">
                <h4
                  class="font-bold text-sm text-slate-900 leading-snug"
                  :class="act.status === 'completed' ? 'line-through text-slate-400' : ''"
                >
                  {{ act.title }}
                </h4>
                <UiBadge variant="default" class="text-[9px] uppercase font-bold">
                  {{ t(`crm.activities.types.${act.type}`, act.type.replace('_', ' ')) }}
                </UiBadge>
                <UiBadge
                  v-if="act.priority === 'high'"
                  variant="warning"
                  class="text-[9px] font-bold"
                >
                  {{ t('crm.activities.highPriorityBadge') }}
                </UiBadge>
              </div>

              <p v-if="act.description" class="text-xs text-slate-600 leading-relaxed">
                {{ act.description }}
              </p>

              <div class="flex flex-wrap items-center gap-3 text-xs text-slate-400 pt-0.5">
                <span v-if="act.due_date" class="flex items-center gap-1 font-medium text-slate-500">
                  <Calendar class="w-3 h-3 text-slate-400" />
                  {{ t('crm.activities.due') }}: {{ new Date(act.due_date).toLocaleString() }}
                </span>
                <span v-if="act.customer" class="font-semibold text-slate-700">
                  • {{ act.customer.company || act.customer.name }}
                </span>
                <span v-if="act.deal" class="text-primary-600 font-semibold">
                  • {{ t('crm.activities.dealPrefix') }}: {{ act.deal.title }}
                </span>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center gap-1 shrink-0">
            <UiButton variant="ghost" size="sm" @click="openEditModal(act)" :title="t('crm.activities.modal.editTitle')">
              <Edit class="w-3.5 h-3.5 text-slate-600" />
            </UiButton>
            <UiButton
              variant="ghost"
              size="sm"
              class="text-red-500 hover:text-red-700 hover:bg-red-50"
              @click="deleteMutation.mutate(act.id)"
              :title="t('crm.common.delete')"
            >
              <Trash2 class="w-3.5 h-3.5" />
            </UiButton>
          </div>
        </div>
      </div>

      <div v-else class="p-12 text-center text-slate-400 text-xs">
        {{ t('crm.activities.emptyText') }}
      </div>
    </div>

    <!-- Create / Edit Activity Modal -->
    <UiModal v-model="isModalOpen" :title="editingActivity ? t('crm.activities.modal.editTitle') : t('crm.activities.modal.createTitle')" size="md">
      <div class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UiSelect
            v-model="form.type"
            :label="t('crm.activities.modal.activityType')"
            :options="[
              { label: t('crm.activities.types.call'), value: 'call' },
              { label: t('crm.activities.types.meeting'), value: 'meeting' },
              { label: t('crm.activities.types.email'), value: 'email' },
              { label: t('crm.activities.types.task'), value: 'task' },
              { label: t('crm.activities.types.follow_up'), value: 'follow_up' },
              { label: t('crm.activities.types.note'), value: 'note' },
            ]"
          />
          <UiSelect
            v-model="form.priority"
            :label="t('crm.activities.modal.priority')"
            :options="[
              { label: t('crm.activities.priorities.low'), value: 'low' },
              { label: t('crm.activities.priorities.medium'), value: 'medium' },
              { label: t('crm.activities.priorities.high'), value: 'high' },
            ]"
          />
        </div>

        <UiInput v-model="form.title" :label="t('crm.activities.modal.activityTitle')" :placeholder="t('crm.activities.modal.titlePlaceholder')" required />

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UiSelect
            v-model="form.customer_id"
            :label="t('crm.activities.modal.relatedCustomer')"
            :options="[{ label: t('crm.activities.modal.none'), value: '' }, ...(contacts?.map(c => ({ label: c.company ? `${c.name} (${c.company})` : c.name, value: c.id })) || [])]"
          />
          <UiSelect
            v-model="form.deal_id"
            :label="t('crm.activities.modal.linkedDeal')"
            :options="[{ label: t('crm.activities.modal.none'), value: '' }, ...(deals?.map(d => ({ label: d.title, value: d.id })) || [])]"
          />
        </div>

        <UiInput v-model="form.due_date" :label="t('crm.activities.modal.dueDateTime')" type="datetime-local" />

        <div class="space-y-1">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">{{ t('crm.activities.modal.descriptionAgenda') }}</label>
          <textarea
            v-model="form.description"
            rows="3"
            :placeholder="t('crm.activities.modal.descriptionPlaceholder')"
            class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500"
          ></textarea>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
          <UiButton variant="outline" type="button" @click="isModalOpen = false">{{ t('crm.common.cancel') }}</UiButton>
          <UiButton :loading="saveMutation.isPending.value" @click="handleSave">
            {{ editingActivity ? t('crm.activities.modal.saveBtn') : t('crm.activities.modal.scheduleBtn') }}
          </UiButton>
        </div>
      </div>
    </UiModal>
  </div>
</template>
