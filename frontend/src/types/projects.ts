export type ProjectStatus = 'planned' | 'in_progress' | 'on_hold' | 'completed' | 'cancelled'
export type ProjectPriority = 'low' | 'medium' | 'high' | 'urgent'
export type TaskStatus = 'todo' | 'in_progress' | 'review' | 'done'
export type MilestoneStatus = 'pending' | 'in_progress' | 'achieved' | 'delayed'

export interface Project {
  id: string
  code: string
  name: string
  description?: string | null
  manager_id?: string | null
  customer_id?: string | null
  status: ProjectStatus
  priority: ProjectPriority
  budget: number | string
  currency: string
  start_date?: string | null
  due_date?: string | null
  completed_at?: string | null
  color: string
  tasks_count?: number
  progress_percent: number
  total_logged_hours: number
  total_estimated_hours: number
  manager?: { id: string; name: string; email: string }
  customer?: { id: string; name: string; company?: string }
  tasks?: ProjectTask[]
  milestones?: ProjectMilestone[]
  time_logs?: ProjectTimeLog[]
  created_at: string
  updated_at: string
}

export interface ProjectTask {
  id: string
  project_id: string
  milestone_id?: string | null
  title: string
  description?: string | null
  assigned_to_user_id?: string | null
  status: TaskStatus
  priority: ProjectPriority
  due_date?: string | null
  estimated_hours: number | string
  logged_hours: number | string
  order: number
  completed_at?: string | null
  project?: { id: string; code: string; name: string; status: ProjectStatus; color?: string }
  assignee?: { id: string; name: string; email?: string }
  milestone?: { id: string; title: string }
  created_at: string
  updated_at: string
}

export interface ProjectMilestone {
  id: string
  project_id: string
  title: string
  description?: string | null
  due_date?: string | null
  status: MilestoneStatus
  completed_at?: string | null
  tasks_count?: number
  project?: { id: string; code: string; name: string; status: ProjectStatus; color?: string }
  created_at: string
  updated_at: string
}

export interface ProjectTimeLog {
  id: string
  project_id: string
  task_id?: string | null
  user_id: string
  hours: number | string
  log_date: string
  description?: string | null
  is_billable: boolean
  project?: { id: string; code: string; name: string; status: ProjectStatus; color?: string }
  task?: { id: string; title: string; status: TaskStatus; priority: ProjectPriority }
  user?: { id: string; name: string }
  created_at: string
  updated_at: string
}

export interface ProjectsDashboardStats {
  total_projects: number
  active_projects: number
  completed_projects: number
  total_budget: number
  task_completion_rate: number
  total_logged_hours: number
  total_estimated_hours: number
  status_breakdown: Record<string, number>
  recent_projects: Project[]
  recent_tasks: ProjectTask[]
}
