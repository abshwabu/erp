<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { useQuery } from '@tanstack/vue-query'
import { crmApi } from '@/api/crm'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSpinner from '@/components/ui/UiSpinner.vue'
import {
  Sparkles,
  ArrowRight,
  ArrowLeft,
  CheckCircle2,
  AlertCircle,
  Building2,
  Mail,
  Phone,
  User,
  ChevronRight,
  Check,
} from '@lucide/vue'

const route = useRoute()
const idOrSlug = (route.params.slug || route.params.id) as string

// Form answers state
const currentStepIndex = ref(0)
const name = ref('')
const company = ref('')
const email = ref('')
const phone = ref('')
const title = ref('')
const customResponses = ref<Record<string, any>>({})
const notes = ref('')

const isSubmitting = ref(false)
const isSubmitted = ref(false)
const submissionResult = ref<any>(null)
const validationError = ref('')

const { data: formResponse, isLoading, error } = useQuery({
  queryKey: ['public', 'lead-forms', idOrSlug],
  queryFn: () => crmApi.getPublicLeadForm(idOrSlug).then((r) => r.data),
})

const form = computed(() => formResponse.value?.data)
const companyName = computed(() => formResponse.value?.company?.name || 'ERP System')

// Sequence of steps:
// Step 0: Welcome
// Step 1: Name & Company
// Step 2: Email & Phone
// Step 3..(3 + N - 1): Custom Questions (1 per step)
// Last Step: Notes & Review
const totalSteps = computed(() => {
  const customCount = form.value?.custom_questions?.length || 0
  return 3 + customCount + 1 // Welcome + Basic Info (2) + Custom Questions (N) + Notes/Submit (1)
})

const progressPercent = computed(() => {
  if (currentStepIndex.value === 0) return 5
  return Math.round((currentStepIndex.value / (totalSteps.value - 1)) * 100)
})

const customQuestions = computed(() => form.value?.custom_questions || [])

const currentCustomQuestion = computed(() => {
  if (currentStepIndex.value >= 3 && currentStepIndex.value < 3 + customQuestions.value.length) {
    return customQuestions.value[currentStepIndex.value - 3]
  }
  return null
})

const nextStep = () => {
  validationError.value = ''

  // Validate step 1 (Name)
  if (currentStepIndex.value === 1) {
    if (!name.value.trim()) {
      validationError.value = 'Please enter your full name.'
      return
    }
  }

  // Validate step 2 (Email / Phone)
  if (currentStepIndex.value === 2) {
    if (!email.value.trim() && !phone.value.trim()) {
      validationError.value = 'Please provide an email address or phone number so we can get in touch.'
      return
    }
  }

  // Validate custom question step
  if (currentCustomQuestion.value && currentCustomQuestion.value.required) {
    const key = currentCustomQuestion.value.name
    const val = customResponses.value[key]
    if (val === undefined || val === '' || (Array.isArray(val) && val.length === 0)) {
      validationError.value = 'This question is required. Please select or enter an answer.'
      return
    }
  }

  if (currentStepIndex.value < totalSteps.value - 1) {
    currentStepIndex.value++
  } else {
    submitForm()
  }
}

const prevStep = () => {
  validationError.value = ''
  if (currentStepIndex.value > 0) {
    currentStepIndex.value--
  }
}

const selectRadioOption = (nameKey: string, option: string) => {
  customResponses.value[nameKey] = option
  validationError.value = ''
  // Auto-advance for single-choice questions after 300ms for delightful experience
  setTimeout(() => {
    nextStep()
  }, 250)
}

const toggleCheckboxOption = (nameKey: string, option: string) => {
  if (!Array.isArray(customResponses.value[nameKey])) {
    customResponses.value[nameKey] = []
  }
  const arr: string[] = customResponses.value[nameKey]
  const idx = arr.indexOf(option)
  if (idx > -1) {
    arr.splice(idx, 1)
  } else {
    arr.push(option)
  }
}

