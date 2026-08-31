<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useQuery } from '@tanstack/vue-query'
import { crmApi } from '@/api/crm'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiSpinner from '@/components/ui/UiSpinner.vue'
import {
  Sparkles,
  CheckCircle2,
  AlertCircle,
  Building2,
  Mail,
  Phone,
  User,
  Send,
} from '@lucide/vue'

const route = useRoute()
const idOrSlug = (route.params.slug || route.params.id) as string
const isEmbed = computed(() => route.path.startsWith('/embed/'))

// Form inputs
const name = ref('')
const company = ref('')
const title = ref('')
const email = ref('')
const phone = ref('')
const notes = ref('')
const customResponses = ref<Record<string, any>>({})

const isSubmitting = ref(false)
const isSubmitted = ref(false)
const submissionResult = ref<any>(null)
const errorMessage = ref('')

const { data: formResponse, isLoading, error } = useQuery({
  queryKey: ['public', 'lead-forms', idOrSlug],
  queryFn: () => crmApi.getPublicLeadForm(idOrSlug).then((r) => r.data),
})

const form = computed(() => formResponse.value?.data)
const companyName = computed(() => formResponse.value?.company?.name || 'ERP System')

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

const handleSubmit = async () => {
  if (!name.value.trim()) {
    errorMessage.value = 'Please provide your full name.'
    return
  }
  if (!email.value.trim() && !phone.value.trim()) {
    errorMessage.value = 'Please provide an email address or phone number.'
    return
  }

  // Validate custom required fields
  if (form.value?.custom_questions) {
    for (const q of form.value.custom_questions) {
      if (q.required) {
        const val = customResponses.value[q.name]
        if (val === undefined || val === '' || (Array.isArray(val) && val.length === 0)) {
          errorMessage.value = `Please answer mandatory question: "${q.label}"`
          return
        }
      }
    }
  }

  errorMessage.value = ''
  isSubmitting.value = true

  try {
    const payload = {
      name: name.value,
      company: company.value || null,
      title: title.value || null,
      email: email.value || null,
      phone: phone.value || null,
      notes: notes.value || null,
      custom_responses: customResponses.value,
    }

    const res = await crmApi.submitPublicLeadForm(idOrSlug, payload)
    submissionResult.value = res.data
    isSubmitted.value = true

    if (res.data.redirect_url) {
      setTimeout(() => {
        window.location.href = res.data.redirect_url!
      }, 3000)
    }
  } catch (err: any) {
    errorMessage.value = err?.response?.data?.message || 'Failed to submit form. Please try again.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div
    class="min-h-screen text-slate-900 font-sans flex flex-col justify-between"
    :class="isEmbed ? 'bg-transparent p-2' : 'bg-slate-50 p-4 md:p-8'"
  >
    <!-- Standalone Header -->
    <header v-if="!isEmbed" class="max-w-xl mx-auto w-full pb-6 flex items-center justify-between">
      <div class="flex items-center gap-2.5">
        <div class="w-8 h-8 rounded-xl bg-primary-600 flex items-center justify-center text-white font-black text-sm shadow-xs">
          <Sparkles class="w-4 h-4" />
        </div>
        <span class="font-black text-slate-900 text-sm">{{ companyName }}</span>
      </div>
    </header>

    <!-- Form Container Card -->
    <main class="max-w-xl mx-auto w-full flex-1 flex flex-col justify-center">
      <!-- Loading State -->
      <div v-if="isLoading" class="py-16 flex justify-center">
        <UiSpinner size="lg" />
      </div>

      <!-- Error State -->
      <div v-else-if="error || !form" class="p-8 bg-white rounded-3xl border border-slate-200 text-center space-y-3 shadow-xs">
        <AlertCircle class="w-10 h-10 text-red-500 mx-auto" />
        <h2 class="text-base font-bold text-slate-900">Form Not Available</h2>
        <p class="text-xs text-slate-500">This lead intake form is inactive or does not exist.</p>
      </div>

      <!-- Success Screen -->
      <div v-else-if="isSubmitted" class="p-8 bg-white rounded-3xl border border-slate-200 text-center space-y-5 shadow-xs">
        <div class="w-14 h-14 rounded-full bg-emerald-50 text-emerald-600 border border-emerald-200 mx-auto flex items-center justify-center">
          <CheckCircle2 class="w-7 h-7" />
        </div>

        <div class="space-y-1.5">
          <h2 class="text-xl font-black text-slate-900">
            {{ submissionResult?.thank_you_title || 'Thank You!' }}
          </h2>
          <p class="text-xs text-slate-600 max-w-sm mx-auto leading-relaxed">
            {{ submissionResult?.message }}
          </p>
        </div>

        <div v-if="submissionResult?.redirect_url" class="text-xs text-slate-400 font-medium">
          Redirecting you automatically...
        </div>
      </div>

      <!-- Standard / Embedded Form Body -->
      <div v-else class="p-6 md:p-8 bg-white rounded-3xl border border-slate-200 shadow-xs space-y-6">
        <div>
          <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">
            {{ form.headline || form.title }}
          </h1>
          <p v-if="form.description" class="text-xs text-slate-500 mt-1 leading-relaxed">
            {{ form.description }}
          </p>
        </div>

        <!-- Error Alert -->
        <div v-if="errorMessage" class="p-3 bg-red-50 border border-red-200 rounded-xl text-xs text-red-700 font-semibold flex items-center justify-between">
          <span>{{ errorMessage }}</span>
          <button type="button" @click="errorMessage = ''" class="text-red-500 font-bold ml-2">✕</button>
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
            <UiInput v-model="name" label="Full Name *" placeholder="Jane Doe" required />
            <UiInput v-model="company" label="Company" placeholder="Company Name" />
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
            <UiInput v-model="email" label="Email Address *" type="email" placeholder="jane@company.com" />
            <UiInput v-model="phone" label="Phone Number" type="tel" placeholder="+1..." />
          </div>

          <!-- Dynamic Custom Questions -->
          <div v-if="form.custom_questions && form.custom_questions.length" class="space-y-4 pt-2 border-t border-slate-100">
            <div
              v-for="q in form.custom_questions"
              :key="q.id"
              class="space-y-1.5 p-3.5 bg-slate-50/70 rounded-2xl border border-slate-200/80"
            >
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-800">
                {{ q.label }} <span v-if="q.required" class="text-red-500">*</span>
              </label>
              <p v-if="q.help_text" class="text-[11px] text-slate-500">{{ q.help_text }}</p>

              <!-- Select -->
              <div v-if="q.type === 'select'">
                <select
                  v-model="customResponses[q.name]"
                  :required="q.required"
                  class="w-full px-3.5 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-primary-500"
                >
                  <option value="">{{ q.placeholder || 'Select an option...' }}</option>
                  <option v-for="opt in q.options" :key="opt" :value="opt">{{ opt }}</option>
                </select>
              </div>

              <!-- Radio -->
              <div v-else-if="q.type === 'radio'" class="space-y-1.5 pt-0.5">
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
                    class="h-3.5 w-3.5 text-primary-600 focus:ring-primary-500"
                  />
                  <span>{{ opt }}</span>
                </label>
              </div>

              <!-- Checkbox -->
              <div v-else-if="q.type === 'checkbox'" class="space-y-1.5 pt-0.5">
                <label
                  v-for="opt in q.options"
                  :key="opt"
                  class="flex items-center gap-2 text-xs text-slate-800 font-medium cursor-pointer"
                >
                  <input
                    type="checkbox"
                    :checked="(customResponses[q.name] || []).includes(opt)"
                    @change="toggleCheckboxOption(q.name, opt)"
                    class="h-3.5 w-3.5 rounded text-primary-600 focus:ring-primary-500"
                  />
                  <span>{{ opt }}</span>
                </label>
              </div>

              <!-- Textarea -->
              <div v-else-if="q.type === 'textarea'">
                <textarea
                  v-model="customResponses[q.name]"
                  :required="q.required"
                  :placeholder="q.placeholder || 'Your response...'"
                  rows="3"
                  class="w-full px-3.5 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-primary-500"
                ></textarea>
              </div>

              <!-- Generic Input -->
              <div v-else>
                <input
                  v-model="customResponses[q.name]"
                  :type="q.type === 'number' ? 'number' : q.type === 'date' ? 'date' : 'text'"
                  :required="q.required"
                  :placeholder="q.placeholder || 'Your answer'"
                  class="w-full px-3.5 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-primary-500"
                />
              </div>
            </div>
          </div>

          <!-- Additional Notes -->
          <div class="space-y-1 pt-1">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Additional Project Notes</label>
            <textarea
              v-model="notes"
              rows="3"
              placeholder="Tell us about specific challenges, goals, or timelines..."
              class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500"
            ></textarea>
          </div>

          <!-- Submit Action -->
          <div class="pt-3">
            <UiButton type="submit" size="lg" class="w-full font-bold" :loading="isSubmitting">
              <Send class="w-4 h-4 mr-2" /> Submit Inquiry
            </UiButton>
          </div>
        </form>
      </div>
    </main>

    <!-- Standalone Footer -->
    <footer v-if="!isEmbed" class="max-w-xl mx-auto w-full pt-6 text-center text-xs text-slate-400">
      <p>© {{ new Date().getFullYear() }} {{ companyName }}. Powered by ERP Lead Management.</p>
    </footer>
  </div>
</template>
