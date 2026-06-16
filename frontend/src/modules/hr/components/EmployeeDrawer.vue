<script setup lang="ts">
import { ref } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import { hrApi } from '@/api/hr'
import UiDrawer from '@/components/ui/UiDrawer.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import { User, Briefcase, MapPin, Calendar, Plus, Trash2, Heart } from '@lucide/vue'

const props = defineProps<{ modelValue: boolean }>()
const emit = defineEmits(['update:modelValue', 'saved'])

const { data: departments } = useQuery({
  queryKey: ['hr', 'departments'],
  queryFn: () => hrApi.getDepartments().then(res => res.data)
})

const { data: positions } = useQuery({
  queryKey: ['hr', 'positions'],
  queryFn: () => hrApi.getPositions().then(res => res.data)
})

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
  emergency_contacts: [
    { name: '', relationship: '', phone: '' }
  ]
})

const loading = ref(false)
const errors = ref<Record<string, string[]>>({})

const addContact = () => {
  form.value.emergency_contacts.push({ name: '', relationship: '', phone: '' })
}

const removeContact = (index: number) => {
  form.value.emergency_contacts.splice(index, 1)
}

const save = async () => {
  loading.value = true
  errors.value = {}
  
  // Debugging
  console.log('Form data being submitted:', form.value)
  
  try {
    await hrApi.createEmployee(form.value as any)
    emit('saved')
    emit('update:modelValue', false)
    resetForm()
  } catch (e: any) {
    console.error('API Error:', e.response?.data)
    if (e.response?.data?.errors) {
      errors.value = e.response.data.errors
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
    emergency_contacts: [{ name: '', relationship: '', phone: '' }]
  }
}

const employmentTypes = [
  { label: 'Full-time', value: 'full-time' },
  { label: 'Part-time', value: 'part-time' },
  { label: 'Contract', value: 'contract' },
  { label: 'Intern', value: 'intern' },
  { label: 'Probationary', value: 'probationary' },
]

const genders = [
  { label: 'Male', value: 'male' },
  { label: 'Female', value: 'female' },
  { label: 'Other', value: 'other' },
]
</script>

<template>
  <UiDrawer 
    :model-value="modelValue" 
    @update:model-value="emit('update:modelValue', $event)" 
    title="Add New Employee"
    size="lg"
  >
    <div class="space-y-8 pb-20">
      <!-- Section: Personal Info -->
      <section class="space-y-4">
        <div class="flex items-center gap-2 text-slate-900 font-bold border-b border-slate-100 pb-2">
          <User class="h-4 w-4" />
          <span>Personal Information</span>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <UiInput v-model="form.first_name" label="First Name" placeholder="John" :error="errors.first_name?.[0]" />
          <UiInput v-model="form.last_name" label="Last Name" placeholder="Doe" :error="errors.last_name?.[0]" />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <UiInput v-model="form.email" label="Email" type="email" placeholder="john.doe@company.com" :error="errors.email?.[0]" />
          <UiInput v-model="form.phone" label="Phone" type="tel" placeholder="+1..." pattern="[0-9+\s\-]+" title="Please enter a valid phone number" :error="errors.phone?.[0]" />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <UiSelect v-model="form.gender" label="Gender" :options="genders" />
          <UiInput v-model="form.date_of_birth" label="Date of Birth" type="date" />
        </div>
      </section>

      <!-- Section: Work Info -->
      <section class="space-y-4">
        <div class="flex items-center gap-2 text-slate-900 font-bold border-b border-slate-100 pb-2">
          <Briefcase class="h-4 w-4" />
          <span>Work Details</span>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <UiInput v-model="form.employee_number" label="Employee ID" placeholder="EMP-001" :error="errors.employee_number?.[0]" />
          <UiInput v-model="form.start_date" label="Start Date" type="date" :error="errors.start_date?.[0]" />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <UiSelect 
            v-model="form.department_id" 
            label="Department" 
            :options="departments?.map(d => ({ label: d.name, value: d.id })) || []" 
            :error="errors.department_id?.[0]"
          />
          <UiSelect 
            v-model="form.position_id" 
            label="Position" 
            :options="positions?.map(p => ({ label: p.title, value: p.id })) || []" 
            :error="errors.position_id?.[0]"
          />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <UiSelect v-model="form.employment_type" label="Employment Type" :options="employmentTypes" />
          <UiSelect 
            v-model="form.manager_id" 
            label="Direct Manager" 
            :options="[]" 
            placeholder="Search manager..."
          />
        </div>
      </section>

      <!-- Section: Emergency Contacts -->
      <section class="space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-2">
          <div class="flex items-center gap-2 text-slate-900 font-bold">
            <Heart class="h-4 w-4" />
            <span>Emergency Contacts</span>
          </div>
          <UiButton variant="ghost" size="sm" @click="addContact">
            <Plus class="h-4 w-4 mr-1" /> Add
          </UiButton>
        </div>
        
        <div v-for="(contact, index) in form.emergency_contacts" :key="index" class="p-4 bg-slate-50 rounded-xl border border-slate-200 relative group">
          <button 
            v-if="form.emergency_contacts.length > 1"
            @click="removeContact(index)"
            class="absolute -top-2 -right-2 p-1.5 bg-white border border-slate-200 rounded-full text-slate-400 hover:text-red-600 shadow-sm opacity-0 group-hover:opacity-100 transition-opacity"
          >
            <Trash2 class="h-3.5 w-3.5" />
          </button>
          
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <UiInput v-model="contact.name" label="Name" placeholder="Full name" size="sm" />
            <UiInput v-model="contact.relationship" label="Relationship" placeholder="e.g. Spouse" size="sm" />
            <UiInput v-model="contact.phone" label="Phone" type="tel" placeholder="+1..." pattern="[0-9+\s\-]+" title="Please enter a valid phone number" size="sm" />
          </div>
        </div>
      </section>
    </div>

    <template #footer>
      <div class="flex justify-end gap-3 w-full bg-white p-4 border-t border-slate-100">
        <UiButton variant="outline" @click="emit('update:modelValue', false)">Cancel</UiButton>
        <UiButton @click="save" :loading="loading">Create Employee</UiButton>
      </div>
    </template>
  </UiDrawer>
</template>
