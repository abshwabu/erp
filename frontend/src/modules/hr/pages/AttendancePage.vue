<script setup lang="ts">
import { ref, computed } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { hrApi } from '@/api/hr'
import UiButton from '@/components/ui/UiButton.vue'
import UiTable from '@/components/ui/UiTable.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiModal from '@/components/ui/UiModal.vue'
import {
  Search,
  Download,
  Filter,
  Calendar,
  Users,
  Clock,
  Plus,
  CheckCircle2,
  AlertCircle,
  LogIn,
  LogOut,
} from '@lucide/vue'

const queryClient = useQueryClient()
const viewMode = ref<'by-date' | 'by-employee'>('by-date')
const dateRange = ref({
  start: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
  end: new Date().toISOString().split('T')[0],
})
const searchQuery = ref('')
const departmentFilter = ref('')
const statusFilter = ref('')

const isClockModalOpen = ref(false)
const clockForm = ref({
  employee_id: '',
  action_type: 'in' as 'in' | 'out',
  method: 'web',
  notes: '',
})

// Queries
const { data: departments } = useQuery({
  queryKey: ['hr', 'departments'],
  queryFn: () => hrApi.getDepartments().then((res) => res.data),
})

const { data: employees } = useQuery({
  queryKey: ['hr', 'employees'],
  queryFn: () => hrApi.getEmployees().then((res) => res.data),
})

const { data: attendance, isLoading } = useQuery({
  queryKey: ['hr', 'attendance', dateRange],
  queryFn: () => hrApi.getAttendance({ start_date: dateRange.value.start, end_date: dateRange.value.end }).then((res) => res.data),
})

const { data: attendanceSummary } = useQuery({
  queryKey: ['hr', 'attendance-summary'],
  queryFn: () => hrApi.getAttendanceSummary().then((res) => res.data),
})

const departmentOptions = computed(() => [
  { label: 'All Departments', value: '' },
  ...(departments.value || []).map((d: any) => ({ label: d.name, value: d.id })),
])

const employeeOptions = computed(() => [
  { label: 'Select Employee', value: '' },
  ...(employees.value || []).map((e: any) => ({
    label: `${e.first_name} ${e.last_name} (${e.employee_number || 'No ID'})`,
    value: e.id,
  })),
])

// Mutations
const clockMutation = useMutation({
  mutationFn: (data: { employee_id: string; method: string; notes?: string; action: 'in' | 'out' }) => {
    if (data.action === 'in') {
      return hrApi.clockIn({ employee_id: data.employee_id, method: data.method })
    }
    return hrApi.clockOut({ employee_id: data.employee_id, method: data.method })
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['hr', 'attendance'] })
    queryClient.invalidateQueries({ queryKey: ['hr', 'attendance-summary'] })
    isClockModalOpen.value = false
    clockForm.value = { employee_id: '', action_type: 'in', method: 'web', notes: '' }
  },
})

const filteredAttendance = computed(() => {
  let rows = (attendance.value as any) || []
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    rows = rows.filter((item: any) => {
      const name = `${item.employee?.first_name || ''} ${item.employee?.last_name || ''} ${item.employee?.employee_number || ''}`.toLowerCase()
      return name.includes(q)
    })
  }
  if (departmentFilter.value) {
    rows = rows.filter((item: any) => item.employee?.department_id === departmentFilter.value)
  }
  if (statusFilter.value) {
    rows = rows.filter((item: any) => item.status === statusFilter.value)
  }
  return rows
})

const columns = computed(() => [
  { key: 'employee', label: 'Employee' },
  { key: 'date', label: 'Date' },
  { key: 'clock_in', label: 'Clock In' },
  { key: 'clock_out', label: 'Clock Out' },
  { key: 'status', label: 'Status' },
  { key: 'late_minutes', label: 'Late (Mins)' },
  { key: 'total_hours', label: 'Total Hours' },
])

