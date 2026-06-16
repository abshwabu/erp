<script setup lang="ts">
import { ref, computed } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { hrApi } from '@/api/hr'
import UiButton from '@/components/ui/UiButton.vue'
import UiTable from '@/components/ui/UiTable.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiCard from '@/components/ui/UiCard.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiSpinner from '@/components/ui/UiSpinner.vue'
import { 
  CheckCircle, 
  XCircle, 
  Calendar as CalendarIcon, 
  Filter, 
  Download,
  Search,
  ChevronLeft,
  ChevronRight
} from '@lucide/vue'

const queryClient = useQueryClient()
const activeTab = ref('pending')

const { data: leaveRequests, isLoading } = useQuery({
  queryKey: ['hr', 'leave-requests'],
  queryFn: () => hrApi.getLeaveRequests().then(res => res.data)
})

const { data: departments } = useQuery({
  queryKey: ['hr', 'departments'],
  queryFn: () => hrApi.getDepartments().then(res => res.data)
})

const pendingRequests = computed(() => 
  leaveRequests.value?.filter(r => r.status === 'pending') || []
)

const approveMutation = useMutation({
  mutationFn: ({ id, notes }: { id: string, notes?: string }) => hrApi.approveLeaveRequest(id, notes),
  onSuccess: () => queryClient.invalidateQueries({ queryKey: ['hr', 'leave-requests'] })
})

const rejectMutation = useMutation({
  mutationFn: ({ id, notes }: { id: string, notes: string }) => hrApi.rejectLeaveRequest(id, notes),
  onSuccess: () => queryClient.invalidateQueries({ queryKey: ['hr', 'leave-requests'] })
})

const rejectionNotes = ref<Record<string, string>>({})

const columns = [
  { key: 'employee', label: 'Employee' },
  { key: 'leave_type', label: 'Type' },
  { key: 'dates', label: 'Dates' },
  { key: 'working_days', label: 'Days' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: 'Actions' },
]

// Calendar Logic
const currentMonth = ref(new Date())
const calendarDays = computed(() => {
  const year = currentMonth.value.getFullYear()
  const month = currentMonth.value.getMonth()
  const firstDay = new Date(year, month, 1)
  const lastDay = new Date(year, month + 1, 0)
  
  const days = []
  // Pad beginning
  for (let i = 0; i < firstDay.getDay(); i++) {
    days.push(null)
  }
  // Month days
  for (let i = 1; i <= lastDay.getDate(); i++) {
    days.push(new Date(year, month, i))
  }
  return days
})

const getLeavesForDate = (date: Date) => {
  if (!date) return []
  return leaveRequests.value?.filter(r => {
    if (r.status !== 'approved') return false
    const start = new Date(r.start_date)
    const end = new Date(r.end_date)
    const d = new Date(date)
    d.setHours(0,0,0,0)
    start.setHours(0,0,0,0)
    end.setHours(0,0,0,0)
    return d >= start && d <= end
  }) || []
}

const nextMonth = () => {
  currentMonth.value = new Date(currentMonth.value.getFullYear(), currentMonth.value.getMonth() + 1, 1)
}

