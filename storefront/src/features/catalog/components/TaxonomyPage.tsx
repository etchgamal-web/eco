import Link from 'next/link'
import { notFound } from 'next/navigation'
import { getBrandBySlug, getCategoryBySlug, listProducts } from '@/features/catalog/api'
import AnnouncementBar from '@/shared/layout/AnnouncementBar'
import SiteHeader from '@/shared/layout/SiteHeader'
import SiteFooter from '@/shared/layout/SiteFooter'
import ProductGrid from './ProductGrid'
import BreadcrumbStructuredData from '@/shared/seo/BreadcrumbStructuredData'

type TaxonomyPageProps = {
  kind: 'category' | 'brand'
  slug: string
}

export default async function TaxonomyPage({ kind, slug }: TaxonomyPageProps) {
  let option
  try {
    option = kind === 'category' ? await getCategoryBySlug(slug) : await getBrandBySlug(slug)
  } catch {
    notFound()
  }

  const title = kind === 'category' ? 'تصنيف' : 'علامة تجارية'
  const products = await listProducts('', kind === 'category' ? { categoryId: option.id } : { brandId: option.id })

  return (
    <main>
      <AnnouncementBar />
      <SiteHeader />
      <section className="products-section catalog-page-section taxonomy-page">
        <nav className="breadcrumb" aria-label="مسار التنقل">
          <Link href="/">الرئيسية</Link><span>/</span><Link href="/products">المنتجات</Link><span>/</span><span>{option.name}</span>
        </nav>
        <div className="section-heading">
          <div><p className="kicker">{title}</p><h1>{option.name}</h1></div>
          <p className="taxonomy-count">{option.products_count ?? products.items.length} منتج</p>
        </div>
        <ProductGrid products={products.items} />
        <BreadcrumbStructuredData items={[{ name: 'الرئيسية', path: '/' }, { name: 'المنتجات', path: '/products' }, { name: option.name, path: `/${kind === 'category' ? 'categories' : 'brands'}/${slug}` }]} />
      </section>
      <SiteFooter />
    </main>
  )
}
