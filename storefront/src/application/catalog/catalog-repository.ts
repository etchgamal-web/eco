import type { Product, ProductListOptions, ProductListResult } from '@/infrastructure/api/catalog-api'

export interface CatalogRepository {
  listProducts(search?: string, options?: ProductListOptions): Promise<ProductListResult>
  getProduct(slugOrId: string): Promise<Product>
}
