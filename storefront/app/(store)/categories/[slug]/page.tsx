import type { Metadata } from 'next'
import TaxonomyPage from '@/features/catalog/components/TaxonomyPage'
import { getCategoryBySlug } from '@/features/catalog/api'

type CategoryPageProps = {
  params: Promise<{ slug: string }>
}

export async function generateMetadata({ params }: CategoryPageProps): Promise<Metadata> {
  const { slug } = await params
  try {
    const category = await getCategoryBySlug(slug)
    return {
      title: category.name,
      description: `تصفح منتجات تصنيف ${category.name} في متجر Eco.`,
      alternates: { canonical: `/categories/${category.slug ?? slug}` },
    }
  } catch {
    return { title: 'التصنيف غير متاح' }
  }
}

export default async function CategoryPage({ params }: CategoryPageProps) {
  const { slug } = await params
  return <TaxonomyPage kind="category" slug={slug} />
}
