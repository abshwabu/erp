<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/api/client'
import { FolderKanban, Plus, Clock, CheckCircle2, AlertCircle } from '@lucide/vue'

interface ProjectTask {
  id: string
  title: string
  status: string
  priority: string
  estimated_hours: number
  logged_hours: number
  assignee?: { name: string }
}

interface Project {
  id: string
  code: string
  name: string
  description: string | null
  status: string
  budget_cents: number
  start_date: string | null
  due_date: string | null
  manager?: { name: string; email: string }
  tasks_count?: number
  tasks?: ProjectTask[]
}

const projects = ref<Project[]>([])
const selectedProject = ref<Project | null>(null)
const loading = ref(true)

const statusColors: Record<string, string> = {
  planned: 'bg-blue-50 text-blue-700 border-blue-200',
  in_progress: 'bg-amber-50 text-amber-700 border-amber-200',
  on_hold: 'bg-gray-100 text-gray-700 border-gray-200',
  completed: 'bg-green-50 text-green-700 border-green-200',
  cancelled: 'bg-red-50 text-red-700 border-red-200',
}

const taskStatusColors: Record<string, string> = {
  todo: 'bg-gray-100 text-gray-700',
  in_progress: 'bg-amber-100 text-amber-700',
  review: 'bg-purple-100 text-purple-700',
  done: 'bg-green-100 text-green-700',
}

async function fetchProjects() {
  loading.value = true
  try {
    const res = await api.get('/projects')
    projects.value = res.data?.data?.data ?? res.data?.data ?? []
  } catch (e) {
    console.error('Failed to load projects', e)
  } finally {
    loading.value = false
  }
}

async function selectProject(p: Project) {
  try {
    const res = await api.get(`/projects/${p.id}`)
    selectedProject.value = res.data?.data ?? res.data
  } catch (e) {
    console.error('Failed to load project details', e)
  }
}

function formatCents(cents: number) {
  return '$' + (cents / 100).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

onMounted(fetchProjects)
</script>

<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <div class="flex items-center space-x-3">
        <FolderKanban class="w-7 h-7 text-gray-700" />
        <h1 class="text-2xl font-bold text-gray-900">Projects</h1>
      </div>
    </div>

    <div v-if="loading" class="text-center py-12 text-gray-500">Loading projects…</div>

    <div v-else-if="projects.length === 0" class="text-center py-16 bg-white rounded-lg border border-gray-200">
      <FolderKanban class="w-12 h-12 text-gray-300 mx-auto mb-3" />
      <p class="text-gray-500 font-medium">No projects created yet</p>
      <p class="text-sm text-gray-400 mt-1">Start by creating your first client or internal project.</p>
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Project List -->
      <div class="lg:col-span-1 space-y-3">
        <div
          v-for="proj in projects"
          :key="proj.id"
          @click="selectProject(proj)"
          :class="[
            'p-4 rounded-lg border cursor-pointer transition-all hover:shadow-sm',
            selectedProject?.id === proj.id ? 'bg-primary-50/50 border-primary-500 ring-1 ring-primary-500' : 'bg-white border-gray-200'
          ]"
        >
          <div class="flex items-center justify-between mb-1.5">
            <span class="text-xs font-mono font-medium text-gray-500">{{ proj.code }}</span>
            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium border" :class="statusColors[proj.status] ?? 'bg-gray-100'">
              {{ proj.status.replace('_', ' ') }}
            </span>
          </div>
          <h3 class="text-sm font-semibold text-gray-900">{{ proj.name }}</h3>
          <p v-if="proj.description" class="text-xs text-gray-500 mt-1 line-clamp-2">{{ proj.description }}</p>
          <div class="flex items-center justify-between text-xs text-gray-400 mt-3 pt-2 border-t border-gray-100">
            <span>{{ proj.manager?.name ?? 'Unassigned' }}</span>
            <span>{{ proj.tasks_count ?? 0 }} tasks</span>
          </div>
        </div>
      </div>

      <!-- Project Detail / Tasks Panel -->
      <div class="lg:col-span-2">
        <div v-if="!selectedProject" class="bg-white rounded-lg border border-gray-200 p-12 text-center text-gray-400">
          Select a project from the left to view details and task breakdown.
        </div>
        <div v-else class="bg-white rounded-lg border border-gray-200 p-6 space-y-6">
          <div class="flex items-start justify-between">
            <div>
              <span class="text-xs font-mono text-gray-500">{{ selectedProject.code }}</span>
              <h2 class="text-xl font-bold text-gray-900">{{ selectedProject.name }}</h2>
              <p v-if="selectedProject.description" class="text-sm text-gray-600 mt-1">{{ selectedProject.description }}</p>
            </div>
            <span class="inline-flex px-3 py-1 rounded-full text-xs font-medium border" :class="statusColors[selectedProject.status] ?? 'bg-gray-100'">
              {{ selectedProject.status.replace('_', ' ') }}
            </span>
          </div>

          <!-- Metadata Grid -->
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 bg-gray-50 rounded-lg text-sm">
            <div>
              <span class="text-xs text-gray-500 block">Manager</span>
              <span class="font-medium text-gray-900">{{ selectedProject.manager?.name ?? '—' }}</span>
            </div>
            <div>
              <span class="text-xs text-gray-500 block">Budget</span>
              <span class="font-medium text-gray-900">{{ formatCents(selectedProject.budget_cents) }}</span>
            </div>
            <div>
              <span class="text-xs text-gray-500 block">Start Date</span>
              <span class="font-medium text-gray-900">{{ selectedProject.start_date?.substring(0, 10) ?? '—' }}</span>
            </div>
            <div>
              <span class="text-xs text-gray-500 block">Due Date</span>
              <span class="font-medium text-gray-900">{{ selectedProject.due_date?.substring(0, 10) ?? '—' }}</span>
            </div>
          </div>

          <!-- Tasks Section -->
          <div class="space-y-3">
            <h3 class="text-sm font-semibold text-gray-900">Tasks</h3>
            <div v-if="!selectedProject.tasks || selectedProject.tasks.length === 0" class="text-sm text-gray-400 py-4 text-center">
              No tasks added to this project yet.
            </div>
            <div v-else class="divide-y divide-gray-100 border border-gray-200 rounded-lg overflow-hidden">
              <div
                v-for="task in selectedProject.tasks"
                :key="task.id"
                class="p-3.5 flex items-center justify-between hover:bg-gray-50 transition-colors"
              >
                <div class="space-y-0.5">
                  <span class="text-sm font-medium text-gray-900">{{ task.title }}</span>
                  <div class="flex items-center space-x-3 text-xs text-gray-400">
                    <span v-if="task.assignee">{{ task.assignee.name }}</span>
                    <span>{{ task.logged_hours }} / {{ task.estimated_hours }} hrs</span>
                  </div>
                </div>
                <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium" :class="taskStatusColors[task.status] ?? 'bg-gray-100'">
                  {{ task.status.replace('_', ' ') }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
