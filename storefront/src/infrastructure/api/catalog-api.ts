import { requestJson } from '@/core/http/client'
import type { Product } from '@/domain/catalog/product'
import type { CatalogFilterOption, ProductListOptions, ProductListResult } from '@/application/catalog/catalog-types'

export type { Product } from '@/domain/catalog/product'
export type { CatalogFilterOption, ProductListOptions, ProductListResult } from '@/application/catalog/catalog-types'

type ProductResponse = {
  data: Product[] | { items: Product[]; meta?: { total?: number; current_page?: number; last_page?: number; per_page?: number } }
}

type SingleProductResponse = {
  data: Product
}

async function listFilterOptions(path: 'categories' | 'brands'): Promise<CatalogFilterOption[]> {
  const payload = await requestJson<{ data: CatalogFilterOption[] }>(`/${path}`, { cache: 'no-store' })
  return payload.data
}

export const catalogApi = {
  async listProducts(search = '', options: ProductListOptions = {}): Promise<ProductListResult> {
    const params = new URLSearchParams()
    if (search.trim()) params.set('search', search.trim())
    if (options.page && options.page > 1) params.set('page', String(options.page))
    if (options.perPage) params.set('per_page', String(options.perPage))
    if (options.categoryId) params.set('category_id', String(options.categoryId))
    if (options.brandId) params.set('brand_id', String(options.brandId))
    if (options.sort && options.sort !== 'newest') params.set('sort', options.sort)
    const query = params.toString()
    const payload = await requestJson<ProductResponse>(`/products${query ? `?${query}` : ''}`, { cache: 'no-store' })
    if (Array.isArray(payload.data)) return { items: payload.data, page: 1, totalPages: 1 }
    const meta = payload.data.meta
    return { items: payload.data.items ?? [], total: meta?.total, page: meta?.current_page ?? options.page ?? 1, totalPages: meta?.last_page ?? 1 }
  },

  async getProduct(slugOrId: string): Promise<Product> {
    const payload = await requestJson<SingleProductResponse>(`/products/${encodeURIComponent(slugOrId)}`, { cache: 'no-store' })
    return payload.data
  },

  listCategories: () => listFilterOptions('categories'),
  listBrands: () => listFilterOptions('brands'),
  async getCategoryBySlug(slug: string): Promise<CatalogFilterOption> {
    const payload = await requestJson<{ data: CatalogFilterOption }>(`/categories/${encodeURIComponent(slug)}`, { cache: 'no-store' })
    return payload.data
  },
  async getBrandBySlug(slug: string): Promise<CatalogFilterOption> {
    const payload = await requestJson<{ data: CatalogFilterOption }>(`/brands/${encodeURIComponent(slug)}`, { cache: 'no-store' })
    return payload.data
  },
}

export const catalogRepository = catalogApi
