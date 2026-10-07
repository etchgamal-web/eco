import type { Metadata } from 'next'
import { getPublishedContentBySlug } from '@/features/content/api'
import ContentDetailRoute from '@/features/content/components/ContentDetailRoute'
import { buildContentMetadata, buildContentNotFoundMetadata } from '@/features/content/metadata'

type GuidePageProps = { params: Promise<{ slug: string }> }

export async function generateMetadata({ params }: GuidePageProps): Promise<Metadata> {
  const { slug } = await params
  try {
    return buildContentMetadata(await getPublishedContentBySlug('guide', slug))
  } catch {
    return buildContentNotFoundMetadata('guide')
  }
}

export default async function GuidePage({ params }: GuidePageProps) {
  const { slug } = await params
  return <ContentDetailRoute type="guide" slug={slug} />
}
