export type LeadStatus = 'new' | 'contacted' | 'qualified' | 'unqualified' | 'converted'
export type LeadPriority = 'low' | 'medium' | 'high' | 'urgent'
export type DealStage = 'qualification' | 'proposal' | 'negotiation' | 'won' | 'lost'
export type ActivityType = 'call' | 'meeting' | 'email' | 'task' | 'follow_up' | 'note'
export type ActivityStatus = 'pending' | 'completed' | 'cancelled'

export interface Lead {
  id: string
  name: string
  company?: string | null
  title?: string | null
  email?: string | null
  phone?: string | null
  source: string
  status: LeadStatus
  priority: LeadPriority
  estimated_value?: number | string | null
  currency: string
  assigned_to_user_id?: string | null
  converted_customer_id?: string | null
  converted_deal_id?: string | null
  notes?: string | null
  customer?: CrmContact
  deal?: Deal
  assigned_user?: { id: string; name: string }
  created_at: string
  updated_at: string
}

export interface Deal {
  id: string
  title: string
  customer_id?: string | null
  lead_id?: string | null
  stage: DealStage
  amount: number | string
  currency: string
  probability: number
  expected_close_date?: string | null
  actual_close_date?: string | null
  assigned_to_user_id?: string | null
  lost_reason?: string | null
  notes?: string | null
  customer?: CrmContact
  lead?: Lead
  assigned_user?: { id: string; name: string }
  activities?: Activity[]
  created_at: string
  updated_at: string
}

export interface CrmContact {
  id: string
  name: string
  email?: string | null
  phone?: string | null
  company?: string | null
  job_title?: string | null
  status: 'lead' | 'customer' | 'partner' | 'churned'
  source?: string | null
  address?: string | null
  city?: string | null
  country?: string | null
  website?: string | null
  notes?: string | null
  deals_count?: number
  invoices_count?: number
  activities_count?: number
  deals?: Deal[]
  invoices?: any[]
  activities?: Activity[]
  created_at: string
  updated_at: string
}

export interface Activity {
  id: string
  type: ActivityType
  title: string
  description?: string | null
  due_date?: string | null
  completed_at?: string | null
  status: ActivityStatus
  priority: 'low' | 'medium' | 'high'
  customer_id?: string | null
  lead_id?: string | null
  deal_id?: string | null
  customer?: CrmContact
  deal?: Deal
  lead?: Lead
  assigned_user?: { id: string; name: string }
  created_at: string
  updated_at: string
}

export interface CrmDashboardStats {
  total_pipeline_value: number
  won_deals_value: number
  win_rate: number
  active_leads_count: number
  total_customers_count: number
  deals_by_stage: Record<string, { count: number; total_amount: number }>
  recent_activities: Activity[]
  top_deals: Deal[]
}
