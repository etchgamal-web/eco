import { requestJson } from '@/core/http/client'
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

export type CustomerOrderDetails = CustomerOrder & { items?: Array<{ id: number; quantity?: number; product_name?: string; unit_price?: number; total_amount?: number }>; shipping_amount?: number; subtotal_amount?: number; payment_status?: string }

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

export type CheckoutInput = { address_id: number; currency: string; payment_method: 'cash_on_delivery' | 'paymob' | 'kashier'; idempotency_key: string; payment_idempotency_key: string; coupon_code?: string }

export async function checkout(input: CheckoutInput): Promise<CustomerOrderDetails> {
  const response = await requestJson<DataResponse<CustomerOrderDetails>>('/customer/checkout', { method: 'POST', headers: { 'Content-Type': 'application/json', 'Idempotency-Key': input.idempotency_key }, body: JSON.stringify(input) })
  return response.data
}

export async function cancelCustomerOrder(id: number): Promise<CustomerOrderDetails> {
  const response = await requestJson<DataResponse<CustomerOrderDetails>>(`/customer/orders/${id}/cancel`, { method: 'POST' })
  return response.data
}
