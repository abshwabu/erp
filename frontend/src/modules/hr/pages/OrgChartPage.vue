<script setup lang="ts">
import { useQuery } from '@tanstack/vue-query'
import { hrApi } from '@/api/hr'
import { computed } from 'vue'

const { data: employees } = useQuery({
  queryKey: ['hr', 'employees'],
  queryFn: () => hrApi.getEmployees().then(res => res.data)
})

const orgTree = computed(() => {
  if (!employees.value) return []
  // Basic tree construction - needs mapping
  return employees.value.filter(e => !e.manager_id)
})
</script>

<template>
  <div class="space-y-6">
    <h1 class="text-2xl font-bold text-slate-900">Organizational Chart</h1>
    <div class="p-6 bg-white border border-slate-200 rounded-lg">
      <div v-for="emp in orgTree" :key="emp.id" class="border p-4 rounded">
        {{ emp.first_name }} {{ emp.last_name }} - {{ emp.position?.title }}
      </div>
    </div>
  </div>
</template>
