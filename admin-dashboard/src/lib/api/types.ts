export type ApiOrder = {
  id: number
  order_number?: string | null
  status: string
  total_amount: number
  currency?: string | null
  created_at?: string | null
  user?: { name?: string | null; email?: string | null } | null
  items?: Array<{ name?: string | null; quantity?: number; total_amount?: number; product?: { name?: string | null } | null }>
  shipping_address?: { recipient_name?: string; city?: string; address_line1?: string } | null
  payments?: Array<{ id: number; method?: string | null; amount?: number; currency?: string | null; status?: string | null }>
  shipments?: Array<{ id: number; provider_code?: string; tracking_number?: string | null; status?: string; created_at?: string | null }>
}
export type ApiProduct = { id: number; name: string; type?: 'simple' | 'variable'; status?: string; price?: number; category?: { id?: number; name?: string } | null; brand?: { id?: number; name?: string } | null; variants?: Array<{ inventory?: { on_hand?: number; available?: number } | null }> }
export type ApiProductPage = { data: ApiProduct[]; current_page: number; last_page: number; per_page: number; total: number }
export type ApiInventory = { product_id: number; variant_id?: number | null; on_hand?: number; available?: number; reserved?: number; product?: ApiProduct | null; variant?: { sku?: string | null } | null; movements?: Array<{ id: number; quantity: number; on_hand_after: number; reason?: string; note?: string | null; created_at?: string; actor?: { name?: string | null } | null }> }
export type ApiCustomer = { id: number; name?: string | null; email?: string | null; phone?: string | null; status?: string | null; created_at?: string | null; orders_count?: number; total_spent?: number }
export type ApiOrderPage = { data: ApiOrder[]; current_page: number; last_page: number; per_page: number; total: number }
export type ApiShippingProvider = { id: number; code: string; name: string; metadata?: Record<string, unknown> | null }
