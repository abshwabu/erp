<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import UiButton from '@/components/ui/UiButton.vue'
import {
  Building2,
  Globe,
  User,
  Mail,
  Lock,
  Eye,
  EyeOff,
  Check,
  AlertCircle,
  ArrowRight,
  ShieldCheck,
  Rocket,
} from '@lucide/vue'

const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()

const companyName = ref('')
const domain = ref('')
const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const showPassword = ref(false)
const agreeTerms = ref(true)
const isManualSlug = ref(false)
const isLoading = ref(false)
const errorMessage = ref('')

// Auto-generate subdomain slug from company name
watch(companyName, (val) => {
  if (!isManualSlug.value) {
    domain.value = val
      .toLowerCase()
      .replace(/[^a-z0-9]/g, '')
      .slice(0, 30)
  }
})

const onSlugInput = () => {
  isManualSlug.value = true
  domain.value = domain.value.toLowerCase().replace(/[^a-z0-9]/g, '')
}

// Password strength calculation
const passwordScore = computed(() => {
  const p = password.value
  if (!p) return 0
  let score = 0
  if (p.length >= 8) score++
  if (/[A-Z]/.test(p)) score++
  if (/[0-9]/.test(p)) score++
  if (/[^A-Za-z0-9]/.test(p)) score++
  return score
})

const passwordStrengthLabel = computed(() => {
  switch (passwordScore.value) {
    case 0:
      return { text: 'Very Weak', color: 'text-slate-400', bar: 'bg-slate-200' }
    case 1:
      return { text: 'Weak', color: 'text-red-500', bar: 'bg-red-500' }
    case 2:
      return { text: 'Fair', color: 'text-amber-500', bar: 'bg-amber-500' }
    case 3:
      return { text: 'Good', color: 'text-blue-500', bar: 'bg-blue-500' }
    case 4:
      return { text: 'Strong', color: 'text-emerald-500', bar: 'bg-emerald-500' }
    default:
      return { text: '', color: '', bar: '' }
  }
})

const passwordsMatch = computed(() => {
  return !passwordConfirmation.value || password.value === passwordConfirmation.value
})

