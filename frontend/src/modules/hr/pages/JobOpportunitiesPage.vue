<script setup lang="ts">
import { ref, computed, markRaw } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { recruitmentApi } from '@/api/recruitment'
import type { JobPosting } from '@/types/recruitment'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiStat from '@/components/ui/UiStat.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiSpinner from '@/components/ui/UiSpinner.vue'
import CreateEditJobModal from '../components/CreateEditJobModal.vue'
import JobApplicationsDrawer from '../components/JobApplicationsDrawer.vue'
import { useToast } from '@/composables/useToast'
import {
  Briefcase,
  Plus,
  Search,
  Users,
  Eye,
  Link,
  Copy,
  ExternalLink,
  CheckCircle2,
  Clock,
  Trash2,
  Edit,
  TrendingUp,
  MapPin,
  Calendar,
  Sparkles,
  Layers,
} from '@lucide/vue'

const queryClient = useQueryClient()
const toast = useToast()

const searchQuery = ref('')
const selectedStatus = ref<string>('all')
const isJobModalOpen = ref(false)
const isApplicationsDrawerOpen = ref(false)
const selectedJob = ref<JobPosting | null>(null)

// Queries
const { data: jobs, isLoading: isLoadingJobs } = useQuery({
  queryKey: ['hr', 'jobs'],
  queryFn: () => recruitmentApi.getJobs().then((r) => r.data),
})

const { data: stats } = useQuery({
  queryKey: ['hr', 'jobs', 'stats'],
  queryFn: () => recruitmentApi.getStats().then((r) => r.data),
})

const filteredJobs = computed(() => {
  let list = jobs.value || []
  if (selectedStatus.value !== 'all') {
    list = list.filter((j) => j.status === selectedStatus.value)
  }
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(
      (j) =>
        j.title.toLowerCase().includes(q) ||
        j.location.toLowerCase().includes(q) ||
        (j.department?.name || '').toLowerCase().includes(q)
    )
  }
  return list
})

const statCards = computed(() => [
  {
    label: 'Active Job Openings',
    value: stats.value?.active_jobs ?? 0,
    icon: markRaw(Briefcase),
  },
  {
    label: 'Total Applications',
    value: stats.value?.total_applications ?? 0,
    icon: markRaw(Users),
  },
  {
    label: 'In Interview Pipeline',
    value: stats.value?.in_pipeline ?? 0,
    icon: markRaw(Clock),
  },
  {
    label: 'Hired Candidates',
    value: stats.value?.hired_candidates ?? 0,
    icon: markRaw(CheckCircle2),
  },
])

// Mutations
const deleteMutation = useMutation({
  mutationFn: (id: string) => recruitmentApi.deleteJob(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['hr', 'jobs'] })
    toast.success('Job posting removed')
  },
  onError: () => {
    toast.error('Failed to delete job posting')
  },
})

const openCreateModal = () => {
  selectedJob.value = null
  isJobModalOpen.value = true
}

const openEditModal = (job: JobPosting) => {
  selectedJob.value = job
  isJobModalOpen.value = true
}

const openApplications = (job: JobPosting) => {
  selectedJob.value = job
  isApplicationsDrawerOpen.value = true
}

const handleDelete = (job: JobPosting) => {
  if (confirm(`Are you sure you want to delete "${job.title}"?`)) {
    deleteMutation.mutate(job.id)
  }
}

const copyPublicLink = (job: JobPosting) => {
  const url = `${window.location.origin}/careers/${job.slug || job.id}`
  navigator.clipboard.writeText(url)
  toast.success('Public job application link copied to clipboard!')
}

