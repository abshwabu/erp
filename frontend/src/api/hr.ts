import apiClient from './client'
import type { 
  Employee, 
  Department, 
  Position, 
  AttendanceLog, 
  LeaveRequest, 
  LeaveType, 
  LeaveBalance,
  AttendanceSummary,
  EmployeeDocument
} from '@/types/hr'

export const hrApi = {
  // Employees
  getEmployees: (params?: any) => apiClient.get<Employee[]>('/hr/employees', { params }),
  getEmployee: (id: string) => apiClient.get<Employee>(`/hr/employees/${id}`),
  createEmployee: (data: Partial<Employee>) => apiClient.post<Employee>('/hr/employees', data),
  updateEmployee: (id: string, data: Partial<Employee>) => apiClient.patch<Employee>(`/hr/employees/${id}`, data),
  deleteEmployee: (id: string) => apiClient.delete(`/hr/employees/${id}`),
  resetEmployeePassword: (id: string, data: { password: string }) => apiClient.post(`/hr/employees/${id}/reset-password`, data),
  
  // Employee Documents
  getEmployeeDocuments: (employeeId: string, params?: any) => 
    apiClient.get<EmployeeDocument[]>(`/hr/employees/${employeeId}/documents`, { params }),
  uploadEmployeeDocument: (employeeId: string, formData: FormData) => 
    apiClient.post<EmployeeDocument>(`/hr/employees/${employeeId}/documents`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    }),
  deleteEmployeeDocument: (employeeId: string, documentId: string) => 
    apiClient.delete(`/hr/employees/${employeeId}/documents/${documentId}`),
  downloadEmployeeDocument: (employeeId: string, documentId: string) =>
    apiClient.get(`/hr/employees/${employeeId}/documents/${documentId}/download`, { responseType: 'blob' }),

  // Org Structure
  getDepartments: () => apiClient.get<Department[]>('/hr/departments'),
  createDepartment: (data: Partial<Department>) => apiClient.post<Department>('/hr/departments', data),
  updateDepartment: (id: string, data: Partial<Department>) => apiClient.patch<Department>(`/hr/departments/${id}`, data),
  deleteDepartment: (id: string) => apiClient.delete(`/hr/departments/${id}`),
  getPositions: () => apiClient.get<Position[]>('/hr/positions'),
  createPosition: (data: Partial<Position>) => apiClient.post<Position>('/hr/positions', data),
  updatePosition: (id: string, data: Partial<Position>) => apiClient.patch<Position>(`/hr/positions/${id}`, data),
  deletePosition: (id: string) => apiClient.delete(`/hr/positions/${id}`),
  
  // Attendance
  getAttendance: (params?: any) => apiClient.get<AttendanceLog[]>('/hr/attendance', { params }),
  getAttendanceSummary: (params?: any) => apiClient.get<AttendanceSummary>('/hr/attendance/summary', { params }),
  getEmployeeAttendance: (id: string, params?: any) => apiClient.get<AttendanceLog[]>(`/hr/employees/${id}/attendance`, { params }),
  clockIn: (data: { employee_id: string; method: string; location?: any }) => apiClient.post('/hr/attendance/clock-in', data),
  clockOut: (data: { employee_id: string; method: string; location?: any }) => apiClient.post('/hr/attendance/clock-out', data),
  
  // Leave
  getLeaveTypes: () => apiClient.get<LeaveType[]>('/hr/leave/types'),
  getLeaveRequests: (params?: any) => apiClient.get<LeaveRequest[]>('/hr/leave/requests', { params }),
  getEmployeeLeaveBalances: (id: string) => apiClient.get<LeaveBalance[]>(`/hr/employees/${id}/leave-balances`),
  getEmployeeLeaveRequests: (id: string) => apiClient.get<LeaveRequest[]>('/hr/leave/requests', { params: { employee_id: id } }),
  submitLeaveRequest: (data: Partial<LeaveRequest>) => apiClient.post<LeaveRequest>('/hr/leave/requests', data),
  approveLeaveRequest: (id: string, notes?: string) => apiClient.patch(`/hr/leave/requests/${id}/approve`, { notes }),
  rejectLeaveRequest: (id: string, notes: string) => apiClient.patch(`/hr/leave/requests/${id}/reject`, { notes }),
  cancelLeaveRequest: (id: string) => apiClient.patch(`/hr/leave/requests/${id}/cancel`),
}
