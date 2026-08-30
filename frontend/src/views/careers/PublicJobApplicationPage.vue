<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useQuery } from '@tanstack/vue-query'
import { recruitmentApi } from '@/api/recruitment'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiSpinner from '@/components/ui/UiSpinner.vue'
import { useToast } from '@/composables/useToast'
import {
  Briefcase,
  MapPin,
  Calendar,
  DollarSign,
  Upload,
  CheckCircle2,
  AlertCircle,
  FileText,
  Image,
  ArrowLeft,
  Share2,
  Building2,
  Sparkles,
  Paperclip,
} from '@lucide/vue'

const route = useRoute()
const toast = useToast()
const idOrSlug = (route.params.slug || route.params.id) as string

// Form state
const applicantName = ref('')
const applicantEmail = ref('')
const applicantPhone = ref('')
const coverLetter = ref('')
const resumeFile = ref<File | null>(null)
const photoFile = ref<File | null>(null)
const photoPreviewUrl = ref<string | null>(null)

// Custom dynamic answers map: { [field_name]: string | string[] | File }
const customResponses = ref<Record<string, any>>({})
const customFiles = ref<Record<string, File>>({})

const isSubmitting = ref(false)
const isSubmitted = ref(false)
const submissionResponse = ref<any>(null)
const errorMessage = ref('')

const { data: jobResponse, isLoading, error } = useQuery({
  queryKey: ['public', 'careers', idOrSlug],
  queryFn: () => recruitmentApi.getPublicJob(idOrSlug).then((r) => r.data),
})

const job = computed(() => jobResponse.value?.data)
const companyName = computed(() => jobResponse.value?.company?.name || 'Careers')

const handleResumeSelect = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files && target.files[0]) {
    resumeFile.value = target.files[0]
  }
}

const handlePhotoSelect = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files && target.files[0]) {
    const file = target.files[0]
    photoFile.value = file
    photoPreviewUrl.value = URL.createObjectURL(file)
  }
}

const handleCustomFileSelect = (key: string, e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files && target.files[0]) {
    customFiles.value[key] = target.files[0]
  }
}

const toggleCheckbox = (fieldName: string, option: string) => {
  if (!Array.isArray(customResponses.value[fieldName])) {
    customResponses.value[fieldName] = []
  }
  const arr: string[] = customResponses.value[fieldName]
  const idx = arr.indexOf(option)
  if (idx > -1) {
    arr.splice(idx, 1)
  } else {
    arr.push(option)
  }
}

const handleSubmit = async () => {
  if (!applicantName.value || !applicantEmail.value) {
    errorMessage.value = 'Please provide your full name and email address.'
    return
  }

  // Validate required custom fields
  if (job.value?.custom_form_schema) {
    for (const q of job.value.custom_form_schema) {
      if (q.required) {
        if (['file', 'image'].includes(q.type)) {
          if (!customFiles.value[q.name]) {
            errorMessage.value = `Please upload ${q.label}`
            return
          }
        } else {
          const val = customResponses.value[q.name]
          if (val === undefined || val === '' || (Array.isArray(val) && val.length === 0)) {
            errorMessage.value = `Please answer mandatory question: "${q.label}"`
            return
          }
        }
      }
    }
  }

  errorMessage.value = ''
  isSubmitting.value = true

  const formData = new FormData()
  formData.append('applicant_name', applicantName.value)
  formData.append('applicant_email', applicantEmail.value)
  if (applicantPhone.value) formData.append('applicant_phone', applicantPhone.value)
  if (coverLetter.value) formData.append('cover_letter', coverLetter.value)

  if (resumeFile.value) {
    formData.append('resume', resumeFile.value)
  }
  if (photoFile.value) {
    formData.append('photo', photoFile.value)
  }

  // Append custom text responses
  formData.append('custom_responses', JSON.stringify(customResponses.value))

  // Append custom file uploads
  for (const [key, file] of Object.entries(customFiles.value)) {
    formData.append(key, file)
  }

  try {
    const res = await recruitmentApi.submitPublicApplication(idOrSlug, formData)
    submissionResponse.value = res.data
    isSubmitted.value = true
    window.scrollTo({ top: 0, behavior: 'smooth' })
  } catch (err: any) {
    errorMessage.value = err?.response?.data?.message || 'Failed to submit application. Please try again.'
  } finally {
    isSubmitting.value = false
  }
}

