import type { Metadata } from 'next'
import TaxonomyPage from '@/features/catalog/components/TaxonomyPage'
import { getBrandBySlug } from '@/features/catalog/api'

type BrandPageProps = {
  params: Promise<{ slug: string }>
}

export async function generateMetadata({ params }: BrandPageProps): Promise<Metadata> {
  const { slug } = await params
  try {
    const brand = await getBrandBySlug(slug)
    return {
      title: brand.name,
      description: `تصفح منتجات علامة ${brand.name} في متجر Eco.`,
      alternates: { canonical: `/brands/${brand.slug ?? slug}` },
    }
  } catch {
    return { title: 'العلامة التجارية غير متاحة' }
  }
}

export default async function BrandPage({ params }: BrandPageProps) {
  const { slug } = await params
  return <TaxonomyPage kind="brand" slug={slug} />
}
