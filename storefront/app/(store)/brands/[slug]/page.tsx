import type { Metadata } from 'next'
import TaxonomyPage from '@/features/catalog/components/TaxonomyPage'
import { getBrandBySlug } from '@/features/catalog/api'
import { env } from '@/core/config/env'
import { siteConfig } from '@/core/config/site'

type BrandPageProps = {
  params: Promise<{ slug: string }>
}

export async function generateMetadata({ params }: BrandPageProps): Promise<Metadata> {
  const { slug } = await params
  try {
    const brand = await getBrandBySlug(slug)
    return {
      title: brand.name,
      description: `تصفح منتجات علامة ${brand.name} في متجر ${siteConfig.name}.`,
      alternates: { canonical: `/brands/${brand.slug ?? slug}` },
      openGraph: {
        title: `${brand.name} | ${siteConfig.name}`,
        description: `تصفح منتجات علامة ${brand.name} في متجر ${siteConfig.name}.`,
        url: `${env.siteUrl}/brands/${brand.slug ?? slug}`,
        type: 'website',
      },
    }
  } catch {
    return { title: 'العلامة التجارية غير متاحة', robots: { index: false, follow: false } }
  }
}

export default async function BrandPage({ params }: BrandPageProps) {
  const { slug } = await params
  return <TaxonomyPage kind="brand" slug={slug} />
}
