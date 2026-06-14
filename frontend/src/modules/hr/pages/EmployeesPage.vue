<script setup lang="ts">
import { ref, computed } from 'vue'
import { useQuery, useQueryClient } from '@tanstack/vue-query'
import { hrApi } from '@/api/hr'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiTable from '@/components/ui/UiTable.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import EmployeeDrawer from '../components/EmployeeDrawer.vue'
import { Plus, Search } from '@lucide/vue'

const queryClient = useQueryClient()
const { data: employees, isLoading } = useQuery({
  queryKey: ['hr', 'employees'],
  queryFn: () => hrApi.getEmployees().then(res => res.data)
})

const isDrawerOpen = ref(false)

const searchQuery = ref('')
const filteredEmployees = computed(() => {
  const employeesList = employees.value
  if (!employeesList || !Array.isArray(employeesList)) return []
  return employeesList.filter(e => 
    `${e.first_name} ${e.last_name}`.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    e.employee_number.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})

const columns = [
  { key: 'name', label: 'Name' },
  { key: 'employee_number', label: 'ID' },
  { key: 'department', label: 'Department' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: '' },
]
</script>

<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h1 class="text-2xl font-bold text-slate-900">Employees</h1>
      <UiButton size="sm" @click="isDrawerOpen = true">
        <Plus class="h-4 w-4 mr-2" /> New Employee
      </UiButton>
    </div>

    <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm flex items-center gap-4">
      <UiInput v-model="searchQuery" placeholder="Search employees..." class="w-full max-w-sm">
        <template #prefix><Search class="h-4 w-4 text-slate-400" /></template>
      </UiInput>
    </div>

    <UiTable :columns="columns" :data="filteredEmployees" :loading="isLoading">
      <template #cell(name)="{ item }">
        <div class="font-medium text-slate-900">{{ item.first_name }} {{ item.last_name }}</div>
      </template>
      <template #cell(department)="{ item }">
        <div class="text-slate-600">{{ item.department?.name || 'N/A' }}</div>
      </template>
      <template #cell(status)="{ item }">
        <UiBadge :variant="item.status === 'active' ? 'success' : 'warning'">{{ item.status }}</UiBadge>
      </template>
    </UiTable>

    <EmployeeDrawer v-model="isDrawerOpen" @saved="queryClient.invalidateQueries({ queryKey: ['hr', 'employees'] })" />
  </div>
</template>
