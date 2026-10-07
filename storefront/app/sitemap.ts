import type { MetadataRoute } from 'next'
import { env } from '@/core/config/env'
import { listBrands, listCategories, listProducts } from '@/features/catalog/api'
import { listPublishedContent } from '@/features/content/api'
import type { ContentItem, ContentType } from '@/domain/content/content-item'
import type { ProductListResult } from '@/application/catalog/catalog-types'
import type { ContentListResult } from '@/application/content/content-types'
import { CONTENT_SITEMAP_BATCH_SIZE, CONTENT_SITEMAP_PAGE_SIZE } from '@/features/content/pagination'

const PRODUCTS_PER_PAGE = 100
const SITEMAP_PAGE_BATCH_SIZE = 5

function optionalLastModified(value?: string | null) {
  if (!value) return undefined
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? undefined : date
}

async function listAllProducts(): Promise<ProductListResult['items']> {
  const firstPage = await listProducts('', { page: 1, perPage: PRODUCTS_PER_PAGE })
  if (firstPage.totalPages <= 1) return firstPage.items

  const remainingPages = Array.from({ length: firstPage.totalPages - 1 }, (_, index) => index + 2)
  const pages: ProductListResult[] = []
  for (let index = 0; index < remainingPages.length; index += SITEMAP_PAGE_BATCH_SIZE) {
    const batch = remainingPages.slice(index, index + SITEMAP_PAGE_BATCH_SIZE)
    pages.push(...await Promise.all(
      batch.map((page) => listProducts('', { page, perPage: PRODUCTS_PER_PAGE })),
    ))
  }

  return [firstPage, ...pages].flatMap((result) => result.items)
}

async function listAllContent(type: ContentType): Promise<ContentItem[]> {
  const firstPage = await listPublishedContent(type, { page: 1, perPage: CONTENT_SITEMAP_PAGE_SIZE })
  if (firstPage.lastPage <= 1) return firstPage.items

  const remainingPages = Array.from({ length: firstPage.lastPage - 1 }, (_, index) => index + 2)
  const pages: ContentListResult[] = []
  for (let index = 0; index < remainingPages.length; index += CONTENT_SITEMAP_BATCH_SIZE) {
    const batch = remainingPages.slice(index, index + CONTENT_SITEMAP_BATCH_SIZE)
    pages.push(...await Promise.all(
      batch.map((page) => listPublishedContent(type, { page, perPage: CONTENT_SITEMAP_PAGE_SIZE })),
    ))
  }

  return [firstPage, ...pages].flatMap((result) => result.items)
}

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const staticRoutes: MetadataRoute.Sitemap = [
    { url: env.siteUrl, changeFrequency: 'daily', priority: 1 },
    { url: `${env.siteUrl}/products`, changeFrequency: 'daily', priority: 0.9 },
    { url: `${env.siteUrl}/articles`, changeFrequency: 'weekly', priority: 0.7 },
    { url: `${env.siteUrl}/guides`, changeFrequency: 'weekly', priority: 0.7 },
  ]

  const [articlesResult, guidesResult] = await Promise.allSettled([
    listAllContent('article'),
    listAllContent('guide'),
  ])
  const contentItems = [
    ...(articlesResult.status === 'fulfilled' ? articlesResult.value : []),
    ...(guidesResult.status === 'fulfilled' ? guidesResult.value : []),
  ]
  const contentRoutes: MetadataRoute.Sitemap = contentItems.map((item) => ({
    url: `${env.siteUrl}/${item.type === 'article' ? 'articles' : 'guides'}/${item.slug}`,
    lastModified: optionalLastModified(item.updated_at),
    changeFrequency: 'weekly',
    priority: 0.65,
  }))

  try {
    const [products, categories, brands] = await Promise.all([
      listAllProducts(),
      listCategories(),
      listBrands(),
    ])

    return [
      ...staticRoutes,
      ...products.map((product) => ({
        url: `${env.siteUrl}/products/${product.slug ?? product.id}`,
        lastModified: optionalLastModified(product.updated_at),
        changeFrequency: 'daily' as const,
        priority: 0.8,
      })),
      ...categories.filter((item) => item.slug).map((item) => ({
        url: `${env.siteUrl}/categories/${item.slug}`,
        lastModified: optionalLastModified(item.updated_at),
        changeFrequency: 'weekly' as const,
        priority: 0.7,
      })),
      ...brands.filter((item) => item.slug).map((item) => ({
        url: `${env.siteUrl}/brands/${item.slug}`,
        lastModified: optionalLastModified(item.updated_at),
        changeFrequency: 'weekly' as const,
        priority: 0.7,
      })),
      ...contentRoutes,
    ]
  } catch {
    return [...staticRoutes, ...contentRoutes]
  }
}
