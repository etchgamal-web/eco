import AnnouncementBar from '@/shared/layout/AnnouncementBar'
import SiteHeader from '@/shared/layout/SiteHeader'
import SiteFooter from '@/shared/layout/SiteFooter'
import ProductListing from '@/features/catalog/components/ProductListing'
import { listBrands, listCategories, listProducts } from '@/features/catalog/api'
import type { CatalogFilterOption, ProductListResult } from '@/application/catalog/catalog-types'
import type { Metadata } from 'next'
import { env } from '@/core/config/env'
import { siteConfig } from '@/core/config/site'

type SearchParams = Record<string, string | string[] | undefined>

type ProductsPageProps = {
  searchParams?: Promise<SearchParams>
}

type SortValue = 'newest' | 'price_asc' | 'price_desc' | 'name_asc'

export async function generateMetadata({ searchParams }: ProductsPageProps): Promise<Metadata> {
  const params = searchParams ? await searchParams : {}
  const hasFilters = ['search', 'category_id', 'brand_id', 'page', 'sort'].some((key) => Boolean(firstValue(params[key])))
  return {
    title: 'المنتجات',
    description: `تصفح منتجات ${siteConfig.name} المختارة بعناية، وابحث عن القطعة المناسبة لبيتك ويومك.`,
    alternates: { canonical: `${env.siteUrl}/products` },
    robots: hasFilters ? { index: false, follow: true } : { index: true, follow: true },
    openGraph: {
      title: `المنتجات | ${siteConfig.name}`,
      description: siteConfig.description,
      url: `${env.siteUrl}/products`,
      type: 'website',
    },
  }
}

const sortValues: SortValue[] = ['newest', 'price_asc', 'price_desc', 'name_asc']
const PER_PAGE = 12

function firstValue(value: string | string[] | undefined): string | undefined {
  return Array.isArray(value) ? value[0] : value
}

function positiveNumber(value: string | undefined, fallback?: number): number | undefined {
  const parsed = Number(value)
  return Number.isInteger(parsed) && parsed > 0 ? parsed : fallback
}

function sortValue(value: string | undefined): SortValue {
  return sortValues.includes(value as SortValue) ? value as SortValue : 'newest'
}

const emptyResult: ProductListResult = { items: [], page: 1, totalPages: 1 }

export default async function ProductsPage({ searchParams }: ProductsPageProps) {
  const params = searchParams ? await searchParams : {}
  const search = firstValue(params.search)?.trim() ?? ''
  const categoryId = positiveNumber(firstValue(params.category_id))
  const brandId = positiveNumber(firstValue(params.brand_id))
  const page = positiveNumber(firstValue(params.page), 1) ?? 1
  const sort = sortValue(firstValue(params.sort))
  const options = { page, perPage: PER_PAGE, categoryId, brandId, sort }

  const [productsResult, categoriesResult, brandsResult] = await Promise.allSettled([
    listProducts(search, options),
    listCategories(),
    listBrands(),
  ])

  const initialProducts = productsResult.status === 'fulfilled' ? productsResult.value : emptyResult
  const initialError = productsResult.status === 'rejected' ? 'تعذر تحميل المنتجات من الخادم' : ''
  const categories: CatalogFilterOption[] = categoriesResult.status === 'fulfilled' ? categoriesResult.value : []
  const brands: CatalogFilterOption[] = brandsResult.status === 'fulfilled' ? brandsResult.value : []

  return <main>
    <AnnouncementBar />
    <SiteHeader />
    <ProductListing
      initialProducts={initialProducts}
      initialCategories={categories}
      initialBrands={brands}
      initialSearch={search}
      initialCategoryId={categoryId}
      initialBrandId={brandId}
      initialSort={sort}
      initialError={initialError}
    />
    <SiteFooter />
  </main>
}
