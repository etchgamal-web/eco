import { requestJson } from '@/core/http/client'
import type { Customer } from '@/domain/customer/customer'

type AuthResponse = {
  data: Customer
  token: string
  token_type: string
}

type MeResponse = { data: Customer }

export async function login(identifier: string, password: string, remember = false): Promise<AuthResponse> {
  return requestJson<AuthResponse>('/auth/login', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ identifier, password, remember }) })
}

export async function getCurrentCustomer(): Promise<Customer> {
  const response = await requestJson<MeResponse>('/auth/me', { cache: 'no-store' })
  return response.data
}

export async function logout(): Promise<void> {
  await requestJson<{ message?: string }>('/auth/logout', { method: 'POST' })
}
