<script setup lang="ts">
import { ref, computed, markRaw } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { projectsApi } from '@/api/projects'
import type { ProjectTask, TaskStatus, ProjectPriority } from '@/types/projects'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiStat from '@/components/ui/UiStat.vue'
import UiSpinner from '@/components/ui/UiSpinner.vue'
import { useToast } from '@/composables/useToast'
import {
  CheckSquare,
  LayoutGrid,
  List,
  Plus,
  Search,
  CheckCircle2,
  Clock,
  ChevronRight,
  ChevronLeft,
  Calendar,
  Building2,
  Trash2,
  Edit,
  Sparkles,
  Layers,
  FolderKanban,
  User,
  Timer,
} from '@lucide/vue'

const queryClient = useQueryClient()
const toast = useToast()

const viewMode = ref<'kanban' | 'list'>('kanban')
const searchQuery = ref('')
const selectedProjectId = ref<string>('all')
const isModalOpen = ref(false)
const isTimeLogModalOpen = ref(false)
const editingTask = ref<ProjectTask | null>(null)
const timeLogTask = ref<ProjectTask | null>(null)

const taskForm = ref({
  project_id: '',
  title: '',
  description: '',
  status: 'todo' as TaskStatus,
  priority: 'medium' as ProjectPriority,
  due_date: '',
  estimated_hours: 4,
})

const timeLogForm = ref({
  hours: 1,
  log_date: new Date().toISOString().slice(0, 10),
  description: '',
  is_billable: true,
})

// Queries
const { data: tasks, isLoading } = useQuery({
  queryKey: ['projects', 'tasks'],
  queryFn: () => projectsApi.getTasks().then((r) => r.data.data),
})

const { data: projects } = useQuery({
  queryKey: ['projects'],
  queryFn: () => projectsApi.getProjects().then((r) => r.data.data),
})

const columns: Array<{ key: TaskStatus; label: string; color: string; border: string }> = [
  { key: 'todo', label: 'To Do', color: 'bg-slate-50 text-slate-800', border: 'border-slate-200' },
  { key: 'in_progress', label: 'In Progress', color: 'bg-blue-50 text-blue-800', border: 'border-blue-200' },
  { key: 'review', label: 'Under Review', color: 'bg-amber-50 text-amber-800', border: 'border-amber-200' },
  { key: 'done', label: 'Done 🎉', color: 'bg-emerald-50 text-emerald-800', border: 'border-emerald-200' },
]

const filteredTasks = computed(() => {
  let list = tasks.value || []
  if (selectedProjectId.value !== 'all') {
    list = list.filter((t) => t.project_id === selectedProjectId.value)
  }
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(
      (t) =>
        t.title.toLowerCase().includes(q) ||
        (t.description || '').toLowerCase().includes(q) ||
        (t.project?.name || '').toLowerCase().includes(q)
    )
  }
  return list
})

const tasksByStatus = computed(() => {
  const map: Record<TaskStatus, ProjectTask[]> = {
    todo: [],
    in_progress: [],
    review: [],
    done: [],
  }
  for (const task of filteredTasks.value) {
    if (map[task.status]) {
      map[task.status].push(task)
    }
  }
  return map
})

// Stats
const stats = computed(() => {
  const list = tasks.value || []
  const todo = list.filter((t) => t.status === 'todo').length
  const inProgress = list.filter((t) => t.status === 'in_progress').length
  const done = list.filter((t) => t.status === 'done').length
  const totalLogged = list.reduce((sum, t) => sum + Number(t.logged_hours || 0), 0)

  return [
    {
      label: 'Backlog / To Do',
      value: todo,
      icon: markRaw(CheckSquare),
    },
    {
      label: 'In Progress Tasks',
      value: inProgress,
      icon: markRaw(Timer),
    },
    {
      label: 'Completed Tasks',
      value: done,
      icon: markRaw(CheckCircle2),
    },
    {
      label: 'Total Hours Logged',
      value: `${totalLogged.toFixed(1)} hrs`,
      icon: markRaw(Clock),
    },
  ]
})

// Mutations
const saveMutation = useMutation({
  mutationFn: (payload: any) => {
    if (editingTask.value) {
      return projectsApi.updateTask(editingTask.value.id, payload)
    }
    return projectsApi.createTask(payload)
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['projects'] })
    isModalOpen.value = false
    toast.success(editingTask.value ? 'Task updated' : 'New task added to project')
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || 'Failed to save task')
  },
})

const statusMutation = useMutation({
  mutationFn: ({ id, status }: { id: string; status: TaskStatus }) =>
    projectsApi.updateTaskStatus(id, status),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['projects'] })
    toast.success('Task status updated')
  },
})

