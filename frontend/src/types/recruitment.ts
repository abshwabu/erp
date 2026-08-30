import type { Department, Position } from './hr'

export type FormFieldType =
  | 'text'
  | 'textarea'
  | 'number'
  | 'email'
  | 'phone'
  | 'select'
  | 'radio'
  | 'checkbox'
  | 'file'
  | 'image'
  | 'date'

export interface FormQuestionSchema {
  id: string
  label: string
  name: string
  type: FormFieldType
  placeholder?: string
  required: boolean
  options?: string[]
  help_text?: string
}

export type JobStatus = 'published' | 'draft' | 'closed'

export interface JobPosting {
  id: string
  title: string
  slug: string
  department_id?: string
  position_id?: string
  location: string
  employment_type: string
  experience_level?: string
  min_salary?: number | string
  max_salary?: number | string
  salary_currency: string
  description: string
  requirements?: string
  benefits?: string
  deadline?: string
  status: JobStatus
  custom_form_schema?: FormQuestionSchema[]
  views_count: number
  applications_count?: number
  department?: Department
  position?: Position
  created_at: string
  updated_at: string
}

export type ApplicationStatus =
  | 'new'
  | 'reviewed'
  | 'shortlisted'
  | 'interviewing'
  | 'offered'
  | 'hired'
  | 'rejected'

export interface JobApplication {
  id: string
  job_posting_id: string
  applicant_name: string
  applicant_email: string
  applicant_phone?: string
  resume_url?: string
  photo_url?: string
  cover_letter?: string
  custom_form_responses?: Record<string, any>
  status: ApplicationStatus
  rating?: number
  notes?: string
  submitted_at: string
  created_at: string
  updated_at: string
  job_posting?: JobPosting
}

export interface RecruitmentStats {
  active_jobs: number
  total_applications: number
  in_pipeline: number
  hired_candidates: number
}
