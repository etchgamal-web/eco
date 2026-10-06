import type { MetadataRoute } from 'next'
import { env } from '@/core/config/env'
import { listBrands, listCategories, listProducts } from '@/features/catalog/api'

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const now = new Date()
  const staticRoutes: MetadataRoute.Sitemap = [
    { url: env.siteUrl, lastModified: now, changeFrequency: 'daily', priority: 1 },
    { url: `${env.siteUrl}/products`, lastModified: now, changeFrequency: 'daily', priority: 0.9 },
  ]

  try {
    const [products, categories, brands] = await Promise.all([
      listProducts('', { perPage: 100 }),
      listCategories(),
      listBrands(),
    ])

    return [
      ...staticRoutes,
      ...products.items.map((product) => ({
        url: `${env.siteUrl}/products/${product.slug ?? product.id}`,
        lastModified: now,
        changeFrequency: 'daily' as const,
        priority: 0.8,
      })),
      ...categories.filter((item) => item.slug).map((item) => ({
        url: `${env.siteUrl}/categories/${item.slug}`,
        lastModified: now,
        changeFrequency: 'weekly' as const,
        priority: 0.7,
      })),
      ...brands.filter((item) => item.slug).map((item) => ({
        url: `${env.siteUrl}/brands/${item.slug}`,
        lastModified: now,
        changeFrequency: 'weekly' as const,
        priority: 0.7,
      })),
    ]
  } catch {
    return staticRoutes
  }
}
