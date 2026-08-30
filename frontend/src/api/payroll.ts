import apiClient from './client'
import type { Employee } from '@/types/hr'

export interface PayrollRun {
  id: string
  period_start: string
  period_end: string
  status: 'draft' | 'processed'
  processed_at?: string | null
  payslips_count?: number
  payslips_sum_net_cents?: number
  payslips_sum_gross_cents?: number
  payslips_sum_deductions_cents?: number
  payslips?: Payslip[]
}

export interface Payslip {
  id: string
  payroll_run_id: string
  employee_id: string
  gross_cents: number
  deductions_cents: number
  net_cents: number
  created_at: string
  updated_at: string
  employee?: Employee
}

export interface PayrollPreview {
  employee_count: number
  total_gross_cents: number
  total_net_cents: number
  employees: Array<{
    employee_id: string
    employee_number: string
    employee_name: string
    department: string
    position: string
    base_salary: number | null
    currency: string
    salary_type: string
    payment_method: string
    bank_name: string | null
    bank_account_number: string | null
    gross_cents: number
    deductions_cents: number
    net_cents: number
  }>
}

export const payrollApi = {
  getRuns() {
    return apiClient.get<{ data: PayrollRun[] }>('/payroll/runs')
  },

  getRun(id: string) {
    return apiClient.get<{ data: PayrollRun }>(`/payroll/runs/${id}`)
  },

  getPreview() {
    return apiClient.get<{ data: PayrollPreview }>('/payroll/runs/preview')
  },

  createRun(data: { period_start: string; period_end: string; auto_process?: boolean }) {
    return apiClient.post<{ data: PayrollRun }>('/payroll/runs', data)
  },

  processRun(id: string) {
    return apiClient.post<{ data: PayrollRun }>(`/payroll/runs/${id}/process`)
  },

  deleteRun(id: string) {
    return apiClient.delete(`/payroll/runs/${id}`)
  },

  getPayslips(runId: string) {
    return apiClient.get<{ data: Payslip[] }>(`/payroll/runs/${runId}/payslips`)
  },
}
