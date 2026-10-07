export type ProductMedia = {
  id?: number
  url: string
  alt?: string | null
}

export type ProductVariant = {
  id: number
  name?: string | null
  sku?: string | null
  price?: number | null
  stock?: number | null
  options?: Record<string, string>
}

export type Product = {
  id: number
  name: string
  slug?: string
  description?: string | null
  type?: string | null
  status?: string | null
  updated_at?: string | null
  price: number
  currency?: string
  brand?: { id: number; name: string } | null
  category?: { id: number; name: string } | null
  media?: ProductMedia[]
  images?: ProductMedia[]
  variants?: ProductVariant[]
}
