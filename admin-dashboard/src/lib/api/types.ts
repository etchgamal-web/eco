export type ApiOrderItem = {
  id?: number
  product_id?: number | null
  variant_id?: number | null
  name?: string | null
  sku?: string | null
  quantity?: number
  unit_price?: number
  discount_amount?: number
  tax_amount?: number
  total_amount?: number
  product?: { id?: number; name?: string | null } | null
}

export type ApiPaymentSummary = {
  id: number
  method?: string | null
  amount?: number
  currency?: string | null
  status?: string | null
  created_at?: string | null
}

export type ApiOrder = {
  id: number
  order_number?: string | null
  user_id?: number | null
  guest_email?: string | null
  guest_phone?: string | null
  status: string
  total_amount: number
  subtotal_amount?: number
  discount_amount?: number
  coupon_code?: string | null
  tax_amount?: number
  tax_rate?: number | string | null
  shipping_amount?: number
  shipping_cost?: number | null
  shipping_subsidy?: number | null
  currency?: string | null
  created_at?: string | null
  updated_at?: string | null
  user?: { id?: number; name?: string | null; email?: string | null; phone?: string | null } | null
  items?: ApiOrderItem[]
  shipping_address?: { recipient_name?: string; phone?: string; address_line1?: string; address_line2?: string | null; city?: string; state?: string | null; postal_code?: string | null; country?: string | null } | null
  payments?: ApiPaymentSummary[]
  shipments?: Array<{ id: number; provider_code?: string; tracking_number?: string | null; status?: string; creation_error?: string | null; created_at?: string | null; method?: { id?: number; code?: string; name?: string; carrier?: string; base_fee?: number; currency?: string } | null }>
  returns?: Array<{ id: number; status?: string; refund_amount?: number; actual_customer_refund?: number; refund_status?: string; restock_status?: string; reason?: string | null; notes?: string | null; rejection_reason?: string | null; completed_at?: string | null; created_at?: string | null }>
}
export type ApiProduct = { id: number; name: string; description?: string | null; type?: 'simple' | 'variable'; status?: string; price?: number; category?: { id?: number; name?: string } | null; brand?: { id?: number; name?: string } | null; variants?: Array<{ inventory?: { on_hand?: number; available?: number } | null }> }
export type ApiProductPage = { data: ApiProduct[]; current_page: number; last_page: number; per_page: number; total: number }
export type ApiInventory = { product_id: number; variant_id?: number | null; on_hand?: number; available?: number; reserved?: number; product?: ApiProduct | null; variant?: { sku?: string | null } | null; movements?: Array<{ id: number; quantity: number; on_hand_after: number; reason?: string; note?: string | null; created_at?: string; actor?: { name?: string | null } | null }> }
export type ApiCustomer = { id: number; name?: string | null; email?: string | null; phone?: string | null; status?: string | null; created_at?: string | null; orders_count?: number; total_spent?: number }
export type ApiOrderPage = { data: ApiOrder[]; current_page: number; last_page: number; per_page: number; total: number }
export type ApiShippingProvider = { id: number; code: string; name: string; metadata?: Record<string, unknown> | null }
