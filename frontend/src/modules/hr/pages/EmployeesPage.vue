<script setup lang="ts">
import { ref, computed, markRaw } from 'vue'
import { useRouter } from 'vue-router'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { hrApi } from '@/api/hr'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiTable from '@/components/ui/UiTable.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiStat from '@/components/ui/UiStat.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import EmployeeModal from '../components/EmployeeModal.vue'
import { Plus, Search, Users, UserPlus, Calendar, Briefcase, Filter, Trash2, Eye } from '@lucide/vue'
import type { Employee, Department } from '@/types/hr'

const router = useRouter()
const queryClient = useQueryClient()

const { data: employees, isLoading } = useQuery({
  queryKey: ['hr', 'employees'],
  queryFn: () => hrApi.getEmployees().then((res) => res.data),
})

const { data: departments } = useQuery({
  queryKey: ['hr', 'departments'],
  queryFn: () => hrApi.getDepartments().then((res) => res.data),
})

const deleteMutation = useMutation({
  mutationFn: (id: string) => hrApi.deleteEmployee(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['hr', 'employees'] })
  },
})

const isModalOpen = ref(false)
const searchQuery = ref('')
const selectedDepartment = ref('')
const selectedStatus = ref('')
const selectedType = ref('')

const filteredEmployees = computed(() => {
  let list = employees.value || []

  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter((e) =>
      `${e.first_name} ${e.last_name}`.toLowerCase().includes(q) ||
      (e.employee_number || '').toLowerCase().includes(q) ||
      (e.email || '').toLowerCase().includes(q)
    )
  }

  if (selectedDepartment.value) {
    list = list.filter((e) => e.department_id === selectedDepartment.value)
  }

  if (selectedStatus.value) {
    list = list.filter((e) => e.status === selectedStatus.value)
  }

  if (selectedType.value) {
    list = list.filter((e) => e.employment_type === selectedType.value)
  }

  return list
})

const stats = computed(() => {
  const now = new Date()
  const currentMonth = now.getMonth()
  const currentYear = now.getFullYear()

  return [
    { label: 'Total Active', value: employees.value?.filter((e) => e.status === 'active').length || 0, icon: markRaw(Users) },
    {
      label: 'New This Month',
      value: employees.value?.filter((e) => {
        const start = new Date(e.start_date)
        return start.getMonth() === currentMonth && start.getFullYear() === currentYear
      }).length || 0,
      icon: markRaw(UserPlus),
    },
    { label: 'On Leave Today', value: employees.value?.filter((e) => e.status === 'on-leave').length || 0, icon: markRaw(Calendar) },
    { label: 'Total Staff', value: employees.value?.length || 0, icon: markRaw(Briefcase) },
  ]
})

const columns = [
  { key: 'name', label: 'Employee' },
  { key: 'employee_number', label: 'ID' },
  { key: 'department', label: 'Department' },
  { key: 'position', label: 'Position' },
  { key: 'employment_type', label: 'Type' },
  { key: 'status', label: 'Status' },
  { key: 'start_date', label: 'Start Date' },
  { key: 'actions', label: 'Actions', align: 'right' as const },
]

const handleRowClick = (employee: Employee) => {
  router.push({ name: 'hr-employee-profile', params: { id: employee.id } })
}

const handleDelete = (e: Employee) => {
  if (confirm(`Are you sure you want to delete employee "${e.first_name} ${e.last_name}"?`)) {
    deleteMutation.mutate(e.id)
  }
}

const statusOptions = [
  { label: 'All Statuses', value: '' },
  { label: 'Active', value: 'active' },
  { label: 'On Leave', value: 'on-leave' },
  { label: 'Suspended', value: 'suspended' },
  { label: 'Terminated', value: 'terminated' },
]

const typeOptions = [
  { label: 'All Types', value: '' },
  { label: 'Full-time', value: 'full-time' },
  { label: 'Part-time', value: 'part-time' },
  { label: 'Contract', value: 'contract' },
  { label: 'Intern', value: 'intern' },
  { label: 'Probationary', value: 'probationary' },
]
</script>

