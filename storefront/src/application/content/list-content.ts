import type { ContentRepository } from './content-repository'
import type { ContentListOptions } from './content-types'
import type { ContentType } from '@/domain/content/content-item'

export function createListContent(repository: ContentRepository) {
  return (type: ContentType, options?: ContentListOptions) => repository.listPublished(type, options)
}
