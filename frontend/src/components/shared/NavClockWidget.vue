<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { hrApi } from '@/api/hr'
import { useToast } from '@/composables/useToast'
import { Clock, LogIn, LogOut, CheckCircle2, Loader2 } from '@lucide/vue'

const toast = useToast()
const queryClient = useQueryClient()

// Fetch current user attendance status for today
const { data: attendanceStatus, isLoading, refetch } = useQuery({
  queryKey: ['hr', 'attendance', 'my-status'],
  queryFn: () => hrApi.getMyAttendanceStatus().then((res) => res.data),
  refetchInterval: 60000, // refresh every minute
})

// Elapsed time timer calculation
const now = ref(Date.now())
let timerInterval: any = null

onMounted(() => {
  timerInterval = setInterval(() => {
    now.value = Date.now()
  }, 1000)
})

onUnmounted(() => {
  if (timerInterval) clearInterval(timerInterval)
})

const elapsedTime = computed(() => {
  if (!attendanceStatus.value?.clock_in_time || attendanceStatus.value.status !== 'clocked_in') {
    return null
  }
  const start = new Date(attendanceStatus.value.clock_in_time).getTime()
  const diffMs = Math.max(0, now.value - start)
  const hours = Math.floor(diffMs / 3600000)
  const minutes = Math.floor((diffMs % 3600000) / 60000)
  const seconds = Math.floor((diffMs % 60000) / 1000)

  return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`
})

const isClocking = ref(false)

const handleClockIn = async () => {
  isClocking.value = true
  try {
    await hrApi.clockIn()
    toast.success('Successfully clocked in. Have a great shift!')
    queryClient.invalidateQueries({ queryKey: ['hr', 'attendance'] })
    queryClient.invalidateQueries({ queryKey: ['hr', 'attendance', 'my-status'] })
    await refetch()
  } catch (err: any) {
    toast.error(err?.response?.data?.message || err?.message || 'Failed to clock in')
  } finally {
    isClocking.value = false
  }
}

const handleClockOut = async () => {
  isClocking.value = true
  try {
    await hrApi.clockOut()
    toast.success('Successfully clocked out. Shift logged.')
    queryClient.invalidateQueries({ queryKey: ['hr', 'attendance'] })
    queryClient.invalidateQueries({ queryKey: ['hr', 'attendance', 'my-status'] })
    await refetch()
  } catch (err: any) {
    toast.error(err?.response?.data?.message || err?.message || 'Failed to clock out')
  } finally {
    isClocking.value = false
  }
}
</script>

<template>
  <div class="flex items-center">
    <!-- Loading skeleton -->
    <div v-if="isLoading" class="h-8 w-24 bg-slate-100 animate-pulse rounded-full"></div>

    <!-- State: Clocked In (Active Shift) -->
    <div v-else-if="attendanceStatus?.status === 'clocked_in'" class="flex items-center gap-1.5 bg-emerald-50 border border-emerald-200/80 p-1 pl-3 rounded-full shadow-2xs">
      <div class="flex items-center gap-1.5 text-emerald-800">
        <span class="relative flex h-2 w-2">
          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
          <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
        </span>
        <span class="hidden sm:inline text-xs font-mono font-bold">{{ elapsedTime || '00:00:00' }}</span>
      </div>

      <button
        type="button"
        @click="handleClockOut"
        :disabled="isClocking"
        class="inline-flex items-center gap-1 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold px-3 py-1.5 rounded-full transition-all shadow-xs disabled:opacity-50 cursor-pointer"
        title="Clock Out of current shift"
      >
        <Loader2 v-if="isClocking" class="w-3.5 h-3.5 animate-spin" />
        <LogOut v-else class="w-3.5 h-3.5" />
        <span class="hidden md:inline">Clock Out</span>
      </button>
    </div>

    <!-- State: Clocked Out for the day -->
    <div v-else-if="attendanceStatus?.status === 'clocked_out'" class="flex items-center gap-1.5 bg-slate-50 border border-slate-200 p-1 pl-3 rounded-full">
      <div class="hidden sm:flex items-center gap-1 text-slate-500 text-xs">
        <CheckCircle2 class="w-3.5 h-3.5 text-emerald-600" />
        <span class="font-medium text-[11px]">Shift Done</span>
      </div>

      <button
        type="button"
        @click="handleClockIn"
        :disabled="isClocking"
        class="inline-flex items-center gap-1 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold px-3 py-1.5 rounded-full transition-all shadow-xs disabled:opacity-50 cursor-pointer"
        title="Start another shift"
      >
        <Loader2 v-if="isClocking" class="w-3.5 h-3.5 animate-spin" />
        <LogIn v-else class="w-3.5 h-3.5" />
        <span>Clock In</span>
      </button>
    </div>

    <!-- State: Not Clocked In Yet -->
    <div v-else class="flex items-center">
      <button
        type="button"
        @click="handleClockIn"
        :disabled="isClocking"
        class="inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-3.5 py-1.5 rounded-full transition-all shadow-xs hover:shadow-sm disabled:opacity-50 cursor-pointer"
        title="Clock in to start today's work shift"
      >
        <Loader2 v-if="isClocking" class="w-3.5 h-3.5 animate-spin" />
        <Clock v-else class="w-3.5 h-3.5" />
        <span>Clock In</span>
      </button>
    </div>
  </div>
</template>
