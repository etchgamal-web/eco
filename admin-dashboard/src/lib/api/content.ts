import { request } from './client'

export type ContentType = 'article' | 'guide' | 'faq' | 'comparison'
export type ContentStatus = 'draft' | 'published'

export type ApiContent = {
  id: number
  type: ContentType
  title: string
  slug: string
  excerpt?: string | null
  body?: string | null
  status: ContentStatus
  published_at?: string | null
  updated_at?: string | null
  seo_title?: string | null
  seo_description?: string | null
  canonical_url?: string | null
  featured_image?: string | null
  author?: { id?: number; name?: string | null } | null
  products?: Array<{ id: number; name?: string | null }>
  categories?: Array<{ id: number; name?: string | null }>
}

export type ContentFilters = { type?: ContentType; status?: ContentStatus }
export type ContentPayload = Omit<Partial<ApiContent>, 'id' | 'author' | 'products' | 'categories' | 'updated_at' | 'published_at'> & { type: ContentType; title: string; slug: string; status?: ContentStatus; product_ids?: number[]; category_ids?: number[]; published_at?: string | null }

export async function listContent(filters: ContentFilters = {}) {
  const query = new URLSearchParams()
  if (filters.type) query.set('type', filters.type)
  if (filters.status) query.set('status', filters.status)
  const suffix = query.toString() ? `?${query.toString()}` : ''
  return (await request<{ data: ApiContent[] }>(`/admin/content${suffix}`)).data
}

export async function getContent(id: number) {
  return (await request<{ data: ApiContent }>(`/admin/content/${id}`)).data
}

export async function createContent(payload: ContentPayload) {
  return (await request<{ data: ApiContent }>('/admin/content', { method: 'POST', body: JSON.stringify(payload) })).data
}

export async function updateContent(id: number, payload: ContentPayload) {
  return (await request<{ data: ApiContent }>(`/admin/content/${id}`, { method: 'PATCH', body: JSON.stringify(payload) })).data
}

export async function publishContent(id: number) {
  return (await request<{ data: ApiContent }>(`/admin/content/${id}/publish`, { method: 'POST' })).data
}

export async function unpublishContent(id: number) {
  return (await request<{ data: ApiContent }>(`/admin/content/${id}/unpublish`, { method: 'POST' })).data
}

export async function deleteContent(id: number) {
  await request(`/admin/content/${id}`, { method: 'DELETE' })
}
