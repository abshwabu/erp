<script setup lang="ts">
import { ref } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import { hrApi } from '@/api/hr'
import UiTable from '@/components/ui/UiTable.vue'
import UiInput from '@/components/ui/UiInput.vue'

const date = ref(new Date().toISOString().split('T')[0])
const { data: attendance, isLoading } = useQuery({
  queryKey: ['hr', 'attendance', date],
  queryFn: () => hrApi.getAttendance({ date: date.value }).then(res => res.data)
})

const columns = [
  { key: 'employee', label: 'Employee' },
  { key: 'clock_type', label: 'Type' },
  { key: 'logged_at', label: 'Time' },
]
</script>

<template>
  <div class="space-y-6">
    <h1 class="text-2xl font-bold text-slate-900">Attendance</h1>
    
    <div class="flex gap-4">
      <UiInput v-model="date" type="date" label="Date" />
    </div>

    <UiTable :columns="columns" :data="attendance || []" :loading="isLoading">
      <template #cell(employee)="{ item }">
        {{ item.employee_id }}
      </template>
      <template #cell(logged_at)="{ item }">
        {{ new Date(item.logged_at).toLocaleTimeString() }}
      </template>
    </UiTable>
  </div>
</template>
