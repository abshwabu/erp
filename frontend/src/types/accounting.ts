export type AccountType = 'asset' | 'liability' | 'equity' | 'revenue' | 'expense' | 'cost_of_sales'

export type JournalStatus = 'draft' | 'posted' | 'reversed'

export type JournalSource = 'manual' | 'pos' | 'sales' | 'procurement' | 'payroll' | 'inventory' | 'bank' | 'system'

export type FiscalPeriodStatus = 'open' | 'closed' | 'locked'

export interface Account {
  id: string
  parentId?: string | null
  code: string
  name: string
  type: AccountType
  description?: string
  currencyCode: string
  isActive: boolean
  isSystemAccount: boolean
  currentPeriodBalance: number
  openingBalance?: number
  children?: Account[]
}

export interface JournalLine {
  id: string
  journalId: string
  accountId: string
  account?: Account
  description?: string
  debit: number
  credit: number
  currencyCode: string
  baseDebit?: number
  baseCredit?: number
  exchangeRate?: number
}

export interface Journal {
  id: string
  reference: string
  description: string
  journalDate: string
  periodId?: string
  status: JournalStatus
  sourceType: JournalSource
  sourceReference?: string
  reversalOfId?: string | null
  postedAt?: string | null
  postedBy?: string | null
  totalDebit: number
  totalCredit: number
  lines: JournalLine[]
}

export interface FiscalPeriod {
  id: string
  year: number
  month: number
  startDate: string
  endDate: string
  status: FiscalPeriodStatus
}

export interface TrialBalanceRow {
  accountId: string
  code: string
  name: string
  type: AccountType
  openingBalance: number
  debits: number
  credits: number
  closingBalance: number
  priorPeriodBalance?: number
}

export interface TrialBalanceSection {
  type: AccountType
  label: string
  rows: TrialBalanceRow[]
  subtotal: {
    openingBalance: number
    debits: number
    credits: number
    closingBalance: number
    priorPeriodBalance?: number
  }
}

export interface TrialBalance {
  fromDate: string
  toDate: string
  comparedFromDate?: string
  comparedToDate?: string
  comparePreviousPeriod: boolean
  sections: TrialBalanceSection[]
  totals: TrialBalanceSection['subtotal']
}

export interface ProfitLossRow {
  label: string
  amount: number
  priorAmount?: number
  monthlyAmounts?: number[]
}

export interface ProfitLossCategory {
  label: string
  rows: ProfitLossRow[]
  subtotal: number
  priorSubtotal?: number
}

export interface ProfitLoss {
  fromDate: string
  toDate: string
  comparisonFromDate?: string
  comparisonToDate?: string
  comparePreviousPeriod: boolean
  monthlyMode: boolean
  revenue: ProfitLossRow[]
  cogs: ProfitLossRow[]
  operatingExpenses: ProfitLossCategory[]
  revenueSubtotal: number
  cogsSubtotal: number
  grossProfit: number
  totalOperatingExpenses: number
  ebitda: number
  netProfit: number
}

export interface BalanceSheetRow {
  accountId: string
  code: string
  name: string
  balance: number
  comparisonBalance?: number
}

export interface BalanceSheetGroup {
  label: string
  rows: BalanceSheetRow[]
  subtotal: number
  comparisonSubtotal?: number
}

export interface BalanceSheet {
  asOfDate: string
  comparisonDate?: string
  assets: BalanceSheetGroup[]
  liabilities: BalanceSheetGroup[]
  equity: BalanceSheetGroup[]
  assetsTotal: number
  liabilitiesTotal: number
  equityTotal: number
  difference: number
}

export interface AgingInvoice {
  id: string
  reference: string
  dueDate: string
  invoiceDate: string
  amount: number
  bucket: '0-30' | '31-60' | '61-90' | '90+'
  daysOverdue: number
}

export interface AgingBuckets {
  outstanding: number
  bucket0_30: number
  bucket31_60: number
  bucket61_90: number
  bucket90_plus: number
}

export interface ARAgingRow extends AgingBuckets {
  id: string
  customerName: string
  invoiceCount: number
  percentage: number
  invoices: AgingInvoice[]
}

export interface APAgingRow extends AgingBuckets {
  id: string
  supplierName: string
  invoiceCount: number
  percentage: number
  invoices: AgingInvoice[]
}

