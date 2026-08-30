<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import type { Employee, DocumentType, EmployeeDocument } from '@/types/hr'
import { hrApi } from '@/api/hr'
import UiButton from '@/components/ui/UiButton.vue'
import UiCard from '@/components/ui/UiCard.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiTable from '@/components/ui/UiTable.vue'
import UiSpinner from '@/components/ui/UiSpinner.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiModal from '@/components/ui/UiModal.vue'
import LeaveRequestModal from '../components/LeaveRequestModal.vue'
import UploadDocumentModal from '../components/UploadDocumentModal.vue'
import {
  User,
  Calendar,
  Clock,
  FileText,
  Mail,
  Phone,
  Briefcase,
  Plus,
  Trash2,
  Heart,
  ChevronLeft,
  ChevronRight,
  ArrowLeft,
  Check,
  AlertCircle,
  Building2,
  Award,
  Download,
  ExternalLink,
  FileSpreadsheet,
  FileCheck,
  FileCode,
  File,
  ShieldCheck,
  GraduationCap,
  CreditCard,
} from '@lucide/vue'

const route = useRoute()
const router = useRouter()
const employeeId = route.params.id as string
const queryClient = useQueryClient()

// Queries
const { data: employee, isLoading: isLoadingEmployee } = useQuery({
  queryKey: ['hr', 'employees', employeeId],
  queryFn: () => hrApi.getEmployee(employeeId).then((res) => res.data),
})

const { data: departments } = useQuery({
  queryKey: ['hr', 'departments'],
  queryFn: () => hrApi.getDepartments().then((res) => res.data),
})

const { data: positions } = useQuery({
  queryKey: ['hr', 'positions'],
  queryFn: () => hrApi.getPositions().then((res) => res.data),
})

const { data: allEmployees } = useQuery({
  queryKey: ['hr', 'employees'],
  queryFn: () => hrApi.getEmployees().then((res) => res.data),
})

const { data: leaveBalances } = useQuery({
  queryKey: ['hr', 'employees', employeeId, 'leave-balances'],
  queryFn: () => hrApi.getEmployeeLeaveBalances(employeeId).then((res) => res.data),
})

const { data: attendanceLogs } = useQuery({
  queryKey: ['hr', 'employees', employeeId, 'attendance'],
  queryFn: () => hrApi.getEmployeeAttendance(employeeId).then((res) => res.data),
})

const { data: leaveRequests } = useQuery({
  queryKey: ['hr', 'employees', employeeId, 'leave-requests'],
  queryFn: () => hrApi.getEmployeeLeaveRequests(employeeId).then((res) => res.data),
})

const { data: documents, isLoading: isLoadingDocs } = useQuery({
  queryKey: ['hr', 'employees', employeeId, 'documents'],
  queryFn: () => hrApi.getEmployeeDocuments(employeeId).then((res) => res.data),
})

const activeTab = ref<'profile' | 'leave' | 'attendance' | 'documents'>('profile')
const tabs = [
  { id: 'profile', label: 'Profile & Employment', icon: User },
  { id: 'documents', label: 'Documents & Files', icon: FileText },
  { id: 'leave', label: 'Leave Balances & History', icon: Calendar },
  { id: 'attendance', label: 'Attendance Timesheet', icon: Clock },
]

const isLeaveModalOpen = ref(false)
const isUploadDocModalOpen = ref(false)
const isEditing = ref(false)
const isSaving = ref(false)
const errorMessage = ref('')
const selectedDocFilter = ref<string>('all')

const editForm = ref({
  first_name: '',
  last_name: '',
  preferred_name: '',
  email: '',
  phone: '',
  date_of_birth: '',
  gender: '',
  employee_number: '',
  department_id: '',
  position_id: '',
  manager_id: '',
  employment_type: 'full-time',
  status: 'active',
  start_date: '',
  emergency_contacts: [] as Array<{ name: string; relationship: string; phone: string }>,
})

// Quick Creation for Department & Position
const isQuickDeptModalOpen = ref(false)
const quickDeptForm = ref({ name: '', code: '', parent_id: '' })
const quickDeptError = ref('')

const isQuickPositionModalOpen = ref(false)
const quickPositionForm = ref({ title: '', department_id: '', job_grade: '', description: '' })
const quickPositionError = ref('')

