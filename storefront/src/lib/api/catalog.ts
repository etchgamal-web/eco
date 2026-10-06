export { catalogApi as default, catalogApi, catalogRepository } from '@/infrastructure/api/catalog-api'
export type { Product, ProductListOptions, ProductListResult } from '@/infrastructure/api/catalog-api'

import { catalogApi } from '@/infrastructure/api/catalog-api'

export const listProducts = catalogApi.listProducts
export const getProduct = catalogApi.getProduct
