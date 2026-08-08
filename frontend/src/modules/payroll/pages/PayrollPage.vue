<script setup lang="ts">
import { ref } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { payrollApi } from '@/api/payroll'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiModal from '@/components/ui/UiModal.vue'
import { Plus } from '@lucide/vue'
import { useToast } from '@/composables/useToast'

const queryClient = useQueryClient()
const toast = useToast()
const isModalOpen = ref(false)
const form = ref({
  period_start: new Date().toISOString().slice(0, 10),
  period_end: new Date().toISOString().slice(0, 10),
})
const selectedRunId = ref<string | null>(null)

const { data: runs, isLoading } = useQuery({
  queryKey: ['payroll', 'runs'],
  queryFn: () => payrollApi.getRuns().then(r => r.data.data),
})

const { data: payslips } = useQuery({
  queryKey: ['payroll', 'payslips', selectedRunId],
  queryFn: () => payrollApi.getPayslips(selectedRunId.value!).then(r => r.data.data),
  enabled: () => !!selectedRunId.value,
})

const createMutation = useMutation({
  mutationFn: () => payrollApi.createRun(form.value),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['payroll', 'runs'] })
    isModalOpen.value = false
    toast.success('Payroll run created')
  },
})

async function process(id: string) {
  try {
    await payrollApi.processRun(id)
    queryClient.invalidateQueries({ queryKey: ['payroll'] })
    toast.success('Payroll processed')
  } catch (e: any) {
    toast.error(e?.response?.data?.message || 'Process failed')
  }
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Payroll</h1>
        <p class="text-sm text-slate-500">Create runs and generate payslips for active employees.</p>
      </div>
      <UiButton @click="isModalOpen = true"><Plus class="w-4 h-4 mr-2" /> New Run</UiButton>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
      <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Period</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Status</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="isLoading"><td colspan="3" class="px-4 py-8 text-center text-slate-500">Loading…</td></tr>
            <tr v-for="run in runs" :key="run.id" class="hover:bg-slate-50 cursor-pointer" @click="selectedRunId = run.id">
              <td class="px-4 py-3 text-sm">{{ run.period_start }} → {{ run.period_end }}</td>
              <td class="px-4 py-3 text-sm capitalize">{{ run.status }}</td>
              <td class="px-4 py-3 text-right">
                <UiButton v-if="run.status === 'draft'" size="sm" @click.stop="process(run.id)">Process</UiButton>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="bg-white border border-slate-200 rounded-xl p-4">
        <h2 class="font-semibold text-slate-900 mb-3">Payslips</h2>
        <p v-if="!selectedRunId" class="text-sm text-slate-500">Select a run to view payslips.</p>
        <ul v-else class="space-y-2">
          <li v-for="slip in payslips" :key="slip.id" class="flex justify-between text-sm border-b border-slate-100 py-2">
            <span>{{ slip.employee?.first_name }} {{ slip.employee?.last_name }}</span>
            <span class="font-mono">${{ (slip.net_cents / 100).toFixed(2) }}</span>
          </li>
          <li v-if="!(payslips || []).length" class="text-sm text-slate-500">No payslips yet.</li>
        </ul>
      </div>
    </div>

    <UiModal v-model="isModalOpen" title="New Payroll Run">
      <div class="space-y-4">
        <UiInput v-model="form.period_start" type="date" label="Period Start" />
        <UiInput v-model="form.period_end" type="date" label="Period End" />
        <div class="flex justify-end gap-2">
          <UiButton variant="outline" @click="isModalOpen = false">Cancel</UiButton>
          <UiButton :loading="createMutation.isPending.value" @click="createMutation.mutate()">Create</UiButton>
        </div>
      </div>
    </UiModal>
  </div>
</template>
