import { requestJson } from '@/core/http/client'

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
