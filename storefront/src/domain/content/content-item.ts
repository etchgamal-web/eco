export type ContentType = 'article' | 'guide'

export type ContentAuthor = {
  id?: number
  name?: string | null
}

export type ContentLink = {
  id: number
  name?: string | null
  slug?: string | null
  brand?: { name?: string | null; slug?: string | null } | null
  category?: { name?: string | null; slug?: string | null } | null
}

export type ContentItem = {
  id: number
  type: ContentType
  title: string
  slug: string
  excerpt?: string | null
  body?: string | null
  status: 'published'
  published_at?: string | null
  updated_at?: string | null
  seo_title?: string | null
  seo_description?: string | null
  canonical_url?: string | null
  featured_image?: string | null
  author?: ContentAuthor | null
  products?: ContentLink[]
  categories?: ContentLink[]
}
