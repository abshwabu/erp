<script setup lang="ts">
import { useQuery } from '@tanstack/vue-query'
import { hrApi } from '@/api/hr'
import UiTable from '@/components/ui/UiTable.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiBadge from '@/components/ui/UiBadge.vue'

const { data: requests, isLoading } = useQuery({
  queryKey: ['hr', 'leave-requests'],
  queryFn: () => hrApi.getLeaveRequests().then(res => res.data)
})

const columns = [
  { key: 'employee', label: 'Employee' },
  { key: 'dates', label: 'Dates' },
  { key: 'type', label: 'Type' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: '' },
]
</script>

<template>
  <div class="space-y-6">
    <h1 class="text-2xl font-bold text-slate-900">Leave Management</h1>
    
    <UiTable :columns="columns" :data="requests || []" :loading="isLoading">
      <template #cell(employee)="{ item }">
        {{ item.employee_id }} <!-- Needs employee name from relation -->
      </template>
      <template #cell(dates)="{ item }">
        {{ item.start_date }} to {{ item.end_date }}
      </template>
      <template #cell(type)="{ item }">
        {{ item.leave_type?.name }}
      </template>
      <template #cell(status)="{ item }">
        <UiBadge :variant="item.status === 'approved' ? 'success' : 'warning'">{{ item.status }}</UiBadge>
      </template>
      <template #cell(actions)="{ item }">
        <div v-if="item.status === 'pending'" class="flex gap-2">
          <UiButton size="sm" variant="primary">Approve</UiButton>
          <UiButton size="sm" variant="danger">Reject</UiButton>
        </div>
      </template>
    </UiTable>
  </div>
</template>
