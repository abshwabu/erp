<script setup lang="ts">
import { ref, computed, markRaw } from 'vue'
import { useRouter } from 'vue-router'
import { useQuery } from '@tanstack/vue-query'
import { hrApi } from '@/api/hr'
import UiButton from '@/components/ui/UiButton.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import { ZoomIn, ZoomOut } from '@lucide/vue'
import OrgNode from '../components/OrgNode.vue'
import type { Employee } from '@/types/hr'

const router = useRouter()

const icons = {
  ZoomIn: markRaw(ZoomIn),
  ZoomOut: markRaw(ZoomOut)
}
const { data: employees, isLoading } = useQuery({
  queryKey: ['hr', 'employees'],
  queryFn: () => hrApi.getEmployees().then(res => res.data)
})

const { data: departments } = useQuery({
  queryKey: ['hr', 'departments'],
  queryFn: () => hrApi.getDepartments().then(res => res.data)
})

const selectedDepartment = ref('')
const zoom = ref(1)

const orgTree = computed(() => {
  if (!employees.value) return []
  
  const emps = employees.value
  const buildNode = (emp: Employee): any => {
    const children = emps.filter(e => e.manager_id === emp.id)
    return {
      ...emp,
      children: children.map(buildNode)
    }
  }
  
  // Find roots (employees without managers or whose manager is not in the list)
  const roots = emps.filter(e => !e.manager_id || !emps.find(m => m.id === e.manager_id))
  return roots.map(buildNode)
})

const handleNodeClick = (id: string) => {
  router.push({ name: 'hr-employee-profile', params: { id } })
}
</script>

<template>
  <div class="space-y-6 h-[calc(100vh-12rem)] flex flex-col">
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Organizational Chart</h1>
        <p class="text-slate-500">Visualize the company structure and reporting lines.</p>
      </div>
      <div class="flex items-center gap-4 bg-white p-2 rounded-lg border border-slate-200 shadow-sm">
        <UiSelect 
          v-model="selectedDepartment" 
          placeholder="All Departments" 
          :options="departments?.map(d => ({ label: d.name, value: d.id })) || []"
          size="sm"
          class="w-48"
        />
        <div class="flex items-center gap-1 border-l border-slate-100 pl-4">
          <UiButton variant="ghost" size="sm" @click="zoom = Math.max(0.5, zoom - 0.1)"><component :is="icons.ZoomOut" class="h-4 w-4" /></UiButton>
          <span class="text-xs font-bold text-slate-500 w-12 text-center">{{ Math.round(zoom * 100) }}%</span>
          <UiButton variant="ghost" size="sm" @click="zoom = Math.min(2, zoom + 0.1)"><component :is="icons.ZoomIn" class="h-4 w-4" /></UiButton>
        </div>
      </div>
    </div>

    <div class="flex-1 bg-slate-50 rounded-xl border border-slate-200 overflow-auto relative p-12">
      <div 
        class="transition-transform duration-200 origin-top flex justify-center"
        :style="{ transform: `scale(${zoom})` }"
      >
        <div v-if="orgTree.length > 0" class="flex gap-12">
          <OrgNode 
            v-for="root in orgTree" 
            :key="root.id" 
            :node="root" 
            @click="handleNodeClick"
          />
        </div>
        <div v-else-if="isLoading" class="flex items-center justify-center py-24">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.origin-top {
  transform-origin: top center;
}
</style>
