<script setup lang="ts">
import { ref, computed, markRaw } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { payrollApi, type PayrollRun, type Payslip } from '@/api/payroll'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiStat from '@/components/ui/UiStat.vue'
import UiSpinner from '@/components/ui/UiSpinner.vue'
import { useToast } from '@/composables/useToast'
import {
  Plus,
  Banknote,
  DollarSign,
  Calendar,
  Users,
  CheckCircle2,
  Clock,
  Trash2,
  Eye,
  FileText,
  Search,
  ArrowRight,
  TrendingUp,
  Printer,
  CreditCard,
  Building2,
  Sparkles,
} from '@lucide/vue'

const queryClient = useQueryClient()
const toast = useToast()

const isCreateModalOpen = ref(false)
const isPayslipModalOpen = ref(false)
const selectedPayslip = ref<Payslip | null>(null)
const selectedRunId = ref<string | null>(null)
const payslipSearch = ref('')
const runFilter = ref<'all' | 'processed' | 'draft'>('all')

const form = ref({
  period_start: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0, 10),
  period_end: new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0).toISOString().slice(0, 10),
  auto_process: false,
})

// Queries
const { data: runs, isLoading: isLoadingRuns } = useQuery({
  queryKey: ['payroll', 'runs'],
  queryFn: () => payrollApi.getRuns().then((r) => r.data.data),
})

const { data: previewData, isLoading: isLoadingPreview } = useQuery({
  queryKey: ['payroll', 'preview'],
  queryFn: () => payrollApi.getPreview().then((r) => r.data.data),
  enabled: () => isCreateModalOpen.value,
})

const { data: selectedRunData, isLoading: isLoadingSelectedRun } = useQuery({
  queryKey: ['payroll', 'runs', selectedRunId],
  queryFn: () => payrollApi.getRun(selectedRunId.value!).then((r) => r.data.data),
  enabled: () => !!selectedRunId.value,
})

// Auto-select first run if none selected
const activeRun = computed(() => {
  if (selectedRunData.value) return selectedRunData.value
  if (!selectedRunId.value && runs.value && runs.value.length > 0) {
    return runs.value[0]
  }
  return runs.value?.find((r) => r.id === selectedRunId.value) || null
})

const filteredRuns = computed(() => {
  const list = runs.value || []
  if (runFilter.value === 'all') return list
  return list.filter((r) => r.status === runFilter.value)
})

const filteredPayslips = computed(() => {
  const list = activeRun.value?.payslips || []
  if (!payslipSearch.value) return list
  const q = payslipSearch.value.toLowerCase()
  return list.filter((p) => {
    const name = `${p.employee?.first_name || ''} ${p.employee?.last_name || ''}`.toLowerCase()
    const empNum = (p.employee?.employee_number || '').toLowerCase()
    const dept = (p.employee?.department?.name || '').toLowerCase()
    return name.includes(q) || empNum.includes(q) || dept.includes(q)
  })
})

// Executive stats calculation
const stats = computed(() => {
  const list = runs.value || []
  const processedRuns = list.filter((r) => r.status === 'processed')

  const totalProcessedNet = processedRuns.reduce(
    (sum, r) => sum + (r.payslips_sum_net_cents || 0),
    0
  )

  const latestRun = processedRuns[0]
  const latestNet = latestRun ? (latestRun.payslips_sum_net_cents || 0) : 0

  const draftCount = list.filter((r) => r.status === 'draft').length

  return [
    {
      label: 'Total Processed Payout',
      value: `$${(totalProcessedNet / 100).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`,
      icon: markRaw(DollarSign),
    },
    {
      label: 'Latest Run Net Pay',
      value: latestRun ? `$${(latestNet / 100).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` : '—',
      icon: markRaw(Banknote),
    },
    {
      label: 'Processed Payrolls',
      value: processedRuns.length,
      icon: markRaw(CheckCircle2),
    },
    {
      label: 'Draft Runs Pending',
      value: draftCount,
      icon: markRaw(Clock),
    },
  ]
})

// Mutations
const createMutation = useMutation({
  mutationFn: () => payrollApi.createRun(form.value),
  onSuccess: (res) => {
    queryClient.invalidateQueries({ queryKey: ['payroll', 'runs'] })
    selectedRunId.value = res.data.data.id
    isCreateModalOpen.value = false
    toast.success(form.value.auto_process ? 'Payroll created and processed successfully' : 'Draft payroll run created')
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || 'Failed to create payroll run')
  },
})

