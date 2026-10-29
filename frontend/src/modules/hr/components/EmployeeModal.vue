<script setup lang="ts">
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { hrApi } from '@/api/hr'
import UiModal from '@/components/ui/UiModal.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import { User, Briefcase, Plus, Trash2, Heart, DollarSign, CreditCard } from '@lucide/vue'

const { t } = useI18n()
const props = defineProps<{ modelValue: boolean }>()
const emit = defineEmits(['update:modelValue', 'saved'])

const queryClient = useQueryClient()

// Queries
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
  { label: t('hr.employeeModal.topLevel'), value: '' },
  ...(Array.isArray(employees.value) ? employees.value : (employees.value as any)?.data || []).map((e: any) => ({
    label: `${e.first_name} ${e.last_name}`,
    value: e.id,
  })),
])

// Main Employee Form
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
  base_salary: '',
  salary_currency: 'USD',
  salary_type: 'monthly',
  payment_method: 'bank_transfer',
  bank_name: '',
  bank_account_number: '',
  bank_routing_number: '',
  emergency_contacts: [{ name: '', relationship: '', phone: '' }],
})

const loading = ref(false)
const errors = ref<Record<string, string[]>>({})
const generalError = ref('')

// Quick Department Creation State
const isQuickDeptModalOpen = ref(false)
const quickDeptForm = ref({
  name: '',
  code: '',
  parent_id: '',
})
const quickDeptError = ref('')

// Quick Position Creation State
const isQuickPositionModalOpen = ref(false)
const quickPositionForm = ref({
  title: '',
  department_id: '',
  job_grade: '',
  description: '',
})
const quickPositionError = ref('')

// Department Mutation
const createDeptMutation = useMutation({
  mutationFn: (data: any) => hrApi.createDepartment(data),
  onSuccess: (res) => {
    queryClient.invalidateQueries({ queryKey: ['hr', 'departments'] })
    form.value.department_id = res.data.id
    isQuickDeptModalOpen.value = false
    quickDeptForm.value = { name: '', code: '', parent_id: '' }
    quickDeptError.value = ''
  },
  onError: (err: any) => {
    quickDeptError.value = err?.response?.data?.message || err?.message || t('hr.employeeModal.quickDept.error')
  },
})

// Position Mutation
const createPositionMutation = useMutation({
  mutationFn: (data: any) => hrApi.createPosition(data),
  onSuccess: (res) => {
    queryClient.invalidateQueries({ queryKey: ['hr', 'positions'] })
    form.value.position_id = res.data.id
    isQuickPositionModalOpen.value = false
    quickPositionForm.value = { title: '', department_id: '', job_grade: '', description: '' }
    quickPositionError.value = ''
  },
  onError: (err: any) => {
    quickPositionError.value = err?.response?.data?.message || err?.message || t('hr.employeeModal.quickPosition.error')
  },
})

const openQuickDeptModal = () => {
  quickDeptError.value = ''
  quickDeptForm.value = { name: '', code: '', parent_id: '' }
  isQuickDeptModalOpen.value = true
}

const openQuickPositionModal = () => {
  quickPositionError.value = ''
  quickPositionForm.value = {
    title: '',
    department_id: form.value.department_id || (departments.value?.[0]?.id ?? ''),
    job_grade: '',
    description: '',
  }
  isQuickPositionModalOpen.value = true
}

const handleSaveQuickDept = () => {
  if (!quickDeptForm.value.name) return
  createDeptMutation.mutate({
    name: quickDeptForm.value.name,
    code: quickDeptForm.value.code || null,
    parent_id: quickDeptForm.value.parent_id || null,
  })
}

const handleSaveQuickPosition = () => {
  if (!quickPositionForm.value.title || !quickPositionForm.value.department_id) return
  createPositionMutation.mutate({
    title: quickPositionForm.value.title,
    department_id: quickPositionForm.value.department_id,
    job_grade: quickPositionForm.value.job_grade || null,
    description: quickPositionForm.value.description || null,
  })
}

const employmentTypes = computed(() => [
  { label: t('hr.employees.types.fullTime'), value: 'full-time' },
  { label: t('hr.employees.types.partTime'), value: 'part-time' },
  { label: t('hr.employees.types.contract'), value: 'contract' },
  { label: t('hr.employees.types.intern'), value: 'intern' },
  { label: t('hr.employees.types.probationary'), value: 'probationary' },
])

