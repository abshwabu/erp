<script setup lang="ts">
import { ref } from 'vue'
import { authApi } from '@/api/auth'
import { useToast } from '@/composables/useToast'
import UiButton from '@/components/ui/UiButton.vue'
import { Mail, ArrowLeft, KeyRound, CheckCircle2, AlertCircle } from '@lucide/vue'

const toast = useToast()
const email = ref('')
const isLoading = ref(false)
const sent = ref(false)
const errorMessage = ref('')

const handleSubmit = async () => {
  if (!email.value) return
  errorMessage.value = ''
  isLoading.value = true
  try {
    await authApi.forgotPassword(email.value)
    sent.value = true
    toast.success('If the email exists, a password reset link has been sent.')
  } catch (error: any) {
    errorMessage.value = error?.response?.data?.message || 'Failed to send reset link. Please try again.'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header Icon -->
    <div class="text-center space-y-2">
      <div class="w-12 h-12 rounded-xl bg-primary-50 text-primary-600 border border-primary-100 flex items-center justify-center mx-auto">
        <KeyRound class="w-6 h-6" />
      </div>
      <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Forgot password?</h2>
      <p class="text-sm text-slate-500">
        No worries. Enter your registered work email and we'll send you recovery instructions.
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

    <!-- Reset Form -->
    <form v-if="!sent" @submit.prevent="handleSubmit" class="space-y-4">
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

      <UiButton
        type="submit"
        class="w-full h-11 text-base shadow-lg shadow-primary-600/20"
        :loading="isLoading"
      >
        Send Recovery Link
      </UiButton>
    </form>

    <!-- Success Feedback -->
    <div v-else class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl space-y-2 text-center">
      <CheckCircle2 class="w-8 h-8 text-emerald-600 mx-auto" />
      <h3 class="text-sm font-semibold text-emerald-900">Check your inbox</h3>
      <p class="text-xs text-emerald-700 leading-relaxed">
        If an account exists for <span class="font-mono font-medium">{{ email }}</span>, you will receive password reset instructions shortly. Link expires in 60 minutes.
      </p>
    </div>

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
