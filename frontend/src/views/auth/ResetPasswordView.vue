<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { authApi } from '@/api/auth'
import { useToast } from '@/composables/useToast'
import UiButton from '@/components/ui/UiButton.vue'
import { Lock, Mail, KeyRound, Eye, EyeOff, AlertCircle, ArrowLeft, CheckCircle2 } from '@lucide/vue'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const email = ref((route.query.email as string) || '')
const token = ref((route.query.token as string) || '')
const password = ref('')
const passwordConfirmation = ref('')
const showPassword = ref(false)
const isLoading = ref(false)
const errorMessage = ref('')

onMounted(() => {
  if (!token.value) {
    errorMessage.value = 'Password reset token is missing or invalid. Please request a new link.'
  }
})

const handleSubmit = async () => {
  if (!email.value || !token.value || !password.value) {
    errorMessage.value = 'Please fill in all required fields.'
    return
  }
  if (password.value !== passwordConfirmation.value) {
    errorMessage.value = 'Passwords do not match.'
    return
  }

  errorMessage.value = ''
  isLoading.value = true

  try {
    await authApi.resetPassword({
      email: email.value,
      token: token.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })
    toast.success('Your password has been updated. You can now sign in.')
    router.push({ name: 'login' })
  } catch (error: any) {
    errorMessage.value = error?.response?.data?.message || 'Failed to reset password. The link may have expired.'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="text-center space-y-2">
      <div class="w-12 h-12 rounded-xl bg-primary-50 text-primary-600 border border-primary-100 flex items-center justify-center mx-auto">
        <KeyRound class="w-6 h-6" />
      </div>
      <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Set new password</h2>
      <p class="text-sm text-slate-500">
        Choose a strong, secure password for your ERP account
      </p>
    </div>

    <!-- Error Alert -->
    <div
      v-if="errorMessage"
      class="p-3.5 rounded-xl bg-red-50 border border-red-200 flex items-start space-x-3 text-red-700 text-sm"
    >
      <AlertCircle class="w-5 h-5 text-red-500 shrink-0 mt-0.5" />
      <span class="leading-relaxed">{{ errorMessage }}</span>
    </div>

    <!-- Form -->
    <form @submit.prevent="handleSubmit" class="space-y-4">
      <div class="space-y-1">
        <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
          Account Email <span class="text-red-500">*</span>
        </label>
        <div class="relative flex items-center">
          <Mail class="w-4 h-4 text-slate-400 absolute left-3.5 pointer-events-none" />
          <input
            id="email"
            v-model="email"
            type="email"
            required
            readonly
            class="w-full pl-10 pr-3.5 py-2.5 bg-slate-100 border border-slate-300 rounded-lg text-sm text-slate-700 font-mono focus:outline-none"
          />
        </div>
      </div>

      <div class="space-y-1">
        <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
          New Password <span class="text-red-500">*</span>
        </label>
        <div class="relative flex items-center">
          <Lock class="w-4 h-4 text-slate-400 absolute left-3.5 pointer-events-none" />
          <input
            id="password"
            v-model="password"
            :type="showPassword ? 'text' : 'password'"
            required
            autocomplete="new-password"
            placeholder="Min. 8 characters"
            class="w-full pl-10 pr-10 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-600 transition-all font-mono"
          />
          <button
            type="button"
            @click="showPassword = !showPassword"
            class="absolute right-3 text-slate-400 hover:text-slate-600 p-1"
            tabindex="-1"
          >
            <EyeOff v-if="showPassword" class="w-4 h-4" />
            <Eye v-else class="w-4 h-4" />
          </button>
        </div>
      </div>

      <div class="space-y-1">
        <label for="passwordConfirmation" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
          Confirm New Password <span class="text-red-500">*</span>
        </label>
        <div class="relative flex items-center">
          <Lock class="w-4 h-4 text-slate-400 absolute left-3.5 pointer-events-none" />
          <input
            id="passwordConfirmation"
            v-model="passwordConfirmation"
            type="password"
            required
            autocomplete="new-password"
            placeholder="Re-type new password"
            class="w-full pl-10 pr-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-600 transition-all font-mono"
          />
        </div>
      </div>

      <UiButton
        type="submit"
        class="w-full h-11 text-base shadow-lg shadow-primary-600/20"
        :loading="isLoading"
      >
        Update Password & Sign In
      </UiButton>
    </form>

    <!-- Back to login -->
    <div class="text-center pt-2">
      <router-link
        to="/login"
        class="inline-flex items-center space-x-1.5 text-sm font-semibold text-primary-600 hover:text-primary-700 hover:underline transition-colors"
      >
        <ArrowLeft class="w-4 h-4" />
        <span>Back to sign in</span>
      </router-link>
    </div>
  </div>
</template>