const handleRegister = async () => {
  if (!companyName.value || !domain.value || !name.value || !email.value || !password.value) {
    errorMessage.value = 'Please fill in all required fields.'
    return
  }

  if (password.value !== passwordConfirmation.value) {
    errorMessage.value = 'Passwords do not match.'
    return
  }

  if (!agreeTerms.value) {
    errorMessage.value = 'Please agree to the Terms of Service to proceed.'
    return
  }

  errorMessage.value = ''
  isLoading.value = true

  try {
    await authStore.register({
      company_name: companyName.value,
      domain: domain.value,
      name: name.value,
      email: email.value,
      password: password.value,
    })

    toast.success('Workspace created successfully! Welcome to ERP Core.')
    router.push('/')
  } catch (error: any) {
    errorMessage.value =
      error.response?.data?.message ||
      'Registration could not be completed. Please check your details and try again.'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="text-center space-y-1.5">
      <div class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-primary-50 text-primary-700 border border-primary-100">
        <Rocket class="w-3.5 h-3.5 text-primary-600" />
        <span>14-Day Full Access • No Card Required</span>
      </div>
      <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Create your workspace</h2>
      <p class="text-sm text-slate-500">Deploy a multi-tenant business operating system in seconds</p>
    </div>

    <!-- Error Alert -->
    <div
      v-if="errorMessage"
      class="p-3.5 rounded-xl bg-red-50 border border-red-200 flex items-start space-x-3 text-red-700 text-sm animate-shake"
    >
      <AlertCircle class="w-5 h-5 text-red-500 shrink-0 mt-0.5" />
      <span class="leading-relaxed">{{ errorMessage }}</span>
    </div>

    <!-- Form -->
    <form @submit.prevent="handleRegister" class="space-y-4">
      <!-- Company & Domain Section -->
      <div class="space-y-3 p-3.5 bg-slate-50/80 rounded-xl border border-slate-200">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center space-x-1.5">
          <Building2 class="w-3.5 h-3.5 text-primary-600" />
          <span>Organization Details</span>
        </span>

        <div class="space-y-3">
          <!-- Company Name -->
          <div class="space-y-1">
            <label for="companyName" class="block text-xs font-semibold text-slate-700">
              Company / Business Name <span class="text-red-500">*</span>
            </label>
            <input
              id="companyName"
              v-model="companyName"
              type="text"
              required
              placeholder="e.g. Acme Corporation"
              class="w-full px-3.5 py-2 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-600 transition-all"
            />
          </div>

          <!-- Domain Subdomain -->
          <div class="space-y-1">
            <label for="domain" class="block text-xs font-semibold text-slate-700">
              Workspace Subdomain <span class="text-red-500">*</span>
            </label>
            <div class="relative flex items-center">
              <input
                id="domain"
                v-model="domain"
                type="text"
                required
                @input="onSlugInput"
                placeholder="acme"
                class="w-full pl-3.5 pr-28 py-2 bg-white border border-slate-300 rounded-lg text-sm font-mono text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-600 transition-all"
              />
              <span class="absolute right-3 text-xs font-mono text-slate-400 select-none">
                .localhost
              </span>
            </div>
            <p v-if="domain" class="text-xs text-emerald-600 font-mono flex items-center space-x-1 pt-0.5">
              <Check class="w-3 h-3 text-emerald-500" />
              <span>Workspace URL: http://{{ domain }}.localhost:3000</span>
            </p>
          </div>
        </div>
      </div>

      <!-- Administrator Account -->
      <div class="space-y-3 p-3.5 bg-slate-50/80 rounded-xl border border-slate-200">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center space-x-1.5">
          <User class="w-3.5 h-3.5 text-primary-600" />
          <span>Administrator Profile</span>
        </span>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <!-- Full Name -->
          <div class="space-y-1">
            <label for="name" class="block text-xs font-semibold text-slate-700">
              Full Name <span class="text-red-500">*</span>
            </label>
            <div class="relative flex items-center">
              <User class="w-4 h-4 text-slate-400 absolute left-3 pointer-events-none" />
              <input
                id="name"
                v-model="name"
                type="text"
                required
                autocomplete="name"
                placeholder="Jane Doe"
                class="w-full pl-9 pr-3 py-2 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-600 transition-all"
              />
            </div>
          </div>

          <!-- Work Email -->
          <div class="space-y-1">
            <label for="email" class="block text-xs font-semibold text-slate-700">
              Work Email <span class="text-red-500">*</span>
            </label>
            <div class="relative flex items-center">
              <Mail class="w-4 h-4 text-slate-400 absolute left-3 pointer-events-none" />
              <input
                id="email"
                v-model="email"
                type="email"
                required
                autocomplete="email"
                placeholder="jane@company.com"
                class="w-full pl-9 pr-3 py-2 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-600 transition-all"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Security (Passwords) -->
      <div class="space-y-3 p-3.5 bg-slate-50/80 rounded-xl border border-slate-200">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center space-x-1.5">
          <Lock class="w-3.5 h-3.5 text-primary-600" />
          <span>Security & Password</span>
        </span>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <!-- Password -->
          <div class="space-y-1">
            <label for="password" class="block text-xs font-semibold text-slate-700">
              Master Password <span class="text-red-500">*</span>
            </label>
            <div class="relative flex items-center">
              <input
                id="password"
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                required
                autocomplete="new-password"
                placeholder="Min. 8 characters"
                class="w-full pl-3 pr-9 py-2 bg-white border border-slate-300 rounded-lg text-sm font-mono text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-600 transition-all"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute right-2.5 text-slate-400 hover:text-slate-600 p-1"
                tabindex="-1"
              >
                <EyeOff v-if="showPassword" class="w-3.5 h-3.5" />
                <Eye v-else class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>

          <!-- Password Confirmation -->
          <div class="space-y-1">
            <label for="passwordConfirmation" class="block text-xs font-semibold text-slate-700">
              Confirm Password <span class="text-red-500">*</span>
            </label>
            <input
              id="passwordConfirmation"
              v-model="passwordConfirmation"
              type="password"
              required
              autocomplete="new-password"
              placeholder="Re-type password"
              class="w-full px-3 py-2 bg-white border rounded-lg text-sm font-mono text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-600 transition-all"
              :class="passwordsMatch ? 'border-slate-300' : 'border-red-300 bg-red-50/50'"
            />
          </div>
        </div>

        <!-- Password Strength Meter -->
        <div v-if="password" class="space-y-1 pt-1">
          <div class="flex items-center justify-between text-xs">
            <span class="text-slate-500">Strength:</span>
            <span :class="['font-semibold', passwordStrengthLabel.color]">{{ passwordStrengthLabel.text }}</span>
          </div>
          <div class="grid grid-cols-4 gap-1.5 h-1.5 w-full">
            <div
              v-for="step in 4"
              :key="step"
              class="rounded-full transition-colors"
              :class="step <= passwordScore ? passwordStrengthLabel.bar : 'bg-slate-200'"
            ></div>
          </div>
        </div>
      </div>

      <!-- Terms Checkbox -->
      <div class="pt-1">
        <label class="flex items-start space-x-2.5 cursor-pointer text-xs text-slate-600">
          <input
            v-model="agreeTerms"
            type="checkbox"
            required
            class="w-4 h-4 mt-0.5 rounded border-slate-300 text-primary-600 focus:ring-primary-500 cursor-pointer"
          />
          <span>
            I agree to the
            <a href="#" class="text-primary-600 font-medium hover:underline">Terms of Service</a>
            and acknowledge the
            <a href="#" class="text-primary-600 font-medium hover:underline">Privacy Policy</a>.
          </span>
        </label>
      </div>

      <!-- Submit Button -->
      <UiButton
        type="submit"
        class="w-full h-11 text-base shadow-lg shadow-primary-600/20 flex items-center justify-center space-x-2"
        :loading="isLoading"
      >
        <span>Launch My Workspace</span>
        <ArrowRight class="w-4 h-4 ml-1" />
      </UiButton>
    </form>

    <!-- Sign in Link -->
    <div class="text-center pt-2 text-sm text-slate-600">
      Already have a workspace account?
      <router-link
        to="/login"
        class="font-semibold text-primary-600 hover:text-primary-700 hover:underline transition-colors inline-flex items-center ml-1"
      >
        Sign in
      </router-link>
    </div>
  </div>
</template>
