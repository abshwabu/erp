<script setup lang="ts">
import { ref, computed } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { hrApi } from '@/api/hr'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiTable from '@/components/ui/UiTable.vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import { Plus, Search, Building2, Users, PieChart, Edit2, Trash2 } from '@lucide/vue'
import type { Department, Employee } from '@/types/hr'

const queryClient = useQueryClient()
const isModalOpen = ref(false)
const editingDepartment = ref<Partial<Department> | null>(null)
const searchQuery = ref('')
const errorMessage = ref('')

const { data: departments, isLoading } = useQuery({
  queryKey: ['hr', 'departments'],
  queryFn: () => hrApi.getDepartments().then((res) => res.data),
})

const { data: employees } = useQuery({
  queryKey: ['hr', 'employees'],
  queryFn: () => hrApi.getEmployees().then((res) => res.data),
})

const saveMutation = useMutation({
  mutationFn: (data: Partial<Department>) =>
    editingDepartment.value
      ? hrApi.updateDepartment(editingDepartment.value.id!, data)
      : hrApi.createDepartment(data),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['hr', 'departments'] })
    isModalOpen.value = false
    errorMessage.value = ''
  },
  onError: (err: any) => {
    errorMessage.value = err?.response?.data?.message || err?.message || 'Failed to save department'
  },
})

const deleteMutation = useMutation({
  mutationFn: (id: string) => hrApi.deleteDepartment(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['hr', 'departments'] })
    errorMessage.value = ''
  },
  onError: (err: any) => {
    errorMessage.value = err?.response?.data?.message || err?.message || 'Failed to delete department'
  },
})

