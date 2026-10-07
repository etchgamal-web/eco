import type { Metadata } from 'next'
import TaxonomyPage from '@/features/catalog/components/TaxonomyPage'
import { getCategoryBySlug } from '@/features/catalog/api'
import { env } from '@/core/config/env'
import { siteConfig } from '@/core/config/site'

type CategoryPageProps = {
  params: Promise<{ slug: string }>
}

export async function generateMetadata({ params }: CategoryPageProps): Promise<Metadata> {
  const { slug } = await params
  try {
    const category = await getCategoryBySlug(slug)
    return {
      title: category.name,
      description: `تصفح منتجات تصنيف ${category.name} في متجر ${siteConfig.name}.`,
      alternates: { canonical: `/categories/${category.slug ?? slug}` },
      openGraph: {
        title: `${category.name} | ${siteConfig.name}`,
        description: `تصفح منتجات تصنيف ${category.name} في متجر ${siteConfig.name}.`,
        url: `${env.siteUrl}/categories/${category.slug ?? slug}`,
        type: 'website',
      },
    }
  } catch {
    return { title: 'التصنيف غير متاح', robots: { index: false, follow: false } }
  }
}

export default async function CategoryPage({ params }: CategoryPageProps) {
  const { slug } = await params
  return <TaxonomyPage kind="category" slug={slug} />
}
