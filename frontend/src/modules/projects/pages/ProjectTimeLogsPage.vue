<script setup lang="ts">
import { ref, computed, markRaw } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { projectsApi } from '@/api/projects'
import type { ProjectTimeLog } from '@/types/projects'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiStat from '@/components/ui/UiStat.vue'
import UiSpinner from '@/components/ui/UiSpinner.vue'
import { useToast } from '@/composables/useToast'
import {
  Clock,
  Plus,
  Search,
  CheckCircle2,
  Calendar,
  Building2,
  Trash2,
  Edit,
  DollarSign,
  FolderKanban,
  User,
  CheckSquare,
} from '@lucide/vue'

const queryClient = useQueryClient()
const toast = useToast()

const searchQuery = ref('')
const selectedProjectId = ref<string>('all')
const isModalOpen = ref(false)
const editingLog = ref<ProjectTimeLog | null>(null)

const logForm = ref({
  project_id: '',
  task_id: '',
  hours: 1,
  log_date: new Date().toISOString().slice(0, 10),
  description: '',
  is_billable: true,
})

// Queries
const { data: timeLogs, isLoading } = useQuery({
  queryKey: ['projects', 'time-logs'],
  queryFn: () => projectsApi.getTimeLogs().then((r) => r.data.data),
})

const { data: projects } = useQuery({
  queryKey: ['projects'],
  queryFn: () => projectsApi.getProjects().then((r) => r.data.data),
})

const { data: tasks } = useQuery({
  queryKey: ['projects', 'tasks'],
  queryFn: () => projectsApi.getTasks().then((r) => r.data.data),
})

const filteredTasksForProject = computed(() => {
  if (!logForm.value.project_id) return []
  return (tasks.value || []).filter((t) => t.project_id === logForm.value.project_id)
})

const filteredLogs = computed(() => {
  let list = timeLogs.value || []
  if (selectedProjectId.value !== 'all') {
    list = list.filter((l) => l.project_id === selectedProjectId.value)
  }
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(
      (l) =>
        (l.description || '').toLowerCase().includes(q) ||
        (l.project?.name || '').toLowerCase().includes(q) ||
        (l.task?.title || '').toLowerCase().includes(q) ||
        (l.user?.name || '').toLowerCase().includes(q)
    )
  }
  return list
})

// Stats
const stats = computed(() => {
  const list = timeLogs.value || []
  const totalHours = list.reduce((sum, l) => sum + Number(l.hours || 0), 0)
  const billableHours = list.filter((l) => l.is_billable).reduce((sum, l) => sum + Number(l.hours || 0), 0)
  const billableRatio = totalHours > 0 ? Math.round((billableHours / totalHours) * 100) : 0

  return [
    {
      label: 'Total Hours Recorded',
      value: `${totalHours.toFixed(1)} hrs`,
      icon: markRaw(Clock),
    },
    {
      label: 'Billable Hours',
      value: `${billableHours.toFixed(1)} hrs`,
      icon: markRaw(DollarSign),
    },
    {
      label: 'Billable Efficiency',
      value: `${billableRatio}%`,
      icon: markRaw(CheckCircle2),
    },
    {
      label: 'Total Log Entries',
      value: list.length,
      icon: markRaw(CheckSquare),
    },
  ]
})

// Mutations
const saveMutation = useMutation({
  mutationFn: (payload: any) => {
    if (editingLog.value) {
      return projectsApi.updateTimeLog(editingLog.value.id, payload)
    }
    return projectsApi.createTimeLog(payload)
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['projects'] })
    isModalOpen.value = false
    toast.success(editingLog.value ? 'Time log updated' : 'Work hours recorded')
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || 'Failed to save time log')
  },
})

const deleteMutation = useMutation({
  mutationFn: (id: string) => projectsApi.deleteTimeLog(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['projects'] })
    toast.success('Time log deleted')
  },
})

const openCreateModal = () => {
  editingLog.value = null
  logForm.value = {
    project_id: projects.value?.[0]?.id || '',
    task_id: '',
    hours: 1,
    log_date: new Date().toISOString().slice(0, 10),
    description: '',
    is_billable: true,
  }
  isModalOpen.value = true
}

const openEditModal = (log: ProjectTimeLog) => {
  editingLog.value = log
  logForm.value = {
    project_id: log.project_id,
    task_id: log.task_id || '',
    hours: Number(log.hours),
    log_date: log.log_date ? String(log.log_date).slice(0, 10) : new Date().toISOString().slice(0, 10),
    description: log.description || '',
    is_billable: log.is_billable,
  }
  isModalOpen.value = true
}

