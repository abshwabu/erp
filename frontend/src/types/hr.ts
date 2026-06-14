export interface Department {
  id: string
  name: string
  code: string
  parent_id?: string
  head_employee_id?: string
}

export interface Position {
  id: string
  department_id: string
  title: string
  job_grade?: string
  min_salary_cents?: number
  max_salary_cents?: number
  description?: string
  is_active: boolean
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
  status: 'active' | 'on_leave' | 'suspended' | 'terminated'
  start_date: string
  department?: Department
  position?: Position
}

export interface AttendanceLog {
  id: string
  employee_id: string
  clock_type: 'in' | 'out'
  logged_at: string
  method: 'web' | 'mobile' | 'biometric' | 'manual'
  notes?: string
}

export interface LeaveType {
  id: string
  name: string
  code: string
  is_paid: boolean
  max_days_per_year: number
}

export interface LeaveRequest {
  id: string
  employee_id: string
  leave_type_id: string
  start_date: string
  end_date: string
  days_taken: number
  status: 'pending' | 'approved' | 'rejected' | 'cancelled'
  leave_type?: LeaveType
}

export interface LeaveBalance {
  leave_type_id: string
  entitled_days: number
  accrued_days: number
  taken_days: number
  remaining_days: number
  leave_type: LeaveType
}