const exportToCSV = () => {
  const data = filteredAttendance.value || []
  if (data.length === 0) return

  const headers = ['Employee Name', 'Employee ID', 'Department', 'Date', 'Clock In', 'Clock Out', 'Status', 'Late Minutes', 'Total Hours']
  const rows = data.map((item: any) => [
    `"${item.employee?.first_name || ''} ${item.employee?.last_name || ''}"`,
    `"${item.employee?.employee_number || ''}"`,
    `"${item.employee?.department?.name || ''}"`,
    `"${item.date || ''}"`,
    `"${item.clock_in ? new Date(item.clock_in).toLocaleTimeString() : '-'}"`,
    `"${item.clock_out ? new Date(item.clock_out).toLocaleTimeString() : '-'}"`,
    `"${item.status || ''}"`,
    item.late_minutes || 0,
    item.total_hours || 0,
  ])

  const csvContent = 'data:text/csv;charset=utf-8,' + [headers.join(','), ...rows.map((e: any[]) => e.join(','))].join('\n')
  const encodedUri = encodeURI(csvContent)
  const link = document.createElement('a')
  link.setAttribute('href', encodedUri)
  link.setAttribute('download', `attendance_report_${new Date().toISOString().slice(0, 10)}.csv`)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Attendance & Shifts</h1>
        <p class="text-slate-500">Track and manage employee work hours, daily clock-ins, and late minutes.</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <UiButton variant="outline" @click="exportToCSV">
          <Download class="h-4 w-4 mr-2" /> Export CSV
        </UiButton>
        <UiButton @click="isClockModalOpen = true">
          <Clock class="h-4 w-4 mr-2" /> Record Clock In/Out
        </UiButton>
      </div>
    </div>

    <!-- Attendance KPI Cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
        <div class="flex items-center justify-between">
          <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Workforce</p>
          <div class="p-2 rounded-xl bg-blue-50 text-blue-600">
            <Users class="w-4 h-4" />
          </div>
        </div>
        <p class="mt-2 text-2xl font-black text-slate-900 font-mono">
          {{ attendanceSummary?.total_employees ?? employees?.length ?? 0 }}
        </p>
        <p class="text-xs text-slate-400 mt-1">Active staff members</p>
      </div>

      <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
        <div class="flex items-center justify-between">
          <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Present Today</p>
          <div class="p-2 rounded-xl bg-emerald-50 text-emerald-600">
            <CheckCircle2 class="w-4 h-4" />
          </div>
        </div>
        <p class="mt-2 text-2xl font-black text-emerald-600 font-mono">
          {{ attendanceSummary?.present_today ?? 0 }}
        </p>
        <p class="text-xs text-slate-400 mt-1">Clocked in today</p>
      </div>

      <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
        <div class="flex items-center justify-between">
          <p class="text-xs font-bold uppercase tracking-wider text-slate-500">On Leave Today</p>
          <div class="p-2 rounded-xl bg-amber-50 text-amber-600">
            <Calendar class="w-4 h-4" />
          </div>
        </div>
        <p class="mt-2 text-2xl font-black text-amber-600 font-mono">
          {{ attendanceSummary?.on_leave_today ?? 0 }}
        </p>
        <p class="text-xs text-slate-400 mt-1">Approved time-off</p>
      </div>

      <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
        <div class="flex items-center justify-between">
          <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Absent / Not In</p>
          <div class="p-2 rounded-xl bg-red-50 text-red-600">
            <AlertCircle class="w-4 h-4" />
          </div>
        </div>
        <p class="mt-2 text-2xl font-black text-red-600 font-mono">
          {{ attendanceSummary?.absent_today ?? 0 }}
        </p>
        <p class="text-xs text-slate-400 mt-1">Unrecorded presence</p>
      </div>
    </div>

    <!-- Filters & Date Selector -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs space-y-4">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-2">
          <span class="text-xs font-semibold text-slate-500">From:</span>
          <UiInput v-model="dateRange.start" type="date" size="sm" class="w-36" />
          <span class="text-xs font-semibold text-slate-500">To:</span>
          <UiInput v-model="dateRange.end" type="date" size="sm" class="w-36" />
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <UiInput v-model="searchQuery" placeholder="Search employee or ID..." size="sm" class="w-56">
            <template #prefix><Search class="h-4 w-4 text-slate-400" /></template>
          </UiInput>
          <UiSelect
            v-model="departmentFilter"
            size="sm"
            :options="departmentOptions"
            class="w-44"
          />
          <UiSelect
            v-model="statusFilter"
            size="sm"
            :options="[
              { label: 'All Statuses', value: '' },
              { label: 'Present', value: 'present' },
              { label: 'Late', value: 'late' },
              { label: 'Absent', value: 'absent' },
            ]"
            class="w-36"
          />
        </div>
      </div>
    </div>

    <!-- Attendance Table -->
    <UiTable :columns="columns" :data="filteredAttendance" :loading="isLoading">
      <template #cell(employee)="{ item }">
        <div class="flex items-center gap-3">
          <div class="h-9 w-9 rounded-full bg-slate-100 flex items-center justify-center text-xs font-bold text-slate-700">
            {{ item.employee?.first_name?.[0] || 'E' }}{{ item.employee?.last_name?.[0] || 'M' }}
          </div>
          <div>
            <span class="font-bold text-slate-900 text-sm">{{ item.employee?.first_name }} {{ item.employee?.last_name }}</span>
            <p class="text-[11px] text-slate-400 font-mono">{{ item.employee?.employee_number }} • {{ item.employee?.department?.name || 'No Dept' }}</p>
          </div>
        </div>
      </template>

      <template #cell(date)="{ item }">
        <span class="text-xs font-medium text-slate-700">{{ item.date }}</span>
      </template>

      <template #cell(clock_in)="{ item }">
        <div :class="{ 'text-amber-600 font-bold': item.late_minutes > 0, 'text-slate-800': !item.late_minutes }">
          {{ item.clock_in ? new Date(item.clock_in).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '—' }}
        </div>
      </template>

      <template #cell(clock_out)="{ item }">
        <span class="text-slate-700 text-xs">
          {{ item.clock_out ? new Date(item.clock_out).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '—' }}
        </span>
      </template>

      <template #cell(status)="{ item }">
        <UiBadge :variant="item.status === 'present' ? 'success' : item.status === 'late' ? 'warning' : 'danger'" class="capitalize font-bold">
          {{ item.status }}
        </UiBadge>
      </template>

      <template #cell(late_minutes)="{ item }">
        <span v-if="item.late_minutes > 0" class="text-amber-600 font-bold font-mono text-xs">+{{ item.late_minutes }}m</span>
        <span v-else class="text-slate-400 font-mono text-xs">—</span>
      </template>

      <template #cell(total_hours)="{ item }">
        <span v-if="item.total_hours > 0" class="font-bold text-slate-900 font-mono text-xs">{{ item.total_hours.toFixed(1) }}h</span>
        <span v-else class="text-slate-400 font-mono text-xs">—</span>
      </template>
    </UiTable>

    <!-- Clock In/Out Modal -->
    <UiModal v-model="isClockModalOpen" title="Record Attendance Entry" size="md">
      <div class="space-y-4">
        <UiSelect
          v-model="clockForm.employee_id"
          label="Employee"
          :options="employeeOptions"
          placeholder="Select employee"
        />

        <div class="space-y-1.5">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Action</label>
          <div class="grid grid-cols-2 gap-3">
            <button
              type="button"
              @click="clockForm.action_type = 'in'"
              :class="[
                'flex items-center justify-center gap-2 p-3 rounded-xl border text-sm font-bold transition-all',
                clockForm.action_type === 'in'
                  ? 'border-emerald-500 bg-emerald-50 text-emerald-700 shadow-xs'
                  : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50',
              ]"
            >
              <LogIn class="w-4 h-4" /> Clock In
            </button>
            <button
              type="button"
              @click="clockForm.action_type = 'out'"
              :class="[
                'flex items-center justify-center gap-2 p-3 rounded-xl border text-sm font-bold transition-all',
                clockForm.action_type === 'out'
                  ? 'border-blue-500 bg-blue-50 text-blue-700 shadow-xs'
                  : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50',
              ]"
            >
              <LogOut class="w-4 h-4" /> Clock Out
            </button>
          </div>
        </div>

        <UiInput v-model="clockForm.notes" label="Notes" placeholder="Optional comments or location notes" />

        <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
          <UiButton variant="outline" @click="isClockModalOpen = false">Cancel</UiButton>
          <UiButton
            :loading="clockMutation.isPending.value"
            :disabled="!clockForm.employee_id"
            @click="clockMutation.mutate({
              employee_id: clockForm.employee_id,
              method: clockForm.method,
              notes: clockForm.notes,
              action: clockForm.action_type,
            })"
          >
            Submit Entry
          </UiButton>
        </div>
      </div>
    </UiModal>
  </div>
</template>
