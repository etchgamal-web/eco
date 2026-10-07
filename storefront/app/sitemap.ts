import type { MetadataRoute } from 'next'
import { env } from '@/core/config/env'
import { listBrands, listCategories, listProducts } from '@/features/catalog/api'
import type { ProductListResult } from '@/application/catalog/catalog-types'

const PRODUCTS_PER_PAGE = 100

function optionalLastModified(value?: string | null) {
  if (!value) return undefined
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? undefined : date
}

async function listAllProducts(): Promise<ProductListResult['items']> {
  const firstPage = await listProducts('', { page: 1, perPage: PRODUCTS_PER_PAGE })
  if (firstPage.totalPages <= 1) return firstPage.items

  const remainingPages = Array.from({ length: firstPage.totalPages - 1 }, (_, index) => index + 2)
  const pages = await Promise.all(
    remainingPages.map((page) => listProducts('', { page, perPage: PRODUCTS_PER_PAGE })),
  )

  return [firstPage, ...pages].flatMap((result) => result.items)
}

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const staticRoutes: MetadataRoute.Sitemap = [
    { url: env.siteUrl, changeFrequency: 'daily', priority: 1 },
    { url: `${env.siteUrl}/products`, changeFrequency: 'daily', priority: 0.9 },
  ]

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
    ]
  } catch {
    return staticRoutes
  }
}