<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Employees</h1>
        <p class="text-slate-500">Manage your workforce, profiles, and employment details.</p>
      </div>
      <UiButton @click="isModalOpen = true">
        <Plus class="h-4 w-4 mr-2" /> New Employee
      </UiButton>
    </div>

    <!-- Stats Bar -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <UiStat v-for="stat in stats" :key="stat.label" :label="stat.label" :value="stat.value" :icon="stat.icon" />
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs space-y-4">
      <div class="flex flex-wrap items-center gap-3">
        <UiInput v-model="searchQuery" placeholder="Search by name, ID, or email..." class="w-full max-w-xs" size="sm">
          <template #prefix><Search class="h-4 w-4 text-slate-400" /></template>
        </UiInput>

        <div class="flex items-center gap-2 flex-wrap flex-1">
          <UiSelect
            v-model="selectedDepartment"
            :options="[{ label: 'All Departments', value: '' }, ...(departments?.map((d) => ({ label: d.name, value: d.id })) || [])]"
            class="w-48"
            size="sm"
          />
          <UiSelect
            v-model="selectedStatus"
            :options="statusOptions"
            class="w-36"
            size="sm"
          />
          <UiSelect
            v-model="selectedType"
            :options="typeOptions"
            class="w-40"
            size="sm"
          />
          <UiButton
            v-if="selectedDepartment || selectedStatus || selectedType || searchQuery"
            variant="ghost"
            size="sm"
            @click="selectedDepartment = ''; selectedStatus = ''; selectedType = ''; searchQuery = ''"
          >
            Reset
          </UiButton>
        </div>
      </div>
    </div>

    <UiTable
      :columns="columns"
      :data="filteredEmployees"
      :loading="isLoading"
      @row-click="handleRowClick"
      class="cursor-pointer"
    >
      <template #cell(name)="{ item }">
        <div class="flex items-center gap-3 py-1">
          <div class="h-9 w-9 rounded-full bg-slate-100 flex items-center justify-center text-slate-700 font-bold overflow-hidden text-xs">
            <img v-if="item.avatar_url" :src="item.avatar_url" class="h-full w-full object-cover" />
            <span v-else>{{ item.first_name?.[0] }}{{ item.last_name?.[0] }}</span>
          </div>
          <div>
            <div class="font-bold text-slate-900 text-sm">{{ item.first_name }} {{ item.last_name }}</div>
            <div class="text-xs text-slate-400 font-mono">{{ item.email }}</div>
          </div>
        </div>
      </template>

      <template #cell(employee_number)="{ item }">
        <span class="font-mono text-xs text-slate-600">{{ item.employee_number || '—' }}</span>
      </template>

      <template #cell(department)="{ item }">
        <div class="text-slate-700 text-xs font-medium">{{ item.department?.name || 'Unassigned' }}</div>
      </template>

      <template #cell(position)="{ item }">
        <div class="text-slate-700 text-xs">{{ item.position?.title || '—' }}</div>
      </template>

      <template #cell(employment_type)="{ item }">
        <div class="capitalize text-slate-600 text-xs">{{ (item.employment_type || '').replace('-', ' ') }}</div>
      </template>

      <template #cell(status)="{ item }">
        <UiBadge :variant="item.status === 'active' ? 'success' : item.status === 'on-leave' ? 'info' : 'warning'" class="capitalize font-bold">
          {{ (item.status || '').replace('-', ' ') }}
        </UiBadge>
      </template>

      <template #cell(start_date)="{ item }">
        <div class="text-slate-600 text-xs">{{ item.start_date ? new Date(item.start_date).toLocaleDateString() : '—' }}</div>
      </template>

      <template #cell(actions)="{ item }">
        <div class="flex justify-end gap-1">
          <UiButton variant="ghost" size="sm" @click.stop="handleRowClick(item)" title="View Profile">
            <Eye class="h-4 w-4" />
          </UiButton>
          <UiButton variant="ghost" size="sm" class="text-red-500 hover:text-red-600 hover:bg-red-50" @click.stop="handleDelete(item)" title="Delete Employee">
            <Trash2 class="h-4 w-4" />
          </UiButton>
        </div>
      </template>
    </UiTable>

    <EmployeeModal
      v-model="isModalOpen"
      @saved="queryClient.invalidateQueries({ queryKey: ['hr', 'employees'] })"
    />
  </div>
</template>
