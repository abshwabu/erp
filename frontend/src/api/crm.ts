import apiClient from './client'

export interface CrmContact {
  id: string
  name: string
  email?: string | null
  phone?: string | null
  company?: string | null
  status?: 'lead' | 'customer'
}

export const crmApi = {
  getContacts() {
    return apiClient.get<{ data: CrmContact[] }>('/crm/contacts')
  },

  createContact(data: Partial<CrmContact>) {
    return apiClient.post<{ data: CrmContact }>('/crm/contacts', data)
  },
}