const filteredDepartments = computed(() => {
  if (!departments.value) return []
  return departments.value.filter((d) =>
    d.name.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})

const columns = [
  { key: 'name', label: 'Department Name' },
  { key: 'manager', label: 'Head of Department' },
  { key: 'headcount', label: 'Current Staff' },
  { key: 'code', label: 'Code / Cost Center' },
  { key: 'actions', label: 'Actions', align: 'right' as const },
]

const form = ref<Partial<Department>>({
  name: '',
  manager_id: '',
  parent_id: '',
  code: '',
  cost_center_id: '',
})

const openModal = (dept: Department | null = null) => {
  errorMessage.value = ''
  if (dept) {
    editingDepartment.value = dept
    form.value = {
      name: dept.name,
      manager_id: (dept as any).head_employee_id || (dept as any).manager_id || '',
      parent_id: dept.parent_id || '',
      code: dept.code || '',
      cost_center_id: (dept as any).cost_center_id || '',
    }
  } else {
    editingDepartment.value = null
    form.value = { name: '', manager_id: '', parent_id: '', code: '', cost_center_id: '' }
  }
  isModalOpen.value = true
}

const saveDepartment = () => {
  saveMutation.mutate(form.value)
}

const deleteDepartment = (dept: any) => {
  if ((dept.employees_count ?? 0) > 0) {
    alert(`Cannot delete "${dept.name}" because it has ${dept.employees_count} staff members.`)
    return
  }
  if (confirm(`Are you sure you want to delete department "${dept.name}"?`)) {
    deleteMutation.mutate(dept.id)
  }
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Departments</h1>
        <p class="text-slate-500">Manage company organizational units, heads, and staff distribution.</p>
      </div>
      <UiButton @click="openModal()">
        <Plus class="h-4 w-4 mr-2" /> New Department
      </UiButton>
    </div>

    <div v-if="errorMessage" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 flex items-center justify-between">
      <span>{{ errorMessage }}</span>
      <button type="button" @click="errorMessage = ''" class="text-red-500 hover:text-red-700 font-bold ml-2">✕</button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4">
        <div class="p-3 bg-blue-50 rounded-xl text-blue-600">
          <Building2 class="h-6 w-6" />
        </div>
        <div>
          <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Departments</p>
          <p class="text-2xl font-black text-slate-900 font-mono">{{ departments?.length || 0 }}</p>
        </div>
      </div>

      <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4">
        <div class="p-3 bg-emerald-50 rounded-xl text-emerald-600">
          <Users class="h-6 w-6" />
        </div>
        <div>
          <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Workforce</p>
          <p class="text-2xl font-black text-emerald-600 font-mono">{{ employees?.length || 0 }}</p>
        </div>
      </div>

      <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4">
        <div class="p-3 bg-purple-50 rounded-xl text-purple-600">
          <PieChart class="h-6 w-6" />
        </div>
        <div>
          <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Active Units</p>
          <p class="text-2xl font-black text-purple-600 font-mono">
            {{ departments?.filter((d: any) => (d.employees_count || 0) > 0).length || 0 }}
          </p>
        </div>
      </div>
    </div>

    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex items-center gap-4">
      <UiInput v-model="searchQuery" placeholder="Search departments by name..." class="w-full max-w-sm">
        <template #prefix><Search class="h-4 w-4 text-slate-400" /></template>
      </UiInput>
    </div>

    <UiTable :columns="columns" :data="filteredDepartments" :loading="isLoading">
      <template #cell(name)="{ item }">
        <div class="font-bold text-slate-900 text-sm">{{ item.name }}</div>
        <div v-if="item.parent" class="text-xs text-slate-500">Parent: {{ item.parent.name }}</div>
      </template>

      <template #cell(manager)="{ item }">
        <div v-if="item.manager" class="flex items-center gap-2">
          <div class="h-7 w-7 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-bold text-slate-700">
            {{ item.manager.first_name?.[0] }}{{ item.manager.last_name?.[0] }}
          </div>
          <span class="text-xs font-semibold text-slate-800">{{ item.manager.first_name }} {{ item.manager.last_name }}</span>
        </div>
        <span v-else class="text-slate-400 text-xs italic">Unassigned</span>
      </template>

      <template #cell(headcount)="{ item }">
        <UiBadge variant="default" class="font-semibold font-mono text-xs">
          {{ item.employees_count ?? 0 }} Staff
        </UiBadge>
      </template>

      <template #cell(code)="{ item }">
        <span class="text-xs font-mono text-slate-600">{{ item.code || item.cost_center_id || '—' }}</span>
      </template>

      <template #cell(actions)="{ item }">
        <div class="flex justify-end gap-1">
          <UiButton variant="ghost" size="sm" @click="openModal(item)">
            <Edit2 class="h-4 w-4" />
          </UiButton>
          <UiButton
            variant="ghost"
            size="sm"
            class="text-red-500 hover:text-red-600 hover:bg-red-50"
            @click="deleteDepartment(item)"
          >
            <Trash2 class="h-4 w-4" />
          </UiButton>
        </div>
      </template>
    </UiTable>

    <!-- Create / Edit Department Modal -->
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
            :options="[{ label: 'Select Manager', value: '' }, ...(employees?.map((e: any) => ({ label: `${e.first_name} ${e.last_name}`, value: e.id })) || [])]"
            placeholder="Select Manager"
          />
          <UiSelect
            v-model="form.parent_id"
            label="Parent Department"
            :options="[{ label: 'None (Top Level)', value: '' }, ...(departments?.filter((d: any) => d.id !== editingDepartment?.id).map((d: any) => ({ label: d.name, value: d.id })) || [])]"
            placeholder="None"
          />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <UiInput v-model="form.code" label="Department Code" placeholder="ENG" />
          <UiInput v-model="form.cost_center_id" label="Cost Center" placeholder="CC-100" />
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
          <UiButton variant="outline" @click="isModalOpen = false">Cancel</UiButton>
          <UiButton @click="saveDepartment" :loading="saveMutation.isPending.value" :disabled="!form.name">
            {{ editingDepartment ? 'Update Department' : 'Create Department' }}
          </UiButton>
        </div>
      </div>
    </UiModal>
  </div>
</template>
