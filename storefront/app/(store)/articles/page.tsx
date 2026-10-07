import type { Metadata } from 'next'
import { env } from '@/core/config/env'
import { siteConfig } from '@/core/config/site'
import ContentListing from '@/features/content/components/ContentListing'
import { parseContentPage } from '@/features/content/pagination'

type ArticlesPageProps = {
  searchParams?: Promise<Record<string, string | string[] | undefined>>
}

export async function generateMetadata({ searchParams }: ArticlesPageProps): Promise<Metadata> {
  const params = searchParams ? await searchParams : {}
  const page = parseContentPage(params.page)
  const canonical = `${env.siteUrl}/articles${page > 1 ? `?page=${page}` : ''}`
  const title = `المقالات${page > 1 ? ` - صفحة ${page}` : ''}`
  const description = `أفكار ومعلومات عملية من ${siteConfig.name} تساعدك على اختيار ما يناسب حياتك اليومية.`

  return {
    title,
    description,
    alternates: { canonical },
    openGraph: {
      title: `${title} | ${siteConfig.name}`,
      description: `اقرأ مقالات ${siteConfig.name} ومعلوماتها العملية.`,
      url: canonical,
      type: 'website',
    },
  }
}

export default async function ArticlesPage({ searchParams }: ArticlesPageProps) {
  const params = searchParams ? await searchParams : {}
  return <ContentListing type="article" page={parseContentPage(params.page)} />
}
