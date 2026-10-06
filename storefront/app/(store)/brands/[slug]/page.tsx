import TaxonomyPage from '@/features/catalog/components/TaxonomyPage'

type BrandPageProps = {
  params: Promise<{ slug: string }>
}

export default async function BrandPage({ params }: BrandPageProps) {
  const { slug } = await params
  return <TaxonomyPage kind="brand" slug={slug} />
}
