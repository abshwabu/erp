<script setup lang="ts">
import { ref } from 'vue'
import { authApi } from '@/api/auth'
import { useToast } from '@/composables/useToast'
import UiInput from '@/components/ui/UiInput.vue'
import UiButton from '@/components/ui/UiButton.vue'

const toast = useToast()
const email = ref('')
const isLoading = ref(false)
const sent = ref(false)

const handleSubmit = async () => {
  if (!email.value) return
  isLoading.value = true
  try {
    await authApi.forgotPassword(email.value)
    sent.value = true
    toast.success('If the email exists, a reset link has been sent.')
  } catch (error: any) {
    toast.error(error?.response?.data?.message || 'Failed to send reset link.')
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div>
    <h3 class="text-xl font-semibold text-slate-900 mb-6 text-center">Forgot Password</h3>
    <p class="text-sm text-slate-500 mb-6 text-center">
      Enter your email address and we'll send you a link to reset your password.
    </p>
    <form v-if="!sent" @submit.prevent="handleSubmit" class="space-y-6">
      <UiInput
        v-model="email"
        label="Email address"
        type="email"
        placeholder="admin@example.com"
        required
      />
      <UiButton type="submit" class="w-full" :loading="isLoading">Send Reset Link</UiButton>
      <div class="text-center">
        <router-link to="/login" class="text-sm font-medium text-primary-600 hover:text-primary-500">
          Back to login
        </router-link>
      </div>
    </form>
    <div v-else class="text-center space-y-4">
      <p class="text-sm text-slate-600">
        Check your inbox for a password reset link. It expires in 60 minutes.
      </p>
      <router-link to="/login" class="text-sm font-medium text-primary-600 hover:text-primary-500">
        Back to login
      </router-link>
    </div>
  </div>
</template>
