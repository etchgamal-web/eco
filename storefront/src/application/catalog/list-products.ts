import type { CatalogRepository } from './catalog-repository'

export function createListProducts(repository: CatalogRepository) {
  return (search = '', options = {}) => repository.listProducts(search, options)
}
