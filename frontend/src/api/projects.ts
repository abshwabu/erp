import apiClient from './client'
import type {
  Project,
  ProjectTask,
  ProjectMilestone,
  ProjectTimeLog,
  ProjectsDashboardStats,
} from '@/types/projects'

export const projectsApi = {
  // Dashboard & Stats
  getStats: () => apiClient.get<{ data: ProjectsDashboardStats }>('/projects/dashboard/stats'),

  // Projects
  getProjects: (params?: any) => apiClient.get<{ data: Project[] }>('/projects', { params }),
  getProject: (id: string) => apiClient.get<{ data: Project }>(`/projects/${id}`),
  createProject: (data: Partial<Project>) => apiClient.post<{ data: Project }>('/projects', data),
  updateProject: (id: string, data: Partial<Project>) => apiClient.put<{ data: Project }>(`/projects/${id}`, data),
  deleteProject: (id: string) => apiClient.delete(`/projects/${id}`),

  // Tasks & Kanban
  getTasks: (params?: any) => apiClient.get<{ data: ProjectTask[] }>('/projects/tasks/all', { params }),
  getTask: (id: string) => apiClient.get<{ data: ProjectTask }>(`/projects/tasks/${id}`),
  createTask: (data: Partial<ProjectTask>) => apiClient.post<{ data: ProjectTask }>('/projects/tasks', data),
  updateTask: (id: string, data: Partial<ProjectTask>) => apiClient.put<{ data: ProjectTask }>(`/projects/tasks/${id}`, data),
  updateTaskStatus: (id: string, status: string) => apiClient.patch<{ data: ProjectTask }>(`/projects/tasks/${id}/status`, { status }),
  deleteTask: (id: string) => apiClient.delete(`/projects/tasks/${id}`),

  // Milestones
  getMilestones: (params?: any) => apiClient.get<{ data: ProjectMilestone[] }>('/projects/milestones/all', { params }),
  createMilestone: (data: Partial<ProjectMilestone>) => apiClient.post<{ data: ProjectMilestone }>('/projects/milestones', data),
  updateMilestone: (id: string, data: Partial<ProjectMilestone>) => apiClient.put<{ data: ProjectMilestone }>(`/projects/milestones/${id}`, data),
  deleteMilestone: (id: string) => apiClient.delete(`/projects/milestones/${id}`),

  // Time Tracking / Timesheets
  getTimeLogs: (params?: any) => apiClient.get<{ data: ProjectTimeLog[] }>('/projects/time-logs/all', { params }),
  createTimeLog: (data: Partial<ProjectTimeLog>) => apiClient.post<{ data: ProjectTimeLog }>('/projects/time-logs', data),
  updateTimeLog: (id: string, data: Partial<ProjectTimeLog>) => apiClient.put<{ data: ProjectTimeLog }>(`/projects/time-logs/${id}`, data),
  deleteTimeLog: (id: string) => apiClient.delete(`/projects/time-logs/${id}`),
}