const handleSave = () => {
  if (!logForm.value.project_id) {
    toast.error('Please select a project')
    return
  }
  if (!logForm.value.hours || Number(logForm.value.hours) <= 0) {
    toast.error('Please enter valid hours spent')
    return
  }
  saveMutation.mutate({
    ...logForm.value,
    task_id: logForm.value.task_id || null,
    hours: Number(logForm.value.hours),
  })
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Timesheets & Work Logs</h1>
        <p class="text-xs sm:text-sm text-slate-500">Track labor expenditure, billable consultant hours, and development timesheets.</p>
      </div>
      <UiButton @click="openCreateModal">
        <Plus class="w-4 h-4 mr-2" /> Log Work Hours
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
          All Projects ({{ timeLogs?.length || 0 }})
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
        placeholder="Filter logs by description, member, task..."
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

    <!-- Timesheets Table -->
    <div v-else class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
      <div v-if="filteredLogs.length" class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100 text-xs">
          <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
            <tr>
              <th class="px-4 py-3 text-left">Project & Task</th>
              <th class="px-4 py-3 text-left">Team Member</th>
              <th class="px-4 py-3 text-left">Work Description</th>
              <th class="px-4 py-3 text-left">Work Date</th>
              <th class="px-4 py-3 text-right">Hours Logged</th>
              <th class="px-4 py-3 text-center">Billable</th>
              <th class="px-4 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-slate-700">
            <tr v-for="log in filteredLogs" :key="log.id" class="hover:bg-slate-50/70 transition-colors">
              <td class="px-4 py-3">
                <div class="font-bold text-slate-900 text-sm">
                  {{ log.task?.title || 'General Project Work' }}
                </div>
                <div class="text-[11px] text-slate-500 mt-0.5">
                  <span class="font-mono font-semibold text-primary-700">{{ log.project?.code }}</span> • {{ log.project?.name }}
                </div>
              </td>

              <td class="px-4 py-3 font-semibold text-slate-800">
                {{ log.user?.name || 'Current User' }}
              </td>

              <td class="px-4 py-3 text-slate-600 max-w-xs truncate">
                {{ log.description || '—' }}
              </td>

              <td class="px-4 py-3 text-slate-500 font-mono">
                {{ log.log_date ? new Date(log.log_date).toLocaleDateString() : '—' }}
              </td>

              <td class="px-4 py-3 text-right font-mono font-bold text-slate-900 text-sm">
                {{ Number(log.hours).toFixed(2) }}h
              </td>

              <td class="px-4 py-3 text-center">
                <UiBadge :variant="log.is_billable ? 'success' : 'default'" class="text-[10px] font-bold">
                  {{ log.is_billable ? 'Billable' : 'Internal' }}
                </UiBadge>
              </td>

              <td class="px-4 py-3 text-right space-x-1 whitespace-nowrap">
                <UiButton variant="ghost" size="sm" @click="openEditModal(log)">
                  <Edit class="w-3.5 h-3.5 text-slate-600" />
                </UiButton>
                <UiButton variant="ghost" size="sm" class="text-red-500 hover:text-red-700 hover:bg-red-50" @click="deleteMutation.mutate(log.id)">
                  <Trash2 class="w-3.5 h-3.5" />
                </UiButton>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="p-12 text-center text-slate-400 text-xs">
        No timesheet logs recorded yet.
      </div>
    </div>

    <!-- Create / Edit Time Log Modal -->
    <UiModal v-model="isModalOpen" :title="editingLog ? 'Edit Time Log' : 'Record Timesheet Work Hours'" size="md">
      <div class="space-y-4">
        <UiSelect
          v-model="logForm.project_id"
          label="Project"
          :options="projects?.map(p => ({ label: `${p.code} - ${p.name}`, value: p.id })) || []"
          required
        />

        <UiSelect
          v-model="logForm.task_id"
          label="Associated Task (Optional)"
          :options="[{ label: 'General Project Work (No Task)', value: '' }, ...(filteredTasksForProject.map(t => ({ label: t.title, value: t.id })) || [])]"
        />

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UiInput v-model="logForm.hours" label="Hours Spent" type="number" step="0.25" min="0.1" max="24" required />
          <UiInput v-model="logForm.log_date" label="Work Date" type="date" required />
        </div>

        <div class="space-y-1">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Work Delivered & Summary</label>
          <textarea
            v-model="logForm.description"
            rows="3"
            placeholder="Describe completed tasks, milestone progress, or meeting notes..."
            class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500"
          ></textarea>
        </div>

        <div class="flex items-center gap-2 pt-1">
          <input
            id="modal_billable_checkbox"
            v-model="logForm.is_billable"
            type="checkbox"
            class="h-4 w-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500 cursor-pointer"
          />
          <label for="modal_billable_checkbox" class="text-xs text-slate-700 font-semibold cursor-pointer">
            Billable client work
          </label>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
          <UiButton variant="outline" type="button" @click="isModalOpen = false">Cancel</UiButton>
          <UiButton :loading="saveMutation.isPending.value" @click="handleSave">
            {{ editingLog ? 'Save Changes' : 'Record Time Log' }}
          </UiButton>
        </div>
      </div>
    </UiModal>
  </div>
</template>
