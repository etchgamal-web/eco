export const CONTENT_PAGE_SIZE = 20
export const CONTENT_SITEMAP_PAGE_SIZE = 100
export const CONTENT_SITEMAP_BATCH_SIZE = 5

export function parseContentPage(value?: string | string[]): number {
  const raw = Array.isArray(value) ? value[0] : value
  const page = Number(raw)
  return Number.isInteger(page) && page > 0 ? page : 1
}