const prevMonth = () => {
  currentMonth.value = new Date(currentMonth.value.getFullYear(), currentMonth.value.getMonth() - 1, 1)
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Leave Management</h1>
        <p class="text-slate-500">Review and manage employee leave requests.</p>
      </div>
      <UiButton variant="outline">
        <Download class="h-4 w-4 mr-2" /> Export
      </UiButton>
    </div>

    <!-- Tabs Navigation -->
    <div class="flex border-b border-slate-200">
      <button 
        v-for="tab in [
          { id: 'pending', label: `Pending Approval (${pendingRequests.length})` },
          { id: 'all', label: 'All Requests' },
          { id: 'calendar', label: 'Leave Calendar' }
        ]" 
        :key="tab.id"
        @click="activeTab = tab.id"
        class="px-6 py-3 text-sm font-medium border-b-2 transition-colors"
        :class="activeTab === tab.id ? 'border-primary-600 text-primary-600' : 'border-transparent text-slate-500 hover:text-slate-700'"
      >
        {{ tab.label }}
      </button>
    </div>

    <!-- Tab Content -->
    <div class="mt-6">
      <!-- Pending Tab -->
      <div v-if="activeTab === 'pending'" class="space-y-4">
        <div v-if="pendingRequests.length === 0" class="bg-white p-12 text-center rounded-xl border border-dashed border-slate-300">
          <CalendarIcon class="h-12 w-12 text-slate-300 mx-auto mb-4" />
          <h3 class="text-lg font-medium text-slate-900">No pending requests</h3>
          <p class="text-slate-500">All leave requests have been processed.</p>
        </div>
        
        <div v-for="request in pendingRequests" :key="request.id" class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex flex-col md:flex-row gap-6">
          <div class="flex-1 flex gap-4">
            <div class="h-12 w-12 rounded-full bg-slate-100 flex items-center justify-center font-bold text-slate-400">
              {{ request.employee?.first_name[0] }}{{ request.employee?.last_name[0] }}
            </div>
            <div>
              <p class="font-bold text-slate-900">{{ request.employee?.first_name }} {{ request.employee?.last_name }}</p>
              <p class="text-sm text-slate-500">{{ request.leave_type?.name }} • {{ request.working_days }} days</p>
              <p class="text-sm text-slate-500 mt-1">
                {{ new Date(request.start_date).toLocaleDateString() }} - {{ new Date(request.end_date).toLocaleDateString() }}
              </p>
              <p v-if="request.reason" class="mt-2 text-sm bg-slate-50 p-2 rounded italic text-slate-600">
                "{{ request.reason }}"
              </p>
            </div>
          </div>
          
          <div class="w-full md:w-80 space-y-3">
            <UiInput v-model="rejectionNotes[request.id]" placeholder="Notes (required for rejection)" size="sm" />
            <div class="flex gap-2">
              <UiButton 
                variant="outline" 
                class="flex-1 border-red-200 text-red-600 hover:bg-red-50" 
                size="sm"
                @click="rejectMutation.mutate({ id: request.id, notes: rejectionNotes[request.id] })"
                :disabled="!rejectionNotes[request.id]"
              >
                <XCircle class="h-4 w-4 mr-2" /> Reject
              </UiButton>
              <UiButton 
                class="flex-1" 
                size="sm"
                @click="approveMutation.mutate({ id: request.id, notes: rejectionNotes[request.id] })"
              >
                <CheckCircle class="h-4 w-4 mr-2" /> Approve
              </UiButton>
            </div>
          </div>
        </div>
      </div>

      <!-- All Requests Tab -->
      <div v-if="activeTab === 'all'" class="space-y-4">
        <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm flex flex-wrap items-center gap-4">
          <UiInput placeholder="Search employee..." class="w-full max-w-xs">
            <template #prefix><Search class="h-4 w-4 text-slate-400" /></template>
          </UiInput>
          <UiSelect placeholder="Department" :options="departments?.map(d => ({ label: d.name, value: d.id })) || []" class="w-48" />
          <UiSelect placeholder="Status" :options="[{label:'Approved', value:'approved'}, {label:'Rejected', value:'rejected'}, {label:'Pending', value:'pending'}]" class="w-40" />
        </div>

        <UiTable :columns="columns" :data="leaveRequests || []" :loading="isLoading">
          <template #cell(employee)="{ item }">
            {{ item.employee?.first_name }} {{ item.employee?.last_name }}
          </template>
          <template #cell(leave_type)="{ item }">
            {{ item.leave_type?.name }}
          </template>
          <template #cell(dates)="{ item }">
            {{ new Date(item.start_date).toLocaleDateString() }} - {{ new Date(item.end_date).toLocaleDateString() }}
          </template>
          <template #cell(status)="{ item }">
            <UiBadge :variant="item.status === 'approved' ? 'success' : item.status === 'pending' ? 'info' : 'warning'">
              {{ item.status }}
            </UiBadge>
          </template>
          <template #cell(actions)="{ item }">
            <UiButton variant="ghost" size="sm">View</UiButton>
          </template>
        </UiTable>
      </div>

      <!-- Calendar Tab -->
      <div v-if="activeTab === 'calendar'" class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
          <h3 class="font-bold text-slate-900">{{ currentMonth.toLocaleString('default', { month: 'long', year: 'numeric' }) }}</h3>
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
            class="min-h-[120px] p-2 border-r border-b border-slate-100 last:border-r-0"
            :class="{ 'bg-slate-50/50': !date }"
          >
            <div v-if="date" class="text-right text-sm font-medium text-slate-400 mb-2">
              {{ date.getDate() }}
            </div>
            <div v-if="date" class="space-y-1">
              <div 
                v-for="leave in getLeavesForDate(date)" 
                :key="leave.id"
                class="text-[10px] p-1 rounded bg-primary-50 text-primary-700 border border-primary-100 truncate font-medium"
                :title="`${leave.employee?.first_name} ${leave.employee?.last_name} (${leave.leave_type?.name})`"
              >
                {{ leave.employee?.first_name }} {{ leave.employee?.last_name[0] }}.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
