import type { CartItem } from '@/domain/cart/cart'
import { mergeLocalCart } from '@/infrastructure/api/cart-api'

export async function syncCartAfterAuthentication(items: CartItem[]) {
  if (items.length === 0) return null
  return mergeLocalCart(items)
}
