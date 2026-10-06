import type { CartItem } from '@/domain/cart/cart'
import { mergeLocalCart, serverCartToLocalCart } from '@/infrastructure/api/cart-api'

export async function syncCartAfterAuthentication(items: CartItem[]) {
  if (items.length === 0) return { items: [] }
  return serverCartToLocalCart(await mergeLocalCart(items))
}
