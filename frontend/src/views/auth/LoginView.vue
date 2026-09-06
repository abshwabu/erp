<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import UiButton from '@/components/ui/UiButton.vue'
import { Mail, Lock, Eye, EyeOff, AlertCircle, ArrowRight, Sparkles } from '@lucide/vue'

const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()

const email = ref('')
const password = ref('')
const showPassword = ref(false)
const rememberMe = ref(true)
const isLoading = ref(false)
const errorMessage = ref('')

const handleLogin = async () => {
  if (!email.value || !password.value) return

  errorMessage.value = ''
  isLoading.value = true

  try {
    await authStore.login(email.value, password.value)
    toast.success('Signed in successfully!')
    router.push('/dashboard')
  } catch (error: any) {
    errorMessage.value =
      error.response?.data?.message ||
      'Invalid email or password. Please verify your credentials and try again.'
  } finally {
    isLoading.value = false
  }
}

const fillDemo = (demoEmail: string) => {
  email.value = demoEmail
  password.value = 'password123'
  errorMessage.value = ''
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="text-center space-y-1.5">
      <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Welcome back</h2>
      <p class="text-sm text-slate-500">Enter your credentials to access your ERP workspace</p>
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
    <form @submit.prevent="handleLogin" class="space-y-4">
      <!-- Email Field -->
      <div class="space-y-1">
        <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
          Work Email <span class="text-red-500">*</span>
        </label>
        <div class="relative flex items-center">
          <Mail class="w-4 h-4 text-slate-400 absolute left-3.5 pointer-events-none" />
          <input
            id="email"
            v-model="email"
            type="email"
            required
            autocomplete="email"
            placeholder="name@company.com"
            class="w-full pl-10 pr-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-600 transition-all"
          />
        </div>
      </div>

      <!-- Password Field -->
      <div class="space-y-1">
        <div class="flex items-center justify-between">
          <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
            Password <span class="text-red-500">*</span>
          </label>
          <router-link
            to="/forgot-password"
            class="text-xs font-medium text-primary-600 hover:text-primary-700 transition-colors"
          >
            Forgot password?
          </router-link>
        </div>
        <div class="relative flex items-center">
          <Lock class="w-4 h-4 text-slate-400 absolute left-3.5 pointer-events-none" />
          <input
            id="password"
            v-model="password"
            :type="showPassword ? 'text' : 'password'"
            required
            autocomplete="current-password"
            placeholder="••••••••••••"
            class="w-full pl-10 pr-10 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-600 transition-all font-mono"
          />
          <button
            type="button"
            @click="showPassword = !showPassword"
            class="absolute right-3 text-slate-400 hover:text-slate-600 p-1 rounded transition-colors"
            tabindex="-1"
          >
            <EyeOff v-if="showPassword" class="w-4 h-4" />
            <Eye v-else class="w-4 h-4" />
          </button>
        </div>
      </div>

      <!-- Remember Me -->
      <div class="flex items-center justify-between pt-1">
        <label class="flex items-center space-x-2 cursor-pointer select-none text-xs text-slate-600">
          <input
            v-model="rememberMe"
            type="checkbox"
            class="w-4 h-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500 transition-all cursor-pointer"
          />
          <span>Remember this device for 30 days</span>
        </label>
      </div>

      <!-- Submit Button -->
      <UiButton
        type="submit"
        class="w-full h-11 text-base shadow-lg shadow-primary-600/20 flex items-center justify-center space-x-2"
        :loading="isLoading"
      >
        <span>Sign In</span>
        <ArrowRight class="w-4 h-4 ml-1" />
      </UiButton>
    </form>

    <!-- Quick Demo Accounts -->
    <div class="pt-4 border-t border-slate-100">
      <div class="flex items-center justify-center space-x-1.5 text-xs text-slate-400 mb-2.5">
        <Sparkles class="w-3.5 h-3.5 text-amber-500" />
        <span class="font-medium">Quick Demo Autofill:</span>
      </div>
      <div class="grid grid-cols-3 gap-2">
        <button
          type="button"
          @click="fillDemo('owner@example.com')"
          class="px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-medium text-slate-700 bg-slate-50 hover:bg-slate-100 hover:border-slate-300 transition-colors"
        >
          👑 Owner
        </button>
        <button
          type="button"
          @click="fillDemo('admin@example.com')"
          class="px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-medium text-slate-700 bg-slate-50 hover:bg-slate-100 hover:border-slate-300 transition-colors"
        >
          🛠️ Admin
        </button>
        <button
          type="button"
          @click="fillDemo('cashier@example.com')"
          class="px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-medium text-slate-700 bg-slate-50 hover:bg-slate-100 hover:border-slate-300 transition-colors"
        >
          💳 Cashier
        </button>
      </div>
    </div>

    <!-- Registration Link -->
    <div class="text-center pt-2 text-sm text-slate-600">
      Don't have a workspace yet?
      <router-link
        to="/register"
        class="font-semibold text-primary-600 hover:text-primary-700 hover:underline transition-colors inline-flex items-center ml-1"
      >
        Create one for free
      </router-link>
    </div>
  </div>
</template>
