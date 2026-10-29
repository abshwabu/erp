<script setup lang="ts">
import { ref, computed, markRaw } from 'vue'
import { useI18n } from 'vue-i18n'
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
  RefreshCw,
} from '@lucide/vue'

const { t } = useI18n()
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
  auto_process: true,
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

// Active run selection
const activeRun = computed(() => {
  if (selectedRunData.value) return selectedRunData.value
  if (!selectedRunId.value && runs.value && runs.value.length > 0) {
    return runs.value[0]
  }
  return runs.value?.find((r) => r.id === selectedRunId.value) || null
})

// Detect primary currency from payslips or employees
const activeCurrency = computed(() => {
  const payslips = activeRun.value?.payslips || []
  if (payslips.length > 0 && payslips[0]?.employee?.salary_currency) {
    return payslips[0].employee.salary_currency
  }
  if (previewData.value?.currency) {
    return previewData.value.currency
  }
  return 'USD'
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
    (sum, r) => sum + (Number(r.payslips_sum_net_cents) || 0),
    0
  )

  const latestRun = processedRuns[0]
  const latestNet = latestRun ? (Number(latestRun.payslips_sum_net_cents) || 0) : 0

  const draftCount = list.filter((r) => r.status === 'draft').length

  return [
    {
      label: t('payroll.kpis.totalProcessed'),
      value: formatCurrency(totalProcessedNet, activeCurrency.value),
      icon: markRaw(DollarSign),
    },
    {
      label: t('payroll.kpis.latestNet'),
      value: latestRun ? formatCurrency(latestNet, activeCurrency.value) : '—',
      icon: markRaw(Banknote),
    },
    {
      label: t('payroll.kpis.processedPayrolls'),
      value: processedRuns.length,
      icon: markRaw(CheckCircle2),
    },
    {
      label: t('payroll.kpis.draftRunsPending'),
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
    toast.success(form.value.auto_process ? t('payroll.messages.createdAndProcessed') : t('payroll.messages.draftCreated'))
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || t('payroll.messages.failedCreate'))
  },
})

const processMutation = useMutation({
  mutationFn: (id: string) => payrollApi.processRun(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['payroll'] })
    toast.success(t('payroll.messages.recalculated'))
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || t('payroll.messages.failedProcess'))
  },
})

const deleteMutation = useMutation({
  mutationFn: (id: string) => payrollApi.deleteRun(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['payroll', 'runs'] })
    if (selectedRunId.value) {
      selectedRunId.value = null
    }
    toast.success(t('payroll.messages.deleted'))
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || t('payroll.messages.failedDelete'))
  },
})

const handleDeleteRun = (run: PayrollRun) => {
  if (confirm(`${t('payroll.runs.deleteConfirm')} ${run.period_start} → ${run.period_end}?`)) {
    deleteMutation.mutate(run.id)
  }
}

