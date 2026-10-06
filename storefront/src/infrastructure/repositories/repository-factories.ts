import { createGetProduct } from '@/application/catalog/get-product'
import { createListProducts } from '@/application/catalog/list-products'
import { catalogRepository } from '@/infrastructure/api/catalog-api'

export const listProducts = createListProducts(catalogRepository)
export const getProduct = createGetProduct(catalogRepository)
