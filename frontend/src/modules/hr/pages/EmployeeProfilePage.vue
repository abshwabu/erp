<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useQuery, useQueryClient } from '@tanstack/vue-query'
import type { Employee } from '@/types/hr'
import { hrApi } from '@/api/hr'
import UiButton from '@/components/ui/UiButton.vue'
import UiCard from '@/components/ui/UiCard.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiTable from '@/components/ui/UiTable.vue'
import UiSpinner from '@/components/ui/UiSpinner.vue'
import LeaveRequestModal from '../components/LeaveRequestModal.vue'
import { 
  User, 
  Calendar, 
  Clock, 
  FileText, 
  Mail, 
  Phone, 
  MapPin, 
  Briefcase, 
  ChevronRight,
  Download,
  Upload,
  Plus,
  ChevronLeft
} from '@lucide/vue'

const route = useRoute()
const employeeId = route.params.id as string
const queryClient = useQueryClient()

const { data: employee, isLoading: isLoadingEmployee } = useQuery({
  queryKey: ['hr', 'employees', employeeId],
  queryFn: () => hrApi.getEmployee(employeeId).then(res => res.data)
})

const { data: leaveBalances } = useQuery({
  queryKey: ['hr', 'employees', employeeId, 'leave-balances'],
  queryFn: () => hrApi.getEmployeeLeaveBalances(employeeId).then(res => res.data)
})

const { data: attendanceLogs } = useQuery({
  queryKey: ['hr', 'employees', employeeId, 'attendance'],
  queryFn: () => hrApi.getEmployeeAttendance(employeeId).then(res => res.data)
})

const { data: leaveRequests } = useQuery({
  queryKey: ['hr', 'employees', employeeId, 'leave-requests'],
  queryFn: () => hrApi.getEmployeeLeaveRequests(employeeId).then(res => res.data)
})

const activeTab = ref('profile')
const tabs = [
  { id: 'profile', label: 'Profile', icon: User },
  { id: 'leave', label: 'Leave', icon: Calendar },
  { id: 'attendance', label: 'Attendance', icon: Clock },
  { id: 'documents', label: 'Documents', icon: FileText },
]

const isLeaveModalOpen = ref(false)
const isEditing = ref(false)
const editForm = ref<Partial<Employee>>({})

const startEditing = () => {
  if (employee.value) {
    editForm.value = JSON.parse(JSON.stringify(employee.value))
    isEditing.value = true
  }
}

const cancelEditing = () => {
  isEditing.value = false
  editForm.value = {}
}

const saveProfile = async () => {
  if (!employee.value) return
  try {
    await hrApi.updateEmployee(employeeId, editForm.value)
    queryClient.invalidateQueries({ queryKey: ['hr', 'employees', employeeId] })
    isEditing.value = false
  } catch (e) {
    console.error('Failed to save profile', e)
  }
}

// Attendance Calendar Logic
const currentMonth = ref(new Date())
const calendarDays = computed(() => {
  const year = currentMonth.value.getFullYear()
  const month = currentMonth.value.getMonth()
  const firstDay = new Date(year, month, 1)
  const lastDay = new Date(year, month + 1, 0)
  
  const days = []
  for (let i = 0; i < firstDay.getDay(); i++) days.push(null)
  for (let i = 1; i <= lastDay.getDate(); i++) days.push(new Date(year, month, i))
  return days
})

const getAttendanceForDate = (date: Date) => {
  if (!date || !attendanceLogs.value) return null
  const d = date.toISOString().split('T')[0]
  return attendanceLogs.value.find(l => l.date === d)
}

const getStatusColor = (status: string) => {
  switch (status) {
    case 'present': return 'bg-green-100 text-green-700 border-green-200'
    case 'absent': return 'bg-red-100 text-red-700 border-red-200'
    case 'on-leave': return 'bg-blue-100 text-blue-700 border-blue-200'
    case 'holiday': return 'bg-slate-100 text-slate-700 border-slate-200'
    case 'late': return 'bg-amber-100 text-amber-700 border-amber-200'
    default: return 'bg-slate-50 text-slate-400 border-slate-100'
  }
}

const nextMonth = () => currentMonth.value = new Date(currentMonth.value.getFullYear(), currentMonth.value.getMonth() + 1, 1)
const prevMonth = () => currentMonth.value = new Date(currentMonth.value.getFullYear(), currentMonth.value.getMonth() - 1, 1)

// Leave formatting
const leaveColumns = [
  { key: 'leave_type', label: 'Type' },
  { key: 'dates', label: 'Dates' },
  { key: 'working_days', label: 'Days' },
  { key: 'status', label: 'Status' },
]

