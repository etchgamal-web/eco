import type { ContentRepository } from './content-repository'
import type { ContentItem, ContentType } from '@/domain/content/content-item'

export function createGetContent(repository: ContentRepository) {
  return (type: ContentType, slug: string): Promise<ContentItem> => repository.findPublishedBySlug(type, slug)
}
