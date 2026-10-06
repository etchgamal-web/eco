export { catalogApi as default, catalogApi, catalogRepository } from '@/infrastructure/api/catalog-api'
export type { CatalogFilterOption, Product, ProductListOptions, ProductListResult } from '@/infrastructure/api/catalog-api'

import { catalogApi } from '@/infrastructure/api/catalog-api'

export const listProducts = catalogApi.listProducts
export const getProduct = catalogApi.getProduct
export const listCategories = catalogApi.listCategories
export const listBrands = catalogApi.listBrands