const attendanceSummary = computed(() => {
  if (!attendanceLogs.value) return null
  return {
    total: attendanceLogs.value.length,
    present: attendanceLogs.value.filter(l => l.status === 'present' || l.status === 'late').length,
    absent: attendanceLogs.value.filter(l => l.status === 'absent').length,
    late: attendanceLogs.value.filter(l => l.status === 'late').length,
    overtime: attendanceLogs.value.reduce((acc, l) => acc + (l.overtime_minutes || 0), 0) / 60
  }
})
</script>

<template>
  <div v-if="isLoadingEmployee" class="flex justify-center py-12">
    <UiSpinner size="lg" />
  </div>

  <div v-else-if="employee" class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
      <div class="flex items-center gap-4">
        <div class="h-20 w-20 rounded-full bg-slate-100 flex items-center justify-center text-2xl font-bold text-slate-400 overflow-hidden">
          <img v-if="employee.avatar_url" :src="employee.avatar_url" class="h-full w-full object-cover" />
          <span v-else>{{ employee.first_name[0] }}{{ employee.last_name[0] }}</span>
        </div>
        <div>
          <h1 class="text-2xl font-bold text-slate-900">{{ employee.first_name }} {{ employee.last_name }}</h1>
          <div class="flex items-center gap-2 text-slate-500">
            <span>{{ employee.employee_number }}</span>
            <span>•</span>
            <span>{{ employee.position?.title }}</span>
          </div>
          <div class="mt-2">
            <UiBadge :variant="employee.status === 'active' ? 'success' : 'warning'">{{ employee.status }}</UiBadge>
          </div>
        </div>
      </div>
      <div class="flex gap-2">
        <template v-if="isEditing">
          <UiButton variant="outline" size="sm" @click="cancelEditing">Cancel</UiButton>
          <UiButton size="sm" @click="saveProfile">Save Changes</UiButton>
        </template>
        <template v-else>
          <UiButton variant="outline" size="sm">
            <Mail class="h-4 w-4 mr-2" /> Message
          </UiButton>
          <UiButton size="sm" @click="startEditing">
            Edit Profile
          </UiButton>
        </template>
      </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="flex border-b border-slate-200">
      <button 
        v-for="tab in tabs" 
        :key="tab.id"
        @click="activeTab = tab.id"
        class="flex items-center gap-2 px-6 py-3 text-sm font-medium border-b-2 transition-colors"
        :class="activeTab === tab.id ? 'border-primary-600 text-primary-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
      >
        <component :is="tab.icon" class="h-4 w-4" />
        {{ tab.label }}
      </button>
    </div>

    <!-- Tab Content -->
    <div class="mt-6">
      <!-- Profile Tab -->
      <div v-if="activeTab === 'profile'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
          <UiCard title="Personal Information">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
              <div>
                <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Full Name</label>
                <div v-if="isEditing" class="flex gap-2">
                  <input v-model="editForm.first_name" class="w-full rounded border-slate-300 px-2 py-1 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500" />
                  <input v-model="editForm.last_name" class="w-full rounded border-slate-300 px-2 py-1 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500" />
                </div>
                <p v-else class="text-slate-900 font-medium">{{ employee.first_name }} {{ employee.last_name }}</p>
              </div>
              <div>
                <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Preferred Name</label>
                <input v-if="isEditing" v-model="editForm.preferred_name" class="w-full rounded border-slate-300 px-2 py-1 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500" />
                <p v-else class="text-slate-900">{{ employee.preferred_name || 'N/A' }}</p>
              </div>
              <div>
                <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Email Address</label>
                <input v-if="isEditing" v-model="editForm.email" type="email" class="w-full rounded border-slate-300 px-2 py-1 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500" />
                <p v-else class="text-slate-900">{{ employee.email }}</p>
              </div>
              <div>
                <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Phone Number</label>
                <input v-if="isEditing" v-model="editForm.phone" class="w-full rounded border-slate-300 px-2 py-1 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500" />
                <p v-else class="text-slate-900">{{ employee.phone || 'N/A' }}</p>
              </div>
              <div>
                <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Date of Birth</label>
                <input v-if="isEditing" v-model="editForm.date_of_birth" type="date" class="w-full rounded border-slate-300 px-2 py-1 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500" />
                <p v-else class="text-slate-900">{{ employee.date_of_birth ? new Date(employee.date_of_birth).toLocaleDateString() : 'N/A' }}</p>
              </div>
              <div>
                <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Gender</label>
                <select v-if="isEditing" v-model="editForm.gender" class="w-full rounded border-slate-300 px-2 py-1 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                  <option value="male">Male</option>
                  <option value="female">Female</option>
                  <option value="other">Other</option>
                </select>
                <p v-else class="text-slate-900 capitalize">{{ employee.gender || 'N/A' }}</p>
              </div>
            </div>
          </UiCard>

          <UiCard title="Work Information">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
              <div>
                <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Department</label>
                <p class="text-slate-900">{{ employee.department?.name || 'N/A' }}</p>
              </div>
              <div>
                <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Position</label>
                <p class="text-slate-900">{{ employee.position?.title || 'N/A' }}</p>
              </div>
              <div>
                <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Employment Type</label>
                <select v-if="isEditing" v-model="editForm.employment_type" class="w-full rounded border-slate-300 px-2 py-1 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                  <option value="full-time">Full-time</option>
                  <option value="part-time">Part-time</option>
                  <option value="contract">Contract</option>
                  <option value="intern">Intern</option>
                  <option value="probationary">Probationary</option>
                </select>
                <p v-else class="text-slate-900 capitalize">{{ employee.employment_type.replace('-', ' ') }}</p>
              </div>
              <div>
                <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Start Date</label>
                <input v-if="isEditing" v-model="editForm.start_date" type="date" class="w-full rounded border-slate-300 px-2 py-1 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500" />
                <p v-else class="text-slate-900">{{ new Date(employee.start_date).toLocaleDateString() }}</p>
              </div>
              <div>
                <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Reporting Manager</label>
                <div v-if="employee.manager" class="flex items-center gap-2 mt-1">
                  <div class="h-6 w-6 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-bold">
                    {{ employee.manager.first_name[0] }}{{ employee.manager.last_name[0] }}
                  </div>
                  <span class="text-slate-900">{{ employee.manager.first_name }} {{ employee.manager.last_name }}</span>
                </div>
                <p v-else class="text-slate-900">N/A</p>
              </div>
            </div>
          </UiCard>

          <UiCard title="Emergency Contacts">
            <div class="p-6">
              <div v-if="employee.emergency_contacts?.length" class="space-y-4">
                <div v-for="contact in employee.emergency_contacts" :key="contact.phone" class="flex justify-between items-center p-3 rounded-lg border border-slate-100 bg-slate-50">
                  <div>
                    <p class="font-medium text-slate-900">{{ contact.name }}</p>
                    <p class="text-xs text-slate-500">{{ contact.relationship }}</p>
                  </div>
                  <div class="flex items-center gap-2 text-slate-600">
                    <Phone class="h-4 w-4" />
                    <span>{{ contact.phone }}</span>
                  </div>
                </div>
              </div>
              <p v-else class="text-slate-500 text-center py-4">No emergency contacts listed.</p>
            </div>
          </UiCard>
        </div>

        <div class="space-y-6">
          <UiCard title="Quick Actions">
            <div class="p-4 space-y-2">
              <UiButton variant="outline" class="w-full justify-start">
                <FileText class="h-4 w-4 mr-2" /> Download Contract
              </UiButton>
              <UiButton variant="outline" class="w-full justify-start">
                <Mail class="h-4 w-4 mr-2" /> Reset Password
              </UiButton>
              <UiButton variant="outline" class="w-full justify-start text-red-600 hover:text-red-700 hover:bg-red-50">
                <User class="h-4 w-4 mr-2" /> Terminate Employment
              </UiButton>
            </div>
          </UiCard>
        </div>
      </div>

      <!-- Leave Tab -->
      <div v-if="activeTab === 'leave'" class="space-y-6">
        <div class="flex justify-between items-center">
          <h2 class="text-lg font-semibold text-slate-900">Leave Balance</h2>
          <UiButton size="sm" @click="isLeaveModalOpen = true">
            <Plus class="h-4 w-4 mr-2" /> Request Leave
          </UiButton>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <UiCard v-for="balance in leaveBalances" :key="balance.id" class="p-4">
            <div class="flex justify-between items-start mb-4">
              <div>
                <p class="text-sm font-medium text-slate-500">{{ balance.leave_type?.name }}</p>
                <h3 class="text-2xl font-bold text-slate-900">{{ balance.remaining }} <span class="text-sm font-normal text-slate-400">Days</span></h3>
              </div>
              <UiBadge variant="default">{{ balance.entitled }} Total</UiBadge>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-2">
              <div 
                class="bg-primary-600 h-2 rounded-full" 
                :style="{ width: `${(balance.used / balance.entitled) * 100}%` }"
              ></div>
            </div>
            <div class="flex justify-between mt-2 text-xs text-slate-500">
              <span>{{ balance.used }} Used</span>
              <span>{{ balance.pending }} Pending</span>
            </div>
          </UiCard>
        </div>

        <UiCard title="Leave History">
          <UiTable :columns="leaveColumns" :data="leaveRequests || []">
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
          </UiTable>
        </UiCard>
      </div>

      <!-- Attendance Tab -->
      <div v-if="activeTab === 'attendance'" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <UiCard class="p-4 text-center">
            <p class="text-sm text-slate-500">Present Days</p>
            <p class="text-2xl font-bold text-slate-900">{{ attendanceSummary?.present || 0 }}</p>
          </UiCard>
          <UiCard class="p-4 text-center">
            <p class="text-sm text-slate-500">Absent Days</p>
            <p class="text-2xl font-bold text-red-600">{{ attendanceSummary?.absent || 0 }}</p>
          </UiCard>
          <UiCard class="p-4 text-center">
            <p class="text-sm text-slate-500">Late Arrivals</p>
            <p class="text-2xl font-bold text-amber-600">{{ attendanceSummary?.late || 0 }}</p>
          </UiCard>
          <UiCard class="p-4 text-center">
            <p class="text-sm text-slate-500">Overtime Hours</p>
            <p class="text-2xl font-bold text-primary-600">{{ attendanceSummary?.overtime.toFixed(1) || 0 }}</p>
          </UiCard>
        </div>

        <!-- Attendance Calendar -->
        <UiCard>
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
              class="min-h-[80px] p-2 border-r border-b border-slate-100 last:border-r-0 relative"
              :class="{ 'bg-slate-50/50': !date }"
            >
              <div v-if="date" class="text-right text-xs font-medium text-slate-400 mb-1">
                {{ date.getDate() }}
              </div>
              <div v-if="date && getAttendanceForDate(date)" 
                class="absolute inset-2 mt-6 rounded-md border text-[10px] p-1 flex items-center justify-center font-bold"
                :class="getStatusColor(getAttendanceForDate(date)!.status)"
              >
                {{ getAttendanceForDate(date)!.status.toUpperCase() }}
              </div>
            </div>
          </div>
          
          <div class="p-4 bg-slate-50 flex flex-wrap gap-4 text-xs">
            <div class="flex items-center gap-1"><div class="w-3 h-3 bg-green-100 border border-green-200 rounded"></div> Present</div>
            <div class="flex items-center gap-1"><div class="w-3 h-3 bg-red-100 border border-red-200 rounded"></div> Absent</div>
            <div class="flex items-center gap-1"><div class="w-3 h-3 bg-blue-100 border border-blue-200 rounded"></div> Leave</div>
            <div class="flex items-center gap-1"><div class="w-3 h-3 bg-amber-100 border border-amber-200 rounded"></div> Late</div>
            <div class="flex items-center gap-1"><div class="w-3 h-3 bg-slate-100 border border-slate-200 rounded"></div> Holiday</div>
          </div>
        </UiCard>

        <!-- Attendance Table -->
        <UiCard title="Raw Clock Log">
          <UiTable :columns="[
            { key: 'date', label: 'Date' },
            { key: 'clock_in', label: 'In' },
            { key: 'clock_out', label: 'Out' },
            { key: 'total_hours', label: 'Hours' },
            { key: 'status', label: 'Status' }
          ]" :data="attendanceLogs || []">
            <template #cell(clock_in)="{ item }">
              {{ item.clock_in ? new Date(item.clock_in).toLocaleTimeString() : '-' }}
            </template>
            <template #cell(clock_out)="{ item }">
              {{ item.clock_out ? new Date(item.clock_out).toLocaleTimeString() : '-' }}
            </template>
            <template #cell(status)="{ item }">
              <UiBadge :variant="item.status === 'present' ? 'success' : item.status === 'absent' ? 'danger' : 'warning'">
                {{ item.status }}
              </UiBadge>
            </template>
          </UiTable>
        </UiCard>
      </div>

      <!-- Documents Tab -->
      <div v-if="activeTab === 'documents'" class="space-y-6">
        <div class="flex justify-between items-center">
          <h2 class="text-lg font-semibold text-slate-900">Employee Documents</h2>
          <UiButton size="sm">
            <Upload class="h-4 w-4 mr-2" /> Upload Document
          </UiButton>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <UiCard v-for="i in 3" :key="i" class="p-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="p-2 bg-slate-100 rounded-lg">
                <FileText class="h-6 w-6 text-slate-400" />
              </div>
              <div>
                <p class="font-medium text-slate-900">Employment_Contract.pdf</p>
                <p class="text-xs text-slate-500">Uploaded on June 12, 2026</p>
              </div>
            </div>
            <UiButton variant="ghost" size="sm">
              <Download class="h-4 w-4" />
            </UiButton>
          </UiCard>
        </div>
      </div>
    </div>

    <LeaveRequestModal 
      v-model="isLeaveModalOpen" 
      :employee-id="employeeId"
      @saved="queryClient.invalidateQueries({ queryKey: ['hr', 'employees', employeeId] })"
    />
  </div>
</template>