const processMutation = useMutation({
  mutationFn: (id: string) => payrollApi.processRun(id),
  onSuccess: (res) => {
    queryClient.invalidateQueries({ queryKey: ['payroll'] })
    toast.success('Payroll run processed successfully')
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || 'Failed to process payroll run')
  },
})

const deleteMutation = useMutation({
  mutationFn: (id: string) => payrollApi.deleteRun(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['payroll', 'runs'] })
    if (selectedRunId.value) {
      selectedRunId.value = null
    }
    toast.success('Draft payroll run deleted')
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || 'Failed to delete payroll run')
  },
})

const openCreateModal = () => {
  const now = new Date()
  form.value = {
    period_start: new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0, 10),
    period_end: new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().slice(0, 10),
    auto_process: false,
  }
  isCreateModalOpen.value = true
}

const setPeriodPreset = (type: 'this_month' | 'last_month' | 'bi_weekly') => {
  const now = new Date()
  if (type === 'this_month') {
    form.value.period_start = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0, 10)
    form.value.period_end = new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().slice(0, 10)
  } else if (type === 'last_month') {
    form.value.period_start = new Date(now.getFullYear(), now.getMonth() - 1, 1).toISOString().slice(0, 10)
    form.value.period_end = new Date(now.getFullYear(), now.getMonth(), 0).toISOString().slice(0, 10)
  } else if (type === 'bi_weekly') {
    const end = new Date()
    const start = new Date(Date.now() - 14 * 24 * 60 * 60 * 1000)
    form.value.period_start = start.toISOString().slice(0, 10)
    form.value.period_end = end.toISOString().slice(0, 10)
  }
}

const viewPayslipDetails = (slip: Payslip) => {
  selectedPayslip.value = slip
  isPayslipModalOpen.value = true
}

const printPayslip = () => {
  window.print()
}

