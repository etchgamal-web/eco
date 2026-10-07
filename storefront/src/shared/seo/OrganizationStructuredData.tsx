import { env } from '@/core/config/env'
import { siteConfig } from '@/core/config/site'

export default function OrganizationStructuredData() {
  const data = {
    '@context': 'https://schema.org',
    '@type': 'Organization',
    '@id': `${env.siteUrl}/#organization`,
    name: siteConfig.name,
    url: env.siteUrl,
    description: siteConfig.description,
  }

  return <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(data) }} />
}
