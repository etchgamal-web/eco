import { ApiError, requestJson } from '@/core/http/client'
import type { Customer } from '@/domain/customer/customer'

type DataResponse<T> = { data: T }

export type CustomerOrder = {
  id: number
  order_number?: string
  status?: string
  total_amount?: number
  currency?: string
  created_at?: string
}

export type CustomerAddress = {
  id: number
  recipient_name?: string
  address_line1?: string
  city?: string
  country?: string
  is_default?: boolean
}

export async function getCustomerOrders(): Promise<CustomerOrder[]> {
  const response = await requestJson<DataResponse<CustomerOrder[]>>('/customer/orders', { cache: 'no-store' })
  return response.data
}

export async function getCustomerAddresses(): Promise<CustomerAddress[]> {
  const response = await requestJson<DataResponse<CustomerAddress[]>>('/customer/addresses', { cache: 'no-store' })
  return response.data
}

export type AddressInput = Omit<CustomerAddress, 'id'> & { phone: string; address_line1: string; country: string }

export async function createCustomerAddress(input: AddressInput): Promise<CustomerAddress> {
  const response = await requestJson<DataResponse<CustomerAddress>>('/customer/addresses', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(input) })
  return response.data
}

export async function deleteCustomerAddress(id: number): Promise<void> {
  await requestJson<unknown>(`/customer/addresses/${id}`, { method: 'DELETE' })
}

export async function getCustomerProfile(): Promise<Customer> {
  const response = await requestJson<DataResponse<Customer>>('/customer/profile', { cache: 'no-store' })
  return response.data
}

export async function updateCustomerProfile(input: Pick<Customer, 'name' | 'email' | 'phone'>): Promise<Customer> {
  const response = await requestJson<DataResponse<Customer>>('/customer/profile', { method: 'PATCH', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(input) })
  return response.data
}

export type CustomerOrderDetails = CustomerOrder & { items?: Array<{ id: number; quantity?: number; product_name?: string; unit_price?: number; total_amount?: number }>; payments?: Array<{ id: number; method?: string; amount?: number; currency?: string; status?: string; provider_reference?: string | null; checkout_url?: string | null }>; shipping_amount?: number; subtotal_amount?: number; payment_status?: string }

export async function getCustomerOrder(id: number): Promise<CustomerOrderDetails> {
  const response = await requestJson<DataResponse<CustomerOrderDetails>>(`/customer/orders/${id}`, { cache: 'no-store' })
  return response.data
}

export type WishlistItem = { id: number; product_id?: number; product?: { name?: string; slug?: string; price?: number; currency?: string } }
export type CustomerNotification = { id: number; type?: string; title?: string; body?: string; read_at?: string | null; created_at?: string }

export async function getWishlist(): Promise<WishlistItem[]> {
  const response = await requestJson<DataResponse<WishlistItem[]>>('/customer/wishlist', { cache: 'no-store' })
  return response.data
}

export async function removeFromWishlist(productId: number): Promise<void> {
  await requestJson<unknown>(`/customer/wishlist/${productId}`, { method: 'DELETE' })
}

export async function getNotifications(): Promise<CustomerNotification[]> {
  const response = await requestJson<DataResponse<CustomerNotification[]>>('/customer/notifications', { cache: 'no-store' })
  return response.data
}

export async function markNotificationRead(id: number): Promise<CustomerNotification> {
  const response = await requestJson<DataResponse<CustomerNotification>>(`/customer/notifications/${id}/read`, { method: 'PATCH' })
  return response.data
}

export type ShippingMethod = { id: number; code?: string; name?: string; carrier?: string; base_fee?: number; currency?: string }

export async function getShippingMethods(): Promise<ShippingMethod[]> {
  const response = await requestJson<DataResponse<ShippingMethod[]>>('/customer/shipping-methods', { cache: 'no-store' })
  return response.data
}

export type CheckoutInput = { address_id: number; shipping_method_id?: number; currency: string; payment_method: 'cash_on_delivery' | 'paymob' | 'kashier'; idempotency_key: string; payment_idempotency_key: string; coupon_code?: string; preview_token: string }

export type CheckoutPreview = {
  items?: Array<{ product_id?: number; variant_id?: number; name?: string; quantity?: number; unit_price?: number; total_amount?: number }>
  subtotal_amount: number
  discount_amount: number
  coupon_code?: string | null
  tax_amount: number
  tax_rate?: number | string
  shipping_amount: number
  total_amount: number
  currency: string
  preview_token: string
}

export class CheckoutPreviewStaleError extends Error {
  constructor(message: string, public readonly preview: CheckoutPreview) {
    super(message)
    this.name = 'CheckoutPreviewStaleError'
  }
}

export type CheckoutPreviewInput = Pick<CheckoutInput, 'address_id' | 'shipping_method_id' | 'currency' | 'coupon_code'>

export async function previewCheckout(input: CheckoutPreviewInput): Promise<CheckoutPreview> {
  const response = await requestJson<DataResponse<CheckoutPreview>>('/customer/checkout/preview', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(input) })
  return response.data
}

export async function checkout(input: CheckoutInput): Promise<CustomerOrderDetails> {
  try {
    const response = await requestJson<DataResponse<CustomerOrderDetails>>('/customer/checkout', { method: 'POST', headers: { 'Content-Type': 'application/json', 'Idempotency-Key': input.idempotency_key }, body: JSON.stringify(input) })
    return response.data
  } catch (reason) {
    if (reason instanceof ApiError && reason.status === 409) {
      const payload = reason.payload as { error_code?: string; data?: CheckoutPreview } | undefined
      if (payload?.error_code === 'checkout_preview_stale' && payload.data?.preview_token) {
        throw new CheckoutPreviewStaleError(reason.message, payload.data)
      }
    }
    throw reason
  }
}

export async function cancelCustomerOrder(id: number): Promise<CustomerOrderDetails> {
  const response = await requestJson<DataResponse<CustomerOrderDetails>>(`/customer/orders/${id}/cancel`, { method: 'POST' })
  return response.data
}
