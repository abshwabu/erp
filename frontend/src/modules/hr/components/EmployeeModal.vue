<script setup lang="ts">
import { ref } from 'vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiButton from '@/components/ui/UiButton.vue'
import { hrApi } from '@/api/hr'

defineProps<{ modelValue: boolean }>()
const emit = defineEmits(['update:modelValue', 'saved'])

const form = ref({
  first_name: '',
  last_name: '',
  email: '',
  employee_number: '',
  department_id: '50000000-0000-0000-0000-000000000000', // Needs valid UUID from DB
  position_id: '60000000-0000-0000-0000-000000000000',   // Needs valid UUID from DB
  start_date: '2026-01-01',
  employment_type: 'full_time',
  status: 'active'
})

const errors = ref<Record<string, string[]>>({})
const loading = ref(false)

const validate = () => {
  errors.value = {}
  if (!form.value.first_name) errors.value.first_name = ['First name is required']
  if (!form.value.last_name) errors.value.last_name = ['Last name is required']
  if (!form.value.email) errors.value.email = ['Email is required']
  if (!form.value.employee_number) errors.value.employee_number = ['Employee number is required']
  return Object.keys(errors.value).length === 0
}

const save = async () => {
  if (!validate()) return
  
  loading.value = true
  try {
    await hrApi.createEmployee(form.value as any)
    emit('saved')
    emit('update:modelValue', false)
    form.value = { first_name: '', last_name: '', email: '', employee_number: '', department_id: '50000000-0000-0000-0000-000000000000', position_id: '60000000-0000-0000-0000-000000000000', start_date: '2026-01-01', employment_type: 'full_time', status: 'active' }
  } catch (e: any) {
    if (e.response?.data?.errors) {
      errors.value = e.response.data.errors
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UiModal :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)" title="New Employee">
    <form id="employeeForm" @submit.prevent="save">
      <div class="space-y-4">
        <UiInput v-model="form.first_name" label="First Name" :error="errors.first_name?.[0]" required />
        <UiInput v-model="form.last_name" label="Last Name" :error="errors.last_name?.[0]" required />
        <UiInput v-model="form.email" type="email" label="Email" :error="errors.email?.[0]" required />
        <UiInput v-model="form.employee_number" label="Employee Number" :error="errors.employee_number?.[0]" required />
        <UiInput v-model="form.start_date" type="date" label="Start Date" :error="errors.start_date?.[0]" required />
      </div>
    </form>
    <template #footer>
      <UiButton variant="ghost" @click="emit('update:modelValue', false)" class="mr-2">Cancel</UiButton>
      <UiButton type="submit" form="employeeForm" :loading="loading">Save Employee</UiButton>
    </template>
  </UiModal>
</template>
