import apiClient from './client'

export interface DocumentItem {
  id: string
  name: string
  file_name: string
  mime_type: string
  file_size_bytes: number
  folder: string
  tags: string[] | null
  description: string | null
  created_at: string
  updated_at: string
  uploader?: { id: string; name: string; email: string }
}

export interface DocumentFilters {
  search?: string
  folder?: string
}

export interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number | null
  to: number | null
}

export interface PaginatedDocuments {
  data: DocumentItem[]
  meta: PaginationMeta
}

function normalizeMeta(meta: any): PaginationMeta {
  return {
    current_page: Number(meta?.current_page ?? meta?.currentPage ?? 1),
    last_page: Number(meta?.last_page ?? meta?.lastPage ?? 1),
    per_page: Number(meta?.per_page ?? meta?.perPage ?? 25),
    total: Number(meta?.total ?? 0),
    from: meta?.from ?? null,
    to: meta?.to ?? null,
  }
}

function asPaginated(payload: any): PaginatedDocuments {
  // Handle nested Laravel paginated responses: { data: { data: [...], meta/links } }
  const inner = payload?.data ?? payload
  const data = Array.isArray(inner?.data) ? inner.data : Array.isArray(inner) ? inner : []
  return {
    data,
    meta: normalizeMeta(inner?.meta ?? payload?.meta ?? payload),
  }
}

export const documentsApi = {
  /**
   * List documents with optional filters and pagination.
   */
  getDocuments(filters: DocumentFilters = {}, page = 1) {
    return apiClient
      .get('/documents', {
        params: {
          page,
          search: filters.search || undefined,
          folder: filters.folder || undefined,
        },
      })
      .then((res) => ({ ...res, data: asPaginated(res.data) }))
  },

  /**
   * Get a single document by ID.
   */
  getDocument(id: string) {
    return apiClient.get(`/documents/${id}`)
  },

  /**
   * Upload a new document with multipart/form-data.
   */
  uploadDocument(payload: {
    file: File
    name: string
    folder?: string
    description?: string
    tags?: string[]
  }) {
    const formData = new FormData()
    formData.append('file', payload.file)
    formData.append('name', payload.name)
    if (payload.folder) formData.append('folder', payload.folder)
    if (payload.description) formData.append('description', payload.description)
    if (payload.tags && payload.tags.length > 0) {
      payload.tags.forEach((tag, i) => formData.append(`tags[${i}]`, tag))
    }

    return apiClient.post('/documents', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })
  },

  /**
   * Download a document. Returns a blob response for browser download.
   */
  downloadDocument(id: string) {
    return apiClient.get(`/documents/${id}/download`, {
      responseType: 'blob',
    })
  },

  /**
   * Delete a document by ID.
   */
  deleteDocument(id: string) {
    return apiClient.delete(`/documents/${id}`)
  },
}
