import { requestJson } from '@/core/http/client'

export async function addToWishlist(productId: number): Promise<void> {
  await requestJson<unknown>('/customer/wishlist', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ product_id: productId }),
  })
}
