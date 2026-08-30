<script setup lang="ts">
import { onMounted, ref } from 'vue'
import apiClient from '@/api/client'
import { authApi } from '@/api/auth'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import { useToast } from '@/composables/useToast'
import { Settings, Lock, Shield, CheckCircle, Eye, EyeOff, KeyRound } from '@lucide/vue'

const toast = useToast()
const loading = ref(false)
const form = ref({
  display_name: '',
  timezone: 'UTC',
  currency: 'USD',
})

// Password change state
const passwordLoading = ref(false)
const showCurrentPassword = ref(false)
const showNewPassword = ref(false)
const passwordForm = ref({
  current_password: '',
  password: '',
  password_confirmation: '',
})
const passwordError = ref('')
const passwordSuccess = ref('')

onMounted(async () => {
  try {
    const res = await apiClient.get('/core/settings')
    form.value = { ...form.value, ...res.data.data }
  } catch {
    // ignore — page still usable
  }
})

async function save() {
  loading.value = true
  try {
    const res = await apiClient.post('/core/settings', form.value)
    form.value = { ...form.value, ...res.data.data }
    toast.success('Settings saved successfully')
  } catch (e: any) {
    toast.error(e?.response?.data?.message || 'Failed to save settings')
  } finally {
    loading.value = false
  }
}

async function changePassword() {
  passwordError.value = ''
  passwordSuccess.value = ''

  if (!passwordForm.value.current_password) {
    passwordError.value = 'Please enter your current password.'
    return
  }

  if (passwordForm.value.password.length < 8) {
    passwordError.value = 'New password must be at least 8 characters long.'
    return
  }

  if (passwordForm.value.password !== passwordForm.value.password_confirmation) {
    passwordError.value = 'New passwords do not match.'
    return
  }

  passwordLoading.value = true
  try {
    const res = await authApi.changePassword({
      current_password: passwordForm.value.current_password,
      password: passwordForm.value.password,
      password_confirmation: passwordForm.value.password_confirmation,
    })
    passwordSuccess.value = res.message || 'Password changed successfully.'
    passwordForm.value = {
      current_password: '',
      password: '',
      password_confirmation: '',
    }
    toast.success('Password changed successfully')
  } catch (e: any) {
    passwordError.value = e?.response?.data?.message || e?.response?.data?.errors?.current_password?.[0] || 'Failed to change password.'
  } finally {
    passwordLoading.value = false
  }
}
</script>

<template>
  <div class="space-y-8 max-w-2xl">
    <div>
      <h1 class="text-2xl font-bold text-slate-900">Settings & Security</h1>
      <p class="text-sm text-slate-500">Manage tenant preferences and account security credentials.</p>
    </div>

    <!-- Tenant Preferences -->
    <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-5 shadow-xs">
      <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
        <Settings class="w-4 h-4 text-primary-600" />
        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Tenant Preferences</h2>
      </div>

      <UiInput v-model="form.display_name" label="Display Name / Organization Name" placeholder="e.g. Acme Corp" />
      
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <UiInput v-model="form.timezone" label="Timezone" placeholder="UTC" />
        <UiInput v-model="form.currency" label="Currency Code" placeholder="USD" />
      </div>

      <div class="flex justify-end pt-2">
        <UiButton :loading="loading" @click="save">Save Preferences</UiButton>
      </div>
    </div>

    <!-- Password & Security Card -->
    <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-5 shadow-xs">
      <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
        <Lock class="w-4 h-4 text-primary-600" />
        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Change Password</h2>
      </div>

      <div v-if="passwordSuccess" class="p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-semibold rounded-xl flex items-center justify-between">
        <span>{{ passwordSuccess }}</span>
        <button type="button" @click="passwordSuccess = ''" class="text-emerald-500 font-bold ml-2">✕</button>
      </div>

      <div v-if="passwordError" class="p-3.5 bg-red-50 border border-red-200 text-red-700 text-xs font-semibold rounded-xl flex items-center justify-between">
        <span>{{ passwordError }}</span>
        <button type="button" @click="passwordError = ''" class="text-red-500 font-bold ml-2">✕</button>
      </div>

      <div class="space-y-4">
        <div class="space-y-1">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Current Password</label>
          <div class="relative flex items-center">
            <input
              v-model="passwordForm.current_password"
              :type="showCurrentPassword ? 'text' : 'password'"
              placeholder="Enter current password"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-mono text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-600"
            />
            <button
              type="button"
              @click="showCurrentPassword = !showCurrentPassword"
              class="absolute right-3 text-slate-400 hover:text-slate-600 p-1"
              tabindex="-1"
            >
              <EyeOff v-if="showCurrentPassword" class="w-4 h-4" />
              <Eye v-else class="w-4 h-4" />
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="space-y-1">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">New Password</label>
            <div class="relative flex items-center">
              <input
                v-model="passwordForm.password"
                :type="showNewPassword ? 'text' : 'password'"
                placeholder="Min. 8 characters"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-mono text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-600"
              />
              <button
                type="button"
                @click="showNewPassword = !showNewPassword"
                class="absolute right-3 text-slate-400 hover:text-slate-600 p-1"
                tabindex="-1"
              >
                <EyeOff v-if="showNewPassword" class="w-4 h-4" />
                <Eye v-else class="w-4 h-4" />
              </button>
            </div>
          </div>

          <div class="space-y-1">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Confirm New Password</label>
            <input
              v-model="passwordForm.password_confirmation"
              type="password"
              placeholder="Re-type new password"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-mono text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-600"
            />
          </div>
        </div>

        <div class="flex justify-end pt-2">
          <UiButton :loading="passwordLoading" @click="changePassword" :disabled="!passwordForm.current_password || !passwordForm.password">
            Update Password
          </UiButton>
        </div>
      </div>
    </div>
  </div>
</template>