const formatCurrency = (cents: number, currency: string = 'USD') => {
  return `${currency} ${(cents / 100).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Payroll & Compensation</h1>
        <p class="text-xs sm:text-sm text-slate-500">Run monthly payroll, generate itemized employee payslips, and review disbursement totals.</p>
      </div>
      <UiButton @click="openCreateModal">
        <Plus class="w-4 h-4 mr-2" /> New Payroll Run
      </UiButton>
    </div>

    <!-- Executive Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <UiStat
        v-for="stat in stats"
        :key="stat.label"
        :label="stat.label"
        :value="stat.value"
        :icon="stat.icon"
      />
    </div>

    <!-- Main Workspace -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      <!-- Left: Payroll Runs List -->
      <div class="lg:col-span-5 space-y-4">
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
          <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Payroll Runs</h2>
            <div class="flex items-center gap-1 bg-slate-100 p-0.5 rounded-lg text-xs font-semibold">
              <button
                type="button"
                @click="runFilter = 'all'"
                class="px-2.5 py-1 rounded-md transition-colors"
                :class="runFilter === 'all' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900'"
              >
                All
              </button>
              <button
                type="button"
                @click="runFilter = 'processed'"
                class="px-2.5 py-1 rounded-md transition-colors"
                :class="runFilter === 'processed' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900'"
              >
                Processed
              </button>
              <button
                type="button"
                @click="runFilter = 'draft'"
                class="px-2.5 py-1 rounded-md transition-colors"
                :class="runFilter === 'draft' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900'"
              >
                Draft
              </button>
            </div>
          </div>

          <div v-if="isLoadingRuns" class="p-12 flex justify-center">
            <UiSpinner size="md" />
          </div>

          <div v-else-if="filteredRuns.length" class="divide-y divide-slate-100 max-h-[600px] overflow-y-auto">
            <div
              v-for="run in filteredRuns"
              :key="run.id"
              @click="selectedRunId = run.id"
              class="p-4 transition-all cursor-pointer hover:bg-slate-50/80 flex flex-col gap-2"
              :class="activeRun?.id === run.id ? 'bg-primary-50/40 border-l-4 border-primary-600' : ''"
            >
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <Calendar class="w-4 h-4 text-slate-400" />
                  <span class="font-bold text-sm text-slate-900">
                    {{ new Date(run.period_start).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) }}
                    →
                    {{ new Date(run.period_end).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) }}
                  </span>
                </div>
                <UiBadge
                  :variant="run.status === 'processed' ? 'success' : 'warning'"
                  class="capitalize text-[10px] font-bold"
                >
                  {{ run.status }}
                </UiBadge>
              </div>

              <div class="flex items-center justify-between text-xs text-slate-500 pt-1">
                <span class="flex items-center gap-1 font-medium">
                  <Users class="w-3.5 h-3.5 text-slate-400" />
                  {{ run.payslips_count || (run.payslips?.length ?? 0) }} Employees
                </span>
                <span class="font-mono font-bold text-slate-900 text-sm">
                  {{ formatCurrency(run.payslips_sum_net_cents || 0) }}
                </span>
              </div>

              <div v-if="run.status === 'draft'" class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <button
                  type="button"
                  @click.stop="deleteMutation.mutate(run.id)"
                  class="text-xs text-red-500 hover:text-red-700 font-semibold px-2 py-1 hover:bg-red-50 rounded-md transition-colors"
                >
                  Delete Draft
                </button>
                <UiButton size="sm" @click.stop="processMutation.mutate(run.id)" :loading="processMutation.isPending.value">
                  Process Now
                </UiButton>
              </div>
            </div>
          </div>

          <div v-else class="p-12 text-center text-slate-400 space-y-3">
            <Banknote class="w-10 h-10 mx-auto text-slate-300" />
            <p class="text-xs font-semibold">No payroll runs found.</p>
            <UiButton size="sm" variant="outline" @click="openCreateModal">Create First Run</UiButton>
          </div>
        </div>
      </div>

      <!-- Right: Detailed Run View & Payslips -->
      <div class="lg:col-span-7 space-y-4">
        <div v-if="activeRun" class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-xs space-y-5">
          <!-- Run Header Summary -->
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
            <div>
              <div class="flex items-center gap-2">
                <h2 class="text-base font-black text-slate-900">
                  {{ new Date(activeRun.period_start).toLocaleDateString(undefined, { month: 'long', day: 'numeric' }) }}
                  –
                  {{ new Date(activeRun.period_end).toLocaleDateString(undefined, { month: 'long', day: 'numeric', year: 'numeric' }) }}
                </h2>
                <UiBadge :variant="activeRun.status === 'processed' ? 'success' : 'warning'" class="capitalize font-bold">
                  {{ activeRun.status }}
                </UiBadge>
              </div>
              <p v-if="activeRun.processed_at" class="text-xs text-slate-400 mt-0.5">
                Processed on {{ new Date(activeRun.processed_at).toLocaleString() }}
              </p>
            </div>

            <div v-if="activeRun.status === 'draft'" class="flex items-center gap-2">
              <UiButton size="sm" @click="processMutation.mutate(activeRun.id)" :loading="processMutation.isPending.value">
                Process Payroll Run
              </UiButton>
            </div>
          </div>

          <!-- Payout Metrics for Selected Run -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="p-4 bg-emerald-50/70 border border-emerald-100 rounded-xl">
              <span class="text-xs font-bold uppercase tracking-wider text-emerald-800">Total Net Disbursed</span>
              <p class="text-xl font-black font-mono text-emerald-950 mt-1">
                {{ formatCurrency(activeRun.payslips_sum_net_cents || 0) }}
              </p>
            </div>

            <div class="p-4 bg-slate-50 border border-slate-100 rounded-xl">
              <span class="text-xs font-bold uppercase tracking-wider text-slate-600">Total Gross Salary</span>
              <p class="text-xl font-black font-mono text-slate-900 mt-1">
                {{ formatCurrency(activeRun.payslips_sum_gross_cents || 0) }}
              </p>
            </div>

            <div class="p-4 bg-slate-50 border border-slate-100 rounded-xl">
              <span class="text-xs font-bold uppercase tracking-wider text-slate-600">Employees Paid</span>
              <p class="text-xl font-black font-mono text-slate-900 mt-1">
                {{ activeRun.payslips?.length || activeRun.payslips_count || 0 }}
              </p>
            </div>
          </div>

          <!-- Payslips Search & Table -->
          <div class="space-y-3 pt-2">
            <div class="flex items-center justify-between gap-3">
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">
                Itemized Employee Payslips ({{ filteredPayslips.length }})
              </h3>
              <UiInput
                v-model="payslipSearch"
                placeholder="Filter by employee name..."
                size="sm"
                class="w-56"
              >
                <template #prefix><Search class="w-3.5 h-3.5 text-slate-400" /></template>
              </UiInput>
            </div>

            <div v-if="filteredPayslips.length" class="divide-y divide-slate-100 border border-slate-100 rounded-xl overflow-hidden">
              <div
                v-for="slip in filteredPayslips"
                :key="slip.id"
                class="p-3.5 hover:bg-slate-50/70 transition-colors flex items-center justify-between gap-3"
              >
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full bg-slate-100 text-slate-700 font-bold flex items-center justify-center text-xs">
                    {{ slip.employee?.first_name?.[0] }}{{ slip.employee?.last_name?.[0] }}
                  </div>
                  <div>
                    <h4 class="font-bold text-slate-900 text-xs sm:text-sm">
                      {{ slip.employee?.first_name }} {{ slip.employee?.last_name }}
                    </h4>
                    <p class="text-[11px] text-slate-500">
                      {{ slip.employee?.position?.title || 'No Position' }} • {{ slip.employee?.department?.name || 'No Dept' }}
                    </p>
                  </div>
                </div>

                <div class="flex items-center gap-4">
                  <div class="text-right">
                    <span class="font-mono font-bold text-slate-900 text-sm">
                      {{ formatCurrency(slip.net_cents, slip.employee?.salary_currency || 'USD') }}
                    </span>
                    <p class="text-[10px] text-slate-400 capitalize">
                      {{ slip.employee?.payment_method?.replace('_', ' ') || 'Bank Transfer' }}
                    </p>
                  </div>

                  <UiButton
                    variant="ghost"
                    size="sm"
                    @click="viewPayslipDetails(slip)"
                    title="View Payslip Receipt"
                  >
                    <FileText class="w-4 h-4 text-slate-600" />
                  </UiButton>
                </div>
              </div>
            </div>

            <div v-else class="p-8 text-center bg-slate-50 rounded-xl text-xs text-slate-400">
              No payslips generated for this run yet. Click "Process Payroll Run" to compute payslips.
            </div>
          </div>
        </div>

        <div v-else class="bg-white rounded-2xl border border-slate-200/90 p-12 text-center space-y-3">
          <Banknote class="w-12 h-12 mx-auto text-slate-300" />
          <h3 class="font-bold text-slate-800 text-base">Select a Payroll Run</h3>
          <p class="text-xs text-slate-500 max-w-sm mx-auto">
            Choose a payroll period from the left list or create a new run to view payslips and disbursement details.
          </p>
        </div>
      </div>
    </div>

    <!-- Create Payroll Run Modal -->
    <UiModal v-model="isCreateModalOpen" title="New Payroll Run" size="lg">
      <div class="space-y-5">
        <!-- Date Presets -->
        <div class="space-y-1.5">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Quick Period Preset</label>
          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="setPeriodPreset('this_month')"
              class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition-colors cursor-pointer"
            >
              This Month
            </button>
            <button
              type="button"
              @click="setPeriodPreset('last_month')"
              class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition-colors cursor-pointer"
            >
              Last Month
            </button>
            <button
              type="button"
              @click="setPeriodPreset('bi_weekly')"
              class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition-colors cursor-pointer"
            >
              Last 14 Days
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UiInput v-model="form.period_start" type="date" label="Period Start Date" required />
          <UiInput v-model="form.period_end" type="date" label="Period End Date" required />
        </div>

        <!-- Live Eligible Workforce Preview -->
        <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 space-y-3">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <Sparkles class="w-4 h-4 text-primary-600" />
              <h4 class="text-xs font-bold uppercase tracking-wider text-slate-900">Workforce & Payout Preview</h4>
            </div>
            <span v-if="previewData" class="text-xs font-bold text-slate-700">
              {{ previewData.employee_count }} Active Employees
            </span>
          </div>

          <div v-if="isLoadingPreview" class="py-4 flex justify-center">
            <UiSpinner size="sm" />
          </div>

          <div v-else-if="previewData" class="space-y-2">
            <div class="flex items-center justify-between text-xs text-slate-600">
              <span>Estimated Total Gross Salary:</span>
              <span class="font-mono font-bold text-slate-900">{{ formatCurrency(previewData.total_gross_cents) }}</span>
            </div>
            <div class="flex items-center justify-between text-xs text-slate-600">
              <span>Estimated Net Disbursement:</span>
              <span class="font-mono font-bold text-emerald-700">{{ formatCurrency(previewData.total_net_cents) }}</span>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-2 pt-1">
          <input
            id="auto_process"
            v-model="form.auto_process"
            type="checkbox"
            class="h-4 w-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500 cursor-pointer"
          />
          <label for="auto_process" class="text-xs text-slate-700 font-semibold cursor-pointer">
            Process immediately and generate payslips upon creation
          </label>
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
          <UiButton variant="outline" type="button" @click="isCreateModalOpen = false">Cancel</UiButton>
          <UiButton :loading="createMutation.isPending.value" @click="createMutation.mutate()">
            {{ form.auto_process ? 'Create & Process' : 'Save Draft Run' }}
          </UiButton>
        </div>
      </div>
    </UiModal>

    <!-- Individual Payslip Receipt / Print Modal -->
    <UiModal v-model="isPayslipModalOpen" title="Employee Payslip Details" size="lg">
      <div v-if="selectedPayslip" class="space-y-6">
        <!-- Printable Payslip Voucher -->
        <div class="p-6 bg-white border border-slate-200 rounded-2xl space-y-6 shadow-xs" id="printable-payslip">
          <!-- Header -->
          <div class="flex justify-between items-start border-b border-slate-100 pb-4">
            <div>
              <h2 class="text-lg font-black text-slate-900">PAYSLIP VOUCHER</h2>
              <p class="text-xs font-mono text-slate-400 mt-0.5">SLIP-{{ selectedPayslip.id.slice(0, 8).toUpperCase() }}</p>
            </div>
            <div class="text-right">
              <UiBadge variant="success" class="font-bold">PAID</UiBadge>
              <p class="text-xs text-slate-400 mt-1">
                {{ new Date(selectedPayslip.created_at).toLocaleDateString() }}
              </p>
            </div>
          </div>

          <!-- Employee & Pay Info Grid -->
          <div class="grid grid-cols-2 gap-4 text-xs">
            <div>
              <span class="font-bold uppercase tracking-wider text-slate-400">Employee</span>
              <p class="font-black text-slate-900 text-sm mt-0.5">
                {{ selectedPayslip.employee?.first_name }} {{ selectedPayslip.employee?.last_name }}
              </p>
              <p class="text-slate-500 font-mono">{{ selectedPayslip.employee?.employee_number }}</p>
              <p class="text-slate-500">{{ selectedPayslip.employee?.email }}</p>
            </div>

            <div>
              <span class="font-bold uppercase tracking-wider text-slate-400">Department & Role</span>
              <p class="font-bold text-slate-900 text-sm mt-0.5">
                {{ selectedPayslip.employee?.position?.title || 'Position' }}
              </p>
              <p class="text-slate-500">{{ selectedPayslip.employee?.department?.name || 'Department' }}</p>
              <p class="text-slate-500 capitalize">Type: {{ selectedPayslip.employee?.employment_type?.replace('-', ' ') }}</p>
            </div>
          </div>

          <!-- Breakdown Table -->
          <div class="border border-slate-100 rounded-xl overflow-hidden">
            <table class="min-w-full divide-y divide-slate-100 text-xs">
              <thead class="bg-slate-50 font-bold uppercase tracking-wider text-slate-500 text-[10px]">
                <tr>
                  <th class="px-4 py-2.5 text-left">Earnings & Deductions Description</th>
                  <th class="px-4 py-2.5 text-right">Amount</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr>
                  <td class="px-4 py-2.5 text-slate-800 font-medium">Base Salary</td>
                  <td class="px-4 py-2.5 text-right font-mono font-bold text-slate-900">
                    {{ formatCurrency(selectedPayslip.gross_cents, selectedPayslip.employee?.salary_currency || 'USD') }}
                  </td>
                </tr>
                <tr>
                  <td class="px-4 py-2.5 text-slate-500">Statutory Deductions & Taxes</td>
                  <td class="px-4 py-2.5 text-right font-mono text-slate-500">
                    {{ formatCurrency(selectedPayslip.deductions_cents, selectedPayslip.employee?.salary_currency || 'USD') }}
                  </td>
                </tr>
                <tr class="bg-emerald-50/50 font-bold">
                  <td class="px-4 py-3 text-emerald-950 font-bold">Net Payout</td>
                  <td class="px-4 py-3 text-right font-mono font-black text-emerald-950 text-sm">
                    {{ formatCurrency(selectedPayslip.net_cents, selectedPayslip.employee?.salary_currency || 'USD') }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Banking & Payment Info -->
          <div class="p-3.5 bg-slate-50 rounded-xl text-xs flex justify-between items-center text-slate-600">
            <div>
              <span class="font-bold text-slate-800">Payment Method: </span>
              <span class="capitalize">{{ selectedPayslip.employee?.payment_method?.replace('_', ' ') || 'Direct Bank Transfer' }}</span>
              <span v-if="selectedPayslip.employee?.bank_name"> ({{ selectedPayslip.employee.bank_name }})</span>
            </div>
            <div v-if="selectedPayslip.employee?.bank_account_number" class="font-mono">
              Account: {{ selectedPayslip.employee.bank_account_number }}
            </div>
          </div>
        </div>

        <div class="flex justify-between items-center pt-2">
          <UiButton variant="outline" size="sm" @click="printPayslip">
            <Printer class="w-4 h-4 mr-2" /> Print Payslip
          </UiButton>
          <UiButton size="sm" @click="isPayslipModalOpen = false">Close</UiButton>
        </div>
      </div>
    </UiModal>
  </div>
</template>
