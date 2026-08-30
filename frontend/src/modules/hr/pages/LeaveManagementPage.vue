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
  CheckCircle,
  XCircle,
  Calendar as CalendarIcon,
  Filter,
  Download,
  Search,
  ChevronLeft,
  ChevronRight,
  Plus,
  Clock,
  CheckCircle2,
  AlertCircle,
} from '@lucide/vue'

const queryClient = useQueryClient()
const activeTab = ref<'pending' | 'all' | 'calendar'>('pending')
const searchQuery = ref('')
const selectedDepartment = ref('')
const selectedStatus = ref('')
const isNewLeaveModalOpen = ref(false)

const newLeaveForm = ref({
  employee_id: '',
  leave_type_id: '',
  start_date: new Date().toISOString().slice(0, 10),
  end_date: new Date().toISOString().slice(0, 10),
  reason: '',
  half_day: false,
})

// Queries
const { data: leaveRequests, isLoading } = useQuery({
  queryKey: ['hr', 'leave-requests'],
  queryFn: () => hrApi.getLeaveRequests().then((res) => res.data),
})

const { data: departments } = useQuery({
  queryKey: ['hr', 'departments'],
  queryFn: () => hrApi.getDepartments().then((res) => res.data),
})

const { data: employees } = useQuery({
  queryKey: ['hr', 'employees'],
  queryFn: () => hrApi.getEmployees().then((res) => res.data),
})

const { data: leaveTypes } = useQuery({
  queryKey: ['hr', 'leave-types'],
  queryFn: () => hrApi.getLeaveTypes().then((res) => res.data),
})

const pendingRequests = computed(() =>
  (leaveRequests.value || []).filter((r: any) => r.status === 'pending')
)

const approveMutation = useMutation({
  mutationFn: ({ id, notes }: { id: string; notes?: string }) => hrApi.approveLeaveRequest(id, notes),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['hr', 'leave-requests'] })
    queryClient.invalidateQueries({ queryKey: ['hr', 'attendance-summary'] })
  },
})

const rejectMutation = useMutation({
  mutationFn: ({ id, notes }: { id: string; notes?: string }) => hrApi.rejectLeaveRequest(id, notes || ''),
  onSuccess: () => queryClient.invalidateQueries({ queryKey: ['hr', 'leave-requests'] }),
})

const submitLeaveMutation = useMutation({
  mutationFn: (data: any) => hrApi.submitLeaveRequest(data),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['hr', 'leave-requests'] })
    isNewLeaveModalOpen.value = false
    newLeaveForm.value = {
      employee_id: '',
      leave_type_id: '',
      start_date: new Date().toISOString().slice(0, 10),
      end_date: new Date().toISOString().slice(0, 10),
      reason: '',
      half_day: false,
    }
  },
})

const rejectionNotes = ref<Record<string, string>>({})

const employeeOptions = computed(() => [
  { label: 'Select Employee', value: '' },
  ...(employees.value || []).map((e: any) => ({
    label: `${e.first_name} ${e.last_name} (${e.employee_number || 'No ID'})`,
    value: e.id,
  })),
])

const leaveTypeOptions = computed(() => [
  { label: 'Select Leave Type', value: '' },
  ...(leaveTypes.value || []).map((t: any) => ({
    label: `${t.name} (${t.max_days_per_year || 0} days/yr)`,
    value: t.id,
  })),
])

const filteredLeaveRequests = computed(() => {
  let list = leaveRequests.value || []
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter((r: any) => {
      const name = `${r.employee?.first_name || ''} ${r.employee?.last_name || ''} ${r.employee?.employee_number || ''}`.toLowerCase()
      return name.includes(q)
    })
  }
  if (selectedDepartment.value) {
    list = list.filter((r: any) => r.employee?.department_id === selectedDepartment.value)
  }
  if (selectedStatus.value) {
    list = list.filter((r: any) => r.status === selectedStatus.value)
  }
  return list
})

const columns = [
  { key: 'employee', label: 'Employee' },
  { key: 'leave_type', label: 'Leave Type' },
  { key: 'dates', label: 'Dates Requested' },
  { key: 'days', label: 'Days' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: 'Actions', align: 'right' as const },
]

