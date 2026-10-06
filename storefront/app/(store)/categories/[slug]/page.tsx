import TaxonomyPage from '@/features/catalog/components/TaxonomyPage'

type CategoryPageProps = {
  params: Promise<{ slug: string }>
}

export default async function CategoryPage({ params }: CategoryPageProps) {
  const { slug } = await params
  return <TaxonomyPage kind="category" slug={slug} />
}
