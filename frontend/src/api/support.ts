import apiClient from './client'
import type {
  SupportTicket,
  SupportTicketMessage,
  SupportKnowledgeArticle,
  SupportDashboardStats,
} from '@/types/support'

export const supportApi = {
  // Dashboard & KPIs
  getStats: () => apiClient.get<{ data: SupportDashboardStats }>('/support/dashboard/stats'),

  // Tickets
  getTickets: (params?: any) => apiClient.get<{ data: SupportTicket[] }>('/support/tickets', { params }),
  getTicket: (id: string) => apiClient.get<{ data: SupportTicket }>(`/support/tickets/${id}`),
  createTicket: (data: Partial<SupportTicket> & { message: string }) =>
    apiClient.post<{ data: SupportTicket }>('/support/tickets', data),
  updateTicket: (id: string, data: Partial<SupportTicket>) =>
    apiClient.put<{ data: SupportTicket }>(`/support/tickets/${id}`, data),
  replyTicket: (id: string, data: { message: string; is_internal?: boolean; sender_type?: string }) =>
    apiClient.post<{ data: SupportTicketMessage }>(`/support/tickets/${id}/reply`, data),
  deleteTicket: (id: string) => apiClient.delete(`/support/tickets/${id}`),

  // Knowledge Base Articles
  getArticles: (params?: any) => apiClient.get<{ data: SupportKnowledgeArticle[] }>('/support/articles', { params }),
  getArticle: (idOrSlug: string) => apiClient.get<{ data: SupportKnowledgeArticle }>(`/support/articles/${idOrSlug}`),
  createArticle: (data: Partial<SupportKnowledgeArticle>) =>
    apiClient.post<{ data: SupportKnowledgeArticle }>('/support/articles', data),
  updateArticle: (id: string, data: Partial<SupportKnowledgeArticle>) =>
    apiClient.put<{ data: SupportKnowledgeArticle }>(`/support/articles/${id}`, data),
  deleteArticle: (id: string) => apiClient.delete(`/support/articles/${id}`),
}
