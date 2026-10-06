import type { Cart, CartItem } from '@/domain/cart/cart'
import { requestJson } from '@/core/http/client'

type ServerCartItem = {
  product_id: number
  variant_id?: number | null
  quantity: number
  product?: { name?: string; slug?: string; price?: number; currency?: string }
  variant?: { id?: number; name?: string; sku?: string; price?: number }
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

export function serverCartToLocalCart(serverCart: ServerCart): Cart {
  return {
    items: serverCart.items.map((item) => ({
      productId: item.product_id,
      name: item.product?.name || 'منتج إيكو',
      slug: item.product?.slug,
      price: item.variant?.price ?? item.product?.price ?? 0,
      currency: item.product?.currency || serverCart.totals?.currency,
      quantity: item.quantity,
      variantId: item.variant_id ?? undefined,
      variantName: item.variant?.name || item.variant?.sku,
    })),
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

/** Merge policy: local quantities are added to remote quantities; Laravel remains authoritative. */
export async function mergeLocalCart(items: CartItem[]): Promise<ServerCart> {
  let serverCart = await getServerCart()
  for (const item of items) serverCart = await addServerCartItem(item)
  return serverCart
}
