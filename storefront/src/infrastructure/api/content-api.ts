import { ApiError, requestJson } from '@/core/http/client'
import type { ContentRepository } from '@/application/content/content-repository'
import type { ContentListOptions, ContentListResult } from '@/application/content/content-types'
import type { ContentItem, ContentType } from '@/domain/content/content-item'

type ContentListResponse = {
  data: ContentItem[]
  meta?: {
    current_page?: number
    per_page?: number
    total?: number
    last_page?: number
  }
}
type ContentResponse = { data: ContentItem }

export const contentApi: ContentRepository = {
  async listPublished(type: ContentType, options: ContentListOptions = {}): Promise<ContentListResult> {
    const page = options.page ?? 1
    const perPage = options.perPage ?? 20
    const query = new URLSearchParams({ type, page: String(page), per_page: String(perPage) })
    const payload = await requestJson<ContentListResponse>(`/content?${query.toString()}`, { cache: 'no-store' })
    const items = Array.isArray(payload.data)
      ? payload.data.filter((item) => item.type === type && item.status === 'published')
      : []
    return {
      items,
      page: payload.meta?.current_page ?? page,
      perPage: payload.meta?.per_page ?? perPage,
      total: payload.meta?.total ?? items.length,
      lastPage: payload.meta?.last_page ?? 1,
    }
  },

  async findPublishedBySlug(type: ContentType, slug: string): Promise<ContentItem> {
    const payload = await requestJson<ContentResponse>(`/content/${encodeURIComponent(slug)}`, { cache: 'no-store' })
    if (payload.data.type !== type || payload.data.status !== 'published') {
      throw new ApiError('المحتوى غير متاح', 404, payload)
    }
    return payload.data
  },
}