const genders = computed(() => [
  { label: t('hr.employeeModal.selectGender'), value: '' },
  { label: t('hr.employeeModal.genders.male'), value: 'male' },
  { label: t('hr.employeeModal.genders.female'), value: 'female' },
  { label: t('hr.employeeModal.genders.other'), value: 'other' },
])

const salaryTypes = computed(() => [
  { label: t('hr.employeeModal.frequencies.monthly'), value: 'monthly' },
  { label: t('hr.employeeModal.frequencies.hourly'), value: 'hourly' },
  { label: t('hr.employeeModal.frequencies.yearly'), value: 'yearly' },
  { label: t('hr.employeeModal.frequencies.weekly'), value: 'weekly' },
])

const currencyOptions = [
  { label: 'USD ($)', value: 'USD' },
  { label: 'EUR (€)', value: 'EUR' },
  { label: 'GBP (£)', value: 'GBP' },
  { label: 'ETB (Br)', value: 'ETB' },
  { label: 'CAD ($)', value: 'CAD' },
  { label: 'AUD ($)', value: 'AUD' },
]

const paymentMethods = computed(() => [
  { label: t('hr.employeeModal.methods.bankTransfer'), value: 'bank_transfer' },
  { label: t('hr.employeeModal.methods.cash'), value: 'cash' },
  { label: t('hr.employeeModal.methods.cheque'), value: 'cheque' },
])

const addContact = () => {
  form.value.emergency_contacts.push({ name: '', relationship: '', phone: '' })
}

