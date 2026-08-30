export type EmploymentType = 'full-time' | 'part-time' | 'contract' | 'intern' | 'probationary'
export type EmployeeStatus = 'active' | 'on-leave' | 'suspended' | 'terminated'
export type Gender = 'male' | 'female' | 'other'
export type LeaveStatus = 'pending' | 'approved' | 'rejected' | 'cancelled'
export type AttendanceStatus = 'present' | 'absent' | 'late' | 'on-leave' | 'holiday'
export type DocumentType = 'cv' | 'contract' | 'education' | 'id_proof' | 'certification' | 'tax' | 'other'

export interface EmployeeDocument {
  id: string
  employee_id: string
  title: string
  document_type: DocumentType
  file_path: string
  file_name: string
  file_size: number
  file_type?: string
  file_url?: string
  expiry_date?: string
  notes?: string
  uploaded_by_user_id?: string
  created_at: string
  updated_at: string
}

export interface Department {
  id: string
  name: string
  code?: string
  parent_id?: string
  manager_id?: string
  cost_center?: string
  headcount_budget?: number
  employees_count?: number
  created_at: string
  updated_at: string
  parent?: Department
  manager?: Employee
}

export interface Position {
  id: string
  title: string
  department_id: string
  job_grade?: string
  job_description?: string
  requirements?: string
  pay_grade_range?: string
  employees_count?: number
  created_at: string
  updated_at: string
  department?: Department
}

export interface Employee {
  id: string
  employee_number: string
  user_id?: string
  department_id: string
  position_id: string
  manager_id?: string
  first_name: string
  last_name: string
  preferred_name?: string
  email: string
  phone?: string
  date_of_birth?: string
  gender?: Gender
  employment_type: EmploymentType
  status: EmployeeStatus
  base_salary?: number | string
  salary_currency?: string
  salary_type?: 'monthly' | 'hourly' | 'yearly' | 'weekly' | string
  payment_method?: 'bank_transfer' | 'cash' | 'cheque' | string
  bank_name?: string
  bank_account_number?: string
  bank_routing_number?: string
  start_date: string
  probation_end_date?: string
  contract_end_date?: string
  work_location_id?: string
  emergency_contacts?: EmergencyContact[]
  custom_fields?: Record<string, any>
  created_at: string
  updated_at: string
  department?: Department
  position?: Position
  manager?: Employee
  avatar_url?: string
}

export interface EmergencyContact {
  name: string
  relationship: string
  phone: string
}

export interface LeaveType {
  id: string
  name: string
  code: string
  is_paid: boolean
  requires_approval: boolean
  max_consecutive_days?: number
  requires_attachment: boolean
}

export interface LeaveRequest {
  id: string
  employee_id: string
  leave_type_id: string
  start_date: string
  end_date: string
  is_half_day: boolean
  half_day_period?: 'morning' | 'afternoon'
  reason: string
  status: LeaveStatus
  approved_by?: string
  approved_at?: string
  rejection_reason?: string
  working_days: number
  attachment_url?: string
  created_at: string
  updated_at: string
  employee?: Employee
  leave_type?: LeaveType
}

export interface LeaveBalance {
  id: string
  employee_id: string
  leave_type_id: string
  entitled: number
  used: number
  pending: number
  remaining: number
  carry_over: number
  leave_type?: LeaveType
}

export interface AttendanceLog {
  id: string
  employee_id: string
  date: string
  clock_in?: string
  clock_out?: string
  method: string
  status: AttendanceStatus
  late_minutes: number
  early_departure_minutes: number
  overtime_minutes: number
  total_hours: number
  location_data?: any
  employee?: Employee
}

export interface AttendanceSummary {
  total_days: number
  present: number
  absent: number
  late: number
  on_leave: number
  holiday: number
  total_overtime_hours: number
}
