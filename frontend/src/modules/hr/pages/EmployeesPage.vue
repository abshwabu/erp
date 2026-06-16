<script setup lang="ts">
import { ref, computed, markRaw } from 'vue'
import { useRouter } from 'vue-router'
import { useQuery, useQueryClient } from '@tanstack/vue-query'
import { hrApi } from '@/api/hr'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiTable from '@/components/ui/UiTable.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiStat from '@/components/ui/UiStat.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import EmployeeDrawer from '../components/EmployeeDrawer.vue'
import { Plus, Search, Users, UserPlus, Calendar, Briefcase, Filter } from '@lucide/vue'
import type { Employee, Department } from '@/types/hr'

const router = useRouter()
const queryClient = useQueryClient()

const { data: employees, isLoading } = useQuery({
  queryKey: ['hr', 'employees'],
  queryFn: () => hrApi.getEmployees().then(res => res.data)
})

const { data: departments } = useQuery({
  queryKey: ['hr', 'departments'],
  queryFn: () => hrApi.getDepartments().then(res => res.data)
})

const isDrawerOpen = ref(false)
const searchQuery = ref('')
const selectedDepartment = ref('')
const selectedStatus = ref('')
const selectedType = ref('')

const filteredEmployees = computed(() => {
  let list = employees.value || []
  
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(e => 
      `${e.first_name} ${e.last_name}`.toLowerCase().includes(q) ||
      e.employee_number.toLowerCase().includes(q)
    )
  }

  if (selectedDepartment.value) {
    list = list.filter(e => e.department_id === selectedDepartment.value)
  }

  if (selectedStatus.value) {
    list = list.filter(e => e.status === selectedStatus.value)
  }

  if (selectedType.value) {
    list = list.filter(e => e.employment_type === selectedType.value)
  }

  return list
})

const stats = computed(() => {
  const now = new Date()
  const currentMonth = now.getMonth()
  const currentYear = now.getFullYear()

  return [
    { label: 'Total Active', value: employees.value?.filter(e => e.status === 'active').length || 0, icon: markRaw(Users) },
    { 
      label: 'New This Month', 
      value: employees.value?.filter(e => {
        const start = new Date(e.start_date)
        return start.getMonth() === currentMonth && start.getFullYear() === currentYear
      }).length || 0, 
      icon: markRaw(UserPlus) 
    },
    { label: 'On Leave Today', value: employees.value?.filter(e => e.status === 'on-leave').length || 0, icon: markRaw(Calendar) },
    { label: 'Open Positions', value: 0, icon: markRaw(Briefcase) },
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
  { key: 'actions', label: '' },
]

const handleRowClick = (employee: Employee) => {
  router.push({ name: 'hr-employee-profile', params: { id: employee.id } })
}

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'On Leave', value: 'on-leave' },
  { label: 'Suspended', value: 'suspended' },
  { label: 'Terminated', value: 'terminated' },
]

const typeOptions = [
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
        <p class="text-slate-500">Manage your workforce and employment details.</p>
      </div>
      <UiButton @click="isDrawerOpen = true">
        <Plus class="h-4 w-4 mr-2" /> New Employee
      </UiButton>
    </div>

    <!-- Stats Bar -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <UiStat v-for="stat in stats" :key="stat.label" :label="stat.label" :value="stat.value" :icon="stat.icon" />
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm space-y-4">
      <div class="flex flex-wrap items-center gap-4">
        <UiInput v-model="searchQuery" placeholder="Search by name or ID..." class="w-full max-w-xs">
          <template #prefix><Search class="h-4 w-4 text-slate-400" /></template>
        </UiInput>
        
        <div class="flex items-center gap-2 flex-1">
          <Filter class="h-4 w-4 text-slate-400" />
          <UiSelect 
            v-model="selectedDepartment" 
            placeholder="Department" 
            :options="departments?.map(d => ({ label: d.name, value: d.id })) || []" 
            class="w-48"
          />
          <UiSelect 
            v-model="selectedStatus" 
            placeholder="Status" 
            :options="statusOptions" 
            class="w-40"
          />
          <UiSelect 
            v-model="selectedType" 
            placeholder="Employment Type" 
            :options="typeOptions" 
            class="w-48"
          />
          <UiButton v-if="selectedDepartment || selectedStatus || selectedType || searchQuery" 
            variant="ghost" size="sm" @click="selectedDepartment = ''; selectedStatus = ''; selectedType = ''; searchQuery = ''">
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
        <div class="flex items-center gap-3">
          <div class="h-10 w-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 font-bold overflow-hidden">
            <img v-if="item.avatar_url" :src="item.avatar_url" class="h-full w-full object-cover" />
            <span v-else>{{ item.first_name[0] }}{{ item.last_name[0] }}</span>
          </div>
          <div>
            <div class="font-medium text-slate-900">{{ item.first_name }} {{ item.last_name }}</div>
            <div class="text-xs text-slate-500">{{ item.email }}</div>
          </div>
        </div>
      </template>
      
      <template #cell(department)="{ item }">
        <div class="text-slate-600">{{ item.department?.name || 'N/A' }}</div>
      </template>
      
      <template #cell(position)="{ item }">
        <div class="text-slate-600">{{ item.position?.title || 'N/A' }}</div>
      </template>
      
      <template #cell(employment_type)="{ item }">
        <div class="capitalize text-slate-600">{{ item.employment_type.replace('-', ' ') }}</div>
      </template>

      <template #cell(status)="{ item }">
        <UiBadge :variant="item.status === 'active' ? 'success' : item.status === 'on-leave' ? 'info' : 'warning'">
          {{ item.status.replace('-', ' ') }}
        </UiBadge>
      </template>
      
      <template #cell(start_date)="{ item }">
        <div class="text-slate-600">{{ new Date(item.start_date).toLocaleDateString() }}</div>
      </template>

      <template #cell(actions)="{ item }">
        <UiButton variant="ghost" size="sm" @click.stop="handleRowClick(item)">View</UiButton>
      </template>
    </UiTable>

    <EmployeeDrawer 
      v-model="isDrawerOpen" 
      @saved="queryClient.invalidateQueries({ queryKey: ['hr', 'employees'] })" 
    />
  </div>
</template>