const createDeptMutation = useMutation({
  mutationFn: (data: any) => hrApi.createDepartment(data),
  onSuccess: (res) => {
    queryClient.invalidateQueries({ queryKey: ['hr', 'departments'] })
    editForm.value.department_id = res.data.id
    isQuickDeptModalOpen.value = false
    quickDeptForm.value = { name: '', code: '', parent_id: '' }
  },
  onError: (err: any) => {
    quickDeptError.value = err?.response?.data?.message || err?.message || 'Failed to create department'
  },
})

const createPositionMutation = useMutation({
  mutationFn: (data: any) => hrApi.createPosition(data),
  onSuccess: (res) => {
    queryClient.invalidateQueries({ queryKey: ['hr', 'positions'] })
    editForm.value.position_id = res.data.id
    isQuickPositionModalOpen.value = false
    quickPositionForm.value = { title: '', department_id: '', job_grade: '', description: '' }
  },
  onError: (err: any) => {
    quickPositionError.value = err?.response?.data?.message || err?.message || 'Failed to create position'
  },
})

const deleteDocMutation = useMutation({
  mutationFn: (docId: string) => hrApi.deleteEmployeeDocument(employeeId, docId),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['hr', 'employees', employeeId, 'documents'] })
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
    department_id: editForm.value.department_id || (departments.value?.[0]?.id ?? ''),
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

const managerOptions = computed(() => [
  { label: 'None (Top Level)', value: '' },
  ...(allEmployees.value || [])
    .filter((e) => e.id !== employeeId)
    .map((e) => ({
      label: `${e.first_name} ${e.last_name} (${e.employee_number || 'No ID'})`,
      value: e.id,
    })),
])

const startEditing = () => {
  if (!employee.value) return
  errorMessage.value = ''
  editForm.value = {
    first_name: employee.value.first_name || '',
    last_name: employee.value.last_name || '',
    preferred_name: employee.value.preferred_name || '',
    email: employee.value.email || '',
    phone: employee.value.phone || '',
    date_of_birth: employee.value.date_of_birth ? String(employee.value.date_of_birth).slice(0, 10) : '',
    gender: employee.value.gender || '',
    employee_number: employee.value.employee_number || '',
    department_id: (employee.value as any).department_id || employee.value.department?.id || '',
    position_id: (employee.value as any).position_id || employee.value.position?.id || '',
    manager_id: (employee.value as any).manager_id || employee.value.manager?.id || '',
    employment_type: employee.value.employment_type || 'full-time',
    status: employee.value.status || 'active',
    start_date: employee.value.start_date ? String(employee.value.start_date).slice(0, 10) : '',
    emergency_contacts: Array.isArray(employee.value.emergency_contacts)
      ? JSON.parse(JSON.stringify(employee.value.emergency_contacts))
      : [{ name: '', relationship: '', phone: '' }],
  }
  isEditing.value = true
}

const cancelEditing = () => {
  isEditing.value = false
  errorMessage.value = ''
}

const addEmergencyContact = () => {
  editForm.value.emergency_contacts.push({ name: '', relationship: '', phone: '' })
}

const removeEmergencyContact = (index: number) => {
  editForm.value.emergency_contacts.splice(index, 1)
}

const saveProfile = async () => {
  if (!employee.value) return
  isSaving.value = true
  errorMessage.value = ''

  const payload: any = {
    first_name: editForm.value.first_name,
    last_name: editForm.value.last_name,
    preferred_name: editForm.value.preferred_name || null,
    email: editForm.value.email,
    phone: editForm.value.phone || null,
    date_of_birth: editForm.value.date_of_birth || null,
    gender: editForm.value.gender || null,
    employee_number: editForm.value.employee_number,
    department_id: editForm.value.department_id || null,
    position_id: editForm.value.position_id || null,
    manager_id: editForm.value.manager_id || null,
    employment_type: editForm.value.employment_type,
    status: editForm.value.status,
    start_date: editForm.value.start_date,
    emergency_contacts: editForm.value.emergency_contacts.filter((c) => c.name || c.phone),
  }

  try {
    await hrApi.updateEmployee(employeeId, payload)
    queryClient.invalidateQueries({ queryKey: ['hr', 'employees', employeeId] })
    queryClient.invalidateQueries({ queryKey: ['hr', 'employees'] })
    queryClient.invalidateQueries({ queryKey: ['hr', 'departments'] })
    queryClient.invalidateQueries({ queryKey: ['hr', 'positions'] })
    isEditing.value = false
  } catch (e: any) {
    errorMessage.value = e?.response?.data?.message || e?.message || 'Failed to save employee profile'
  } finally {
    isSaving.value = false
  }
}

// Document Management Logic
const filteredDocuments = computed(() => {
  if (!documents.value) return []
  if (selectedDocFilter.value === 'all') return documents.value
  return documents.value.filter((d) => d.document_type === selectedDocFilter.value)
})

