import apiClient from './client'

export interface PayrollRun {
  id: string
  period_start: string
  period_end: string
  status: 'draft' | 'processed'
  processed_at?: string | null
  payslips_count?: number
  payslips?: Payslip[]
}

export interface Payslip {
  id: string
  payroll_run_id: string
  employee_id: string
  gross_cents: number
  deductions_cents: number
  net_cents: number
  employee?: {
    id: string
    first_name: string
    last_name: string
    employee_number?: string
  }
}

export const payrollApi = {
  getRuns() {
    return apiClient.get<{ data: PayrollRun[] }>('/payroll/runs')
  },

  createRun(data: { period_start: string; period_end: string }) {
    return apiClient.post<{ data: PayrollRun }>('/payroll/runs', data)
  },

  processRun(id: string) {
    return apiClient.post<{ data: PayrollRun }>(`/payroll/runs/${id}/process`)
  },

  getPayslips(runId: string) {
    return apiClient.get<{ data: Payslip[] }>(`/payroll/runs/${runId}/payslips`)
  },
}
