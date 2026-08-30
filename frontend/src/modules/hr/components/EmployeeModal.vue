<script setup lang="ts">
import { ref, computed } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import { hrApi } from '@/api/hr'
import UiModal from '@/components/ui/UiModal.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import { User, Briefcase, Plus, Trash2, Heart } from '@lucide/vue'

const props = defineProps<{ modelValue: boolean }>()
const emit = defineEmits(['update:modelValue', 'saved'])

const { data: departments } = useQuery({
  queryKey: ['hr', 'departments'],
  queryFn: () => hrApi.getDepartments().then((res) => res.data),
})

const { data: positions } = useQuery({
  queryKey: ['hr', 'positions'],
  queryFn: () => hrApi.getPositions().then((res) => res.data),
})

const { data: employees } = useQuery({
  queryKey: ['hr', 'employees'],
  queryFn: () => hrApi.getEmployees().then((res) => res.data),
})

const managerOptions = computed(() => [
  { label: 'None (Top Level)', value: '' },
  ...(Array.isArray(employees.value) ? employees.value : (employees.value as any)?.data || []).map((e: any) => ({
    label: `${e.first_name} ${e.last_name}`,
    value: e.id,
  })),
])

const form = ref({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  employee_number: '',
  department_id: '',
  position_id: '',
  manager_id: '',
  employment_type: 'full-time',
  status: 'active',
  start_date: new Date().toISOString().split('T')[0],
  gender: '',
  date_of_birth: '',
  emergency_contacts: [{ name: '', relationship: '', phone: '' }],
})

const loading = ref(false)
const errors = ref<Record<string, string[]>>({})
const generalError = ref('')

const addContact = () => {
  form.value.emergency_contacts.push({ name: '', relationship: '', phone: '' })
}

const removeContact = (index: number) => {
  form.value.emergency_contacts.splice(index, 1)
}

const save = async () => {
  loading.value = true
  errors.value = {}
  generalError.value = ''

  const payload: any = {
    ...form.value,
    department_id: form.value.department_id || null,
    position_id: form.value.position_id || null,
    manager_id: form.value.manager_id || null,
    date_of_birth: form.value.date_of_birth || null,
    phone: form.value.phone || null,
    gender: form.value.gender || null,
    employee_number: form.value.employee_number || null,
    emergency_contacts: form.value.emergency_contacts.filter((c) => c.name || c.phone),
  }

  try {
    await hrApi.createEmployee(payload)
    emit('saved')
    emit('update:modelValue', false)
    resetForm()
  } catch (e: any) {
    if (e.response?.data?.errors) {
      errors.value = e.response.data.errors
    } else {
      generalError.value = e.response?.data?.message || e.message || 'Failed to create employee'
    }
  } finally {
    loading.value = false
  }
}

const resetForm = () => {
  form.value = {
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    employee_number: '',
    department_id: '',
    position_id: '',
    manager_id: '',
    employment_type: 'full-time',
    status: 'active',
    start_date: new Date().toISOString().split('T')[0],
    gender: '',
    date_of_birth: '',
    emergency_contacts: [{ name: '', relationship: '', phone: '' }],
  }
  errors.value = {}
  generalError.value = ''
}

const employmentTypes = [
  { label: 'Full-time', value: 'full-time' },
  { label: 'Part-time', value: 'part-time' },
  { label: 'Contract', value: 'contract' },
  { label: 'Intern', value: 'intern' },
  { label: 'Probationary', value: 'probationary' },
]

const genders = [
  { label: 'Select Gender', value: '' },
  { label: 'Male', value: 'male' },
  { label: 'Female', value: 'female' },
  { label: 'Other', value: 'other' },
]
</script>

