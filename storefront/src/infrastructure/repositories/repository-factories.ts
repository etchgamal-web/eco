import { createGetProduct } from '@/application/catalog/get-product'
import { createListProducts } from '@/application/catalog/list-products'
import { createGetContent } from '@/application/content/get-content'
import { createListContent } from '@/application/content/list-content'
import { catalogRepository } from '@/infrastructure/api/catalog-api'
import { contentApi } from '@/infrastructure/api/content-api'

export const listProducts = createListProducts(catalogRepository)
export const getProduct = createGetProduct(catalogRepository)
export const listCategories = catalogRepository.listCategories
export const listBrands = catalogRepository.listBrands
export const getCategoryBySlug = catalogRepository.getCategoryBySlug
export const getBrandBySlug = catalogRepository.getBrandBySlug
export const listPublishedContent = createListContent(contentApi)
export const getPublishedContentBySlug = createGetContent(contentApi)
