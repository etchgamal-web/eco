import type { Metadata } from 'next'
import { env } from '@/core/config/env'
import { siteConfig } from '@/core/config/site'
import ContentListing from '@/features/content/components/ContentListing'
import { parseContentPage } from '@/features/content/pagination'

type GuidesPageProps = {
  searchParams?: Promise<Record<string, string | string[] | undefined>>
}

export async function generateMetadata({ searchParams }: GuidesPageProps): Promise<Metadata> {
  const params = searchParams ? await searchParams : {}
  const page = parseContentPage(params.page)
  const canonical = `${env.siteUrl}/guides${page > 1 ? `?page=${page}` : ''}`
  const title = `الأدلة${page > 1 ? ` - صفحة ${page}` : ''}`
  const description = `أدلة ونصائح واضحة من ${siteConfig.name} لاختيار المنتجات والعناية بها.`

  return {
    title,
    description,
    alternates: { canonical },
    openGraph: {
      title: `${title} | ${siteConfig.name}`,
      description: `تصفّح أدلة ${siteConfig.name} العملية.`,
      url: canonical,
      type: 'website',
    },
  }
}

export default async function GuidesPage({ searchParams }: GuidesPageProps) {
  const params = searchParams ? await searchParams : {}
  return <ContentListing type="guide" page={parseContentPage(params.page)} />
}
