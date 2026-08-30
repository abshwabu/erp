<script setup lang="ts">
import { ref, computed } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { recruitmentApi } from '@/api/recruitment'
import type { JobPosting, JobApplication, ApplicationStatus } from '@/types/recruitment'
import UiModal from '@/components/ui/UiModal.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiSpinner from '@/components/ui/UiSpinner.vue'
import { useToast } from '@/composables/useToast'
import {
  Users,
  Search,
  Download,
  ExternalLink,
  Star,
  Trash2,
  Mail,
  Phone,
  Calendar,
  FileText,
  CheckCircle2,
  XCircle,
  UserCheck,
  Clock,
  Briefcase,
} from '@lucide/vue'

const props = defineProps<{
  modelValue: boolean
  job: JobPosting | null
}>()

const emit = defineEmits(['update:modelValue'])
const queryClient = useQueryClient()
const toast = useToast()

const selectedStatus = ref<string>('all')
const searchQuery = ref('')
const selectedApplication = ref<JobApplication | null>(null)
const isDetailModalOpen = ref(false)

const { data: applications, isLoading, refetch } = useQuery({
  queryKey: ['hr', 'jobs', computed(() => props.job?.id), 'applications'],
  queryFn: () => props.job?.id ? recruitmentApi.getApplications(props.job.id).then((r) => r.data) : Promise.resolve([]),
  enabled: () => !!props.job?.id && props.modelValue,
})

const filteredApplications = computed(() => {
  let list = applications.value || []
  if (selectedStatus.value !== 'all') {
    list = list.filter((a) => a.status === selectedStatus.value)
  }
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(
      (a) =>
        a.applicant_name.toLowerCase().includes(q) ||
        a.applicant_email.toLowerCase().includes(q) ||
        (a.applicant_phone || '').toLowerCase().includes(q)
    )
  }
  return list
})

const updateMutation = useMutation({
  mutationFn: ({ appId, payload }: { appId: string; payload: any }) =>
    recruitmentApi.updateApplication(props.job!.id, appId, payload),
  onSuccess: (res) => {
    queryClient.invalidateQueries({ queryKey: ['hr', 'jobs'] })
    refetch()
    if (selectedApplication.value?.id === res.data.id) {
      selectedApplication.value = res.data
    }
    toast.success('Candidate status updated')
  },
  onError: () => {
    toast.error('Failed to update candidate status')
  },
})

const deleteMutation = useMutation({
  mutationFn: (appId: string) => recruitmentApi.deleteApplication(props.job!.id, appId),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['hr', 'jobs'] })
    refetch()
    isDetailModalOpen.value = false
    toast.success('Application deleted')
  },
})

const setStatus = (app: JobApplication, status: ApplicationStatus) => {
  updateMutation.mutate({ appId: app.id, payload: { status } })
}

const setRating = (app: JobApplication, rating: number) => {
  updateMutation.mutate({ appId: app.id, payload: { rating } })
}

const saveNotes = (app: JobApplication, notes: string) => {
  updateMutation.mutate({ appId: app.id, payload: { notes } })
}

const viewDetails = (app: JobApplication) => {
  selectedApplication.value = app
  isDetailModalOpen.value = true
}

const getStatusBadge = (status: string) => {
  switch (status) {
    case 'new':
      return { label: 'New Application', variant: 'info' as const }
    case 'shortlisted':
      return { label: 'Shortlisted', variant: 'purple' as const }
    case 'interviewing':
      return { label: 'Interviewing', variant: 'warning' as const }
    case 'offered':
      return { label: 'Job Offered', variant: 'info' as const }
    case 'hired':
      return { label: 'Hired 🎉', variant: 'success' as const }
    case 'rejected':
      return { label: 'Rejected', variant: 'danger' as const }
    default:
      return { label: status, variant: 'default' as const }
  }
}
</script>

