export type TransactionType = 'income' | 'expense'
export type TransactionStatus = 'completed' | 'pending' | 'cancelled'
export type PaymentMethod =
  | 'cash'
  | 'bank_transfer'
  | 'check'
  | 'credit_card'
  | 'mobile_money'
  | 'other'

export interface FinanceCategory {
  id: string
  name: string
  type: 'income' | 'expense' | 'both'
  color?: string
  description?: string
  account_id?: string | null
  account?: {
    id: string
    code: string
    name: string
  } | null
  is_system?: boolean
  transactions_count?: number
  created_at?: string
  updated_at?: string
}

export interface FinanceTransaction {
  id: string
  transaction_number: string
  type: TransactionType
  category_id?: string | null
  category?: FinanceCategory | null
  account_id?: string | null
  account?: {
    id: string
    code: string
    name: string
  } | null
  payment_account_id?: string | null
  payment_account?: {
    id: string
    code: string
    name: string
  } | null
  journal_id?: string | null
  journal?: {
    id: string
    reference: string
    description: string
    journal_date: string
    status: string
    lines?: Array<{
      id: string
      account_id: string
      debit_cents: number
      credit_cents: number
      description?: string
      account?: {
        id: string
        code: string
        name: string
      }
    }>
  } | null
  amount_cents: number
  amount: number
  currency_code: string
  transaction_date: string
  payment_method: PaymentMethod
  reference?: string | null
  payee_payer?: string | null
  description?: string | null
  status: TransactionStatus
  receipt_url?: string | null
  created_by_id?: string | null
  created_by?: {
    id: string
    name: string
    email: string
  } | null
  created_at: string
  updated_at: string
}

export interface FinanceSummary {
  total_income_cents: number
  total_expense_cents: number
  net_cents: number
  total_income: number
  total_expense: number
  net: number
  income_count: number
  expense_count: number
  total_transactions: number
  pending_count: number
  pending_cents: number
  pending_amount: number
  by_category: Array<{
    id: string
    name: string
    type: string
    color?: string
    total_cents: number
    total: number
  }>
  monthly_trend: Array<{
    label: string
    year: number
    month: number
    income: number
    expense: number
    net: number
    income_cents: number
    expense_cents: number
  }>
}

export interface FinanceAccountOption {
  id: string
  code: string
  name: string
  currency_code: string
}

export interface SelectableAccounts {
  payment_accounts: FinanceAccountOption[]
  revenue_accounts: FinanceAccountOption[]
  expense_accounts: FinanceAccountOption[]
}
