import apiClient from './client'
import type {
  Account,
  Journal,
} from '@/types/accounting'

export const accountingApi = {
  // Accounts
  getAccounts: () => apiClient.get<any[]>('/accounting/accounts'),
  getAccountsTree: () => apiClient.get<Account[]>('/accounting/accounts/tree'),
  getAccountTypes: () => apiClient.get<any[]>('/accounting/account-types'),
  createAccount: (data: any) => apiClient.post<Account>('/accounting/accounts', data),
  getAccount: (id: string) => apiClient.get<Account>(`/accounting/accounts/${id}`),
  updateAccount: (id: string, data: any) => apiClient.patch<Account>(`/accounting/accounts/${id}`, data),
  deleteAccount: (id: string) => apiClient.delete(`/accounting/accounts/${id}`),
  importAccountsCsv: (file: File) => {
    const formData = new FormData()
    formData.append('file', file)
    return apiClient.post('/accounting/accounts/import', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })
  },

  // Journals
  getJournals: (params?: any) => apiClient.get<Journal[]>('/accounting/journals', { params }),
  createJournal: (data: any) => apiClient.post<Journal>('/accounting/journals', data),
  getJournal: (id: string) => apiClient.get<Journal>(`/accounting/journals/${id}`),
  postJournal: (id: string) => apiClient.post<Journal>(`/accounting/journals/${id}/post`),
  reverseJournal: (id: string) => apiClient.post<Journal>(`/accounting/journals/${id}/reverse`),
  getJournalLines: (id: string) => apiClient.get<any[]>(`/accounting/journals/${id}/lines`),

  // Reports
  getTrialBalance: (params: { from_date: string; to_date: string }) => 
    apiClient.get<any>('/accounting/reports/trial-balance', { params }),
  getProfitLoss: (params: { from_date: string; to_date: string }) => 
    apiClient.get<any>('/accounting/reports/profit-loss', { params }),
  getBalanceSheet: (params: { as_of_date: string }) => 
    apiClient.get<any>('/accounting/reports/balance-sheet', { params }),
  getGeneralLedger: (params: { account_id: string; from_date: string; to_date: string }) => 
    apiClient.get<any>('/accounting/reports/general-ledger', { params }),
  getARAging: () => apiClient.get<any[]>('/accounting/reports/ar-aging'),
  getAPAging: () => apiClient.get<any[]>('/accounting/reports/ap-aging'),
}
