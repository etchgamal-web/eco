import type { CatalogRepository } from './catalog-repository'

export function createGetProduct(repository: CatalogRepository) {
  return (slugOrId: string) => repository.getProduct(slugOrId)
}
