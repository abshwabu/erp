<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useTenantStore } from '@/stores/tenant'
import { useToast } from '@/composables/useToast'
import UiInput from '@/components/ui/UiInput.vue'
import UiButton from '@/components/ui/UiButton.vue'

const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()

const companyName = ref('')
const domain = ref('')
const name = ref('')
const email = ref('')
const password = ref('')
const isLoading = ref(false)

const handleRegister = async () => {
  if (!email.value || !password.value || !domain.value || !name.value || !companyName.value) return

  isLoading.value = true
  try {
    await authStore.register({
      company_name: companyName.value,
      domain: domain.value,
      name: name.value,
      email: email.value,
      password: password.value,
    })
    
    toast.success('Account created successfully!')
    router.push('/')
  } catch (error: any) {
    const message = error.response?.data?.message || 'Registration failed. Please try again.'
    toast.error(message)
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div>
    <h3 class="text-xl font-semibold text-slate-900 mb-2 text-center">Get started for free</h3>
    <p class="text-sm text-slate-500 mb-6 text-center">
      Create your company workspace in seconds.
    </p>
    
    <form @submit.prevent="handleRegister" class="space-y-4">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <UiInput
          v-model="companyName"
          label="Company Name"
          placeholder="Acme Corp"
          required
        />
        <UiInput
          v-model="domain"
          label="Workspace URL"
          placeholder="acme"
          required
        >
          <template #suffix>
            <span class="text-xs font-medium text-slate-400">.erp.com</span>
          </template>
        </UiInput>
      </div>

      <UiInput
        v-model="name"
        label="Your Name"
        placeholder="John Doe"
        required
      />
      
      <UiInput
        v-model="email"
        label="Work Email"
        type="email"
        placeholder="john@company.com"
        required
      />
      
      <UiInput
        v-model="password"
        label="Password"
        type="password"
        placeholder="••••••••"
        required
      />

      <div class="text-xs text-slate-500 text-center px-4">
        By signing up, you agree to our 
        <a href="#" class="text-primary-600 hover:underline">Terms of Service</a> and 
        <a href="#" class="text-primary-600 hover:underline">Privacy Policy</a>.
      </div>

      <UiButton type="submit" class="w-full" :loading="isLoading">
        Create My Workspace
      </UiButton>

      <div class="text-center mt-4">
        <p class="text-sm text-slate-600">
          Already have an account? 
          <router-link to="/login" class="font-medium text-primary-600 hover:text-primary-500">
            Sign in
          </router-link>
        </p>
      </div>
    </form>
  </div>
</template>
