<script setup lang="ts">
import { ref, computed } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import { hrApi } from '@/api/hr'
import UiButton from '@/components/ui/UiButton.vue'
import UiTable from '@/components/ui/UiTable.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import { Search, Download, Filter, Calendar, Users, Clock } from '@lucide/vue'

const viewMode = ref<'by-employee' | 'by-date'>('by-date')
const dateRange = ref({
  start: new Date().toISOString().split('T')[0],
  end: new Date().toISOString().split('T')[0]
})

const { data: attendance, isLoading } = useQuery({
  queryKey: ['hr', 'attendance', dateRange],
  queryFn: () => hrApi.getAttendance({ ...dateRange.value }).then(res => res.data)
})

const columns = computed(() => {
  if (viewMode.value === 'by-date') {
    return [
      { key: 'employee', label: 'Employee' },
      { key: 'clock_in', label: 'Clock In' },
      { key: 'clock_out', label: 'Clock Out' },
      { key: 'status', label: 'Status' },
      { key: 'late_minutes', label: 'Late' },
      { key: 'total_hours', label: 'Hours' },
    ]
  } else {
    return [
      { key: 'employee', label: 'Employee' },
      { key: 'days_present', label: 'Days Present' },
      { key: 'days_absent', label: 'Days Absent' },
      { key: 'total_late_minutes', label: 'Total Late' },
      { key: 'total_overtime', label: 'Overtime' },
    ]
  }
})

const exportToCSV = () => {
  // Mock export
  console.log('Exporting to CSV...')
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Attendance</h1>
        <p class="text-slate-500">Track and monitor employee work hours.</p>
      </div>
      <UiButton variant="outline" @click="exportToCSV">
        <Download class="h-4 w-4 mr-2" /> Export to CSV
      </UiButton>
    </div>

    <!-- Filters & View Toggle -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm space-y-4">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center bg-slate-100 p-1 rounded-lg">
          <button 
            @click="viewMode = 'by-date'"
            class="flex items-center gap-2 px-4 py-1.5 text-sm font-medium rounded-md transition-colors"
            :class="viewMode === 'by-date' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
          >
            <Calendar class="h-4 w-4" /> By Date
          </button>
          <button 
            @click="viewMode = 'by-employee'"
            class="flex items-center gap-2 px-4 py-1.5 text-sm font-medium rounded-md transition-colors"
            :class="viewMode === 'by-employee' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
          >
            <Users class="h-4 w-4" /> By Employee
          </button>
        </div>

        <div class="flex items-center gap-2">
          <UiInput v-model="dateRange.start" type="date" size="sm" class="w-40" />
          <span class="text-slate-400">to</span>
          <UiInput v-model="dateRange.end" type="date" size="sm" class="w-40" />
          <UiButton size="sm">Apply</UiButton>
        </div>
      </div>

      <div class="flex flex-wrap items-center gap-4 pt-4 border-t border-slate-100">
        <UiInput placeholder="Search employee..." size="sm" class="w-full max-w-xs">
          <template #prefix><Search class="h-4 w-4 text-slate-400" /></template>
        </UiInput>
        <UiSelect placeholder="Department" size="sm" :options="[]" class="w-48" />
        <UiSelect placeholder="Status" size="sm" :options="[{label:'Present', value:'present'}, {label:'Absent', value:'absent'}, {label:'Late', value:'late'}]" class="w-40" />
      </div>
    </div>

    <!-- Attendance Table -->
    <UiTable :columns="columns" :data="attendance || []" :loading="isLoading">
      <template #cell(employee)="{ item }">
        <div class="flex items-center gap-3">
          <div class="h-8 w-8 rounded-full bg-slate-100 flex items-center justify-center text-xs font-bold">
            {{ item.employee?.first_name[0] }}{{ item.employee?.last_name[0] }}
          </div>
          <span class="font-medium">{{ item.employee?.first_name }} {{ item.employee?.last_name }}</span>
        </div>
      </template>

      <template #cell(clock_in)="{ item }">
        <div :class="{ 'text-red-600 font-medium': item.late_minutes > 0 }">
          {{ item.clock_in ? new Date(item.clock_in).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '-' }}
        </div>
      </template>

      <template #cell(clock_out)="{ item }">
        {{ item.clock_out ? new Date(item.clock_out).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '-' }}
      </template>

      <template #cell(status)="{ item }">
        <UiBadge :variant="item.status === 'present' ? 'success' : item.status === 'absent' ? 'danger' : 'warning'">
          {{ item.status }}
        </UiBadge>
      </template>

      <template #cell(late_minutes)="{ item }">
        <span v-if="item.late_minutes > 0" class="text-red-600 font-medium">+{{ item.late_minutes }}m</span>
        <span v-else class="text-slate-400">-</span>
      </template>

      <template #cell(total_hours)="{ item }">
        {{ item.total_hours ? item.total_hours.toFixed(1) + 'h' : '-' }}
      </template>
    </UiTable>
  </div>
</template>
