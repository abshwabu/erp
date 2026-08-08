<script setup lang="ts">
import { onMounted, ref } from 'vue'
import apiClient from '@/api/client'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import { useToast } from '@/composables/useToast'

const toast = useToast()
const loading = ref(false)
const form = ref({
  display_name: '',
  timezone: 'UTC',
  currency: 'USD',
})

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
    toast.success('Settings saved')
  } catch (e: any) {
    toast.error(e?.response?.data?.message || 'Failed to save settings')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="space-y-6 max-w-xl">
    <div>
      <h1 class="text-2xl font-bold text-slate-900">Settings</h1>
      <p class="text-sm text-slate-500">Tenant display preferences.</p>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl p-6 space-y-4">
      <UiInput v-model="form.display_name" label="Display Name" />
      <UiInput v-model="form.timezone" label="Timezone" placeholder="UTC" />
      <UiInput v-model="form.currency" label="Currency" placeholder="USD" />
      <div class="flex justify-end">
        <UiButton :loading="loading" @click="save">Save Settings</UiButton>
      </div>
    </div>
  </div>
</template>
