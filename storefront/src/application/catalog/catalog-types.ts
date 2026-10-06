import type { Product } from '@/domain/catalog/product'

export type CatalogFilterOption = {
  id: number
  name: string
  slug?: string | null
  products_count?: number
}

export type ProductListOptions = {
  page?: number
  perPage?: number
  categoryId?: number
  brandId?: number
  sort?: 'newest' | 'price_asc' | 'price_desc' | 'name_asc'
}

export type ProductListResult = {
  items: Product[]
  total?: number
  page: number
  totalPages: number
}
