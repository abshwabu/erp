<script setup lang="ts">
import { ref, computed } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { hrApi } from '@/api/hr'
import UiModal from '@/components/ui/UiModal.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiButton from '@/components/ui/UiButton.vue'

const props = defineProps<{ modelValue: boolean; employeeId: string }>()
const emit = defineEmits(['update:modelValue', 'saved'])

const queryClient = useQueryClient()
const leaveTypeId = ref('')
const startDate = ref('')
const endDate = ref('')

const { data: leaveTypes } = useQuery({
  queryKey: ['hr', 'leave-types'],
  queryFn: () => hrApi.getLeaveTypes().then(res => res.data)
})

const mutation = useMutation({
  mutationFn: (data: any) => hrApi.submitLeaveRequest(data),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['hr', 'leave-requests'] })
    emit('saved')
    emit('update:modelValue', false)
  }
})

const submit = () => {
  mutation.mutate({
    employee_id: props.employeeId,
    leave_type_id: leaveTypeId.value,
    start_date: startDate.value,
    end_date: endDate.value,
    days_taken: 1 // Placeholder calculation
  })
}
</script>

<template>
  <UiModal :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)" title="Request Leave">
    <div class="space-y-4">
      <UiSelect v-model="leaveTypeId" label="Leave Type" 
        :options="leaveTypes?.map(t => ({ label: t.name, value: t.id })) || []" />
      <input v-model="startDate" type="date" class="w-full p-2 border rounded" />
      <input v-model="endDate" type="date" class="w-full p-2 border rounded" />
      <UiButton @click="submit" class="w-full">Submit Request</UiButton>
    </div>
  </UiModal>
</template>