const docTypeCounts = computed(() => {
  const list = documents.value || []
  return {
    all: list.length,
    cv: list.filter((d) => d.document_type === 'cv').length,
    contract: list.filter((d) => d.document_type === 'contract').length,
    education: list.filter((d) => d.document_type === 'education').length,
    id_proof: list.filter((d) => d.document_type === 'id_proof').length,
    certification: list.filter((d) => d.document_type === 'certification').length,
    tax: list.filter((d) => d.document_type === 'tax').length,
    other: list.filter((d) => d.document_type === 'other').length,
  }
})

const formatBytes = (bytes: number) => {
  if (!bytes) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
}

const getDocTypeBadge = (type: string) => {
  switch (type) {
    case 'cv':
      return { label: 'CV / Resume', variant: 'info' as const, icon: FileText }
    case 'contract':
      return { label: 'Contract', variant: 'success' as const, icon: ShieldCheck }
    case 'education':
      return { label: 'Education / Degree', variant: 'purple' as const, icon: GraduationCap }
    case 'id_proof':
      return { label: 'ID / Passport', variant: 'warning' as const, icon: CreditCard }
    case 'certification':
      return { label: 'Certification', variant: 'info' as const, icon: Award }
    case 'tax':
      return { label: 'Tax Document', variant: 'default' as const, icon: FileSpreadsheet }
    default:
      return { label: 'Document', variant: 'default' as const, icon: File }
  }
}

const deleteDocument = (doc: EmployeeDocument) => {
  if (confirm(`Are you sure you want to delete "${doc.title}"?`)) {
    deleteDocMutation.mutate(doc.id)
  }
}

const downloadDocFile = async (doc: EmployeeDocument) => {
  try {
    const res = await hrApi.downloadEmployeeDocument(employeeId, doc.id)
    const blob = new Blob([res.data])
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = doc.file_name || `${doc.title}.pdf`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (err) {
    console.error('Download failed, opening file url directly', err)
    if (doc.file_url) {
      window.open(doc.file_url, '_blank')
    }
  }
}

// Attendance & Leave Logic
const leaveColumns = [
  { key: 'leave_type', label: 'Type' },
  { key: 'dates', label: 'Dates' },
  { key: 'working_days', label: 'Days' },
  { key: 'status', label: 'Status' },
]

const employmentTypes = [
  { label: 'Full-time', value: 'full-time' },
  { label: 'Part-time', value: 'part-time' },
  { label: 'Contract', value: 'contract' },
  { label: 'Intern', value: 'intern' },
  { label: 'Probationary', value: 'probationary' },
]

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'On Leave', value: 'on-leave' },
  { label: 'Suspended', value: 'suspended' },
  { label: 'Terminated', value: 'terminated' },
]

const genderOptions = [
  { label: 'Select Gender', value: '' },
  { label: 'Male', value: 'male' },
  { label: 'Female', value: 'female' },
  { label: 'Other', value: 'other' },
]
</script>

