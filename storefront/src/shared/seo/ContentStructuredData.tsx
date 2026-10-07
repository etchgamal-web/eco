import { env } from '@/core/config/env'
import { siteConfig } from '@/core/config/site'
import type { ContentItem } from '@/domain/content/content-item'

type ContentStructuredDataProps = { item: ContentItem }

function isoDate(value?: string | null): string | undefined {
  if (!value) return undefined
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? undefined : date.toISOString()
}

export default function ContentStructuredData({ item }: ContentStructuredDataProps) {
  const canonical = item.canonical_url?.trim() || `/${item.type === 'article' ? 'articles' : 'guides'}/${item.slug}`
  const data = {
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: item.title,
    description: item.seo_description?.trim() || item.excerpt?.trim() || undefined,
    articleSection: item.type === 'article' ? 'مقالات' : 'أدلة',
    inLanguage: siteConfig.locale,
    url: new URL(canonical, env.siteUrl).toString(),
    mainEntityOfPage: {
      '@type': 'WebPage',
      '@id': new URL(canonical, env.siteUrl).toString(),
    },
    ...(item.featured_image ? { image: [item.featured_image] } : {}),
    ...(isoDate(item.published_at) ? { datePublished: isoDate(item.published_at) } : {}),
    ...(isoDate(item.updated_at) ? { dateModified: isoDate(item.updated_at) } : {}),
    ...(item.author?.name?.trim() ? { author: { '@type': 'Person', name: item.author.name.trim() } } : {}),
    publisher: {
      '@type': 'Organization',
      name: siteConfig.name,
      url: env.siteUrl,
    },
  }
  const serialized = JSON.stringify(data).replace(/</g, '\\u003c')
  return <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: serialized }} />
}
