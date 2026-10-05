export type CartItem = {
  productId: number
  name: string
  slug?: string
  price: number
  currency?: string
  quantity: number
  variantId?: number
  variantName?: string
}

export type Cart = {
  items: CartItem[]
}

export function cartItemCount(cart: Cart): number {
  return cart.items.reduce((total, item) => total + item.quantity, 0)
}