<template>
  <div v-if="isLoadingEmployee" class="flex justify-center py-20">
    <UiSpinner size="lg" />
  </div>

  <div v-else-if="employee" class="space-y-6">
    <!-- Back Button & Breadcrumbs -->
    <div class="flex items-center gap-2">
      <button
        type="button"
        @click="router.push({ name: 'hr-employees' })"
        class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-slate-900 transition-colors"
      >
        <ArrowLeft class="w-3.5 h-3.5" /> Back to Employees
      </button>
    </div>

    <!-- Error Alert -->
    <div v-if="errorMessage" class="rounded-xl border border-red-200 bg-red-50 p-4 text-xs font-semibold text-red-700 flex items-center justify-between">
      <span>{{ errorMessage }}</span>
      <button type="button" @click="errorMessage = ''" class="text-red-500 font-bold ml-2">✕</button>
    </div>

    <!-- Header Card -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/90 shadow-xs">
      <div class="flex items-center gap-4">
        <div class="h-20 w-20 rounded-2xl bg-slate-100 flex items-center justify-center text-2xl font-black text-slate-700 overflow-hidden shadow-xs">
          <img v-if="employee.avatar_url" :src="employee.avatar_url" class="h-full w-full object-cover" />
          <span v-else>{{ employee.first_name?.[0] }}{{ employee.last_name?.[0] }}</span>
        </div>
        <div>
          <h1 class="text-2xl font-black text-slate-900">{{ employee.first_name }} {{ employee.last_name }}</h1>
          <div class="flex items-center gap-2 text-xs font-medium text-slate-500 mt-1">
            <span class="font-mono font-bold text-slate-700">{{ employee.employee_number }}</span>
            <span>•</span>
            <span class="text-slate-700 font-semibold">{{ employee.position?.title || 'No Position' }}</span>
            <span>•</span>
            <span class="text-slate-700">{{ employee.department?.name || 'No Department' }}</span>
          </div>
          <div class="mt-2.5 flex items-center gap-2">
            <UiBadge :variant="employee.status === 'active' ? 'success' : employee.status === 'on-leave' ? 'info' : 'warning'" class="capitalize font-bold">
              {{ employee.status }}
            </UiBadge>
            <span class="text-xs text-slate-400">Started {{ employee.start_date ? new Date(employee.start_date).toLocaleDateString() : 'N/A' }}</span>
          </div>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <UiButton variant="outline" size="sm" type="button" @click="isUploadDocModalOpen = true">
          <Plus class="w-3.5 h-3.5 mr-1.5" /> Upload Document
        </UiButton>
        <template v-if="isEditing">
          <UiButton variant="outline" size="sm" type="button" @click="cancelEditing" :disabled="isSaving">Cancel</UiButton>
          <UiButton size="sm" type="button" @click="saveProfile" :loading="isSaving">Save Changes</UiButton>
        </template>
        <template v-else>
          <UiButton size="sm" type="button" @click="startEditing">
            Edit Profile
          </UiButton>
        </template>
      </div>
    </div>

    <!-- Tab Bar -->
    <div class="flex gap-2 border-b border-slate-200">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        class="px-5 py-3 text-sm font-bold border-b-2 -mb-px transition-colors flex items-center gap-2"
        :class="activeTab === tab.id ? 'border-primary-600 text-primary-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
        @click="activeTab = tab.id as any"
      >
        <component :is="tab.icon" class="h-4 w-4" />
        <span>{{ tab.label }}</span>
        <span
          v-if="tab.id === 'documents' && documents?.length"
          class="ml-1 px-1.5 py-0.5 text-[10px] font-mono rounded-full bg-slate-100 text-slate-600 font-bold"
        >
          {{ documents.length }}
        </span>
      </button>
    </div>

    <!-- Tab Content: Profile -->
    <div v-if="activeTab === 'profile'" class="space-y-6">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Personal Details Card -->
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
          <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
            <User class="w-4 h-4 text-primary-600" />
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Personal Information</h2>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">First Name</label>
              <UiInput v-if="isEditing" v-model="editForm.first_name" placeholder="First Name" />
              <p v-else class="text-sm font-bold text-slate-900">{{ employee.first_name }}</p>
            </div>

            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Last Name</label>
              <UiInput v-if="isEditing" v-model="editForm.last_name" placeholder="Last Name" />
              <p v-else class="text-sm font-bold text-slate-900">{{ employee.last_name }}</p>
            </div>

            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Preferred Name</label>
              <UiInput v-if="isEditing" v-model="editForm.preferred_name" placeholder="Nick / Preferred name" />
              <p v-else class="text-sm text-slate-800">{{ employee.preferred_name || '—' }}</p>
            </div>

            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Email Address</label>
              <UiInput v-if="isEditing" v-model="editForm.email" type="email" placeholder="Email" />
              <p v-else class="text-sm font-mono text-slate-800">{{ employee.email }}</p>
            </div>

            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Phone Number</label>
              <UiInput v-if="isEditing" v-model="editForm.phone" type="tel" placeholder="+1..." />
              <p v-else class="text-sm text-slate-800">{{ employee.phone || '—' }}</p>
            </div>

            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Gender</label>
              <UiSelect v-if="isEditing" v-model="editForm.gender" :options="genderOptions" />
              <p v-else class="text-sm capitalize text-slate-800">{{ employee.gender || '—' }}</p>
            </div>

            <div class="sm:col-span-2">
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Date of Birth</label>
              <UiInput v-if="isEditing" v-model="editForm.date_of_birth" type="date" />
              <p v-else class="text-sm text-slate-800">{{ employee.date_of_birth ? new Date(employee.date_of_birth).toLocaleDateString() : '—' }}</p>
            </div>
          </div>
        </div>

        <!-- Work & Organizational Details Card -->
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
          <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
            <Briefcase class="w-4 h-4 text-primary-600" />
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Employment & Hierarchy</h2>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Employee ID</label>
              <UiInput v-if="isEditing" v-model="editForm.employee_number" placeholder="EMP-0001" />
              <p v-else class="text-sm font-mono font-bold text-slate-900">{{ employee.employee_number }}</p>
            </div>

            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Start Date</label>
              <UiInput v-if="isEditing" v-model="editForm.start_date" type="date" />
              <p v-else class="text-sm text-slate-800">{{ employee.start_date ? new Date(employee.start_date).toLocaleDateString() : '—' }}</p>
            </div>

            <!-- Department Selector with Quick + New Trigger -->
            <div>
              <div class="flex justify-between items-center mb-1">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500">Department</label>
                <button
                  v-if="isEditing"
                  type="button"
                  @click="openQuickDeptModal"
                  class="text-xs text-blue-600 hover:text-blue-700 font-bold inline-flex items-center gap-1 hover:underline"
                >
                  <Plus class="w-3.5 h-3.5" /> New Department
                </button>
              </div>
              <UiSelect
                v-if="isEditing"
                v-model="editForm.department_id"
                :options="[{ label: 'Select Department', value: '' }, ...(departments?.map((d) => ({ label: d.name, value: d.id })) || [])]"
                placeholder="Select Department"
              />
              <p v-else class="text-sm font-bold text-slate-900">{{ employee.department?.name || 'Unassigned' }}</p>
            </div>

            <!-- Position Selector with Quick + New Trigger -->
            <div>
              <div class="flex justify-between items-center mb-1">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500">Position</label>
                <button
                  v-if="isEditing"
                  type="button"
                  @click="openQuickPositionModal"
                  class="text-xs text-blue-600 hover:text-blue-700 font-bold inline-flex items-center gap-1 hover:underline"
                >
                  <Plus class="w-3.5 h-3.5" /> New Position
                </button>
              </div>
              <UiSelect
                v-if="isEditing"
                v-model="editForm.position_id"
                :options="[{ label: 'Select Position', value: '' }, ...(positions?.map((p) => ({ label: p.title, value: p.id })) || [])]"
                placeholder="Select Position"
              />
              <p v-else class="text-sm font-bold text-slate-900">{{ employee.position?.title || 'Unassigned' }}</p>
            </div>

            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Employment Type</label>
              <UiSelect v-if="isEditing" v-model="editForm.employment_type" :options="employmentTypes" />
              <p v-else class="text-sm capitalize text-slate-800">{{ (employee.employment_type || '').replace('-', ' ') }}</p>
            </div>

            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Account Status</label>
              <UiSelect v-if="isEditing" v-model="editForm.status" :options="statusOptions" />
              <p v-else class="text-sm capitalize font-bold" :class="employee.status === 'active' ? 'text-emerald-600' : 'text-amber-600'">
                {{ employee.status }}
              </p>
            </div>

            <div class="sm:col-span-2">
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Reporting Manager</label>
              <UiSelect
                v-if="isEditing"
                v-model="editForm.manager_id"
                :options="managerOptions"
                placeholder="Select Manager"
              />
              <div v-else-if="employee.manager" class="flex items-center gap-2 mt-1">
                <div class="h-6 w-6 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-bold text-slate-700">
                  {{ employee.manager.first_name?.[0] }}{{ employee.manager.last_name?.[0] }}
                </div>
                <span class="text-sm font-semibold text-slate-900">{{ employee.manager.first_name }} {{ employee.manager.last_name }}</span>
              </div>
              <p v-else class="text-sm text-slate-400 italic">No direct manager assigned</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Emergency Contacts Card -->
      <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center gap-2">
            <Heart class="w-4 h-4 text-red-500" />
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Emergency Contacts</h2>
          </div>
          <UiButton v-if="isEditing" variant="ghost" size="sm" type="button" @click="addEmergencyContact">
            <Plus class="w-3.5 h-3.5 mr-1" /> Add Contact
          </UiButton>
        </div>

        <div v-if="isEditing" class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div
            v-for="(contact, index) in editForm.emergency_contacts"
            :key="index"
            class="p-4 bg-slate-50/70 rounded-xl border border-slate-200/90 relative group space-y-2"
          >
            <button
              v-if="editForm.emergency_contacts.length > 1"
              type="button"
              @click="removeEmergencyContact(index)"
              class="absolute -top-2 -right-2 p-1.5 bg-white border border-slate-200 rounded-full text-slate-400 hover:text-red-600 shadow-xs opacity-0 group-hover:opacity-100 transition-opacity"
            >
              <Trash2 class="h-3.5 w-3.5" />
            </button>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <UiInput v-model="contact.name" label="Contact Name" placeholder="Full name" size="sm" />
              <UiInput v-model="contact.relationship" label="Relationship" placeholder="e.g. Spouse" size="sm" />
              <UiInput v-model="contact.phone" label="Phone" type="tel" placeholder="+1..." size="sm" />
            </div>
          </div>
        </div>

        <div v-else-if="employee.emergency_contacts?.length" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
          <div
            v-for="contact in employee.emergency_contacts"
            :key="contact.phone"
            class="p-4 rounded-xl border border-slate-100 bg-slate-50/80 flex items-start justify-between"
          >
            <div>
              <p class="font-bold text-slate-900 text-sm">{{ contact.name }}</p>
              <p class="text-xs text-slate-500">{{ contact.relationship || 'Emergency Contact' }}</p>
              <div class="flex items-center gap-1.5 text-xs text-slate-700 font-mono mt-2">
                <Phone class="h-3.5 w-3.5 text-slate-400" />
                <span>{{ contact.phone }}</span>
              </div>
            </div>
          </div>
        </div>
        <p v-else class="text-slate-400 text-xs italic py-2">No emergency contacts registered on profile.</p>
      </div>
    </div>

    <!-- Tab Content: Documents & Files -->
    <div v-if="activeTab === 'documents'" class="space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-lg font-bold text-slate-900">Employee Documents & Dossier</h2>
          <p class="text-xs text-slate-500">Secure storage for CVs, employment contracts, degree certificates, and IDs.</p>
        </div>
        <UiButton size="sm" @click="isUploadDocModalOpen = true">
          <Plus class="h-4 w-4 mr-2" /> Upload Document
        </UiButton>
      </div>

      <!-- Filter Categories Bar -->
      <div class="flex items-center gap-2 overflow-x-auto pb-1">
        <button
          type="button"
          @click="selectedDocFilter = 'all'"
          class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all"
          :class="selectedDocFilter === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50'"
        >
          All Files ({{ docTypeCounts.all }})
        </button>
        <button
          type="button"
          @click="selectedDocFilter = 'cv'"
          class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all"
          :class="selectedDocFilter === 'cv' ? 'bg-blue-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50'"
        >
          CV & Resume ({{ docTypeCounts.cv }})
        </button>
        <button
          type="button"
          @click="selectedDocFilter = 'contract'"
          class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all"
          :class="selectedDocFilter === 'contract' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50'"
        >
          Contracts ({{ docTypeCounts.contract }})
        </button>
        <button
          type="button"
          @click="selectedDocFilter = 'education'"
          class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all"
          :class="selectedDocFilter === 'education' ? 'bg-purple-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50'"
        >
          Degrees & Education ({{ docTypeCounts.education }})
        </button>
        <button
          type="button"
          @click="selectedDocFilter = 'id_proof'"
          class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all"
          :class="selectedDocFilter === 'id_proof' ? 'bg-amber-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50'"
        >
          ID & Passport ({{ docTypeCounts.id_proof }})
        </button>
        <button
          type="button"
          @click="selectedDocFilter = 'certification'"
          class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all"
          :class="selectedDocFilter === 'certification' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50'"
        >
          Certifications ({{ docTypeCounts.certification }})
        </button>
      </div>

      <!-- Loading State -->
      <div v-if="isLoadingDocs" class="flex justify-center py-12">
        <UiSpinner size="md" />
      </div>

      <!-- Documents Grid -->
      <div v-else-if="filteredDocuments.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="doc in filteredDocuments"
          :key="doc.id"
          class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between hover:border-slate-300 transition-all group"
        >
          <div class="space-y-3">
            <div class="flex items-start justify-between gap-2">
              <div class="flex items-center gap-3">
                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100 text-slate-700">
                  <component :is="getDocTypeBadge(doc.document_type).icon" class="w-5 h-5" />
                </div>
                <div>
                  <h3 class="font-bold text-slate-900 text-sm line-clamp-1" :title="doc.title">{{ doc.title }}</h3>
                  <p class="text-[11px] font-mono text-slate-400 line-clamp-1">{{ doc.file_name }}</p>
                </div>
              </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 pt-1">
              <UiBadge :variant="getDocTypeBadge(doc.document_type).variant" class="text-[10px] font-bold">
                {{ getDocTypeBadge(doc.document_type).label }}
              </UiBadge>
              <span class="text-[11px] font-mono text-slate-500">{{ formatBytes(doc.file_size) }}</span>
              <span v-if="doc.expiry_date" class="text-[10px] px-2 py-0.5 rounded-md font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                Expires: {{ new Date(doc.expiry_date).toLocaleDateString() }}
              </span>
            </div>

            <p v-if="doc.notes" class="text-xs text-slate-500 bg-slate-50 p-2 rounded-lg border border-slate-100">
              {{ doc.notes }}
            </p>
          </div>

          <div class="flex items-center justify-between pt-4 mt-3 border-t border-slate-100 text-xs">
            <span class="text-[11px] text-slate-400">
              {{ new Date(doc.created_at).toLocaleDateString() }}
            </span>

            <div class="flex items-center gap-1">
              <button
                type="button"
                @click="downloadDocFile(doc)"
                class="p-1.5 rounded-lg text-slate-500 hover:text-primary-600 hover:bg-primary-50 transition-colors"
                title="Download Document"
              >
                <Download class="w-4 h-4" />
              </button>
              <a
                v-if="doc.file_url"
                :href="doc.file_url"
                target="_blank"
                rel="noopener"
                class="p-1.5 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                title="Open in new tab"
              >
                <ExternalLink class="w-4 h-4" />
              </a>
              <button
                type="button"
                @click="deleteDocument(doc)"
                class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                title="Delete Document"
              >
                <Trash2 class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="bg-white rounded-2xl border border-slate-200 p-12 text-center space-y-4">
        <div class="w-16 h-16 rounded-full bg-slate-50 border border-slate-200 mx-auto flex items-center justify-center text-slate-400">
          <FileText class="w-8 h-8" />
        </div>
        <div>
          <h3 class="text-base font-bold text-slate-900">No documents found</h3>
          <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">
            Upload employment contracts, CV/resumes, degree certificates, and identification documents for this employee.
          </p>
        </div>
        <UiButton size="sm" @click="isUploadDocModalOpen = true">
          <Plus class="w-4 h-4 mr-1.5" /> Upload Document
        </UiButton>
      </div>
    </div>

    <!-- Tab Content: Leave -->
    <div v-if="activeTab === 'leave'" class="space-y-6">
      <div class="flex justify-between items-center">
        <div>
          <h2 class="text-lg font-bold text-slate-900">Leave Entitlements & Balances</h2>
          <p class="text-xs text-slate-500">Statutory and company time-off balance for this employee.</p>
        </div>
        <UiButton size="sm" @click="isLeaveModalOpen = true">
          <Plus class="h-4 w-4 mr-2" /> Submit Leave Request
        </UiButton>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="bal in leaveBalances" :key="bal.id" class="p-5 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-3">
          <div class="flex items-center justify-between">
            <h3 class="font-bold text-slate-900 text-sm">{{ bal.leaveType?.name || (bal as any).leave_type?.name }}</h3>
            <UiBadge variant="default" class="font-mono text-xs">{{ bal.entitled_days ?? (bal as any).entitled ?? 0 }} Total</UiBadge>
          </div>
          <div class="flex items-baseline gap-1">
            <span class="text-3xl font-black text-slate-900 font-mono">
              {{ Math.max(0, (bal.entitled_days ?? 0) - (bal.taken_days ?? 0)) }}
            </span>
            <span class="text-xs font-semibold text-slate-400">Days Remaining</span>
          </div>
          <div class="w-full bg-slate-100 rounded-full h-2">
            <div
              class="bg-blue-600 h-2 rounded-full"
              :style="{ width: `${Math.min(100, (((bal.taken_days ?? 0) / Math.max(1, bal.entitled_days ?? 1)) * 100))}%` }"
            ></div>
          </div>
          <div class="flex items-center justify-between text-xs text-slate-500 font-medium">
            <span>{{ bal.taken_days ?? 0 }} Days Taken</span>
            <span>{{ bal.entitled_days ?? 0 }} Days Entitled</span>
          </div>
        </div>
      </div>

      <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs space-y-3">
        <h3 class="font-bold text-slate-900 text-sm">Leave History for {{ employee.first_name }}</h3>
        <UiTable :columns="leaveColumns" :data="leaveRequests || []">
          <template #cell(leave_type)="{ item }">
            <span class="font-semibold text-slate-900">{{ item.leave_type?.name }}</span>
          </template>
          <template #cell(dates)="{ item }">
            <span class="text-xs text-slate-600">{{ new Date(item.start_date).toLocaleDateString() }} → {{ new Date(item.end_date).toLocaleDateString() }}</span>
          </template>
          <template #cell(working_days)="{ item }">
            <span class="font-bold text-slate-900 text-xs">{{ item.days_taken }} d</span>
          </template>
          <template #cell(status)="{ item }">
            <UiBadge :variant="item.status === 'approved' ? 'success' : item.status === 'pending' ? 'warning' : 'danger'" class="capitalize font-bold">
              {{ item.status }}
            </UiBadge>
          </template>
        </UiTable>
      </div>
    </div>

    <!-- Tab Content: Attendance -->
    <div v-if="activeTab === 'attendance'" class="space-y-6">
      <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs space-y-3">
        <h3 class="font-bold text-slate-900 text-sm">Recent Attendance Logs</h3>
        <div v-if="attendanceLogs?.length" class="space-y-2">
          <div
            v-for="log in attendanceLogs"
            :key="log.id"
            class="flex items-center justify-between p-3.5 rounded-xl border border-slate-100 bg-slate-50/60 text-xs"
          >
            <div class="flex items-center gap-3">
              <Clock class="w-4 h-4 text-blue-600" />
              <div>
                <span class="font-bold text-slate-900 uppercase">{{ log.clock_type }}</span>
                <p class="text-[11px] text-slate-500">Method: {{ log.method }}</p>
              </div>
            </div>
            <span class="font-mono font-medium text-slate-700">{{ new Date(log.logged_at).toLocaleString() }}</span>
          </div>
        </div>
        <p v-else class="text-xs text-slate-400 italic py-4 text-center">No attendance logs found for this employee.</p>
      </div>
    </div>

    <!-- Leave Request Modal -->
    <LeaveRequestModal
      v-model="isLeaveModalOpen"
      :employee-id="employeeId"
      @saved="queryClient.invalidateQueries({ queryKey: ['hr', 'employees', employeeId] })"
    />

    <!-- Upload Document Modal -->
    <UploadDocumentModal
      v-model="isUploadDocModalOpen"
      :employee-id="employeeId"
      @saved="queryClient.invalidateQueries({ queryKey: ['hr', 'employees', employeeId, 'documents'] })"
    />

    <!-- Quick Create Department Sub-Modal -->
    <UiModal v-model="isQuickDeptModalOpen" title="New Department" size="md">
      <div class="space-y-4">
        <div v-if="quickDeptError" class="rounded-xl border border-red-200 bg-red-50 p-3 text-xs text-red-700 font-medium">
          {{ quickDeptError }}
        </div>
        <UiInput v-model="quickDeptForm.name" label="Department Name" placeholder="e.g. Engineering, Sales" required />
        <UiInput v-model="quickDeptForm.code" label="Department Code" placeholder="e.g. ENG" />
        <UiSelect
          v-model="quickDeptForm.parent_id"
          label="Parent Department"
          :options="[{ label: 'None (Top Level)', value: '' }, ...(departments?.map((d) => ({ label: d.name, value: d.id })) || [])]"
          placeholder="None"
        />
        <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
          <UiButton variant="outline" type="button" @click="isQuickDeptModalOpen = false">Cancel</UiButton>
          <UiButton
            type="button"
            :loading="createDeptMutation.isPending.value"
            :disabled="!quickDeptForm.name"
            @click="handleSaveQuickDept"
          >
            Save Department
          </UiButton>
        </div>
      </div>
    </UiModal>

    <!-- Quick Create Position Sub-Modal -->
    <UiModal v-model="isQuickPositionModalOpen" title="New Position" size="md">
      <div class="space-y-4">
        <div v-if="quickPositionError" class="rounded-xl border border-red-200 bg-red-50 p-3 text-xs text-red-700 font-medium">
          {{ quickPositionError }}
        </div>
        <UiInput v-model="quickPositionForm.title" label="Position Title" placeholder="e.g. Senior Software Engineer" required />
        <UiSelect
          v-model="quickPositionForm.department_id"
          label="Department"
          :options="[{ label: 'Select Department', value: '' }, ...(departments?.map((d) => ({ label: d.name, value: d.id })) || [])]"
          placeholder="Select Department"
          required
        />
        <UiInput v-model="quickPositionForm.job_grade" label="Job Grade" placeholder="e.g. L4, Mid-Level" />
        <UiInput v-model="quickPositionForm.description" label="Description" placeholder="Brief role summary" />
        <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
          <UiButton variant="outline" type="button" @click="isQuickPositionModalOpen = false">Cancel</UiButton>
          <UiButton
            type="button"
            :loading="createPositionMutation.isPending.value"
            :disabled="!quickPositionForm.title || !quickPositionForm.department_id"
            @click="handleSaveQuickPosition"
          >
            Save Position
          </UiButton>
        </div>
      </div>
    </UiModal>
  </div>
</template>