const openCreateModal = () => {
  const now = new Date()
  form.value = {
    period_start: new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0, 10),
    period_end: new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().slice(0, 10),
    auto_process: true,
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

const formatCurrency = (cents: number | string, currency: string = 'USD') => {
  const val = (Number(cents) || 0) / 100
  return `${currency} ${val.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">{{ t('payroll.header.title') }}</h1>
        <p class="text-xs sm:text-sm text-slate-500">{{ t('payroll.header.subtitle') }}</p>
      </div>
      <UiButton @click="openCreateModal">
        <Plus class="w-4 h-4 mr-2" /> {{ t('payroll.header.newRunBtn') }}
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
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">{{ t('payroll.runs.title') }}</h2>
            <div class="flex items-center gap-1 bg-slate-100 p-0.5 rounded-lg text-xs font-semibold">
              <button
                type="button"
                @click="runFilter = 'all'"
                class="px-2.5 py-1 rounded-md transition-colors cursor-pointer"
                :class="runFilter === 'all' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900'"
              >
                {{ t('payroll.runs.filters.all') }}
              </button>
              <button
                type="button"
                @click="runFilter = 'processed'"
                class="px-2.5 py-1 rounded-md transition-colors cursor-pointer"
                :class="runFilter === 'processed' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900'"
              >
                {{ t('payroll.runs.filters.processed') }}
              </button>
              <button
                type="button"
                @click="runFilter = 'draft'"
                class="px-2.5 py-1 rounded-md transition-colors cursor-pointer"
                :class="runFilter === 'draft' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900'"
              >
                {{ t('payroll.runs.filters.draft') }}
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
                  {{ t(`payroll.runs.filters.${run.status}`, run.status) }}
                </UiBadge>
              </div>

              <div class="flex items-center justify-between text-xs text-slate-500 pt-1">
                <span class="flex items-center gap-1 font-medium">
                  <Users class="w-3.5 h-3.5 text-slate-400" />
                  {{ run.payslips_count || (run.payslips?.length ?? 0) }} {{ t('payroll.runs.employeesCount') }}
                </span>
                <span class="font-mono font-bold text-slate-900 text-sm">
                  {{ formatCurrency(run.payslips_sum_net_cents || 0, activeCurrency) }}
                </span>
              </div>

              <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <button
                  type="button"
                  @click.stop="handleDeleteRun(run)"
                  class="text-xs text-red-500 hover:text-red-700 font-semibold px-2 py-1 hover:bg-red-50 rounded-md transition-colors cursor-pointer"
                >
                  {{ t('payroll.runs.delete') }}
                </button>
                <UiButton
                  size="sm"
                  variant="outline"
                  @click.stop="processMutation.mutate(run.id)"
                  :loading="processMutation.isPending.value && selectedRunId === run.id"
                >
                  <RefreshCw class="w-3 h-3 mr-1" />
                  {{ run.status === 'processed' ? t('payroll.runs.recalculate') : t('payroll.runs.processNow') }}
                </UiButton>
              </div>
            </div>
          </div>

          <div v-else class="p-12 text-center text-slate-400 space-y-3">
            <Banknote class="w-10 h-10 mx-auto text-slate-300" />
            <p class="text-xs font-semibold">{{ t('payroll.runs.emptyTitle') }}</p>
            <UiButton size="sm" variant="outline" @click="openCreateModal">{{ t('payroll.runs.createFirst') }}</UiButton>
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
                  {{ t(`payroll.runs.filters.${activeRun.status}`, activeRun.status) }}
                </UiBadge>
              </div>
              <p v-if="activeRun.processed_at" class="text-xs text-slate-400 mt-0.5">
                {{ t('payroll.details.processedOn') }} {{ new Date(activeRun.processed_at).toLocaleString() }}
              </p>
            </div>

            <div class="flex items-center gap-2">
              <UiButton
                size="sm"
                variant="outline"
                @click="processMutation.mutate(activeRun.id)"
                :loading="processMutation.isPending.value"
              >
                <RefreshCw class="w-3.5 h-3.5 mr-1.5" />
                {{ activeRun.status === 'processed' ? t('payroll.details.recalculateSalaries') : t('payroll.details.processPayrollRun') }}
              </UiButton>
            </div>
          </div>

          <!-- Payout Metrics for Selected Run -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="p-4 bg-emerald-50/70 border border-emerald-100 rounded-xl">
              <span class="text-xs font-bold uppercase tracking-wider text-emerald-800">{{ t('payroll.details.totalNetDisbursed') }}</span>
              <p class="text-xl font-black font-mono text-emerald-950 mt-1">
                {{ formatCurrency(activeRun.payslips_sum_net_cents || 0, activeCurrency) }}
              </p>
            </div>

            <div class="p-4 bg-slate-50 border border-slate-100 rounded-xl">
              <span class="text-xs font-bold uppercase tracking-wider text-slate-600">{{ t('payroll.details.totalGrossSalary') }}</span>
              <p class="text-xl font-black font-mono text-slate-900 mt-1">
                {{ formatCurrency(activeRun.payslips_sum_gross_cents || 0, activeCurrency) }}
              </p>
            </div>

            <div class="p-4 bg-slate-50 border border-slate-100 rounded-xl">
              <span class="text-xs font-bold uppercase tracking-wider text-slate-600">{{ t('payroll.details.employeesPaid') }}</span>
              <p class="text-xl font-black font-mono text-slate-900 mt-1">
                {{ activeRun.payslips?.length || activeRun.payslips_count || 0 }}
              </p>
            </div>
          </div>

          <!-- Payslips Search & Table -->
          <div class="space-y-3 pt-2">
            <div class="flex items-center justify-between gap-3">
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">
                {{ t('payroll.details.itemizedPayslips') }} ({{ filteredPayslips.length }})
              </h3>
              <UiInput
                v-model="payslipSearch"
                :placeholder="t('payroll.details.searchPlaceholder')"
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
                      {{ slip.employee?.position?.title || t('payroll.details.noPosition') }} • {{ slip.employee?.department?.name || t('payroll.details.noDept') }}
                    </p>
                  </div>
                </div>

                <div class="flex items-center gap-4">
                  <div class="text-right">
                    <span class="font-mono font-bold text-slate-900 text-sm">
                      {{ formatCurrency(slip.net_cents, slip.employee?.salary_currency || activeCurrency) }}
                    </span>
                    <p class="text-[10px] text-slate-400 capitalize">
                      {{ slip.employee?.payment_method?.replace('_', ' ') || t('payroll.details.bankTransfer') }}
                    </p>
                  </div>

                  <UiButton
                    variant="ghost"
                    size="sm"
                    @click="viewPayslipDetails(slip)"
                    :title="t('payroll.payslipModal.title')"
                  >
                    <FileText class="w-4 h-4 text-slate-600" />
                  </UiButton>
                </div>
              </div>
            </div>

            <div v-else class="p-8 text-center bg-slate-50 rounded-xl text-xs text-slate-400">
              {{ t('payroll.details.emptyPayslips') }}
            </div>
          </div>
        </div>

        <div v-else class="bg-white rounded-2xl border border-slate-200/90 p-12 text-center space-y-3">
          <Banknote class="w-12 h-12 mx-auto text-slate-300" />
          <h3 class="font-bold text-slate-800 text-base">{{ t('payroll.details.selectRunTitle') }}</h3>
          <p class="text-xs text-slate-500 max-w-sm mx-auto">
            {{ t('payroll.details.selectRunSubtitle') }}
          </p>
        </div>
      </div>
    </div>

    <!-- Create Payroll Run Modal -->
    <UiModal v-model="isCreateModalOpen" :title="t('payroll.createModal.title')" size="lg">
      <div class="space-y-5">
        <!-- Date Presets -->
        <div class="space-y-1.5">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">{{ t('payroll.createModal.quickPresets') }}</label>
          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="setPeriodPreset('this_month')"
              class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition-colors cursor-pointer"
            >
              {{ t('payroll.createModal.presets.thisMonth') }}
            </button>
            <button
              type="button"
              @click="setPeriodPreset('last_month')"
              class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition-colors cursor-pointer"
            >
              {{ t('payroll.createModal.presets.lastMonth') }}
            </button>
            <button
              type="button"
              @click="setPeriodPreset('bi_weekly')"
              class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition-colors cursor-pointer"
            >
              {{ t('payroll.createModal.presets.last14Days') }}
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UiInput v-model="form.period_start" type="date" :label="t('payroll.createModal.periodStart')" required />
          <UiInput v-model="form.period_end" type="date" :label="t('payroll.createModal.periodEnd')" required />
        </div>

        <!-- Live Eligible Workforce Preview -->
        <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 space-y-3">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <Sparkles class="w-4 h-4 text-primary-600" />
              <h4 class="text-xs font-bold uppercase tracking-wider text-slate-900">{{ t('payroll.createModal.workforcePreview') }}</h4>
            </div>
            <span v-if="previewData" class="text-xs font-bold text-slate-700">
              {{ previewData.employee_count }} {{ t('payroll.createModal.activeEmployees') }}
            </span>
          </div>

          <div v-if="isLoadingPreview" class="py-4 flex justify-center">
            <UiSpinner size="sm" />
          </div>

          <div v-else-if="previewData" class="space-y-2">
            <div class="flex items-center justify-between text-xs text-slate-600">
              <span>{{ t('payroll.createModal.estGross') }}</span>
              <span class="font-mono font-bold text-slate-900">
                {{ formatCurrency(previewData.total_gross_cents, previewData.currency) }}
              </span>
            </div>
            <div class="flex items-center justify-between text-xs text-slate-600">
              <span>{{ t('payroll.createModal.estNet') }}</span>
              <span class="font-mono font-bold text-emerald-700">
                {{ formatCurrency(previewData.total_net_cents, previewData.currency) }}
              </span>
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
            {{ t('payroll.createModal.autoProcessCheckbox') }}
          </label>
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
          <UiButton variant="outline" type="button" @click="isCreateModalOpen = false">{{ t('payroll.createModal.cancel') }}</UiButton>
          <UiButton :loading="createMutation.isPending.value" @click="createMutation.mutate()">
            {{ form.auto_process ? t('payroll.createModal.createProcessBtn') : t('payroll.createModal.saveDraftBtn') }}
          </UiButton>
        </div>
      </div>
    </UiModal>

    <!-- Individual Payslip Receipt / Print Modal -->
    <UiModal v-model="isPayslipModalOpen" :title="t('payroll.payslipModal.title')" size="lg">
      <div v-if="selectedPayslip" class="space-y-6">
        <!-- Printable Payslip Voucher -->
        <div class="p-6 bg-white border border-slate-200 rounded-2xl space-y-6 shadow-xs" id="printable-payslip">
          <!-- Header -->
          <div class="flex justify-between items-start border-b border-slate-100 pb-4">
            <div>
              <h2 class="text-lg font-black text-slate-900">{{ t('payroll.payslipModal.voucherTitle') }}</h2>
              <p class="text-xs font-mono text-slate-400 mt-0.5">SLIP-{{ selectedPayslip.id.slice(0, 8).toUpperCase() }}</p>
            </div>
            <div class="text-right">
              <UiBadge variant="success" class="font-bold">{{ t('payroll.payslipModal.paidBadge') }}</UiBadge>
              <p class="text-xs text-slate-400 mt-1">
                {{ new Date(selectedPayslip.created_at).toLocaleDateString() }}
              </p>
            </div>
          </div>

          <!-- Employee & Pay Info Grid -->
          <div class="grid grid-cols-2 gap-4 text-xs">
            <div>
              <span class="font-bold uppercase tracking-wider text-slate-400">{{ t('payroll.payslipModal.employee') }}</span>
              <p class="font-black text-slate-900 text-sm mt-0.5">
                {{ selectedPayslip.employee?.first_name }} {{ selectedPayslip.employee?.last_name }}
              </p>
              <p class="text-slate-500 font-mono">{{ selectedPayslip.employee?.employee_number }}</p>
              <p class="text-slate-500">{{ selectedPayslip.employee?.email }}</p>
            </div>

            <div>
              <span class="font-bold uppercase tracking-wider text-slate-400">{{ t('payroll.payslipModal.deptRole') }}</span>
              <p class="font-bold text-slate-900 text-sm mt-0.5">
                {{ selectedPayslip.employee?.position?.title || t('payroll.details.noPosition') }}
              </p>
              <p class="text-slate-500">{{ selectedPayslip.employee?.department?.name || t('payroll.details.noDept') }}</p>
              <p class="text-slate-500 capitalize">{{ t('payroll.payslipModal.type') }}: {{ selectedPayslip.employee?.employment_type?.replace('-', ' ') }}</p>
            </div>
          </div>

          <!-- Breakdown Table -->
          <div class="border border-slate-100 rounded-xl overflow-hidden">
            <table class="min-w-full divide-y divide-slate-100 text-xs">
              <thead class="bg-slate-50 font-bold uppercase tracking-wider text-slate-500 text-[10px]">
                <tr>
                  <th class="px-4 py-2.5 text-left">{{ t('payroll.payslipModal.description') }}</th>
                  <th class="px-4 py-2.5 text-right">{{ t('payroll.payslipModal.amount') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr>
                  <td class="px-4 py-2.5 text-slate-800 font-medium">{{ t('payroll.payslipModal.baseSalary') }}</td>
                  <td class="px-4 py-2.5 text-right font-mono font-bold text-slate-900">
                    {{ formatCurrency(selectedPayslip.gross_cents, selectedPayslip.employee?.salary_currency || activeCurrency) }}
                  </td>
                </tr>
                <tr>
                  <td class="px-4 py-2.5 text-slate-500">{{ t('payroll.payslipModal.statutoryDeductions') }}</td>
                  <td class="px-4 py-2.5 text-right font-mono text-slate-500">
                    {{ formatCurrency(selectedPayslip.deductions_cents, selectedPayslip.employee?.salary_currency || activeCurrency) }}
                  </td>
                </tr>
                <tr class="bg-emerald-50/50 font-bold">
                  <td class="px-4 py-3 text-emerald-950 font-bold">{{ t('payroll.payslipModal.netPayout') }}</td>
                  <td class="px-4 py-3 text-right font-mono font-black text-emerald-950 text-sm">
                    {{ formatCurrency(selectedPayslip.net_cents, selectedPayslip.employee?.salary_currency || activeCurrency) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Banking & Payment Info -->
          <div class="p-3.5 bg-slate-50 rounded-xl text-xs flex justify-between items-center text-slate-600">
            <div>
              <span class="font-bold text-slate-800">{{ t('payroll.payslipModal.paymentMethod') }} </span>
              <span class="capitalize">{{ selectedPayslip.employee?.payment_method?.replace('_', ' ') || t('payroll.payslipModal.directBankTransfer') }}</span>
              <span v-if="selectedPayslip.employee?.bank_name"> ({{ selectedPayslip.employee.bank_name }})</span>
            </div>
            <div v-if="selectedPayslip.employee?.bank_account_number" class="font-mono">
              {{ t('payroll.payslipModal.account') }} {{ selectedPayslip.employee.bank_account_number }}
            </div>
          </div>
        </div>

        <div class="flex justify-between items-center pt-2">
          <UiButton variant="outline" size="sm" @click="printPayslip">
            <Printer class="w-4 h-4 mr-2" /> {{ t('payroll.payslipModal.printPayslip') }}
          </UiButton>
          <UiButton size="sm" @click="isPayslipModalOpen = false">{{ t('payroll.payslipModal.close') }}</UiButton>
        </div>
      </div>
    </UiModal>
  </div>
</template>