<template>
  <UiModal
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
    title="New Employee"
    size="xl"
  >
    <div class="space-y-6">
      <div v-if="generalError" class="rounded-xl border border-red-200 bg-red-50 p-3 text-xs text-red-700 font-medium">
        {{ generalError }}
      </div>

      <!-- Personal Info -->
      <div class="space-y-3">
        <div class="flex items-center gap-2 text-slate-900 font-bold text-sm border-b border-slate-100 pb-2">
          <User class="h-4 w-4 text-primary-600" />
          <span>Personal Information</span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <UiInput v-model="form.first_name" label="First Name" placeholder="John" :error="errors.first_name?.[0]" required />
          <UiInput v-model="form.last_name" label="Last Name" placeholder="Doe" :error="errors.last_name?.[0]" required />
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <UiInput v-model="form.email" label="Email Address" type="email" placeholder="john.doe@company.com" :error="errors.email?.[0]" required />
          <UiInput v-model="form.phone" label="Phone Number" type="tel" placeholder="+1 555 0100" :error="errors.phone?.[0]" />
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <UiSelect v-model="form.gender" label="Gender" :options="genders" />
          <UiInput v-model="form.date_of_birth" label="Date of Birth" type="date" />
        </div>
      </div>

      <!-- Work Info -->
      <div class="space-y-3">
        <div class="flex items-center gap-2 text-slate-900 font-bold text-sm border-b border-slate-100 pb-2">
          <Briefcase class="h-4 w-4 text-primary-600" />
          <span>Work & Position Details</span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <UiInput v-model="form.employee_number" label="Employee ID" placeholder="Leave blank for auto-number" :error="errors.employee_number?.[0]" />
          <UiInput v-model="form.start_date" label="Start Date" type="date" :error="errors.start_date?.[0]" required />
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <UiSelect
            v-model="form.department_id"
            label="Department"
            :options="[{ label: 'Select Department', value: '' }, ...(departments?.map((d) => ({ label: d.name, value: d.id })) || [])]"
            :error="errors.department_id?.[0]"
          />
          <UiSelect
            v-model="form.position_id"
            label="Position"
            :options="[{ label: 'Select Position', value: '' }, ...(positions?.map((p) => ({ label: p.title, value: p.id })) || [])]"
            :error="errors.position_id?.[0]"
          />
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <UiSelect v-model="form.employment_type" label="Employment Type" :options="employmentTypes" />
          <UiSelect
            v-model="form.manager_id"
            label="Direct Manager"
            :options="managerOptions"
            placeholder="Select manager"
          />
        </div>
      </div>

      <!-- Emergency Contacts -->
      <div class="space-y-3">
        <div class="flex items-center justify-between border-b border-slate-100 pb-2">
          <div class="flex items-center gap-2 text-slate-900 font-bold text-sm">
            <Heart class="h-4 w-4 text-red-500" />
            <span>Emergency Contacts</span>
          </div>
          <UiButton variant="ghost" size="sm" type="button" @click="addContact">
            <Plus class="h-4 w-4 mr-1" /> Add Contact
          </UiButton>
        </div>

        <div v-for="(contact, index) in form.emergency_contacts" :key="index" class="p-3.5 bg-slate-50/70 rounded-xl border border-slate-200/90 relative group space-y-2">
          <button
            v-if="form.emergency_contacts.length > 1"
            type="button"
            @click="removeContact(index)"
            class="absolute -top-2 -right-2 p-1.5 bg-white border border-slate-200 rounded-full text-slate-400 hover:text-red-600 shadow-xs opacity-0 group-hover:opacity-100 transition-opacity"
          >
            <Trash2 class="h-3.5 w-3.5" />
          </button>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <UiInput v-model="contact.name" label="Contact Name" placeholder="Full name" size="sm" />
            <UiInput v-model="contact.relationship" label="Relationship" placeholder="e.g. Spouse, Parent" size="sm" />
            <UiInput v-model="contact.phone" label="Phone" type="tel" placeholder="+1..." size="sm" />
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
        <UiButton variant="outline" type="button" @click="emit('update:modelValue', false)">Cancel</UiButton>
        <UiButton
          type="button"
          @click="save"
          :loading="loading"
          :disabled="!form.first_name || !form.last_name || !form.email"
        >
          Create Employee
        </UiButton>
      </div>
    </div>
  </UiModal>
</template>