const removeContact = (index: number) => {
  form.value.emergency_contacts.splice(index, 1)
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
    base_salary: '',
    salary_currency: 'USD',
    salary_type: 'monthly',
    payment_method: 'bank_transfer',
    bank_name: '',
    bank_account_number: '',
    bank_routing_number: '',
    emergency_contacts: [{ name: '', relationship: '', phone: '' }],
  }
  errors.value = {}
  generalError.value = ''
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
    gender: form.value.gender || null,
    date_of_birth: form.value.date_of_birth || null,
    phone: form.value.phone || null,
    base_salary: form.value.base_salary ? Number(form.value.base_salary) : null,
    bank_name: form.value.bank_name || null,
    bank_account_number: form.value.bank_account_number || null,
    bank_routing_number: form.value.bank_routing_number || null,
    emergency_contacts: form.value.emergency_contacts.filter((c) => c.name || c.phone),
  }

  try {
    await hrApi.createEmployee(payload)
    emit('saved')
    emit('update:modelValue', false)
    resetForm()
  } catch (err: any) {
    if (err.response?.data?.errors) {
      errors.value = err.response.data.errors
    } else {
      generalError.value = err.response?.data?.message || err.message || t('hr.employeeModal.errors.general')
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UiModal
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
    :title="t('hr.employeeModal.createTitle')"
    size="xl"
  >
    <form @submit.prevent="save" class="space-y-6">
      <!-- General Error Alert -->
      <div v-if="generalError" class="rounded-xl border border-red-200 bg-red-50 p-4 text-xs font-semibold text-red-700">
        {{ generalError }}
      </div>

      <!-- Personal Info -->
      <div class="space-y-3">
        <div class="flex items-center gap-2 text-slate-900 font-bold text-sm border-b border-slate-100 pb-2">
          <User class="h-4 w-4 text-primary-600" />
          <span>{{ t('hr.employeeModal.personalInfo') }}</span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <UiInput v-model="form.first_name" :label="t('hr.employeeModal.firstName')" :error="errors.first_name?.[0]" required />
          <UiInput v-model="form.last_name" :label="t('hr.employeeModal.lastName')" :error="errors.last_name?.[0]" required />
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <UiInput v-model="form.email" :label="t('hr.employeeModal.email')" type="email" :error="errors.email?.[0]" required />
          <UiInput v-model="form.phone" :label="t('hr.employeeModal.phone')" type="tel" :error="errors.phone?.[0]" :placeholder="t('hr.employeeModal.phonePlaceholder')" />
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <UiSelect v-model="form.gender" :label="t('hr.employeeModal.gender')" :options="genders" />
          <UiInput v-model="form.date_of_birth" :label="t('hr.employeeModal.dateOfBirth')" type="date" />
        </div>
      </div>

      <!-- Work Info -->
      <div class="space-y-3">
        <div class="flex items-center gap-2 text-slate-900 font-bold text-sm border-b border-slate-100 pb-2">
          <Briefcase class="h-4 w-4 text-primary-600" />
          <span>{{ t('hr.employeeModal.workDetails') }}</span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <UiInput v-model="form.employee_number" :label="t('hr.employeeModal.employeeId')" :placeholder="t('hr.employeeModal.employeeIdPlaceholder')" :error="errors.employee_number?.[0]" />
          <UiInput v-model="form.start_date" :label="t('hr.employeeModal.startDate')" type="date" :error="errors.start_date?.[0]" required />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Department Select with inline Header + New link button -->
          <div class="space-y-1.5">
            <div class="flex justify-between items-center">
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">{{ t('hr.employeeModal.department') }}</label>
              <button
                type="button"
                @click="openQuickDeptModal"
                class="text-xs text-blue-600 hover:text-blue-700 font-bold inline-flex items-center gap-1 hover:underline"
              >
                <Plus class="w-3.5 h-3.5" /> {{ t('hr.employeeModal.newDepartment') }}
              </button>
            </div>
            <UiSelect
              v-model="form.department_id"
              :options="[{ label: t('hr.employeeModal.selectDepartment'), value: '' }, ...(departments?.map((d) => ({ label: d.name, value: d.id })) || [])]"
              :error="errors.department_id?.[0]"
              :placeholder="t('hr.employeeModal.selectDepartment')"
            />
          </div>

          <!-- Position Select with inline Header + New link button -->
          <div class="space-y-1.5">
            <div class="flex justify-between items-center">
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">{{ t('hr.employeeModal.position') }}</label>
              <button
                type="button"
                @click="openQuickPositionModal"
                class="text-xs text-blue-600 hover:text-blue-700 font-bold inline-flex items-center gap-1 hover:underline"
              >
                <Plus class="w-3.5 h-3.5" /> {{ t('hr.employeeModal.newPosition') }}
              </button>
            </div>
            <UiSelect
              v-model="form.position_id"
              :options="[{ label: t('hr.employeeModal.selectPosition'), value: '' }, ...(positions?.map((p) => ({ label: p.title, value: p.id })) || [])]"
              :error="errors.position_id?.[0]"
              :placeholder="t('hr.employeeModal.selectPosition')"
            />
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <UiSelect v-model="form.employment_type" :label="t('hr.employeeModal.employmentType')" :options="employmentTypes" />
          <UiSelect
            v-model="form.manager_id"
            :label="t('hr.employeeModal.directManager')"
            :options="managerOptions"
            :placeholder="t('hr.employeeModal.selectManager')"
          />
        </div>
      </div>

      <!-- Salary & Compensation Details -->
      <div class="space-y-3">
        <div class="flex items-center gap-2 text-slate-900 font-bold text-sm border-b border-slate-100 pb-2">
          <DollarSign class="h-4 w-4 text-emerald-600" />
          <span>{{ t('hr.employeeModal.compensationDetails') }}</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <UiInput
            v-model="form.base_salary"
            :label="t('hr.employeeModal.baseSalary')"
            type="number"
            step="0.01"
            min="0"
            :placeholder="t('hr.employeeModal.baseSalaryPlaceholder')"
            :error="errors.base_salary?.[0]"
          />
          <UiSelect v-model="form.salary_currency" :label="t('hr.employeeModal.currency')" :options="currencyOptions" />
          <UiSelect v-model="form.salary_type" :label="t('hr.employeeModal.payFrequency')" :options="salaryTypes" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UiSelect v-model="form.payment_method" :label="t('hr.employeeModal.paymentMethod')" :options="paymentMethods" />
          <UiInput
            v-if="form.payment_method === 'bank_transfer'"
            v-model="form.bank_name"
            :label="t('hr.employeeModal.bankName')"
            :placeholder="t('hr.employeeModal.bankNamePlaceholder')"
          />
        </div>

        <div v-if="form.payment_method === 'bank_transfer'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UiInput v-model="form.bank_account_number" :label="t('hr.employeeModal.accountNumber')" :placeholder="t('hr.employeeModal.accountNumberPlaceholder')" />
          <UiInput v-model="form.bank_routing_number" :label="t('hr.employeeModal.routingNumber')" :placeholder="t('hr.employeeModal.routingNumberPlaceholder')" />
        </div>
      </div>

      <!-- Emergency Contacts -->
      <div class="space-y-3">
        <div class="flex items-center justify-between border-b border-slate-100 pb-2">
          <div class="flex items-center gap-2 text-slate-900 font-bold text-sm">
            <Heart class="h-4 w-4 text-red-500" />
            <span>{{ t('hr.employeeModal.emergencyContacts') }}</span>
          </div>
          <UiButton variant="ghost" size="sm" type="button" @click="addContact">
            <Plus class="h-4 w-4 mr-1" /> {{ t('hr.employeeModal.addContact') }}
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
            <UiInput v-model="contact.name" :label="t('hr.employeeModal.contactName')" :placeholder="t('hr.employeeModal.contactNamePlaceholder')" size="sm" />
            <UiInput v-model="contact.relationship" :label="t('hr.employeeModal.relationship')" :placeholder="t('hr.employeeModal.relationshipPlaceholder')" size="sm" />
            <UiInput v-model="contact.phone" :label="t('hr.employeeModal.phone')" type="tel" :placeholder="t('hr.employeeModal.phonePlaceholder')" size="sm" />
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
        <UiButton variant="outline" type="button" @click="emit('update:modelValue', false)">
          {{ t('hr.employeeModal.cancel') }}
        </UiButton>
        <UiButton type="submit" :loading="loading">
          {{ t('hr.employeeModal.submit') }}
        </UiButton>
      </div>
    </form>

    <!-- Quick Create Department Sub-Modal -->
    <UiModal v-model="isQuickDeptModalOpen" :title="t('hr.employeeModal.quickDept.title')" size="md">
      <div class="space-y-4">
        <div v-if="quickDeptError" class="rounded-xl border border-red-200 bg-red-50 p-3 text-xs text-red-700 font-medium">
          {{ quickDeptError }}
        </div>
        <UiInput v-model="quickDeptForm.name" :label="t('hr.employeeModal.quickDept.name')" :placeholder="t('hr.employeeModal.quickDept.namePlaceholder')" required />
        <UiInput v-model="quickDeptForm.code" :label="t('hr.employeeModal.quickDept.code')" :placeholder="t('hr.employeeModal.quickDept.codePlaceholder')" />
        <UiSelect
          v-model="quickDeptForm.parent_id"
          :label="t('hr.employeeModal.quickDept.parent')"
          :options="[{ label: t('hr.employeeModal.quickDept.none'), value: '' }, ...(departments?.map((d) => ({ label: d.name, value: d.id })) || [])]"
          :placeholder="t('hr.employeeModal.quickDept.none')"
        />
        <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
          <UiButton variant="outline" type="button" @click="isQuickDeptModalOpen = false">{{ t('hr.employeeModal.quickDept.cancel') }}</UiButton>
          <UiButton
            type="button"
            :loading="createDeptMutation.isPending.value"
            :disabled="!quickDeptForm.name"
            @click="handleSaveQuickDept"
          >
            {{ t('hr.employeeModal.quickDept.submit') }}
          </UiButton>
        </div>
      </div>
    </UiModal>

    <!-- Quick Create Position Sub-Modal -->
    <UiModal v-model="isQuickPositionModalOpen" :title="t('hr.employeeModal.quickPosition.title')" size="md">
      <div class="space-y-4">
        <div v-if="quickPositionError" class="rounded-xl border border-red-200 bg-red-50 p-3 text-xs text-red-700 font-medium">
          {{ quickPositionError }}
        </div>
        <UiInput v-model="quickPositionForm.title" :label="t('hr.employeeModal.quickPosition.name')" :placeholder="t('hr.employeeModal.quickPosition.namePlaceholder')" required />
        <UiSelect
          v-model="quickPositionForm.department_id"
          :label="t('hr.employeeModal.quickPosition.department')"
          :options="[{ label: t('hr.employeeModal.quickPosition.selectDepartment'), value: '' }, ...(departments?.map((d) => ({ label: d.name, value: d.id })) || [])]"
          :placeholder="t('hr.employeeModal.quickPosition.selectDepartment')"
          required
        />
        <UiInput v-model="quickPositionForm.job_grade" :label="t('hr.employeeModal.quickPosition.jobGrade')" :placeholder="t('hr.employeeModal.quickPosition.jobGradePlaceholder')" />
        <UiInput v-model="quickPositionForm.description" :label="t('hr.employeeModal.quickPosition.description')" :placeholder="t('hr.employeeModal.quickPosition.descriptionPlaceholder')" />
        <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
          <UiButton variant="outline" type="button" @click="isQuickPositionModalOpen = false">{{ t('hr.employeeModal.quickPosition.cancel') }}</UiButton>
          <UiButton
            type="button"
            :loading="createPositionMutation.isPending.value"
            :disabled="!quickPositionForm.title || !quickPositionForm.department_id"
            @click="handleSaveQuickPosition"
          >
            {{ t('hr.employeeModal.quickPosition.submit') }}
          </UiButton>
        </div>
      </div>
    </UiModal>
  </UiModal>
</template>
