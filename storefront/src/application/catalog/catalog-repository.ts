import type { Product } from '@/domain/catalog/product'
import type { CatalogFilterOption, ProductListOptions, ProductListResult } from './catalog-types'

export interface CatalogRepository {
  listProducts(search?: string, options?: ProductListOptions): Promise<ProductListResult>
  getProduct(slugOrId: string): Promise<Product>
  listCategories(): Promise<CatalogFilterOption[]>
  listBrands(): Promise<CatalogFilterOption[]>
}