const shareLink = () => {
  navigator.clipboard.writeText(window.location.href)
  toast.success('Link copied to clipboard!')
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 text-slate-900 flex flex-col justify-between font-sans">
    <!-- Navigation Header -->
    <header class="bg-white border-b border-slate-200/80 sticky top-0 z-20 shadow-2xs">
      <div class="max-w-4xl mx-auto px-4 h-16 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-xl bg-primary-600 flex items-center justify-center text-white font-black text-sm shadow-xs">
            <Briefcase class="w-4 h-4" />
          </div>
          <span class="font-black text-slate-900 tracking-tight text-base">{{ companyName }} Careers</span>
        </div>

        <button
          type="button"
          @click="shareLink"
          class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 px-3 py-1.5 rounded-xl transition-colors cursor-pointer"
        >
          <Share2 class="w-3.5 h-3.5" /> Share
        </button>
      </div>
    </header>

    <!-- Main Container -->
    <main class="max-w-4xl mx-auto px-4 py-8 w-full flex-1 space-y-8">
      <!-- Loading State -->
      <div v-if="isLoading" class="py-20 flex justify-center">
        <UiSpinner size="lg" />
      </div>

      <!-- Error State -->
      <div v-else-if="error || !job" class="p-12 bg-white rounded-3xl border border-slate-200 text-center space-y-4 shadow-xs">
        <AlertCircle class="w-12 h-12 text-red-500 mx-auto" />
        <h2 class="text-xl font-bold text-slate-900">Job Opportunity Not Found or Closed</h2>
        <p class="text-xs text-slate-500 max-w-sm mx-auto">
          This job posting may have expired or is no longer accepting submissions.
        </p>
      </div>

      <!-- Success Confirmation Voucher -->
      <div v-else-if="isSubmitted" class="p-8 md:p-12 bg-white rounded-3xl border border-slate-200 text-center space-y-6 shadow-sm">
        <div class="w-16 h-16 rounded-full bg-emerald-50 text-emerald-600 border border-emerald-200 mx-auto flex items-center justify-center">
          <CheckCircle2 class="w-8 h-8" />
        </div>

        <div class="space-y-2">
          <h2 class="text-2xl font-black text-slate-900">Application Successfully Submitted!</h2>
          <p class="text-sm text-slate-600 max-w-md mx-auto">
            Thank you, <strong class="text-slate-900">{{ applicantName }}</strong>. Your application for
            <strong class="text-slate-900">{{ job.title }}</strong> has been received by our hiring team.
          </p>
        </div>

        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 max-w-sm mx-auto text-xs text-slate-600 space-y-1">
          <span class="font-bold text-slate-400 uppercase tracking-wider">Confirmation ID</span>
          <p class="font-mono font-bold text-slate-900">{{ submissionResponse?.application_id }}</p>
          <p class="text-slate-400 text-[11px] pt-1">A confirmation has been logged with our recruitment team.</p>
        </div>

        <div class="pt-4">
          <UiButton variant="outline" @click="isSubmitted = false; isSubmitting = false">
            Submit Another Application
          </UiButton>
        </div>
      </div>

      <!-- Active Job Overview & Application Form -->
      <div v-else class="space-y-8">
        <!-- Job Banner & Overview Card -->
        <div class="p-6 md:p-8 bg-white rounded-3xl border border-slate-200/90 shadow-xs space-y-6">
          <div class="space-y-2">
            <div class="flex flex-wrap items-center gap-2">
              <UiBadge variant="success" class="text-xs font-bold capitalize">
                {{ job.employment_type }}
              </UiBadge>
              <UiBadge v-if="job.experience_level" variant="purple" class="text-xs font-bold capitalize">
                {{ job.experience_level }} Level
              </UiBadge>
              <span v-if="job.department" class="text-xs text-slate-500 font-semibold">• {{ job.department.name }}</span>
            </div>

            <h1 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight">{{ job.title }}</h1>

            <div class="flex flex-wrap items-center gap-4 text-xs text-slate-600 pt-1">
              <span class="flex items-center gap-1 font-medium">
                <MapPin class="w-3.5 h-3.5 text-slate-400" />
                {{ job.location }}
              </span>
              <span v-if="job.min_salary || job.max_salary" class="flex items-center gap-1 font-mono font-bold text-emerald-700">
                <DollarSign class="w-3.5 h-3.5 text-emerald-600" />
                {{ job.salary_currency }} {{ job.min_salary ? Number(job.min_salary).toLocaleString() : '0' }}
                <span v-if="job.max_salary"> - {{ Number(job.max_salary).toLocaleString() }}</span>
              </span>
              <span v-if="job.deadline" class="flex items-center gap-1 font-medium text-amber-700">
                <Calendar class="w-3.5 h-3.5" />
                Apply by {{ new Date(job.deadline).toLocaleDateString() }}
              </span>
            </div>
          </div>

          <!-- Description Section -->
          <div class="space-y-4 text-xs sm:text-sm text-slate-700 leading-relaxed border-t border-slate-100 pt-6 whitespace-pre-line">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">About the Role & Responsibilities</h3>
            <p>{{ job.description }}</p>
          </div>

          <!-- Requirements -->
          <div v-if="job.requirements" class="space-y-2 text-xs sm:text-sm text-slate-700 leading-relaxed border-t border-slate-100 pt-4 whitespace-pre-line">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Qualifications & Requirements</h3>
            <p>{{ job.requirements }}</p>
          </div>

          <!-- Benefits -->
          <div v-if="job.benefits" class="space-y-2 text-xs sm:text-sm text-slate-700 leading-relaxed border-t border-slate-100 pt-4 whitespace-pre-line">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">What We Offer</h3>
            <p>{{ job.benefits }}</p>
          </div>
        </div>

        <!-- Application Form Card -->
        <div class="p-6 md:p-8 bg-white rounded-3xl border border-slate-200/90 shadow-xs space-y-6">
          <div class="border-b border-slate-100 pb-4">
            <h2 class="text-lg font-black text-slate-900">Apply for this Position</h2>
            <p class="text-xs text-slate-500 mt-0.5">Please fill out the form below carefully. All required fields are marked (*).</p>
          </div>

          <!-- Error Alert -->
          <div v-if="errorMessage" class="p-4 rounded-xl bg-red-50 border border-red-200 text-xs font-semibold text-red-700 flex items-center justify-between">
            <span>{{ errorMessage }}</span>
            <button type="button" @click="errorMessage = ''" class="text-red-500 font-bold ml-2">✕</button>
          </div>

          <form @submit.prevent="handleSubmit" class="space-y-6">
            <!-- Basic Personal Info -->
            <div class="space-y-4">
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">1. Personal Information</h3>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <UiInput v-model="applicantName" label="Full Name *" placeholder="Jane Doe" required />
                <UiInput v-model="applicantEmail" label="Email Address *" type="email" placeholder="jane@example.com" required />
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <UiInput v-model="applicantPhone" label="Phone Number" type="tel" placeholder="+1..." />
                
                <!-- Headshot / Photo Upload -->
                <div class="space-y-1">
                  <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Candidate Photo / Headshot</label>
                  <div class="flex items-center gap-3">
                    <div v-if="photoPreviewUrl" class="w-10 h-10 rounded-xl overflow-hidden border border-slate-200 shrink-0">
                      <img :src="photoPreviewUrl" class="w-full h-full object-cover" />
                    </div>
                    <label class="px-3.5 py-2 bg-slate-50 hover:bg-slate-100 border border-slate-300 rounded-xl text-xs font-semibold text-slate-700 flex items-center gap-2 cursor-pointer transition-colors flex-1">
                      <Image class="w-4 h-4 text-slate-500" />
                      <span class="truncate">{{ photoFile ? photoFile.name : 'Choose Image (JPG, PNG)...' }}</span>
                      <input type="file" accept="image/*" class="hidden" @change="handlePhotoSelect" />
                    </label>
                  </div>
                </div>
              </div>

              <!-- Resume Upload -->
              <div class="space-y-1">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Resume / CV Document</label>
                <label class="p-6 border-2 border-dashed border-slate-300 hover:border-primary-500 bg-slate-50/60 rounded-2xl flex flex-col items-center justify-center text-center cursor-pointer transition-all space-y-1">
                  <Upload class="w-6 h-6 text-slate-400" />
                  <span class="text-xs font-bold text-slate-800">
                    {{ resumeFile ? resumeFile.name : 'Click or Drag & Drop your Resume (PDF, DOCX up to 20MB)' }}
                  </span>
                  <span v-if="resumeFile" class="text-[10px] text-emerald-600 font-bold">✓ File selected ({{ (resumeFile.size / 1024 / 1024).toFixed(2) }} MB)</span>
                  <input type="file" accept=".pdf,.doc,.docx,.rtf" class="hidden" @change="handleResumeSelect" />
                </label>
              </div>

              <!-- Cover Letter -->
              <div class="space-y-1">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Cover Letter & Introduction</label>
                <textarea
                  v-model="coverLetter"
                  rows="4"
                  placeholder="Tell us why you are a great fit for this role and your career goals..."
                  class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-600"
                ></textarea>
              </div>
            </div>

            <!-- Dynamic Custom Questionnaire Fields -->
            <div v-if="job.custom_form_schema && job.custom_form_schema.length" class="space-y-5 border-t border-slate-100 pt-6">
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">2. Additional Questionnaire</h3>

              <div class="space-y-4">
                <div
                  v-for="q in job.custom_form_schema"
                  :key="q.id"
                  class="space-y-1.5 p-4 bg-slate-50/70 rounded-2xl border border-slate-200/80"
                >
                  <label class="block text-xs font-bold uppercase tracking-wider text-slate-800">
                    {{ q.label }} <span v-if="q.required" class="text-red-500">*</span>
                  </label>
                  <p v-if="q.help_text" class="text-[11px] text-slate-500">{{ q.help_text }}</p>

                  <!-- 1. Dropdown Select -->
                  <div v-if="q.type === 'select'">
                    <select
                      v-model="customResponses[q.name]"
                      :required="q.required"
                      class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-600"
                    >
                      <option value="">{{ q.placeholder || 'Select an option...' }}</option>
                      <option v-for="opt in q.options" :key="opt" :value="opt">{{ opt }}</option>
                    </select>
                  </div>

                  <!-- 2. Radio Single Choice -->
                  <div v-else-if="q.type === 'radio'" class="space-y-2 pt-1">
                    <label
                      v-for="opt in q.options"
                      :key="opt"
                      class="flex items-center gap-2 text-xs text-slate-800 font-medium cursor-pointer"
                    >
                      <input
                        type="radio"
                        :name="q.name"
                        :value="opt"
                        v-model="customResponses[q.name]"
                        :required="q.required"
                        class="h-4 w-4 text-primary-600 focus:ring-primary-500"
                      />
                      <span>{{ opt }}</span>
                    </label>
                  </div>

                  <!-- 3. Checkbox Multi Choice -->
                  <div v-else-if="q.type === 'checkbox'" class="space-y-2 pt-1">
                    <label
                      v-for="opt in q.options"
                      :key="opt"
                      class="flex items-center gap-2 text-xs text-slate-800 font-medium cursor-pointer"
                    >
                      <input
                        type="checkbox"
                        :checked="(customResponses[q.name] || []).includes(opt)"
                        @change="toggleCheckbox(q.name, opt)"
                        class="h-4 w-4 rounded text-primary-600 focus:ring-primary-500"
                      />
                      <span>{{ opt }}</span>
                    </label>
                  </div>

                  <!-- 4. Textarea -->
                  <div v-else-if="q.type === 'textarea'">
                    <textarea
                      v-model="customResponses[q.name]"
                      :required="q.required"
                      :placeholder="q.placeholder || 'Enter your response...'"
                      rows="3"
                      class="w-full px-3.5 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-600"
                    ></textarea>
                  </div>

                  <!-- 5. Custom File Upload -->
                  <div v-else-if="q.type === 'file' || q.type === 'image'">
                    <label class="px-3.5 py-2.5 bg-white hover:bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold text-slate-700 flex items-center gap-2 cursor-pointer transition-colors">
                      <Paperclip class="w-4 h-4 text-slate-500" />
                      <span class="truncate">{{ customFiles[q.name] ? customFiles[q.name].name : 'Choose File / Attachment...' }}</span>
                      <input type="file" class="hidden" @change="handleCustomFileSelect(q.name, $event)" />
                    </label>
                  </div>

                  <!-- 6. Generic Text / Number / Date -->
                  <div v-else>
                    <input
                      v-model="customResponses[q.name]"
                      :type="q.type === 'number' ? 'number' : q.type === 'date' ? 'date' : 'text'"
                      :required="q.required"
                      :placeholder="q.placeholder || 'Enter your answer...'"
                      class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-600"
                    />
                  </div>
                </div>
              </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
              <span class="text-[11px] text-slate-400">By submitting, you agree to our recruitment review process.</span>
              <UiButton type="submit" size="lg" :loading="isSubmitting">
                Submit Application
              </UiButton>
            </div>
          </form>
        </div>
      </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-6 text-center text-xs text-slate-400">
      <p>© {{ new Date().getFullYear() }} {{ companyName }}. Powered by ERP Recruitment System.</p>
    </footer>
  </div>
</template>
