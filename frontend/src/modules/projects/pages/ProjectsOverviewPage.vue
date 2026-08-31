<script setup lang="ts">
import { ref, computed, markRaw } from 'vue'
import { useRouter } from 'vue-router'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { projectsApi } from '@/api/projects'
import { crmApi } from '@/api/crm'
import type { Project, ProjectStatus, ProjectPriority } from '@/types/projects'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiStat from '@/components/ui/UiStat.vue'
import UiSpinner from '@/components/ui/UiSpinner.vue'
import { useToast } from '@/composables/useToast'
import {
  FolderKanban,
  Plus,
  Search,
  CheckCircle2,
  Clock,
  ArrowRight,
  TrendingUp,
  Building2,
  Trash2,
  Edit,
  DollarSign,
  Calendar,
  Layers,
  User,
  Eye,
  CheckSquare,
  AlertCircle,
  Flag,
} from '@lucide/vue'

const router = useRouter()
const queryClient = useQueryClient()
const toast = useToast()

const searchQuery = ref('')
const selectedStatus = ref<string>('all')
const isModalOpen = ref(false)
const isDetailModalOpen = ref(false)
const editingProject = ref<Project | null>(null)
const selectedProject = ref<Project | null>(null)

const projectForm = ref({
  name: '',
  code: '',
  description: '',
  manager_id: '',
  customer_id: '',
  status: 'in_progress' as ProjectStatus,
  priority: 'medium' as ProjectPriority,
  budget: '',
  currency: 'USD',
  start_date: '',
  due_date: '',
})

// Queries
const { data: statsData, isLoading: isLoadingStats } = useQuery({
  queryKey: ['projects', 'dashboard', 'stats'],
  queryFn: () => projectsApi.getStats().then((r) => r.data.data),
})

const { data: projects, isLoading: isLoadingProjects } = useQuery({
  queryKey: ['projects'],
  queryFn: () => projectsApi.getProjects().then((r) => r.data.data),
})

const { data: contacts } = useQuery({
  queryKey: ['crm', 'contacts'],
  queryFn: () => crmApi.getContacts().then((r) => r.data.data),
})

const stats = computed(() => {
  const s = statsData.value
  return [
    {
      label: 'Active Projects',
      value: s?.active_projects ?? 0,
      icon: markRaw(FolderKanban),
    },
    {
      label: 'Total Portfolio Budget',
      value: `$${Number(s?.total_budget || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`,
      icon: markRaw(DollarSign),
    },
    {
      label: 'Task Completion Rate',
      value: `${s?.task_completion_rate || 0}%`,
      icon: markRaw(CheckCircle2),
    },
    {
      label: 'Logged Work Hours',
      value: `${Number(s?.total_logged_hours || 0).toFixed(1)} hrs`,
      icon: markRaw(Clock),
    },
  ]
})

const filteredProjects = computed(() => {
  let list = projects.value || []
  if (selectedStatus.value !== 'all') {
    list = list.filter((p) => p.status === selectedStatus.value)
  }
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(
      (p) =>
        p.name.toLowerCase().includes(q) ||
        p.code.toLowerCase().includes(q) ||
        (p.description || '').toLowerCase().includes(q) ||
        (p.customer?.name || '').toLowerCase().includes(q)
    )
  }
  return list
})

// Mutations
const saveMutation = useMutation({
  mutationFn: (payload: any) => {
    if (editingProject.value) {
      return projectsApi.updateProject(editingProject.value.id, payload)
    }
    return projectsApi.createProject(payload)
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['projects'] })
    isModalOpen.value = false
    toast.success(editingProject.value ? 'Project updated' : 'New project initialized')
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || 'Failed to save project')
  },
})

const deleteMutation = useMutation({
  mutationFn: (id: string) => projectsApi.deleteProject(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['projects'] })
    isDetailModalOpen.value = false
    toast.success('Project deleted')
  },
})

const openCreateModal = () => {
  editingProject.value = null
  projectForm.value = {
    name: '',
    code: '',
    description: '',
    manager_id: '',
    customer_id: '',
    status: 'in_progress',
    priority: 'medium',
    budget: '',
    currency: 'USD',
    start_date: new Date().toISOString().slice(0, 10),
    due_date: '',
  }
  isModalOpen.value = true
}

