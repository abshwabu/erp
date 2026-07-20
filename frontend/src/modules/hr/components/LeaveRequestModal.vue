<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { hrApi } from '@/api/hr'
import UiModal from '@/components/ui/UiModal.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiAlert from '@/components/ui/UiAlert.vue'
import { Calendar, Info, Upload } from '@lucide/vue'
import type { LeaveType, LeaveBalance } from '@/types/hr'

const props = defineProps<{
  modelValue: boolean
  employeeId: string
}>()

const emit = defineEmits(['update:modelValue', 'saved'])

const queryClient = useQueryClient()

const { data: leaveTypes } = useQuery({
  queryKey: ['hr', 'leave-types'],
  queryFn: () => hrApi.getLeaveTypes().then(res => res.data)
})

const { data: balances } = useQuery({
  queryKey: ['hr', 'employees', props.employeeId, 'leave-balances'],
  queryFn: () => hrApi.getEmployeeLeaveBalances(props.employeeId).then(res => res.data),
  enabled: computed(() => !!props.employeeId)
})

const form = ref({
  leave_type_id: '',
  start_date: '',
  end_date: '',
  is_half_day: false,
  half_day_period: 'morning' as 'morning' | 'afternoon',
  reason: '',
  attachment: null as File | null
})

const selectedType = computed(() => 
  leaveTypes.value?.find(t => t.id === form.value.leave_type_id)
)

const selectedBalance = computed(() => 
  balances.value?.find(b => b.leave_type_id === form.value.leave_type_id)
)

const workingDays = computed(() => {
  if (!form.value.start_date || !form.value.end_date) return 0
  if (form.value.is_half_day) return 0.5
  
  const start = new Date(form.value.start_date)
  const end = new Date(form.value.end_date)
  let count = 0
  const cur = new Date(start)
  
  while (cur <= end) {
    const day = cur.getDay()
    if (day !== 0 && day !== 6) { // Skip weekends
      count++
    }
    cur.setDate(cur.getDate() + 1)
  }
  return count
})

const submitMutation = useMutation({
  mutationFn: (data: any) => hrApi.submitLeaveRequest(data),
  onSuccess: () => {
    emit('saved')
    emit('update:modelValue', false)
    resetForm()
  }
})

const resetForm = () => {
  form.value = {
    leave_type_id: '',
    start_date: '',
    end_date: '',
    is_half_day: false,
    half_day_period: 'morning',
    reason: '',
    attachment: null
  }
}

const handleSubmit = () => {
  submitMutation.mutate({
    ...form.value,
    employee_id: props.employeeId,
    working_days: workingDays.value
  })
}

watch(() => form.value.is_half_day, (val) => {
  if (val) {
    form.value.end_date = form.value.start_date
  }
})

watch(() => form.value.start_date, (val) => {
  if (form.value.is_half_day) {
    form.value.end_date = val
  }
})
</script>

<template>
  <UiModal 
    :model-value="modelValue" 
    @update:model-value="$emit('update:modelValue', $event)"
    title="Request Leave"
    size="md"
  >
    <div class="space-y-6">
      <!-- Leave Type -->
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Leave Type</label>
        <UiSelect 
          v-model="form.leave_type_id"
          placeholder="Select leave type"
          :options="leaveTypes?.map(t => ({ label: t.name, value: t.id })) || []"
        />
        <div v-if="selectedBalance" class="mt-2 flex items-center gap-2 text-xs">
          <Info class="h-3 w-3 text-slate-400" />
          <span class="text-slate-500">Available balance: <strong>{{ selectedBalance.remaining }} days</strong></span>
        </div>
      </div>

      <!-- Dates -->
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Start Date</label>
          <UiInput v-model="form.start_date" type="date" />
        </div>
        <div v-if="!form.is_half_day">
          <label class="block text-sm font-medium text-slate-700 mb-1">End Date</label>
          <UiInput v-model="form.end_date" type="date" :min="form.start_date" />
        </div>
      </div>

      <!-- Half Day -->
      <div class="flex items-center gap-4">
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" v-model="form.is_half_day" class="rounded border-slate-300 text-primary-600 focus:ring-primary-500" />
          <span class="text-sm font-medium text-slate-700">Half Day</span>
        </label>
        
        <div v-if="form.is_half_day" class="flex gap-2">
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="radio" v-model="form.half_day_period" value="morning" class="text-primary-600 focus:ring-primary-500" />
            <span class="text-sm text-slate-600">Morning</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="radio" v-model="form.half_day_period" value="afternoon" class="text-primary-600 focus:ring-primary-500" />
            <span class="text-sm text-slate-600">Afternoon</span>
          </label>
        </div>
      </div>

      <!-- Summary -->
      <UiAlert v-if="workingDays > 0" variant="info">
        <template #icon><Calendar class="h-4 w-4" /></template>
        You are requesting <strong>{{ workingDays }} working days</strong>.
        <span v-if="selectedBalance">Your remaining balance will be {{ selectedBalance.remaining - workingDays }}.</span>
      </UiAlert>

      <!-- Reason -->
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Reason</label>
        <textarea 
          v-model="form.reason" 
          rows="3" 
          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
          placeholder="Optional notes for your manager..."
        ></textarea>
      </div>

      <!-- Attachment -->
      <div v-if="selectedType?.requires_attachment">
        <label class="block text-sm font-medium text-slate-700 mb-1">Attachment (Required)</label>
        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-slate-300 border-dashed rounded-lg">
          <div class="space-y-1 text-center">
            <Upload class="mx-auto h-10 w-10 text-slate-400" />
            <div class="flex text-sm text-slate-600">
              <label class="relative cursor-pointer bg-white rounded-md font-medium text-primary-600 hover:text-primary-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary-500">
                <span>Upload a file</span>
                <input type="file" class="sr-only" @change="(e: any) => form.attachment = e.target.files[0]" />
              </label>
              <p class="pl-1">or drag and drop</p>
            </div>
            <p class="text-xs text-slate-500">PDF, PNG, JPG up to 10MB</p>
            <p v-if="form.attachment" class="text-sm font-medium text-slate-900 mt-2">{{ form.attachment.name }}</p>
          </div>
        </div>
      </div>

      <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
        <UiButton variant="outline" @click="$emit('update:modelValue', false)">Cancel</UiButton>
        <UiButton :loading="submitMutation.isPending.value" @click="handleSubmit" :disabled="workingDays === 0 || (selectedType?.requires_attachment && !form.attachment)">
          Submit Request
        </UiButton>
      </div>
    </div>
  </UiModal>
</template>