// Calendar Logic
const currentMonth = ref(new Date())
const calendarDays = computed(() => {
  const year = currentMonth.value.getFullYear()
  const month = currentMonth.value.getMonth()
  const firstDay = new Date(year, month, 1)
  const lastDay = new Date(year, month + 1, 0)

  const days = []
  for (let i = 0; i < firstDay.getDay(); i++) {
    days.push(null)
  }
  for (let i = 1; i <= lastDay.getDate(); i++) {
    days.push(new Date(year, month, i))
  }
  return days
})

const getLeavesForDate = (date: Date | null) => {
  if (!date) return []
  return (
    leaveRequests.value?.filter((r: any) => {
      if (r.status !== 'approved') return false
      const start = new Date(r.start_date)
      const end = new Date(r.end_date)
      const d = new Date(date)
      d.setHours(0, 0, 0, 0)
      start.setHours(0, 0, 0, 0)
      end.setHours(0, 0, 0, 0)
      return d >= start && d <= end
    }) || []
  )
}

const nextMonth = () => {
  currentMonth.value = new Date(currentMonth.value.getFullYear(), currentMonth.value.getMonth() + 1, 1)
}

const prevMonth = () => {
  currentMonth.value = new Date(currentMonth.value.getFullYear(), currentMonth.value.getMonth() - 1, 1)
}

