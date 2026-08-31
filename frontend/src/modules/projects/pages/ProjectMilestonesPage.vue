<script setup lang="ts">
import { ref, computed, markRaw } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { projectsApi } from '@/api/projects'
import type { ProjectMilestone, MilestoneStatus } from '@/types/projects'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiStat from '@/components/ui/UiStat.vue'
import UiSpinner from '@/components/ui/UiSpinner.vue'
import { useToast } from '@/composables/useToast'
import {
  Flag,
  Plus,
  Search,
  CheckCircle2,
  Calendar,
  Building2,
  Trash2,
  Edit,
  FolderKanban,
  CheckSquare,
  AlertTriangle,
  Clock,
  Sparkles,
} from '@lucide/vue'

const queryClient = useQueryClient()
const toast = useToast()

const searchQuery = ref('')
const selectedProjectId = ref<string>('all')
const isModalOpen = ref(false)
const editingMilestone = ref<ProjectMilestone | null>(null)

const milestoneForm = ref({
  project_id: '',
  title: '',
  description: '',
  due_date: '',
  status: 'pending' as MilestoneStatus,
})

// Queries
const { data: milestones, isLoading } = useQuery({
  queryKey: ['projects', 'milestones'],
  queryFn: () => projectsApi.getMilestones().then((r) => r.data.data),
})

const { data: projects } = useQuery({
  queryKey: ['projects'],
  queryFn: () => projectsApi.getProjects().then((r) => r.data.data),
})

const filteredMilestones = computed(() => {
  let list = milestones.value || []
  if (selectedProjectId.value !== 'all') {
    list = list.filter((m) => m.project_id === selectedProjectId.value)
  }
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(
      (m) =>
        m.title.toLowerCase().includes(q) ||
        (m.description || '').toLowerCase().includes(q) ||
        (m.project?.name || '').toLowerCase().includes(q)
    )
  }
  return list
})

// Stats
const stats = computed(() => {
  const list = milestones.value || []
  const achieved = list.filter((m) => m.status === 'achieved').length
  const inProgress = list.filter((m) => m.status === 'in_progress').length
  const delayed = list.filter((m) => m.status === 'delayed').length

  return [
    {
      label: 'Total Milestones',
      value: list.length,
      icon: markRaw(Flag),
    },
    {
      label: 'In Progress Targets',
      value: inProgress,
      icon: markRaw(Clock),
    },
    {
      label: 'Achieved Deliverables',
      value: achieved,
      icon: markRaw(CheckCircle2),
    },
    {
      label: 'Delayed / Blocked',
      value: delayed,
      icon: markRaw(AlertTriangle),
    },
  ]
})

// Mutations
const saveMutation = useMutation({
  mutationFn: (payload: any) => {
    if (editingMilestone.value) {
      return projectsApi.updateMilestone(editingMilestone.value.id, payload)
    }
    return projectsApi.createMilestone(payload)
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['projects'] })
    isModalOpen.value = false
    toast.success(editingMilestone.value ? 'Milestone updated' : 'Milestone scheduled')
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || 'Failed to save milestone')
  },
})

const deleteMutation = useMutation({
  mutationFn: (id: string) => projectsApi.deleteMilestone(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['projects'] })
    toast.success('Milestone deleted')
  },
})

const openCreateModal = () => {
  editingMilestone.value = null
  milestoneForm.value = {
    project_id: projects.value?.[0]?.id || '',
    title: '',
    description: '',
    due_date: '',
    status: 'pending',
  }
  isModalOpen.value = true
}

const openEditModal = (m: ProjectMilestone) => {
  editingMilestone.value = m
  milestoneForm.value = {
    project_id: m.project_id,
    title: m.title,
    description: m.description || '',
    due_date: m.due_date ? String(m.due_date).slice(0, 10) : '',
    status: m.status,
  }
  isModalOpen.value = true
}

const handleSave = () => {
  if (!milestoneForm.value.title) {
    toast.error('Please enter a milestone title')
    return
  }
  if (!milestoneForm.value.project_id) {
    toast.error('Please select a project')
    return
  }
  saveMutation.mutate({
    ...milestoneForm.value,
    due_date: milestoneForm.value.due_date || null,
  })
}

