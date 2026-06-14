<script setup lang="ts">
import { ref } from 'vue'
import UiDrawer from '@/components/ui/UiDrawer.vue'
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
  department_id: '',
  position_id: '',
  start_date: '',
  employment_type: 'full_time',
  status: 'active'
})

const save = async () => {
  await hrApi.createEmployee(form.value as any)
  emit('saved')
  emit('update:modelValue', false)
}
</script>

<template>
  <UiDrawer :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)" title="New Employee">
    <div class="space-y-4">
      <UiInput v-model="form.first_name" label="First Name" />
      <UiInput v-model="form.last_name" label="Last Name" />
      <UiInput v-model="form.email" label="Email" />
      <UiInput v-model="form.employee_number" label="Employee Number" />
      <UiButton @click="save" class="w-full">Save Employee</UiButton>
    </div>
  </UiDrawer>
</template>
