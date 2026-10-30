import apiClient from './client'
import type { Asset } from '@/types/assets'

export interface AssetListParams {
  category?: string
  status?: string
  page?: number
  per_page?: number
  search?: string
}

export const assetsApi = {
  getAssets: (params?: AssetListParams) =>
    apiClient.get<any>('/assets', { params }),

  getAsset: (id: string) =>
    apiClient.get<any>(`/assets/${id}`),

  createAsset: (data: any) =>
    apiClient.post<any>('/assets', data),

  updateAsset: (id: string, data: any) =>
    apiClient.put<any>(`/assets/${id}`, data),

  deleteAsset: (id: string) =>
    apiClient.delete(`/assets/${id}`),

  generateDepreciationSchedule: (id: string) =>
    apiClient.post<any>(`/assets/${id}/depreciation-schedule`),
}