const exportToCSV = () => {
  const data = filteredLeaveRequests.value || []
  if (data.length === 0) return

  const headers = ['Employee Name', 'Leave Type', 'Start Date', 'End Date', 'Days', 'Status', 'Reason']
  const rows = data.map((item: any) => [
    `"${item.employee?.first_name || ''} ${item.employee?.last_name || ''}"`,
    `"${item.leave_type?.name || ''}"`,
    `"${item.start_date || ''}"`,
    `"${item.end_date || ''}"`,
    item.days_taken || 0,
    `"${item.status || ''}"`,
    `"${(item.reason || '').replace(/"/g, '""')}"`,
  ])

  const csvContent = 'data:text/csv;charset=utf-8,' + [headers.join(','), ...rows.map((e: any[]) => e.join(','))].join('\n')
  const encodedUri = encodeURI(csvContent)
  const link = document.createElement('a')
  link.setAttribute('href', encodedUri)
  link.setAttribute('download', `leave_requests_${new Date().toISOString().slice(0, 10)}.csv`)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Leave Management</h1>
        <p class="text-slate-500">Review employee time-off requests, balances, and calendar schedules.</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <UiButton variant="outline" @click="exportToCSV">
          <Download class="h-4 w-4 mr-2" /> Export CSV
        </UiButton>
        <UiButton @click="isNewLeaveModalOpen = true">
          <Plus class="h-4 w-4 mr-2" /> New Request
        </UiButton>
      </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="flex border-b border-slate-200">
      <button
        v-for="tab in [
          { id: 'pending', label: `Pending Requests (${pendingRequests.length})` },
          { id: 'all', label: `All Requests (${leaveRequests?.length || 0})` },
          { id: 'calendar', label: 'Leave Calendar' },
        ]"
        :key="tab.id"
        @click="activeTab = tab.id as any"
        class="px-6 py-3 text-sm font-bold border-b-2 transition-colors"
        :class="activeTab === tab.id ? 'border-primary-600 text-primary-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
      >
        {{ tab.label }}
      </button>
    </div>

    <!-- Tab Content -->
    <div class="mt-4">
      <!-- Pending Tab -->
      <div v-if="activeTab === 'pending'" class="space-y-4">
        <div v-if="pendingRequests.length === 0" class="bg-white p-12 text-center rounded-2xl border border-dashed border-slate-300">
          <CheckCircle2 class="h-12 w-12 text-emerald-400 mx-auto mb-3" />
          <h3 class="text-lg font-bold text-slate-900">All caught up!</h3>
          <p class="text-slate-500 text-sm">There are no pending leave requests requiring review.</p>
        </div>

        <div
          v-for="request in pendingRequests"
          :key="request.id"
          class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs flex flex-col md:flex-row gap-6 items-start justify-between"
        >
          <div class="flex gap-4">
            <div class="h-11 w-11 rounded-full bg-slate-100 flex items-center justify-center font-bold text-slate-700">
              {{ request.employee?.first_name?.[0] }}{{ request.employee?.last_name?.[0] }}
            </div>
            <div>
              <p class="font-bold text-slate-900 text-base">
                {{ request.employee?.first_name }} {{ request.employee?.last_name }}
                <span class="text-xs font-mono text-slate-400 font-normal ml-1">({{ request.employee?.employee_number }})</span>
              </p>
              <div class="flex items-center gap-2 mt-1">
                <UiBadge variant="info">{{ request.leave_type?.name }}</UiBadge>
                <span class="text-xs font-bold text-slate-700">{{ request.days_taken }} Day(s)</span>
              </div>
              <p class="text-xs text-slate-500 mt-2 flex items-center gap-1.5">
                <CalendarIcon class="w-3.5 h-3.5 text-slate-400" />
                {{ new Date(request.start_date).toLocaleDateString() }} → {{ new Date(request.end_date).toLocaleDateString() }}
              </p>
              <p v-if="request.reason" class="mt-2 text-xs bg-slate-50 p-2.5 rounded-xl text-slate-700 border border-slate-100">
                "{{ request.reason }}"
              </p>
            </div>
          </div>

          <div class="w-full md:w-80 space-y-2.5">
            <UiInput v-model="rejectionNotes[request.id]" placeholder="Review notes (optional)" size="sm" />
            <div class="flex gap-2">
              <UiButton
                variant="outline"
                class="flex-1 border-red-200 text-red-600 hover:bg-red-50"
                size="sm"
                @click="rejectMutation.mutate({ id: request.id, notes: rejectionNotes[request.id] })"
              >
                <XCircle class="h-4 w-4 mr-1.5" /> Reject
              </UiButton>
              <UiButton
                class="flex-1"
                size="sm"
                @click="approveMutation.mutate({ id: request.id, notes: rejectionNotes[request.id] })"
              >
                <CheckCircle class="h-4 w-4 mr-1.5" /> Approve
              </UiButton>
            </div>
          </div>
        </div>
      </div>

      <!-- All Requests Tab -->
      <div v-if="activeTab === 'all'" class="space-y-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-wrap items-center gap-3">
          <UiInput v-model="searchQuery" placeholder="Search employee or ID..." class="w-full max-w-xs" size="sm">
            <template #prefix><Search class="h-4 w-4 text-slate-400" /></template>
          </UiInput>
          <UiSelect
            v-model="selectedDepartment"
            :options="[{ label: 'All Departments', value: '' }, ...(departments?.map((d: any) => ({ label: d.name, value: d.id })) || [])]"
            class="w-48"
            size="sm"
          />
          <UiSelect
            v-model="selectedStatus"
            :options="[
              { label: 'All Statuses', value: '' },
              { label: 'Approved', value: 'approved' },
              { label: 'Rejected', value: 'rejected' },
              { label: 'Pending', value: 'pending' },
              { label: 'Cancelled', value: 'cancelled' },
            ]"
            class="w-40"
            size="sm"
          />
        </div>

        <UiTable :columns="columns" :data="filteredLeaveRequests" :loading="isLoading">
          <template #cell(employee)="{ item }">
            <div class="py-1">
              <div class="font-bold text-slate-900 text-sm">{{ item.employee?.first_name }} {{ item.employee?.last_name }}</div>
              <span class="text-xs text-slate-400 font-mono">{{ item.employee?.employee_number }}</span>
            </div>
          </template>

          <template #cell(leave_type)="{ item }">
            <UiBadge variant="default" class="font-semibold">{{ item.leave_type?.name }}</UiBadge>
          </template>

          <template #cell(dates)="{ item }">
            <span class="text-xs font-medium text-slate-700">
              {{ new Date(item.start_date).toLocaleDateString() }} → {{ new Date(item.end_date).toLocaleDateString() }}
            </span>
          </template>

          <template #cell(days)="{ item }">
            <span class="font-bold text-slate-900 text-xs">{{ item.days_taken }} d</span>
          </template>

          <template #cell(status)="{ item }">
            <UiBadge :variant="item.status === 'approved' ? 'success' : item.status === 'pending' ? 'warning' : 'danger'" class="capitalize font-bold">
              {{ item.status }}
            </UiBadge>
          </template>

          <template #cell(actions)="{ item }">
            <div class="flex justify-end gap-1.5" v-if="item.status === 'pending'">
              <UiButton size="sm" variant="outline" @click="approveMutation.mutate({ id: item.id })">Approve</UiButton>
              <UiButton size="sm" variant="ghost" class="text-red-500" @click="rejectMutation.mutate({ id: item.id })">Reject</UiButton>
            </div>
            <span v-else class="text-xs text-slate-400">—</span>
          </template>
        </UiTable>
      </div>

      <!-- Calendar Tab -->
      <div v-if="activeTab === 'calendar'" class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
          <h3 class="font-bold text-slate-900 text-base">
            {{ currentMonth.toLocaleString('default', { month: 'long', year: 'numeric' }) }}
          </h3>
          <div class="flex items-center gap-2">
            <UiButton variant="ghost" size="sm" @click="prevMonth"><ChevronLeft class="h-4 w-4" /></UiButton>
            <UiButton variant="ghost" size="sm" @click="currentMonth = new Date()">Today</UiButton>
            <UiButton variant="ghost" size="sm" @click="nextMonth"><ChevronRight class="h-4 w-4" /></UiButton>
          </div>
        </div>

        <div class="grid grid-cols-7 bg-slate-50 border-b border-slate-100">
          <div v-for="day in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']" :key="day" class="p-2 text-center text-xs font-bold text-slate-400 uppercase tracking-wider">
            {{ day }}
          </div>
        </div>

        <div class="grid grid-cols-7">
          <div
            v-for="(date, idx) in calendarDays"
            :key="idx"
            class="min-h-[110px] p-2 border-r border-b border-slate-100 last:border-r-0"
            :class="{ 'bg-slate-50/50': !date }"
          >
            <div v-if="date" class="text-right text-xs font-bold text-slate-400 mb-1">
              {{ date.getDate() }}
            </div>
            <div v-if="date" class="space-y-1">
              <div
                v-for="leave in getLeavesForDate(date)"
                :key="leave.id"
                class="text-[11px] p-1.5 rounded-lg bg-blue-50 text-blue-700 border border-blue-100 truncate font-semibold"
                :title="`${leave.employee?.first_name} ${leave.employee?.last_name} (${leave.leave_type?.name})`"
              >
                {{ leave.employee?.first_name }} {{ leave.employee?.last_name?.[0] }}. ({{ leave.leave_type?.name }})
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- New Leave Request Modal -->
    <UiModal v-model="isNewLeaveModalOpen" title="Submit Leave Request" size="md">
      <div class="space-y-4">
        <UiSelect
          v-model="newLeaveForm.employee_id"
          label="Employee"
          :options="employeeOptions"
          placeholder="Select employee"
        />

        <UiSelect
          v-model="newLeaveForm.leave_type_id"
          label="Leave Type"
          :options="leaveTypeOptions"
          placeholder="Select leave type"
        />

        <div class="grid grid-cols-2 gap-4">
          <UiInput v-model="newLeaveForm.start_date" type="date" label="Start Date" />
          <UiInput v-model="newLeaveForm.end_date" type="date" label="End Date" />
        </div>

        <div class="flex items-center gap-2">
          <input
            id="half_day"
            type="checkbox"
            v-model="newLeaveForm.half_day"
            class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
          />
          <label for="half_day" class="text-xs font-semibold text-slate-700">Half day request (0.5 day)</label>
        </div>

        <UiInput v-model="newLeaveForm.reason" label="Reason" placeholder="Purpose or details of time off" />

        <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
          <UiButton variant="outline" @click="isNewLeaveModalOpen = false">Cancel</UiButton>
          <UiButton
            :loading="submitLeaveMutation.isPending.value"
            :disabled="!newLeaveForm.employee_id || !newLeaveForm.leave_type_id"
            @click="submitLeaveMutation.mutate(newLeaveForm)"
          >
            Submit Request
          </UiButton>
        </div>
      </div>
    </UiModal>
  </div>
</template>