const deleteMutation = useMutation({
  mutationFn: (id: string) => projectsApi.deleteTask(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['projects'] })
    toast.success('Task deleted')
  },
})

const logTimeMutation = useMutation({
  mutationFn: (payload: any) => projectsApi.createTimeLog(payload),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['projects'] })
    isTimeLogModalOpen.value = false
    toast.success('Work hours logged to task! ⏱️')
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || 'Failed to log hours')
  },
})

const openCreateModal = () => {
  editingTask.value = null
  taskForm.value = {
    project_id: projects.value?.[0]?.id || '',
    title: '',
    description: '',
    status: 'todo',
    priority: 'medium',
    due_date: '',
    estimated_hours: 4,
  }
  isModalOpen.value = true
}

const openEditModal = (task: ProjectTask) => {
  editingTask.value = task
  taskForm.value = {
    project_id: task.project_id,
    title: task.title,
    description: task.description || '',
    status: task.status,
    priority: task.priority || 'medium',
    due_date: task.due_date ? String(task.due_date).slice(0, 10) : '',
    estimated_hours: Number(task.estimated_hours || 0),
  }
  isModalOpen.value = true
}

const openTimeLogModal = (task: ProjectTask) => {
  timeLogTask.value = task
  timeLogForm.value = {
    hours: 1,
    log_date: new Date().toISOString().slice(0, 10),
    description: `Work completed on: ${task.title}`,
    is_billable: true,
  }
  isTimeLogModalOpen.value = true
}

const handleSave = () => {
  if (!taskForm.value.title) {
    toast.error('Please enter a task title')
    return
  }
  if (!taskForm.value.project_id) {
    toast.error('Please select a project for this task')
    return
  }
  saveMutation.mutate({
    ...taskForm.value,
    estimated_hours: Number(taskForm.value.estimated_hours || 0),
    due_date: taskForm.value.due_date || null,
  })
}

const handleLogTime = () => {
  if (!timeLogTask.value) return
  logTimeMutation.mutate({
    project_id: timeLogTask.value.project_id,
    task_id: timeLogTask.value.id,
    hours: Number(timeLogForm.value.hours),
    log_date: timeLogForm.value.log_date,
    description: timeLogForm.value.description,
    is_billable: timeLogForm.value.is_billable,
  })
}

const moveToStatus = (task: ProjectTask, newStatus: TaskStatus) => {
  statusMutation.mutate({ id: task.id, status: newStatus })
}

const getNextStatus = (curr: TaskStatus): TaskStatus | null => {
  const order: TaskStatus[] = ['todo', 'in_progress', 'review', 'done']
  const idx = order.indexOf(curr)
  return idx >= 0 && idx < order.length - 1 ? order[idx + 1] : null
}

