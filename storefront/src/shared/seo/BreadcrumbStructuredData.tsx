import { env } from '@/core/config/env'

type BreadcrumbItem = {
  name: string
  path: string
}

type BreadcrumbStructuredDataProps = {
  items: BreadcrumbItem[]
}

export default function BreadcrumbStructuredData({ items }: BreadcrumbStructuredDataProps) {
  const data = {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: items.map((item, index) => ({
      '@type': 'ListItem',
      position: index + 1,
      name: item.name,
      item: new URL(item.path, env.siteUrl).toString(),
    })),
  }

  return <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(data) }} />
}
