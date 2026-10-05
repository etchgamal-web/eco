'use client'

import { FormEvent, useEffect, useMemo, useState } from 'react'
import type { Product } from '@/domain/catalog/product'
import { listProducts } from '@/src/lib/api/catalog'
import ProductGrid from './ProductGrid'
import Pagination from '@/shared/components/Pagination'

const PER_PAGE = 12

type SortValue = 'newest' | 'price_asc' | 'price_desc' | 'name_asc'

export default function ProductListing() {
  const [products, setProducts] = useState<Product[]>([])
  const [search, setSearch] = useState('')
  const [categoryId, setCategoryId] = useState<number | undefined>()
  const [brandId, setBrandId] = useState<number | undefined>()
  const [sort, setSort] = useState<SortValue>('newest')
  const [page, setPage] = useState(1)
  const [totalPages, setTotalPages] = useState(1)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  const loadProducts = async (term = search, requestedPage = page, nextCategoryId = categoryId, nextBrandId = brandId, nextSort = sort) => {
    setLoading(true)
    setError('')
    try {
      const result = await listProducts(term, { page: requestedPage, perPage: PER_PAGE, categoryId: nextCategoryId, brandId: nextBrandId, sort: nextSort })
      setProducts(result.items)
      setPage(result.page)
      setTotalPages(result.totalPages)
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : 'تعذر الاتصال بالمتجر')
    } finally {
      setLoading(false)
    }
  }

  // eslint-disable-next-line react-hooks/set-state-in-effect, react-hooks/exhaustive-deps
  useEffect(() => { void loadProducts('', 1) }, [])

  const categories = useMemo(() => {
    const values = new Map<number, string>()
    products.forEach((product) => { if (product.category) values.set(product.category.id, product.category.name) })
    return [...values.entries()]
  }, [products])

  const brands = useMemo(() => {
    const values = new Map<number, string>()
    products.forEach((product) => { if (product.brand) values.set(product.brand.id, product.brand.name) })
    return [...values.entries()]
  }, [products])

  const submitSearch = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    void loadProducts(search, 1)
  }

  const applyFilter = (nextCategoryId = categoryId, nextBrandId = brandId, nextSort = sort) => {
    setCategoryId(nextCategoryId)
    setBrandId(nextBrandId)
    setSort(nextSort)
    void loadProducts(search, 1, nextCategoryId, nextBrandId, nextSort)
  }

  const changePage = (nextPage: number) => {
    void loadProducts(search, nextPage)
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }

  const resetFilters = () => {
    setCategoryId(undefined)
    setBrandId(undefined)
    setSort('newest')
    void loadProducts(search, 1, undefined, undefined, 'newest')
  }

  return (
    <section className="products-section catalog-page-section">
      <div className="section-heading">
        <div><p className="kicker">كل الاختيارات</p><h1>منتجات إيكو</h1></div>
        <form className="search-form" onSubmit={submitSearch}>
          <input value={search} onChange={(event) => setSearch(event.target.value)} placeholder="ابحث عن منتج..." aria-label="ابحث عن منتج" />
          <button type="submit">بحث</button>
        </form>
      </div>
      <div className="catalog-filters" aria-label="فلاتر المنتجات">
        <select value={categoryId ?? ''} onChange={(event) => applyFilter(event.target.value ? Number(event.target.value) : undefined, brandId)} aria-label="التصنيف">
          <option value="">كل التصنيفات</option>
          {categories.map(([id, name]) => <option key={id} value={id}>{name}</option>)}
        </select>
        <select value={brandId ?? ''} onChange={(event) => applyFilter(categoryId, event.target.value ? Number(event.target.value) : undefined)} aria-label="العلامة التجارية">
          <option value="">كل العلامات</option>
          {brands.map(([id, name]) => <option key={id} value={id}>{name}</option>)}
        </select>
        <select value={sort} onChange={(event) => applyFilter(categoryId, brandId, event.target.value as SortValue)} aria-label="ترتيب المنتجات">
          <option value="newest">الأحدث</option><option value="price_asc">السعر: الأقل أولًا</option><option value="price_desc">السعر: الأعلى أولًا</option><option value="name_asc">الاسم: أ - ي</option>
        </select>
        {categoryId || brandId || sort !== 'newest' ? <button className="clear-filters" type="button" onClick={resetFilters}>مسح الفلاتر</button> : null}
      </div>
      <ProductGrid products={products} loading={loading} error={error} searchTerm={search} onRetry={() => void loadProducts(search, page)} />
      {!loading && !error ? <Pagination page={page} totalPages={totalPages} onChange={changePage} /> : null}
    </section>
  )
}
