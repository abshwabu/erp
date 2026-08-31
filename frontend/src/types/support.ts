export type TicketStatus = 'open' | 'in_progress' | 'pending' | 'resolved' | 'closed'
export type TicketPriority = 'low' | 'normal' | 'high' | 'urgent'
export type TicketChannel = 'web' | 'email' | 'phone' | 'portal'
export type ArticleCategory = 'general' | 'billing' | 'technical' | 'account' | 'faq'

export interface SupportTicketMessage {
  id: string
  ticket_id: string
  user_id?: string | null
  sender_name: string
  sender_type: 'agent' | 'customer' | 'system'
  message: string
  is_internal: boolean
  user?: { id: string; name: string; email?: string }
  created_at: string
  updated_at: string
}

export interface SupportTicket {
  id: string
  ticket_number: string
  subject: string
  customer_id?: string | null
  assigned_to?: string | null
  contact_email?: string | null
  contact_name?: string | null
  status: TicketStatus
  priority: TicketPriority
  channel: TicketChannel
  resolved_at?: string | null
  assignee?: { id: string; name: string; email?: string }
  customer?: { id: string; name: string; company?: string; email?: string; phone?: string }
  messages_count?: number
  messages?: SupportTicketMessage[]
  created_at: string
  updated_at: string
}

export interface SupportKnowledgeArticle {
  id: string
  title: string
  slug: string
  category: ArticleCategory
  content: string
  summary?: string | null
  is_published: boolean
  views_count: number
  helpful_count: number
  author?: { id: string; name: string }
  created_at: string
  updated_at: string
}

export interface SupportDashboardStats {
  total_tickets: number
  open_tickets: number
  in_progress_tickets: number
  pending_tickets: number
  resolved_tickets: number
  urgent_tickets: number
  resolution_rate: number
  tickets_by_channel: Record<string, number>
  tickets_by_priority: Record<string, number>
  recent_tickets: SupportTicket[]
  total_articles: number
}
