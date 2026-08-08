<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { authApi } from '@/api/auth'
import { useToast } from '@/composables/useToast'
import UiInput from '@/components/ui/UiInput.vue'
import UiButton from '@/components/ui/UiButton.vue'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const email = ref((route.query.email as string) || '')
const token = ref((route.query.token as string) || '')
const password = ref('')
const passwordConfirmation = ref('')
const isLoading = ref(false)

onMounted(() => {
  if (!token.value) {
    toast.error('Reset token is missing. Use the link from your email.')
  }
})

const handleSubmit = async () => {
  if (!email.value || !token.value || !password.value) return
  if (password.value !== passwordConfirmation.value) {
    toast.error('Passwords do not match.')
    return
  }

  isLoading.value = true
  try {
    await authApi.resetPassword({
      email: email.value,
      token: token.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })
    toast.success('Password updated. You can sign in now.')
    router.push({ name: 'login' })
  } catch (error: any) {
    toast.error(error?.response?.data?.message || 'Failed to reset password.')
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div>
    <h3 class="text-xl font-semibold text-slate-900 mb-6 text-center">Reset Password</h3>
    <form @submit.prevent="handleSubmit" class="space-y-6">
      <UiInput
        v-model="email"
        label="Email address"
        type="email"
        placeholder="admin@example.com"
        required
      />
      <UiInput
        v-model="password"
        label="New Password"
        type="password"
        placeholder="••••••••"
        required
      />
      <UiInput
        v-model="passwordConfirmation"
        label="Confirm Password"
        type="password"
        placeholder="••••••••"
        required
      />
      <UiButton type="submit" class="w-full" :loading="isLoading">Reset Password</UiButton>
    </form>
  </div>
</template>
