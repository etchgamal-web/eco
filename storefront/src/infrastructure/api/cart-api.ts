import { requestJson } from '@/core/http/client'
import type { CartItem } from '@/domain/cart/cart'

type ServerCartItem = {
  product_id: number
  variant_id?: number | null
  quantity: number
  product?: { name?: string; slug?: string; price?: number; currency?: string }
  variant?: { name?: string; sku?: string; price?: number }
}

export type ServerCart = {
  items: ServerCartItem[]
  totals?: { subtotal?: number; total?: number; currency?: string }
}

type CartResponse = { data: ServerCart }

function itemPayload(item: Pick<CartItem, 'productId' | 'variantId' | 'quantity'>) {
  return {
    product_id: item.productId,
    ...(item.variantId ? { variant_id: item.variantId } : {}),
    quantity: item.quantity,
  }
}

export async function getServerCart(): Promise<ServerCart> {
  const response = await requestJson<CartResponse>('/customer/cart', { cache: 'no-store' })
  return response.data
}

export async function addServerCartItem(item: CartItem): Promise<ServerCart> {
  const response = await requestJson<CartResponse>('/customer/cart/items', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(itemPayload(item)) })
  return response.data
}

export async function updateServerCartItem(item: CartItem): Promise<ServerCart> {
  const response = await requestJson<CartResponse>('/customer/cart/items', { method: 'PATCH', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(itemPayload(item)) })
  return response.data
}

export async function removeServerCartItem(productId: number, variantId?: number): Promise<ServerCart> {
  const suffix = variantId ? `/${productId}/${variantId}` : `/${productId}`
  const response = await requestJson<CartResponse>(`/customer/cart/items${suffix}`, { method: 'DELETE' })
  return response.data
}

export async function clearServerCart(): Promise<ServerCart> {
  const response = await requestJson<CartResponse>('/customer/cart', { method: 'DELETE' })
  return response.data
}

/** Merge guest items into the authenticated Laravel cart. Laravel remains the source of truth after success. */
export async function mergeLocalCart(items: CartItem[]): Promise<ServerCart> {
  let serverCart = await getServerCart()
  for (const item of items) serverCart = await addServerCartItem(item)
  return serverCart
}
