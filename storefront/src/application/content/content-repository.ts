import type { ContentItem, ContentType } from '@/domain/content/content-item'
import type { ContentListOptions, ContentListResult } from './content-types'

export interface ContentRepository {
  listPublished(type: ContentType, options?: ContentListOptions): Promise<ContentListResult>
  findPublishedBySlug(type: ContentType, slug: string): Promise<ContentItem>
}
