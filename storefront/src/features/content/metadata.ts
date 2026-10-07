import type { Metadata } from 'next'
import { env } from '@/core/config/env'
import { siteConfig } from '@/core/config/site'
import type { ContentItem, ContentType } from '@/domain/content/content-item'

const typeLabel: Record<ContentType, string> = {
  article: 'مقال',
  guide: 'دليل',
}

function contentUrl(item: ContentItem): string {
  const fallback = new URL(`/${item.type === 'article' ? 'articles' : 'guides'}/${item.slug}`, env.siteUrl).toString()
  if (!item.canonical_url?.trim()) return fallback
  try {
    return new URL(item.canonical_url).toString()
  } catch {
    return fallback
  }
}

export function buildContentMetadata(item: ContentItem): Metadata {
  const title = item.seo_title?.trim() || item.title
  const description = (item.seo_description?.trim() || item.excerpt?.trim() || `اقرأ ${typeLabel[item.type]} «${item.title}» من ${siteConfig.name}.`).slice(0, 320)
  const url = contentUrl(item)
  const image = item.featured_image?.trim()
  return {
    title,
    description,
    alternates: { canonical: url },
    openGraph: {
      title,
      description,
      url,
      siteName: siteConfig.name,
      locale: 'ar_EG',
      type: 'article',
      ...(image ? { images: [{ url: image, alt: item.title }] } : {}),
    },
    twitter: {
      card: image ? 'summary_large_image' : 'summary',
      title,
      description,
      ...(image ? { images: [image] } : {}),
    },
  }
}

export function buildContentNotFoundMetadata(type: ContentType): Metadata {
  return {
    title: type === 'article' ? 'المقال غير متاح' : 'الدليل غير متاح',
    robots: { index: false, follow: false },
  }
}
