export type Product = {
  id: number
  name: string
  slug?: string
  description?: string | null
  type?: string | null
  status?: string | null
  price: number
  currency?: string
  brand?: { id: number; name: string } | null
  category?: { id: number; name: string } | null
}

type ProductResponse = {
  data: Product[] | { items: Product[]; meta?: { total?: number } }
}

const apiUrl = (process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api/v1').replace(/\/$/, '')

export async function listProducts(search = ''): Promise<{ items: Product[]; total?: number }> {
  const url = new URL(`${apiUrl}/products`)
  if (search.trim()) url.searchParams.set('search', search.trim())
  const response = await fetch(url, { headers: { Accept: 'application/json' }, cache: 'no-store' })
  if (!response.ok) throw new Error('تعذر تحميل المنتجات')
  const payload = (await response.json()) as ProductResponse
  if (Array.isArray(payload.data)) return { items: payload.data }
  return { items: payload.data.items ?? [], total: payload.data.meta?.total }
}
