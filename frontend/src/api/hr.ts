import axios from 'axios'
import type { Employee, AttendanceLog, LeaveRequest, LeaveBalance, LeaveType } from '@/types/hr'

const apiClient = axios.create({
  baseURL: '/api/hr',
})

export const hrApi = {
  // Employees
  getEmployees: () => apiClient.get<Employee[]>('/employees'),
  getEmployee: (id: string) => apiClient.get<Employee>(`/employees/${id}`),
  createEmployee: (data: Partial<Employee>) => apiClient.post('/employees', data),
  updateEmployee: (id: string, data: Partial<Employee>) => apiClient.patch(`/employees/${id}`, data),
  getLeaveBalances: (id: string) => apiClient.get<LeaveBalance[]>(`/employees/${id}/leave-balances`),
  getEmployeeAttendance: (id: string) => apiClient.get<AttendanceLog[]>(`/employees/${id}/attendance`),

  // Attendance
  clockIn: (data: { employee_id: string; method: string }) => apiClient.post('/attendance/clock-in', data),
  clockOut: (data: { employee_id: string; method: string }) => apiClient.post('/attendance/clock-out', data),
  getAttendance: (params: any) => apiClient.get('/attendance', { params }),
  getAttendanceSummary: (params: any) => apiClient.get('/attendance/summary', { params }),

  // Leave
  getLeaveTypes: () => apiClient.get<LeaveType[]>('/leave/types'),
  getLeaveRequests: () => apiClient.get<LeaveRequest[]>('/leave/requests'),
  submitLeaveRequest: (data: Partial<LeaveRequest>) => apiClient.post('/leave/requests', data),
  approveLeaveRequest: (id: string) => apiClient.patch(`/leave/requests/${id}/approve`),
  rejectLeaveRequest: (id: string, notes: string) => apiClient.patch(`/leave/requests/${id}/reject`, { notes }),
  cancelLeaveRequest: (id: string) => apiClient.patch(`/leave/requests/${id}/cancel`),
}
