<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import UiInput from '@/components/ui/UiInput.vue'
import UiButton from '@/components/ui/UiButton.vue'

const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()

const email = ref('')
const password = ref('')
const isLoading = ref(false)

const handleLogin = async () => {
  isLoading.value = true
  try {
    // Mock login for now
    authStore.setTokens('mock-access-token', 'mock-refresh-token')
    authStore.setUser({
      id: 1,
      name: 'Admin User',
      email: email.value,
      role: 'Administrator',
    }, ['pos.sessions.open', 'inventory.products.view'])
    
    toast.success('Login successful')
    router.push('/')
  } catch (error) {
    toast.error('Login failed')
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div>
    <h3 class="text-xl font-semibold text-slate-900 mb-6 text-center">Sign in to your account</h3>
    
    <form @submit.prevent="handleLogin" class="space-y-6">
      <UiInput
        v-model="email"
        label="Email address"
        type="email"
        placeholder="admin@example.com"
        required
      />
      
      <UiInput
        v-model="password"
        label="Password"
        type="password"
        placeholder="••••••••"
        required
      />

      <div class="flex items-center justify-between">
        <div class="flex items-center">
          <input
            id="remember-me"
            name="remember-me"
            type="checkbox"
            class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-slate-300 rounded"
          />
          <label for="remember-me" class="ml-2 block text-sm text-slate-700">
            Remember me
          </label>
        </div>

        <div class="text-sm">
          <router-link to="/forgot-password" class="font-medium text-primary-600 hover:text-primary-500">
            Forgot password?
          </router-link>
        </div>
      </div>

      <UiButton type="submit" class="w-full" :loading="isLoading">
        Sign in
      </UiButton>
    </form>
  </div>
</template>