const getPrevStatus = (curr: TaskStatus): TaskStatus | null => {
  const order: TaskStatus[] = ['todo', 'in_progress', 'review', 'done']
  const idx = order.indexOf(curr)
  return idx > 0 ? order[idx - 1] : null
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
        <h1 class="text-2xl font-bold text-slate-900">Task Board & Kanban</h1>
        <p class="text-xs sm:text-sm text-slate-500">Track execution flow across project backlogs, active sprints, reviews, and completed items.</p>
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
          <Plus class="w-4 h-4 mr-1.5" /> Create Task
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

    <!-- Project Filter & Search -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-3.5 rounded-2xl border border-slate-200 shadow-xs">
      <div class="flex items-center gap-2 overflow-x-auto pb-1 max-w-xl">
        <button
          type="button"
          @click="selectedProjectId = 'all'"
          class="px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all cursor-pointer"
          :class="selectedProjectId === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
        >
          All Projects ({{ tasks?.length || 0 }})
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
        placeholder="Filter task title, description..."
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

    <!-- 1. Kanban Board View -->
    <div v-else-if="viewMode === 'kanban'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-start">
      <div
        v-for="col in columns"
        :key="col.key"
        class="bg-slate-50/70 border rounded-2xl p-3.5 space-y-3 min-h-[500px]"
        :class="col.border"
      >
        <!-- Column Header -->
        <div class="flex items-center justify-between pb-2 border-b border-slate-200">
          <div class="flex items-center gap-2">
            <h3 class="text-xs font-black uppercase tracking-wider text-slate-800">{{ col.label }}</h3>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white text-slate-700 shadow-2xs">
              {{ tasksByStatus[col.key]?.length || 0 }}
            </span>
          </div>
        </div>

        <!-- Task Cards -->
        <div class="space-y-3">
          <div
            v-for="task in tasksByStatus[col.key]"
            :key="task.id"
            class="p-4 bg-white rounded-xl border border-slate-200 hover:border-slate-300 shadow-2xs hover:shadow-xs transition-all space-y-2.5 group"
          >
            <div class="flex items-start justify-between gap-2">
              <div class="space-y-1">
                <span v-if="task.project" class="text-[10px] font-mono font-bold text-primary-700 bg-primary-50 px-1.5 py-0.5 rounded border border-primary-100 inline-block">
                  {{ task.project.code }}
                </span>
                <h4 class="font-bold text-slate-900 text-xs leading-snug group-hover:text-primary-700 transition-colors">
                  {{ task.title }}
                </h4>
              </div>

              <div class="flex items-center gap-0.5">
                <button
                  type="button"
                  @click="openEditModal(task)"
                  class="text-slate-400 hover:text-slate-700 p-0.5 cursor-pointer"
                  title="Edit Task"
                >
                  <Edit class="w-3 h-3" />
                </button>
                <button
                  type="button"
                  @click="deleteMutation.mutate(task.id)"
                  class="text-slate-400 hover:text-red-600 p-0.5 cursor-pointer"
                  title="Delete Task"
                >
                  <Trash2 class="w-3 h-3" />
                </button>
              </div>
            </div>

            <p v-if="task.description" class="text-[11px] text-slate-500 line-clamp-2 leading-relaxed">
              {{ task.description }}
            </p>

            <div class="flex items-center justify-between pt-1 text-xs">
              <UiBadge :variant="getPriorityBadge(task.priority).variant" class="text-[9px] font-bold">
                {{ getPriorityBadge(task.priority).label }}
              </UiBadge>

              <!-- Logged Hours -->
              <button
                type="button"
                @click="openTimeLogModal(task)"
                class="text-[11px] font-mono font-bold text-slate-600 hover:text-primary-700 flex items-center gap-1 bg-slate-50 hover:bg-primary-50 px-2 py-0.5 rounded border border-slate-200 transition-colors cursor-pointer"
                title="Log Hours"
              >
                <Clock class="w-3 h-3 text-slate-400" />
                {{ Number(task.logged_hours).toFixed(1) }} / {{ Number(task.estimated_hours).toFixed(0) }}h
              </button>
            </div>

            <!-- Transition Actions -->
            <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-[11px]">
              <button
                v-if="getPrevStatus(task.status)"
                type="button"
                @click="moveToStatus(task, getPrevStatus(task.status)!)"
                class="text-slate-400 hover:text-slate-700 font-bold inline-flex items-center cursor-pointer"
              >
                <ChevronLeft class="w-3 h-3" /> Back
              </button>
              <div v-else></div>

              <button
                v-if="getNextStatus(task.status)"
                type="button"
                @click="moveToStatus(task, getNextStatus(task.status)!)"
                class="text-primary-600 hover:text-primary-700 font-bold inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded hover:bg-primary-50 cursor-pointer"
              >
                Next <ChevronRight class="w-3 h-3" />
              </button>
            </div>
          </div>

          <div v-if="!tasksByStatus[col.key]?.length" class="p-6 text-center text-slate-300 text-xs italic">
            No tasks in {{ col.label }}
          </div>
        </div>
      </div>
    </div>

    <!-- 2. Table List View -->
    <div v-else class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
      <div v-if="filteredTasks.length" class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100 text-xs">
          <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
            <tr>
              <th class="px-4 py-3 text-left">Task & Project</th>
              <th class="px-4 py-3 text-left">Status</th>
              <th class="px-4 py-3 text-left">Priority</th>
              <th class="px-4 py-3 text-right">Logged / Est. Hours</th>
              <th class="px-4 py-3 text-right">Target Due Date</th>
              <th class="px-4 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-slate-700">
            <tr v-for="task in filteredTasks" :key="task.id" class="hover:bg-slate-50/70 transition-colors">
              <td class="px-4 py-3">
                <div class="font-bold text-slate-900 text-sm">{{ task.title }}</div>
                <div class="text-[11px] text-slate-500 mt-0.5">
                  <span class="font-mono font-semibold text-primary-700">{{ task.project?.code }}</span> • {{ task.project?.name }}
                </div>
              </td>

              <td class="px-4 py-3">
                <UiBadge :variant="task.status === 'done' ? 'success' : 'info'" class="capitalize text-[10px] font-bold">
                  {{ task.status.replace('_', ' ') }}
                </UiBadge>
              </td>

              <td class="px-4 py-3">
                <UiBadge :variant="getPriorityBadge(task.priority).variant" class="text-[10px] font-bold">
                  {{ getPriorityBadge(task.priority).label }}
                </UiBadge>
              </td>

              <td class="px-4 py-3 text-right font-mono font-bold text-slate-900">
                {{ Number(task.logged_hours).toFixed(1) }} / {{ Number(task.estimated_hours).toFixed(1) }}h
              </td>

              <td class="px-4 py-3 text-right text-slate-500">
                {{ task.due_date ? new Date(task.due_date).toLocaleDateString() : '—' }}
              </td>

              <td class="px-4 py-3 text-right space-x-1 whitespace-nowrap">
                <UiButton size="sm" variant="outline" @click="openTimeLogModal(task)">
                  <Clock class="w-3.5 h-3.5 mr-1" /> Log Time
                </UiButton>
                <UiButton variant="ghost" size="sm" @click="openEditModal(task)">
                  <Edit class="w-3.5 h-3.5" />
                </UiButton>
                <UiButton variant="ghost" size="sm" class="text-red-500 hover:text-red-700" @click="deleteMutation.mutate(task.id)">
                  <Trash2 class="w-3.5 h-3.5" />
                </UiButton>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="p-12 text-center text-slate-400 text-xs">
        No tasks found matching your filters.
      </div>
    </div>

    <!-- Create / Edit Task Modal -->
    <UiModal v-model="isModalOpen" :title="editingTask ? 'Edit Task' : 'Add Task to Project'" size="md">
      <div class="space-y-4">
        <UiSelect
          v-model="taskForm.project_id"
          label="Project"
          :options="projects?.map(p => ({ label: `${p.code} - ${p.name}`, value: p.id })) || []"
          required
        />

        <UiInput v-model="taskForm.title" label="Task Title" placeholder="e.g. Implement OAuth2 client provider" required />

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UiSelect
            v-model="taskForm.status"
            label="Initial Status"
            :options="[
              { label: 'To Do', value: 'todo' },
              { label: 'In Progress', value: 'in_progress' },
              { label: 'Under Review', value: 'review' },
              { label: 'Done', value: 'done' },
            ]"
          />
          <UiSelect
            v-model="taskForm.priority"
            label="Priority"
            :options="[
              { label: 'Low', value: 'low' },
              { label: 'Medium', value: 'medium' },
              { label: 'High', value: 'high' },
              { label: 'Urgent', value: 'urgent' },
            ]"
          />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UiInput v-model="taskForm.estimated_hours" label="Estimated Hours" type="number" min="0" />
          <UiInput v-model="taskForm.due_date" label="Due Date" type="date" />
        </div>

        <div class="space-y-1">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Description & Acceptance Criteria</label>
          <textarea
            v-model="taskForm.description"
            rows="3"
            placeholder="Implementation requirements, checklists, dependencies..."
            class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500"
          ></textarea>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
          <UiButton variant="outline" type="button" @click="isModalOpen = false">Cancel</UiButton>
          <UiButton :loading="saveMutation.isPending.value" @click="handleSave">
            {{ editingTask ? 'Save Task' : 'Create Task' }}
          </UiButton>
        </div>
      </div>
    </UiModal>

    <!-- Log Time Modal -->
    <UiModal v-model="isTimeLogModalOpen" title="Log Work Hours on Task" size="md">
      <div v-if="timeLogTask" class="space-y-4">
        <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-1">
          <span class="text-[10px] font-mono font-bold text-primary-700">{{ timeLogTask.project?.code }}</span>
          <h4 class="text-xs font-black text-slate-900">{{ timeLogTask.title }}</h4>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UiInput v-model="timeLogForm.hours" label="Hours Spent" type="number" step="0.25" min="0.1" max="24" required />
          <UiInput v-model="timeLogForm.log_date" label="Work Date" type="date" required />
        </div>

        <div class="space-y-1">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Work Description</label>
          <textarea
            v-model="timeLogForm.description"
            rows="3"
            placeholder="Summary of work delivered, commits, or bugfixes..."
            class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500"
          ></textarea>
        </div>

        <div class="flex items-center gap-2 pt-1">
          <input
            id="billable_checkbox"
            v-model="timeLogForm.is_billable"
            type="checkbox"
            class="h-4 w-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500 cursor-pointer"
          />
          <label for="billable_checkbox" class="text-xs text-slate-700 font-semibold cursor-pointer">
            Billable client work
          </label>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
          <UiButton variant="outline" type="button" @click="isTimeLogModalOpen = false">Cancel</UiButton>
          <UiButton :loading="logTimeMutation.isPending.value" @click="handleLogTime">
            Record Timesheet Log
          </UiButton>
        </div>
      </div>
    </UiModal>
  </div>
</template>
