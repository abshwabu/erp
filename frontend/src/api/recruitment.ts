import apiClient from './client'
import type { JobPosting, JobApplication, RecruitmentStats } from '@/types/recruitment'

export const recruitmentApi = {
  // Job Postings (Internal)
  getJobs: (params?: any) => apiClient.get<JobPosting[]>('/hr/jobs', { params }),
  getJob: (id: string) => apiClient.get<JobPosting>(`/hr/jobs/${id}`),
  createJob: (data: Partial<JobPosting>) => apiClient.post<JobPosting>('/hr/jobs', data),
  updateJob: (id: string, data: Partial<JobPosting>) => apiClient.patch<JobPosting>(`/hr/jobs/${id}`, data),
  deleteJob: (id: string) => apiClient.delete(`/hr/jobs/${id}`),
  getStats: () => apiClient.get<RecruitmentStats>('/hr/jobs/stats'),

  // Applications (Internal)
  getApplications: (jobId: string, params?: any) => 
    apiClient.get<JobApplication[]>(`/hr/jobs/${jobId}/applications`, { params }),
  updateApplication: (jobId: string, applicationId: string, data: Partial<JobApplication>) => 
    apiClient.patch<JobApplication>(`/hr/jobs/${jobId}/applications/${applicationId}`, data),
  deleteApplication: (jobId: string, applicationId: string) => 
    apiClient.delete(`/hr/jobs/${jobId}/applications/${applicationId}`),

  // Public Careers Page & Application Submission
  getPublicJob: (idOrSlug: string) => 
    apiClient.get<{ data: JobPosting; company: { name: string } }>(`/public/careers/jobs/${idOrSlug}`),
  submitPublicApplication: (idOrSlug: string, formData: FormData) => 
    apiClient.post<{ message: string; application_id: string }>(`/public/careers/jobs/${idOrSlug}/apply`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    }),
}
