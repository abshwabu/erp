import apiClient from './client'
import type {
  FinanceCategory,
  FinanceTransaction,
  FinanceSummary,
  SelectableAccounts,
} from '@/types/finance'

export const financeApi = {
  // Summary Statistics
  getSummary: (params?: { start_date?: string; end_date?: string }) =>
    apiClient.get<FinanceSummary>('/finance/summary', { params }),

  // Transactions
  getTransactions: (params?: {
    type?: string
    category_id?: string
    status?: string
    payment_method?: string
    start_date?: string
    end_date?: string
    search?: string
    page?: number
    per_page?: number
  }) => apiClient.get<any>('/finance/transactions', { params }),

  getTransaction: (id: string) =>
    apiClient.get<FinanceTransaction>(`/finance/transactions/${id}`),

  createTransaction: (data: any) =>
    apiClient.post<{ message: string; data: FinanceTransaction }>('/finance/transactions', data),

  updateTransaction: (id: string, data: any) =>
    apiClient.put<{ message: string; data: FinanceTransaction }>(`/finance/transactions/${id}`, data),

  deleteTransaction: (id: string) =>
    apiClient.delete(`/finance/transactions/${id}`),

  // Categories
  getCategories: (params?: { type?: string }) =>
    apiClient.get<FinanceCategory[]>('/finance/categories', { params }),

  createCategory: (data: any) =>
    apiClient.post<FinanceCategory>('/finance/categories', data),

  updateCategory: (id: string, data: any) =>
    apiClient.put<FinanceCategory>(`/finance/categories/${id}`, data),

  deleteCategory: (id: string) =>
    apiClient.delete(`/finance/categories/${id}`),

  // Selectable Accounts for double-entry mapping
  getSelectableAccounts: () =>
    apiClient.get<SelectableAccounts>('/finance/accounts'),

  // Export
  exportTransactions: (payload: {
    format: 'xlsx' | 'csv' | 'pdf'
    type?: string
    category_id?: string
    status?: string
    start_date?: string
    end_date?: string
    inline?: boolean
  }) =>
    apiClient.post('/finance/export', payload, {
      responseType: 'blob',
    }),

  // Sync POS Sales & Invoice Incomes
  syncSales: () =>
    apiClient.post<{ status: string; message: string; data: any }>('/finance/sync'),
}