const getStatusBadge = (status: string) => {
  switch (status) {
    case 'published':
      return { label: 'Published & Active', variant: 'success' as const }
    case 'draft':
      return { label: 'Draft (Hidden)', variant: 'warning' as const }
    case 'closed':
      return { label: 'Closed', variant: 'danger' as const }
    default:
      return { label: status, variant: 'default' as const }
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Job Opportunities & Recruitment</h1>
        <p class="text-xs sm:text-sm text-slate-500">
          Create customized public job application forms with flexible questions (checkboxes, radio, dropdowns, uploads) and review applicant pipelines.
        </p>
      </div>
      <UiButton @click="openCreateModal">
        <Plus class="w-4 h-4 mr-2" /> Post New Job
      </UiButton>
    </div>

    <!-- Stats Bar -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <UiStat
        v-for="stat in statCards"
        :key="stat.label"
        :label="stat.label"
        :value="stat.value"
        :icon="stat.icon"
      />
    </div>

    <!-- Filters & Search -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
      <div class="flex items-center gap-1.5 overflow-x-auto pb-1">
        <button
          v-for="st in ['all', 'published', 'draft', 'closed']"
          :key="st"
          type="button"
          @click="selectedStatus = st"
          class="px-3.5 py-1.5 rounded-xl text-xs font-bold capitalize transition-all cursor-pointer"
          :class="selectedStatus === st ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
        >
          {{ st === 'all' ? `All Openings (${jobs?.length || 0})` : st }}
        </button>
      </div>

      <UiInput
        v-model="searchQuery"
        placeholder="Search job title, location, department..."
        size="sm"
        class="w-full sm:w-64"
      >
        <template #prefix><Search class="w-3.5 h-3.5 text-slate-400" /></template>
      </UiInput>
    </div>

    <!-- Loading State -->
    <div v-if="isLoadingJobs" class="p-16 flex justify-center">
      <UiSpinner size="lg" />
    </div>

    <!-- Jobs Grid -->
    <div v-else-if="filteredJobs.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <div
        v-for="job in filteredJobs"
        :key="job.id"
        class="bg-white rounded-2xl border border-slate-200 hover:border-slate-300 p-5 shadow-xs flex flex-col justify-between transition-all space-y-4 group"
      >
        <div class="space-y-3">
          <div class="flex items-start justify-between gap-2">
            <div class="space-y-1">
              <UiBadge :variant="getStatusBadge(job.status).variant" class="text-[10px] font-bold">
                {{ getStatusBadge(job.status).label }}
              </UiBadge>
              <h3 class="font-bold text-slate-900 text-base leading-snug group-hover:text-primary-700 transition-colors">
                {{ job.title }}
              </h3>
            </div>

            <div class="flex items-center gap-1 text-slate-400 text-xs font-mono" title="Total Page Views">
              <Eye class="w-3.5 h-3.5" />
              <span>{{ job.views_count }}</span>
            </div>
          </div>

          <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
            <span class="flex items-center gap-1 font-medium">
              <MapPin class="w-3.5 h-3.5 text-slate-400" />
              {{ job.location }}
            </span>
            <span>•</span>
            <span class="capitalize font-medium">{{ job.employment_type }}</span>
            <span v-if="job.department">•</span>
            <span v-if="job.department" class="text-slate-700 font-semibold">{{ job.department.name }}</span>
          </div>

          <p class="text-xs text-slate-600 line-clamp-2 leading-relaxed">
            {{ job.description }}
          </p>

          <div class="flex flex-wrap items-center gap-2 pt-1 text-xs">
            <span
              v-if="job.min_salary || job.max_salary"
              class="font-mono font-bold text-emerald-800 bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-100"
            >
              {{ job.salary_currency }} {{ job.min_salary ? Number(job.min_salary).toLocaleString() : '0' }}
              <span v-if="job.max_salary"> - {{ Number(job.max_salary).toLocaleString() }}</span>
            </span>
            <span
              v-if="job.custom_form_schema?.length"
              class="text-primary-700 bg-primary-50 px-2 py-0.5 rounded-md font-semibold text-[11px] flex items-center gap-1"
            >
              <Layers class="w-3 h-3" /> {{ job.custom_form_schema.length }} custom questions
            </span>
          </div>
        </div>

        <div class="pt-4 border-t border-slate-100 space-y-3">
          <div class="flex items-center justify-between text-xs">
            <button
              type="button"
              @click="openApplications(job)"
              class="font-bold text-primary-600 hover:text-primary-700 inline-flex items-center gap-1.5 hover:underline cursor-pointer"
            >
              <Users class="w-4 h-4" />
              <span>{{ job.applications_count || 0 }} Candidate Applications</span>
            </button>

            <button
              type="button"
              @click="copyPublicLink(job)"
              class="text-xs text-slate-500 hover:text-slate-900 font-semibold inline-flex items-center gap-1 bg-slate-100 hover:bg-slate-200 px-2.5 py-1 rounded-lg transition-colors cursor-pointer"
              title="Copy Public Form Link"
            >
              <Copy class="w-3 h-3" /> Share Link
            </button>
          </div>

          <div class="flex items-center justify-between gap-2 pt-1 text-xs">
            <a
              :href="`/careers/${job.slug || job.id}`"
              target="_blank"
              class="text-slate-500 hover:text-blue-600 font-semibold inline-flex items-center gap-1 transition-colors"
            >
              <ExternalLink class="w-3.5 h-3.5" /> Public Form
            </a>

            <div class="flex items-center gap-1">
              <UiButton variant="ghost" size="sm" @click="openEditModal(job)" title="Edit Job & Questions">
                <Edit class="w-3.5 h-3.5" />
              </UiButton>
              <UiButton
                variant="ghost"
                size="sm"
                class="text-red-500 hover:text-red-600 hover:bg-red-50"
                @click="handleDelete(job)"
                title="Delete Job Opening"
              >
                <Trash2 class="w-3.5 h-3.5" />
              </UiButton>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="bg-white rounded-2xl border border-slate-200 p-16 text-center space-y-4">
      <Briefcase class="w-12 h-12 mx-auto text-slate-300" />
      <div>
        <h3 class="font-bold text-slate-900 text-base">No job opportunities found</h3>
        <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">
          Create a job opening with custom questions (checkboxes, radio, select, uploads) to start sourcing top talent.
        </p>
      </div>
      <UiButton size="sm" @click="openCreateModal">
        <Plus class="w-4 h-4 mr-1.5" /> Post First Job Opening
      </UiButton>
    </div>

    <!-- Create / Edit Job Modal with Dynamic Form Builder -->
    <CreateEditJobModal
      v-model="isJobModalOpen"
      :job="selectedJob"
      @saved="queryClient.invalidateQueries({ queryKey: ['hr', 'jobs'] })"
    />

    <!-- Candidate Applications Review Drawer -->
    <JobApplicationsDrawer
      v-model="isApplicationsDrawerOpen"
      :job="selectedJob"
    />
  </div>
</template>
