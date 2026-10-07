import type { MetadataRoute } from 'next'
import { env } from '@/core/config/env'

export default function robots(): MetadataRoute.Robots {
  return {
    rules: [{
      userAgent: '*',
      allow: '/',
      disallow: ['/account/', '/cart', '/checkout', '/api/'],
    }],
    sitemap: `${env.siteUrl}/sitemap.xml`,
  }
}