const submitForm = async () => {
  isSubmitting.value = true
  validationError.value = ''

  try {
    const payload = {
      name: name.value,
      company: company.value || null,
      email: email.value || null,
      phone: phone.value || null,
      title: title.value || null,
      notes: notes.value || null,
      custom_responses: customResponses.value,
    }

    const res = await crmApi.submitPublicLeadForm(idOrSlug, payload)
    submissionResult.value = res.data
    isSubmitted.value = true

    if (res.data.redirect_url) {
      setTimeout(() => {
        window.location.href = res.data.redirect_url!
      }, 3500)
    }
  } catch (err: any) {
    validationError.value = err?.response?.data?.message || 'Failed to submit form. Please try again.'
  } finally {
    isSubmitting.value = false
  }
}

// Keyboard shortcuts (Enter to proceed)
const handleKeyDown = (e: KeyboardEvent) => {
  if (isSubmitted.value) return
  if (e.key === 'Enter' && !e.shiftKey) {
    // Only proceed on enter if not in a multi-line textarea
    const target = e.target as HTMLElement
    if (target.tagName !== 'TEXTAREA') {
      e.preventDefault()
      nextStep()
    }
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleKeyDown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown)
})
</script>

<template>
  <div class="min-h-screen bg-slate-950 text-white flex flex-col justify-between selection:bg-primary-500 selection:text-white font-sans">
    <!-- Progress Bar -->
    <div class="fixed top-0 left-0 right-0 h-1.5 bg-slate-800 z-50">
      <div
        class="h-full bg-linear-to-r from-primary-500 to-indigo-500 transition-all duration-500 ease-out rounded-r-full"
        :style="{ width: `${progressPercent}%` }"
      ></div>
    </div>

    <!-- Top Branding -->
    <header class="p-6 md:px-12 flex items-center justify-between z-10">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-xl bg-primary-600 flex items-center justify-center font-black text-sm text-white shadow-xs">
          <Sparkles class="w-4 h-4" />
        </div>
        <span class="font-bold text-sm tracking-wide text-slate-300">{{ companyName }}</span>
      </div>

      <div v-if="!isSubmitted && currentStepIndex > 0" class="text-xs font-mono text-slate-400">
        Step {{ currentStepIndex }} of {{ totalSteps - 1 }}
      </div>
    </header>

    <!-- Main Wizard Container -->
    <main class="max-w-2xl mx-auto px-6 py-10 w-full flex-1 flex flex-col justify-center">
      <!-- Loading State -->
      <div v-if="isLoading" class="py-20 flex justify-center">
        <UiSpinner size="lg" />
      </div>

      <!-- Error State -->
      <div v-else-if="error || !form" class="p-10 bg-slate-900/80 rounded-3xl border border-slate-800 text-center space-y-4 shadow-xl">
        <AlertCircle class="w-12 h-12 text-red-400 mx-auto" />
        <h2 class="text-xl font-bold">Inquiry Form Not Found</h2>
        <p class="text-xs text-slate-400 max-w-sm mx-auto">
          This form may have been deactivated or has an incorrect link.
        </p>
      </div>

      <!-- Success Screen -->
      <div v-else-if="isSubmitted" class="p-8 md:p-12 bg-slate-900/80 rounded-3xl border border-slate-800 text-center space-y-6 shadow-2xl animate-fade-in">
        <div class="w-20 h-20 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 mx-auto flex items-center justify-center">
          <CheckCircle2 class="w-10 h-10" />
        </div>

        <div class="space-y-2">
          <h2 class="text-2xl md:text-3xl font-black text-white">
            {{ submissionResult?.thank_you_title || 'Thank You!' }}
          </h2>
          <p class="text-sm text-slate-300 max-w-md mx-auto leading-relaxed">
            {{ submissionResult?.message }}
          </p>
        </div>

        <div v-if="submissionResult?.redirect_url" class="text-xs text-slate-400 pt-2 animate-pulse">
          Redirecting you automatically...
        </div>
      </div>

      <!-- Step-by-Step Interactive Form -->
      <div v-else class="space-y-8">
        <!-- Error Toast -->
        <div v-if="validationError" class="p-4 bg-red-500/10 border border-red-500/30 rounded-2xl text-xs text-red-300 font-semibold flex items-center justify-between">
          <span>{{ validationError }}</span>
          <button type="button" @click="validationError = ''" class="text-red-400 font-bold ml-2">✕</button>
        </div>

        <!-- Step 0: Welcome Screen -->
        <div v-if="currentStepIndex === 0" class="space-y-6 text-center animate-fade-in">
          <div class="space-y-3">
            <h1 class="text-3xl md:text-4xl font-black text-white tracking-tight leading-tight">
              {{ form.headline || form.title }}
            </h1>
            <p class="text-sm md:text-base text-slate-400 max-w-lg mx-auto leading-relaxed">
              {{ form.description || 'Answer a few quick questions to receive a tailored consultation.' }}
            </p>
          </div>

          <div class="pt-4">
            <UiButton size="lg" @click="nextStep" class="px-8 py-3.5 text-base font-bold shadow-lg shadow-primary-500/20">
              Get Started <ArrowRight class="w-5 h-5 ml-2" />
            </UiButton>
          </div>
        </div>

        <!-- Step 1: Name & Organization -->
        <div v-else-if="currentStepIndex === 1" class="space-y-6 animate-fade-in">
          <div class="space-y-1.5">
            <span class="text-xs font-bold uppercase tracking-wider text-primary-400 font-mono">Question 1</span>
            <h2 class="text-2xl md:text-3xl font-black text-white tracking-tight">
              What's your name and company?
            </h2>
            <p class="text-xs text-slate-400">Let us know who we're speaking with.</p>
          </div>

          <div class="space-y-4">
            <div class="space-y-1">
              <label class="text-xs font-bold uppercase tracking-wider text-slate-400">Your Full Name *</label>
              <input
                v-model="name"
                type="text"
                placeholder="Jane Doe"
                autofocus
                class="w-full px-4 py-3.5 bg-slate-900 border border-slate-800 focus:border-primary-500 rounded-2xl text-base text-white focus:outline-none focus:ring-2 focus:ring-primary-500/20 transition-all placeholder:text-slate-600"
              />
            </div>

            <div class="space-y-1">
              <label class="text-xs font-bold uppercase tracking-wider text-slate-400">Company / Organization</label>
              <input
                v-model="company"
                type="text"
                placeholder="Acme Corp"
                class="w-full px-4 py-3.5 bg-slate-900 border border-slate-800 focus:border-primary-500 rounded-2xl text-base text-white focus:outline-none focus:ring-2 focus:ring-primary-500/20 transition-all placeholder:text-slate-600"
              />
            </div>
          </div>
        </div>

        <!-- Step 2: Email & Phone -->
        <div v-else-if="currentStepIndex === 2" class="space-y-6 animate-fade-in">
          <div class="space-y-1.5">
            <span class="text-xs font-bold uppercase tracking-wider text-primary-400 font-mono">Question 2</span>
            <h2 class="text-2xl md:text-3xl font-black text-white tracking-tight">
              How can our team contact you?
            </h2>
            <p class="text-xs text-slate-400">We'll send your customized proposal and follow-up details here.</p>
          </div>

          <div class="space-y-4">
            <div class="space-y-1">
              <label class="text-xs font-bold uppercase tracking-wider text-slate-400">Work Email Address *</label>
              <input
                v-model="email"
                type="email"
                placeholder="jane@company.com"
                autofocus
                class="w-full px-4 py-3.5 bg-slate-900 border border-slate-800 focus:border-primary-500 rounded-2xl text-base text-white focus:outline-none focus:ring-2 focus:ring-primary-500/20 transition-all placeholder:text-slate-600"
              />
            </div>

            <div class="space-y-1">
              <label class="text-xs font-bold uppercase tracking-wider text-slate-400">Direct Phone Number</label>
              <input
                v-model="phone"
                type="tel"
                placeholder="+1 (555) 000-0000"
                class="w-full px-4 py-3.5 bg-slate-900 border border-slate-800 focus:border-primary-500 rounded-2xl text-base text-white focus:outline-none focus:ring-2 focus:ring-primary-500/20 transition-all placeholder:text-slate-600"
              />
            </div>
          </div>
        </div>

        <!-- Dynamic Custom Question Step -->
        <div v-else-if="currentCustomQuestion" class="space-y-6 animate-fade-in">
          <div class="space-y-1.5">
            <span class="text-xs font-bold uppercase tracking-wider text-primary-400 font-mono">
              Question {{ currentStepIndex }}
            </span>
            <h2 class="text-2xl md:text-3xl font-black text-white tracking-tight">
              {{ currentCustomQuestion.label }}
              <span v-if="currentCustomQuestion.required" class="text-red-400">*</span>
            </h2>
            <p v-if="currentCustomQuestion.help_text" class="text-xs text-slate-400">
              {{ currentCustomQuestion.help_text }}
            </p>
          </div>

          <!-- 1. Single Choice Radio Cards -->
          <div v-if="currentCustomQuestion.type === 'radio'" class="grid grid-cols-1 gap-3">
            <button
              v-for="opt in currentCustomQuestion.options"
              :key="opt"
              type="button"
              @click="selectRadioOption(currentCustomQuestion.name, opt)"
              class="p-4 rounded-2xl border text-left flex items-center justify-between transition-all cursor-pointer group"
              :class="customResponses[currentCustomQuestion.name] === opt ? 'bg-primary-600/20 border-primary-500 text-white' : 'bg-slate-900 border-slate-800 hover:border-slate-700 text-slate-300'"
            >
              <span class="text-sm font-bold">{{ opt }}</span>
              <div
                class="w-5 h-5 rounded-full border flex items-center justify-center transition-colors"
                :class="customResponses[currentCustomQuestion.name] === opt ? 'border-primary-500 bg-primary-500 text-white' : 'border-slate-700 group-hover:border-slate-500'"
              >
                <Check v-if="customResponses[currentCustomQuestion.name] === opt" class="w-3.5 h-3.5" />
              </div>
            </button>
          </div>

          <!-- 2. Multi-Select Checkbox Cards -->
          <div v-else-if="currentCustomQuestion.type === 'checkbox'" class="grid grid-cols-1 gap-3">
            <button
              v-for="opt in currentCustomQuestion.options"
              :key="opt"
              type="button"
              @click="toggleCheckboxOption(currentCustomQuestion.name, opt)"
              class="p-4 rounded-2xl border text-left flex items-center justify-between transition-all cursor-pointer group"
              :class="(customResponses[currentCustomQuestion.name] || []).includes(opt) ? 'bg-primary-600/20 border-primary-500 text-white' : 'bg-slate-900 border-slate-800 hover:border-slate-700 text-slate-300'"
            >
              <span class="text-sm font-bold">{{ opt }}</span>
              <div
                class="w-5 h-5 rounded-md border flex items-center justify-center transition-colors"
                :class="(customResponses[currentCustomQuestion.name] || []).includes(opt) ? 'border-primary-500 bg-primary-500 text-white' : 'border-slate-700 group-hover:border-slate-500'"
              >
                <Check v-if="(customResponses[currentCustomQuestion.name] || []).includes(opt)" class="w-3.5 h-3.5" />
              </div>
            </button>
          </div>

          <!-- 3. Dropdown Select -->
          <div v-else-if="currentCustomQuestion.type === 'select'">
            <select
              v-model="customResponses[currentCustomQuestion.name]"
              class="w-full px-4 py-3.5 bg-slate-900 border border-slate-800 focus:border-primary-500 rounded-2xl text-base text-white focus:outline-none focus:ring-2 focus:ring-primary-500/20 transition-all cursor-pointer"
            >
              <option value="">{{ currentCustomQuestion.placeholder || 'Select an option...' }}</option>
              <option v-for="opt in currentCustomQuestion.options" :key="opt" :value="opt">{{ opt }}</option>
            </select>
          </div>

          <!-- 4. Textarea -->
          <div v-else-if="currentCustomQuestion.type === 'textarea'">
            <textarea
              v-model="customResponses[currentCustomQuestion.name]"
              rows="4"
              :placeholder="currentCustomQuestion.placeholder || 'Type your response here...'"
              autofocus
              class="w-full px-4 py-3.5 bg-slate-900 border border-slate-800 focus:border-primary-500 rounded-2xl text-base text-white focus:outline-none focus:ring-2 focus:ring-primary-500/20 transition-all placeholder:text-slate-600"
            ></textarea>
          </div>

          <!-- 5. Generic Text / Number / Date -->
          <div v-else>
            <input
              v-model="customResponses[currentCustomQuestion.name]"
              :type="currentCustomQuestion.type === 'number' ? 'number' : currentCustomQuestion.type === 'date' ? 'date' : 'text'"
              :placeholder="currentCustomQuestion.placeholder || 'Your answer'"
              autofocus
              class="w-full px-4 py-3.5 bg-slate-900 border border-slate-800 focus:border-primary-500 rounded-2xl text-base text-white focus:outline-none focus:ring-2 focus:ring-primary-500/20 transition-all placeholder:text-slate-600"
            />
          </div>
        </div>

        <!-- Final Step: Notes & Submission -->
        <div v-else class="space-y-6 animate-fade-in">
          <div class="space-y-1.5">
            <span class="text-xs font-bold uppercase tracking-wider text-primary-400 font-mono">Final Step</span>
            <h2 class="text-2xl md:text-3xl font-black text-white tracking-tight">
              Any additional notes or requirements?
            </h2>
            <p class="text-xs text-slate-400">Share any specific goals or questions before submitting.</p>
          </div>

          <div class="space-y-1">
            <textarea
              v-model="notes"
              rows="4"
              placeholder="Tell us about specific challenges, target milestones, or questions..."
              class="w-full px-4 py-3.5 bg-slate-900 border border-slate-800 focus:border-primary-500 rounded-2xl text-base text-white focus:outline-none focus:ring-2 focus:ring-primary-500/20 transition-all placeholder:text-slate-600"
            ></textarea>
          </div>
        </div>

        <!-- Wizard Navigation Controls -->
        <div v-if="currentStepIndex > 0" class="pt-6 flex items-center justify-between">
          <button
            type="button"
            @click="prevStep"
            class="px-4 py-2 text-xs font-bold text-slate-400 hover:text-white inline-flex items-center gap-1 transition-colors cursor-pointer"
          >
            <ArrowLeft class="w-4 h-4" /> Back
          </button>

          <div class="flex items-center gap-3">
            <span class="text-[11px] text-slate-500 hidden sm:inline">Press <kbd class="px-1.5 py-0.5 bg-slate-800 rounded font-mono text-[10px]">Enter ↵</kbd></span>
            <UiButton
              size="lg"
              :loading="isSubmitting"
              @click="nextStep"
              class="px-6 font-bold shadow-lg shadow-primary-500/20"
            >
              {{ currentStepIndex === totalSteps - 1 ? 'Submit Inquiry' : 'Continue' }}
              <ChevronRight class="w-4 h-4 ml-1" />
            </UiButton>
          </div>
        </div>
      </div>
    </main>

    <!-- Footer -->
    <footer class="p-6 text-center text-xs text-slate-600">
      <p>© {{ new Date().getFullYear() }} {{ companyName }}. Powered by ERP Lead Management.</p>
    </footer>
  </div>
</template>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.3s ease-out forwards;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
