export { catalogApi as default, catalogApi, catalogRepository } from '@/infrastructure/api/catalog-api'
export type { Product } from '@/domain/catalog/product'
export type { CatalogFilterOption, ProductListOptions, ProductListResult } from '@/application/catalog/catalog-types'

import { catalogApi } from '@/infrastructure/api/catalog-api'

export const listProducts = catalogApi.listProducts
export const getProduct = catalogApi.getProduct
export const listCategories = catalogApi.listCategories
export const listBrands = catalogApi.listBrands
