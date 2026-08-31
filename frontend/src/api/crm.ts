import apiClient from './client'
import type { Lead, Deal, CrmContact, Activity, CrmDashboardStats, LeadForm } from '@/types/crm'

export const crmApi = {
  // Analytics & Dashboard
  getStats: () => apiClient.get<{ data: CrmDashboardStats }>('/crm/dashboard/stats'),

  // Lead Forms & Multi-Channel Intake
  getLeadForms: (params?: any) => apiClient.get<{ data: LeadForm[] }>('/crm/lead-forms', { params }),
  getLeadForm: (id: string) => apiClient.get<{ data: LeadForm }>(`/crm/lead-forms/${id}`),
  createLeadForm: (data: Partial<LeadForm>) => apiClient.post<{ data: LeadForm }>('/crm/lead-forms', data),
  updateLeadForm: (id: string, data: Partial<LeadForm>) => apiClient.patch<{ data: LeadForm }>(`/crm/lead-forms/${id}`, data),
  deleteLeadForm: (id: string) => apiClient.delete(`/crm/lead-forms/${id}`),

  // Public Lead Form Views & Submissions (No Auth Required)
  getPublicLeadForm: (idOrSlug: string) => 
    apiClient.get<{ data: LeadForm; company: { name: string } }>(`/public/leads/forms/${idOrSlug}`),
  submitPublicLeadForm: (idOrSlug: string, data: any) => 
    apiClient.post<{ message: string; thank_you_title?: string; redirect_url?: string; lead_id: string }>(
      `/public/leads/forms/${idOrSlug}/submit`,
      data
    ),

  // Leads
  getLeads: (params?: any) => apiClient.get<{ data: Lead[] }>('/crm/leads', { params }),
  getLead: (id: string) => apiClient.get<{ data: Lead }>(`/crm/leads/${id}`),
  createLead: (data: Partial<Lead>) => apiClient.post<{ data: Lead }>('/crm/leads', data),
  updateLead: (id: string, data: Partial<Lead>) => apiClient.patch<{ data: Lead }>(`/crm/leads/${id}`, data),
  deleteLead: (id: string) => apiClient.delete(`/crm/leads/${id}`),
  convertLead: (id: string, data: { deal_title?: string; deal_amount?: number; stage?: string }) =>
    apiClient.post<{ data: { lead: Lead; customer: CrmContact; deal: Deal } }>(`/crm/leads/${id}/convert`, data),

  // Deals & Pipeline
  getDeals: (params?: any) => apiClient.get<{ data: Deal[] }>('/crm/deals', { params }),
  getDeal: (id: string) => apiClient.get<{ data: Deal }>(`/crm/deals/${id}`),
  createDeal: (data: Partial<Deal>) => apiClient.post<{ data: Deal }>('/crm/deals', data),
  updateDeal: (id: string, data: Partial<Deal>) => apiClient.patch<{ data: Deal }>(`/crm/deals/${id}`, data),
  updateDealStage: (id: string, data: { stage: string; lost_reason?: string }) =>
    apiClient.patch<{ data: Deal }>(`/crm/deals/${id}/stage`, data),
  deleteDeal: (id: string) => apiClient.delete(`/crm/deals/${id}`),

  // Contacts
  getContacts: (params?: any) => apiClient.get<{ data: CrmContact[] }>('/crm/contacts', { params }),
  getContact: (id: string) => apiClient.get<{ data: CrmContact }>(`/crm/contacts/${id}`),
  createContact: (data: Partial<CrmContact>) => apiClient.post<{ data: CrmContact }>('/crm/contacts', data),
  updateContact: (id: string, data: Partial<CrmContact>) => apiClient.patch<{ data: CrmContact }>(`/crm/contacts/${id}`, data),
  deleteContact: (id: string) => apiClient.delete(`/crm/contacts/${id}`),

  // Activities
  getActivities: (params?: any) => apiClient.get<{ data: Activity[] }>('/crm/activities', { params }),
  createActivity: (data: Partial<Activity>) => apiClient.post<{ data: Activity }>('/crm/activities', data),
  updateActivity: (id: string, data: Partial<Activity>) => apiClient.patch<{ data: Activity }>(`/crm/activities/${id}`, data),
  toggleActivityComplete: (id: string) => apiClient.patch<{ data: Activity }>(`/crm/activities/${id}/toggle-complete`),
  deleteActivity: (id: string) => apiClient.delete(`/crm/activities/${id}`),
}