const openEditModal = (proj: Project) => {
  editingProject.value = proj
  projectForm.value = {
    name: proj.name,
    code: proj.code || '',
    description: proj.description || '',
    manager_id: proj.manager_id || '',
    customer_id: proj.customer_id || '',
    status: proj.status,
    priority: proj.priority || 'medium',
    budget: proj.budget ? String(proj.budget) : '',
    currency: proj.currency || 'USD',
    start_date: proj.start_date ? String(proj.start_date).slice(0, 10) : '',
    due_date: proj.due_date ? String(proj.due_date).slice(0, 10) : '',
  }
  isModalOpen.value = true
}

const viewProjectDetail = async (proj: Project) => {
  try {
    const res = await projectsApi.getProject(proj.id)
    selectedProject.value = res.data.data
    isDetailModalOpen.value = true
  } catch (e) {
    selectedProject.value = proj
    isDetailModalOpen.value = true
  }
}

const handleSave = () => {
  if (!projectForm.value.name) {
    toast.error('Please enter a project name')
    return
  }
  saveMutation.mutate({
    ...projectForm.value,
    manager_id: projectForm.value.manager_id || null,
    customer_id: projectForm.value.customer_id || null,
    code: projectForm.value.code || null,
    budget: projectForm.value.budget ? Number(projectForm.value.budget) : 0,
    start_date: projectForm.value.start_date || null,
    due_date: projectForm.value.due_date || null,
  })
}

const getStatusBadge = (status: string) => {
  switch (status) {
    case 'in_progress': return { label: 'In Progress', variant: 'info' as const }
    case 'planned': return { label: 'Planned', variant: 'purple' as const }
    case 'on_hold': return { label: 'On Hold', variant: 'warning' as const }
    case 'completed': return { label: 'Completed 🎉', variant: 'success' as const }
    case 'cancelled': return { label: 'Cancelled', variant: 'danger' as const }
    default: return { label: status, variant: 'default' as const }
  }
}