const getStatusBadge = (status: string) => {
  switch (status) {
    case 'achieved': return { label: 'Achieved 🎉', variant: 'success' as const }
    case 'in_progress': return { label: 'In Progress', variant: 'info' as const }
    case 'delayed': return { label: 'Delayed', variant: 'danger' as const }
    default: return { label: 'Pending', variant: 'default' as const }
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Project Milestones & Deliverables</h1>
        <p class="text-xs sm:text-sm text-slate-500">Plan roadmap releases, client approval gates, and delivery commitments.</p>
      </div>
      <UiButton @click="openCreateModal">
        <Plus class="w-4 h-4 mr-2" /> Add Milestone
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

    <!-- Filters & Search -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
      <div class="flex items-center gap-2 overflow-x-auto pb-1 max-w-xl">
        <button
          type="button"
          @click="selectedProjectId = 'all'"
          class="px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all cursor-pointer"
          :class="selectedProjectId === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
        >
          All Projects ({{ milestones?.length || 0 }})
        </button>
        <button
          v-for="proj in projects"
          :key="proj.id"
          type="button"
          @click="selectedProjectId = proj.id"
          class="px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all cursor-pointer flex items-center gap-1"
          :class="selectedProjectId === proj.id ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
        >
          <span class="text-[10px] opacity-75 font-mono">{{ proj.code }}</span>
          <span>{{ proj.name }}</span>
        </button>
      </div>

      <UiInput
        v-model="searchQuery"
        placeholder="Filter milestones..."
        size="sm"
        class="w-full sm:w-64"
      >
        <template #prefix><Search class="w-3.5 h-3.5 text-slate-400" /></template>
      </UiInput>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="p-16 flex justify-center">
      <UiSpinner size="lg" />
    </div>

    <!-- Milestones List -->
    <div v-else-if="filteredMilestones.length" class="space-y-3">
      <div
        v-for="m in filteredMilestones"
        :key="m.id"
        class="bg-white rounded-2xl border border-slate-200 hover:border-slate-300 p-5 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition-all"
      >
        <div class="flex items-start gap-4">
          <div class="w-10 h-10 rounded-2xl bg-slate-100 text-slate-700 flex items-center justify-center shrink-0 mt-0.5">
            <Flag class="w-5 h-5 text-primary-600" />
          </div>

          <div class="space-y-1">
            <div class="flex items-center gap-2">
              <span class="text-[10px] font-mono font-bold text-primary-700 bg-primary-50 px-1.5 py-0.5 rounded border border-primary-100">
                {{ m.project?.code }}
              </span>
              <h3 class="font-bold text-slate-900 text-sm leading-snug">{{ m.title }}</h3>
              <UiBadge :variant="getStatusBadge(m.status).variant" class="text-[10px] font-bold">
                {{ getStatusBadge(m.status).label }}
              </UiBadge>
            </div>

            <p v-if="m.description" class="text-xs text-slate-600 leading-relaxed">
              {{ m.description }}
            </p>

            <div class="flex items-center gap-3 text-xs text-slate-400 pt-0.5">
              <span>{{ m.project?.name }}</span>
              <span v-if="m.due_date" class="flex items-center gap-1 font-medium text-slate-600">
                <Calendar class="w-3 h-3 text-slate-400" /> Target: {{ new Date(m.due_date).toLocaleDateString() }}
              </span>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-2 shrink-0 self-end sm:self-center">
          <UiButton variant="ghost" size="sm" @click="openEditModal(m)">
            <Edit class="w-3.5 h-3.5 text-slate-600" />
          </UiButton>
          <UiButton variant="ghost" size="sm" class="text-red-500 hover:text-red-700 hover:bg-red-50" @click="deleteMutation.mutate(m.id)">
            <Trash2 class="w-3.5 h-3.5" />
          </UiButton>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="bg-white rounded-2xl border border-slate-200 p-16 text-center space-y-4">
      <Flag class="w-12 h-12 mx-auto text-slate-300" />
      <div>
        <h3 class="font-bold text-slate-900 text-base">No milestones found</h3>
        <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">
          Set key project target deadlines and release milestones to keep your team aligned.
        </p>
      </div>
      <UiButton size="sm" @click="openCreateModal">
        <Plus class="w-4 h-4 mr-1.5" /> Schedule Milestone
      </UiButton>
    </div>

    <!-- Create / Edit Milestone Modal -->
    <UiModal v-model="isModalOpen" :title="editingMilestone ? 'Edit Milestone' : 'Schedule Project Milestone'" size="md">
      <div class="space-y-4">
        <UiSelect
          v-model="milestoneForm.project_id"
          label="Project"
          :options="projects?.map(p => ({ label: `${p.code} - ${p.name}`, value: p.id })) || []"
          required
        />

        <UiInput v-model="milestoneForm.title" label="Milestone Title" placeholder="e.g. Beta Release 1.0 Deployment" required />

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UiSelect
            v-model="milestoneForm.status"
            label="Status"
            :options="[
              { label: 'Pending', value: 'pending' },
              { label: 'In Progress', value: 'in_progress' },
              { label: 'Achieved', value: 'achieved' },
              { label: 'Delayed', value: 'delayed' },
            ]"
          />
          <UiInput v-model="milestoneForm.due_date" label="Target Due Date" type="date" required />
        </div>

        <div class="space-y-1">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Milestone Scope & Deliverables</label>
          <textarea
            v-model="milestoneForm.description"
            rows="3"
            placeholder="Key deliverables, verification criteria..."
            class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500"
          ></textarea>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
          <UiButton variant="outline" type="button" @click="isModalOpen = false">Cancel</UiButton>
          <UiButton :loading="saveMutation.isPending.value" @click="handleSave">
            {{ editingMilestone ? 'Save Changes' : 'Schedule Milestone' }}
          </UiButton>
        </div>
      </div>
    </UiModal>
  </div>
</template>