<template>
  <UiModal
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
    :title="`Applications: ${job?.title || 'Job Opening'}`"
    size="xl"
  >
    <div class="space-y-5">
      <!-- Top Filters -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1">
          <button
            v-for="st in ['all', 'new', 'shortlisted', 'interviewing', 'offered', 'hired', 'rejected']"
            :key="st"
            type="button"
            @click="selectedStatus = st"
            class="px-3 py-1.5 rounded-xl text-xs font-bold capitalize transition-all cursor-pointer"
            :class="selectedStatus === st ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
          >
            {{ st === 'all' ? `All (${applications?.length || 0})` : st }}
          </button>
        </div>

        <UiInput
          v-model="searchQuery"
          placeholder="Search applicants..."
          size="sm"
          class="w-full sm:w-48"
        >
          <template #prefix><Search class="w-3.5 h-3.5 text-slate-400" /></template>
        </UiInput>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="p-12 flex justify-center">
        <UiSpinner size="md" />
      </div>

      <!-- Applications List -->
      <div v-else-if="filteredApplications.length" class="space-y-3 max-h-[550px] overflow-y-auto pr-1">
        <div
          v-for="app in filteredApplications"
          :key="app.id"
          class="p-4 rounded-2xl border border-slate-200 hover:border-slate-300 bg-white transition-all shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4"
        >
          <div class="flex items-start gap-3">
            <div class="w-11 h-11 rounded-2xl bg-slate-100 overflow-hidden flex items-center justify-center font-bold text-slate-700 text-sm shrink-0 border border-slate-200">
              <img v-if="app.photo_url" :src="app.photo_url" class="w-full h-full object-cover" />
              <span v-else>{{ app.applicant_name?.[0] }}</span>
            </div>

            <div class="space-y-1">
              <div class="flex items-center gap-2">
                <h4 class="font-bold text-slate-900 text-sm">{{ app.applicant_name }}</h4>
                <UiBadge :variant="getStatusBadge(app.status).variant" class="text-[10px] font-bold">
                  {{ getStatusBadge(app.status).label }}
                </UiBadge>
              </div>

              <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 font-medium">
                <span class="flex items-center gap-1 font-mono">
                  <Mail class="w-3 h-3 text-slate-400" />
                  {{ app.applicant_email }}
                </span>
                <span v-if="app.applicant_phone" class="flex items-center gap-1 font-mono">
                  <Phone class="w-3 h-3 text-slate-400" />
                  {{ app.applicant_phone }}
                </span>
                <span class="text-slate-400">• Applied {{ new Date(app.submitted_at).toLocaleDateString() }}</span>
              </div>

              <!-- Rating Stars -->
              <div class="flex items-center gap-1 pt-1">
                <button
                  v-for="star in [1, 2, 3, 4, 5]"
                  :key="star"
                  type="button"
                  @click="setRating(app, star)"
                  class="p-0.5 transition-transform hover:scale-110 cursor-pointer"
                  :title="`Rate ${star} Stars`"
                >
                  <Star
                    class="w-3.5 h-3.5"
                    :class="(app.rating || 0) >= star ? 'text-amber-400 fill-amber-400' : 'text-slate-300'"
                  />
                </button>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center flex-wrap gap-2">
            <a
              v-if="app.resume_url"
              :href="app.resume_url"
              target="_blank"
              download
              class="px-2.5 py-1.5 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 inline-flex items-center gap-1 transition-colors"
              title="Download Resume / CV"
            >
              <Download class="w-3.5 h-3.5 text-primary-600" /> Resume
            </a>

            <UiButton size="sm" variant="outline" @click="viewDetails(app)">
              View Responses
            </UiButton>

            <!-- Quick Status Change Dropdown -->
            <select
              :value="app.status"
              @change="setStatus(app, ($event.target as HTMLSelectElement).value as any)"
              class="px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:outline-none focus:ring-1 focus:ring-primary-500 cursor-pointer"
            >
              <option value="new">New</option>
              <option value="reviewed">Reviewed</option>
              <option value="shortlisted">Shortlisted</option>
              <option value="interviewing">Interviewing</option>
              <option value="offered">Offered</option>
              <option value="hired">Hired</option>
              <option value="rejected">Rejected</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="p-12 text-center bg-slate-50 rounded-2xl border border-slate-200 text-slate-400 space-y-2">
        <Users class="w-10 h-10 mx-auto text-slate-300" />
        <h4 class="font-bold text-slate-700 text-sm">No applications found</h4>
        <p class="text-xs text-slate-400 max-w-sm mx-auto">
          Share the public job link to start receiving candidate submissions.
        </p>
      </div>

      <div class="flex justify-end pt-4 border-t border-slate-100">
        <UiButton variant="outline" @click="emit('update:modelValue', false)">Close</UiButton>
      </div>
    </div>

    <!-- Candidate Detailed Application Modal -->
    <UiModal v-model="isDetailModalOpen" title="Candidate Profile & Questionnaire Answers" size="lg">
      <div v-if="selectedApplication" class="space-y-5">
        <!-- Header Info -->
        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-slate-200">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-white overflow-hidden flex items-center justify-center font-bold text-slate-700 border border-slate-200">
              <img v-if="selectedApplication.photo_url" :src="selectedApplication.photo_url" class="w-full h-full object-cover" />
              <span v-else>{{ selectedApplication.applicant_name?.[0] }}</span>
            </div>
            <div>
              <h3 class="font-black text-slate-900 text-base">{{ selectedApplication.applicant_name }}</h3>
              <p class="text-xs text-slate-500 font-mono">{{ selectedApplication.applicant_email }} • {{ selectedApplication.applicant_phone || 'No phone' }}</p>
            </div>
          </div>

          <UiBadge :variant="getStatusBadge(selectedApplication.status).variant" class="font-bold">
            {{ getStatusBadge(selectedApplication.status).label }}
          </UiBadge>
        </div>

        <!-- Cover Letter -->
        <div v-if="selectedApplication.cover_letter" class="p-4 bg-white rounded-xl border border-slate-200 space-y-1.5">
          <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Cover Letter</span>
          <p class="text-xs text-slate-700 leading-relaxed whitespace-pre-line">{{ selectedApplication.cover_letter }}</p>
        </div>

        <!-- Custom Questionnaire Responses -->
        <div class="space-y-3">
          <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 border-b border-slate-100 pb-2">
            Questionnaire & Form Submissions
          </h4>

          <div
            v-if="selectedApplication.custom_form_responses && Object.keys(selectedApplication.custom_form_responses).length"
            class="grid grid-cols-1 sm:grid-cols-2 gap-3"
          >
            <div
              v-for="(val, key) in selectedApplication.custom_form_responses"
              :key="key"
              class="p-3.5 bg-slate-50 rounded-xl border border-slate-200/80 space-y-1"
            >
              <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">{{ String(key).replace(/_/g, ' ') }}</span>
              <p v-if="Array.isArray(val)" class="text-xs font-semibold text-slate-900">
                {{ val.join(', ') }}
              </p>
              <a
                v-else-if="typeof val === 'string' && (val.startsWith('http://') || val.startsWith('https://'))"
                :href="val"
                target="_blank"
                class="text-xs font-bold text-primary-600 hover:underline inline-flex items-center gap-1"
              >
                View Attachment <ExternalLink class="w-3 h-3" />
              </a>
              <p v-else class="text-xs font-semibold text-slate-900">{{ val || '—' }}</p>
            </div>
          </div>
          <p v-else class="text-xs text-slate-400 italic">No custom questionnaire responses recorded.</p>
        </div>

        <!-- Recruiter Internal Notes -->
        <div class="space-y-1.5">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Internal Recruiter Notes</label>
          <textarea
            :value="selectedApplication.notes || ''"
            @change="saveNotes(selectedApplication, ($event.target as HTMLTextAreaElement).value)"
            rows="3"
            placeholder="Add confidential interview feedback, technical ratings, or salary notes..."
            class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500"
          ></textarea>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-between pt-4 border-t border-slate-100">
          <button
            type="button"
            @click="deleteMutation.mutate(selectedApplication.id)"
            class="text-xs text-red-500 hover:text-red-700 font-bold inline-flex items-center gap-1 hover:underline cursor-pointer"
          >
            <Trash2 class="w-3.5 h-3.5" /> Delete Application
          </button>
          <UiButton size="sm" @click="isDetailModalOpen = false">Close</UiButton>
        </div>
      </div>
    </UiModal>
  </UiModal>
</template>