const getPriorityBadge = (priority: string) => {
  switch (priority) {
    case 'urgent': return { label: 'Urgent', variant: 'danger' as const }
    case 'high': return { label: 'High', variant: 'warning' as const }
    case 'medium': return { label: 'Medium', variant: 'default' as const }
    default: return { label: 'Low', variant: 'default' as const }
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Projects Portfolio</h1>
        <p class="text-xs sm:text-sm text-slate-500">Plan deliverables, track resource allocation, manage milestones, and monitor project budgets.</p>
      </div>
      <div class="flex items-center gap-2">
        <UiButton variant="outline" size="sm" @click="router.push('/projects/tasks')">
          <CheckSquare class="w-3.5 h-3.5 mr-1" /> Kanban Board
        </UiButton>
        <UiButton size="sm" @click="openCreateModal">
          <Plus class="w-3.5 h-3.5 mr-1" /> New Project
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

    <!-- Filters & Search -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
      <div class="flex flex-wrap items-center gap-1.5 overflow-x-auto pb-1">
        <button
          v-for="st in ['all', 'in_progress', 'planned', 'on_hold', 'completed', 'cancelled']"
          :key="st"
          type="button"
          @click="selectedStatus = st"
          class="px-3 py-1.5 rounded-xl text-xs font-bold capitalize transition-all cursor-pointer"
          :class="selectedStatus === st ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
        >
          {{ st === 'all' ? `All (${projects?.length || 0})` : st.replace('_', ' ') }}
        </button>
      </div>

      <UiInput
        v-model="searchQuery"
        placeholder="Search code, name, client..."
        size="sm"
        class="w-full sm:w-64"
      >
        <template #prefix><Search class="w-3.5 h-3.5 text-slate-400" /></template>
      </UiInput>
    </div>

    <!-- Loading State -->
    <div v-if="isLoadingProjects" class="p-16 flex justify-center">
      <UiSpinner size="lg" />
    </div>

    <!-- Projects Grid -->
    <div v-else-if="filteredProjects.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <div
        v-for="proj in filteredProjects"
        :key="proj.id"
        class="bg-white rounded-2xl border border-slate-200 hover:border-slate-300 p-5 shadow-xs flex flex-col justify-between transition-all space-y-4 cursor-pointer group"
        @click="viewProjectDetail(proj)"
      >
        <div class="space-y-3">
          <!-- Top Row -->
          <div class="flex items-start justify-between gap-2">
            <div class="space-y-1">
              <div class="flex items-center gap-2">
                <span class="text-xs font-mono font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md">
                  {{ proj.code }}
                </span>
                <UiBadge :variant="getStatusBadge(proj.status).variant" class="text-[10px] font-bold">
                  {{ getStatusBadge(proj.status).label }}
                </UiBadge>
              </div>

              <h3 class="font-bold text-slate-900 text-base leading-snug group-hover:text-primary-700 transition-colors">
                {{ proj.name }}
              </h3>
            </div>

            <UiBadge :variant="getPriorityBadge(proj.priority).variant" class="text-[9px] font-bold">
              {{ getPriorityBadge(proj.priority).label }}
            </UiBadge>
          </div>

          <p v-if="proj.description" class="text-xs text-slate-600 line-clamp-2 leading-relaxed">
            {{ proj.description }}
          </p>

          <!-- Client & Manager -->
          <div class="flex items-center justify-between text-xs text-slate-500 pt-1">
            <span v-if="proj.customer" class="flex items-center gap-1 font-semibold text-slate-700 truncate">
              <Building2 class="w-3.5 h-3.5 text-slate-400" />
              {{ proj.customer.company || proj.customer.name }}
            </span>
            <span v-else class="text-slate-400 italic">Internal Project</span>

            <span class="font-mono font-bold text-slate-900">
              ${{ Number(proj.budget).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}
            </span>
          </div>

          <!-- Progress Bar -->
          <div class="space-y-1.5 pt-1">
            <div class="flex items-center justify-between text-[11px] font-semibold text-slate-600">
              <span>Task Progress</span>
              <span class="font-bold font-mono">{{ proj.progress_percent }}%</span>
            </div>
            <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
              <div
                class="bg-linear-to-r from-primary-500 to-indigo-600 h-full rounded-full transition-all duration-500"
                :style="{ width: `${proj.progress_percent}%` }"
              ></div>
            </div>
          </div>
        </div>

        <!-- Footer Meta -->
        <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs" @click.stop>
          <div class="flex items-center gap-3 text-slate-400">
            <span class="flex items-center gap-1">
              <CheckSquare class="w-3.5 h-3.5" /> {{ proj.tasks_count ?? 0 }} tasks
            </span>
            <span v-if="proj.due_date" class="flex items-center gap-1">
              <Calendar class="w-3.5 h-3.5" /> {{ new Date(proj.due_date).toLocaleDateString() }}
            </span>
          </div>

          <div class="flex items-center gap-1">
            <UiButton variant="ghost" size="sm" @click="openEditModal(proj)">
              <Edit class="w-3.5 h-3.5 text-slate-600" />
            </UiButton>
            <UiButton variant="ghost" size="sm" class="text-red-500 hover:text-red-700 hover:bg-red-50" @click="deleteMutation.mutate(proj.id)">
              <Trash2 class="w-3.5 h-3.5" />
            </UiButton>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="bg-white rounded-2xl border border-slate-200 p-16 text-center space-y-4">
      <FolderKanban class="w-12 h-12 mx-auto text-slate-300" />
      <div>
        <h3 class="font-bold text-slate-900 text-base">No projects found</h3>
        <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">
          Create client engagements or internal operational projects to start organizing tasks and tracking hours.
        </p>
      </div>
      <UiButton size="sm" @click="openCreateModal">
        <Plus class="w-4 h-4 mr-1.5" /> Create First Project
      </UiButton>
    </div>

    <!-- Create / Edit Project Modal -->
    <UiModal v-model="isModalOpen" :title="editingProject ? 'Edit Project' : 'Initialize New Project'" size="lg">
      <div class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div class="sm:col-span-2">
            <UiInput v-model="projectForm.name" label="Project Name" placeholder="e.g. ERP Cloud Migration & Mobile App" required />
          </div>
          <UiInput v-model="projectForm.code" label="Project Code" placeholder="Auto (e.g. PRJ-001)" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UiSelect
            v-model="projectForm.customer_id"
            label="Client / Customer Account (Optional)"
            :options="[{ label: 'Internal Initiative (None)', value: '' }, ...(contacts?.map(c => ({ label: c.company ? `${c.name} (${c.company})` : c.name, value: c.id })) || [])]"
          />
          <UiInput v-model="projectForm.budget" label="Project Budget ($)" type="number" placeholder="25000" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <UiSelect
            v-model="projectForm.status"
            label="Project Status"
            :options="[
              { label: 'Planned / Scoping', value: 'planned' },
              { label: 'In Progress / Active', value: 'in_progress' },
              { label: 'On Hold', value: 'on_hold' },
              { label: 'Completed', value: 'completed' },
              { label: 'Cancelled', value: 'cancelled' },
            ]"
          />
          <UiSelect
            v-model="projectForm.priority"
            label="Priority Level"
            :options="[
              { label: 'Low', value: 'low' },
              { label: 'Medium', value: 'medium' },
              { label: 'High', value: 'high' },
              { label: 'Urgent', value: 'urgent' },
            ]"
          />
          <UiInput v-model="projectForm.due_date" label="Target Deadline" type="date" />
        </div>

        <div class="space-y-1">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Project Scope & Description</label>
          <textarea
            v-model="projectForm.description"
            rows="3"
            placeholder="Objectives, key deliverable milestones, architecture notes..."
            class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500"
          ></textarea>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
          <UiButton variant="outline" type="button" @click="isModalOpen = false">Cancel</UiButton>
          <UiButton :loading="saveMutation.isPending.value" @click="handleSave">
            {{ editingProject ? 'Save Project Changes' : 'Initialize Project' }}
          </UiButton>
        </div>
      </div>
    </UiModal>

    <!-- Project Detail Dossier Modal -->
    <UiModal v-model="isDetailModalOpen" title="Project Overview & Tasks Dossier" size="xl">
      <div v-if="selectedProject" class="space-y-6">
        <!-- Header banner -->
        <div class="p-5 bg-slate-50 rounded-2xl border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div class="space-y-1">
            <div class="flex items-center gap-2">
              <span class="text-xs font-mono font-bold bg-white px-2 py-0.5 rounded border border-slate-200 text-slate-700">
                {{ selectedProject.code }}
              </span>
              <UiBadge :variant="getStatusBadge(selectedProject.status).variant" class="font-bold">
                {{ getStatusBadge(selectedProject.status).label }}
              </UiBadge>
            </div>
            <h2 class="text-lg font-black text-slate-900">{{ selectedProject.name }}</h2>
            <p v-if="selectedProject.description" class="text-xs text-slate-600 leading-relaxed">{{ selectedProject.description }}</p>
          </div>

          <div class="text-right shrink-0">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Total Budget</span>
            <span class="text-lg font-mono font-black text-slate-900">
              ${{ Number(selectedProject.budget).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
            </span>
          </div>
        </div>

        <!-- Task List in Project -->
        <div class="space-y-3">
          <div class="flex items-center justify-between border-b border-slate-100 pb-2">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">
              Project Tasks ({{ selectedProject.tasks?.length || 0 }})
            </h3>
            <UiButton size="sm" variant="outline" @click="router.push('/projects/tasks')">
              Open Full Kanban Board →
            </UiButton>
          </div>

          <div v-if="selectedProject.tasks?.length" class="space-y-2 max-h-[300px] overflow-y-auto pr-1">
            <div
              v-for="t in selectedProject.tasks"
              :key="t.id"
              class="p-3 bg-white rounded-xl border border-slate-200 flex items-center justify-between text-xs"
            >
              <div class="space-y-0.5">
                <h4 class="font-bold text-slate-900">{{ t.title }}</h4>
                <p class="text-[11px] text-slate-400">
                  {{ t.assignee?.name || 'Unassigned' }} • {{ t.logged_hours }} / {{ t.estimated_hours }} hrs
                </p>
              </div>
              <UiBadge :variant="t.status === 'done' ? 'success' : 'info'" class="capitalize font-bold text-[10px]">
                {{ t.status.replace('_', ' ') }}
              </UiBadge>
            </div>
          </div>
          <p v-else class="text-xs text-slate-400 italic">No tasks created under this project yet.</p>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
          <UiButton size="sm" @click="isDetailModalOpen = false">Close</UiButton>
        </div>
      </div>
    </UiModal>
  </div>
</template>
