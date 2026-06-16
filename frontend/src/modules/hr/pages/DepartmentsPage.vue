<script setup lang="ts">
import { ref, computed } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { hrApi } from '@/api/hr'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiTable from '@/components/ui/UiTable.vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import { Plus, Search, Building2, Users, PieChart, Edit2, Trash2 } from '@lucide/vue'
import type { Department, Employee } from '@/types/hr'

const queryClient = useQueryClient()
const isModalOpen = ref(false)
const editingDepartment = ref<Partial<Department> | null>(null)
const searchQuery = ref('')

const { data: departments, isLoading } = useQuery({
  queryKey: ['hr', 'departments'],
  queryFn: () => hrApi.getDepartments().then(res => res.data)
})

const { data: employees } = useQuery({
  queryKey: ['hr', 'employees'],
  queryFn: () => hrApi.getEmployees().then(res => res.data)
})

const filteredDepartments = computed(() => {
  if (!departments.value) return []
  return departments.value.filter(d => 
    d.name.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})

const columns = [
  { key: 'name', label: 'Department Name' },
  { key: 'manager', label: 'Head of Department' },
  { key: 'headcount', label: 'Headcount' },
  { key: 'budget', label: 'Budget' },
  { key: 'actions', label: '' },
]

const form = ref<Partial<Department>>({
  name: '',
  manager_id: '',
  parent_id: '',
  cost_center: '',
  headcount_budget: 0
})

const openModal = (dept: Department | null = null) => {
  if (dept) {
    editingDepartment.value = dept
    form.value = { ...dept }
  } else {
    editingDepartment.value = null
    form.value = { name: '', manager_id: '', parent_id: '', cost_center: '', headcount_budget: 0 }
  }
  isModalOpen.value = true
}

// In a real app, these would be mutations
const saveDepartment = () => {
  console.log('Saving department:', form.value)
  isModalOpen.value = false
}

const deleteDepartment = (id: string) => {
  if (confirm('Are you sure you want to delete this department?')) {
    console.log('Deleting department:', id)
  }
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Departments</h1>
        <p class="text-slate-500">Manage organizational units and hierarchies.</p>
      </div>
      <UiButton @click="openModal()">
        <Plus class="h-4 w-4 mr-2" /> New Department
      </UiButton>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
        <div class="p-3 bg-primary-50 rounded-lg text-primary-600">
          <Building2 class="h-6 w-6" />
        </div>
        <div>
          <p class="text-sm text-slate-500">Total Departments</p>
          <p class="text-2xl font-bold text-slate-900">{{ departments?.length || 0 }}</p>
        </div>
      </div>
      <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
        <div class="p-3 bg-green-50 rounded-lg text-green-600">
          <Users class="h-6 w-6" />
        </div>
        <div>
          <p class="text-sm text-slate-500">Total Employees</p>
          <p class="text-2xl font-bold text-slate-900">{{ employees?.length || 0 }}</p>
        </div>
      </div>
      <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
        <div class="p-3 bg-amber-50 rounded-lg text-amber-600">
          <PieChart class="h-6 w-6" />
        </div>
        <div>
          <p class="text-sm text-slate-500">Budget Allocated</p>
          <p class="text-2xl font-bold text-slate-900">
            ${{ departments?.reduce((acc, d) => acc + (d.headcount_budget || 0), 0).toLocaleString() }}
          </p>
        </div>
      </div>
    </div>

    <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm flex items-center gap-4">
      <UiInput v-model="searchQuery" placeholder="Search departments..." class="w-full max-w-sm">
        <template #prefix><Search class="h-4 w-4 text-slate-400" /></template>
      </UiInput>
    </div>

    <UiTable :columns="columns" :data="filteredDepartments" :loading="isLoading">
      <template #cell(name)="{ item }">
        <div class="font-medium text-slate-900">{{ item.name }}</div>
        <div v-if="item.parent" class="text-xs text-slate-500">Under: {{ item.parent.name }}</div>
      </template>
      <template #cell(manager)="{ item }">
        <div v-if="item.manager" class="flex items-center gap-2">
          <div class="h-6 w-6 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-bold">
            {{ item.manager.first_name[0] }}{{ item.manager.last_name[0] }}
          </div>
          <span class="text-sm text-slate-600">{{ item.manager.first_name }} {{ item.manager.last_name }}</span>
        </div>
        <span v-else class="text-slate-400 text-sm">Not Assigned</span>
      </template>
      <template #cell(headcount)="{ item }">
        <div class="text-sm text-slate-600">0 / {{ item.headcount_budget || '-' }}</div>
      </template>
      <template #cell(budget)="{ item }">
        <div class="text-sm text-slate-600">{{ item.cost_center || 'N/A' }}</div>
      </template>
      <template #cell(actions)="{ item }">
        <div class="flex justify-end gap-2">
          <UiButton variant="ghost" size="sm" @click="openModal(item)">
            <Edit2 class="h-4 w-4" />
          </UiButton>
          <UiButton variant="ghost" size="sm" class="text-red-500 hover:text-red-600 hover:bg-red-50" @click="deleteDepartment(item.id)">
            <Trash2 class="h-4 w-4" />
          </UiButton>
        </div>
      </template>
    </UiTable>

    <UiModal 
      v-model="isModalOpen" 
      :title="editingDepartment ? 'Edit Department' : 'New Department'"
      size="md"
    >
      <div class="space-y-4">
        <UiInput v-model="form.name" label="Department Name" placeholder="e.g. Engineering" />
        
        <div class="grid grid-cols-2 gap-4">
          <UiSelect 
            v-model="form.manager_id" 
            label="Department Head" 
            :options="employees?.map(e => ({ label: `${e.first_name} ${e.last_name}`, value: e.id })) || []"
            placeholder="Select Manager"
          />
          <UiSelect 
            v-model="form.parent_id" 
            label="Parent Department" 
            :options="departments?.filter(d => d.id !== editingDepartment?.id).map(d => ({ label: d.name, value: d.id })) || []"
            placeholder="None"
          />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <UiInput v-model="form.cost_center" label="Cost Center" placeholder="CC-001" />
          <UiInput v-model="form.headcount_budget" type="number" label="Headcount Budget" placeholder="10" />
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
          <UiButton variant="outline" @click="isModalOpen = false">Cancel</UiButton>
          <UiButton @click="saveDepartment">
            {{ editingDepartment ? 'Update Department' : 'Create Department' }}
          </UiButton>
        </div>
      </div>
    </UiModal>
  </div>
</template>
