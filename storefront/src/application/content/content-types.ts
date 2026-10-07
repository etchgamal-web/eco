import type { ContentItem } from '@/domain/content/content-item'

export type ContentListOptions = {
  page?: number
  perPage?: number
}

export type ContentListResult = {
  items: ContentItem[]
  page: number
  perPage: number
  total: number
  lastPage: number
}
