import type { Metadata } from 'next'
import { getPublishedContentBySlug } from '@/features/content/api'
import ContentDetailRoute from '@/features/content/components/ContentDetailRoute'
import { buildContentMetadata, buildContentNotFoundMetadata } from '@/features/content/metadata'

type ArticlePageProps = { params: Promise<{ slug: string }> }

export async function generateMetadata({ params }: ArticlePageProps): Promise<Metadata> {
  const { slug } = await params
  try {
    return buildContentMetadata(await getPublishedContentBySlug('article', slug))
  } catch {
    return buildContentNotFoundMetadata('article')
  }
}

export default async function ArticlePage({ params }: ArticlePageProps) {
  const { slug } = await params
  return <ContentDetailRoute type="article" slug={slug} />
}
