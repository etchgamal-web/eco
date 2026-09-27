import { request } from './client'
import type { ApiCustomer } from './types'

export async function listCustomers(params: { search?: string; page?: number; per_page?: number } = {}) { const query = new URLSearchParams(); Object.entries(params).forEach(([key, value]) => { if (value !== undefined && value !== '') query.set(key, String(value)) }); return request<{ data: ApiCustomer[]; meta: { current_page: number; last_page: number; per_page: number; total: number } }>(`/admin/customers?${query.toString()}`) }

export async function getCustomer(id: number) { return (await request<{ data: { customer: ApiCustomer; orders: Array<{ id: number; order_number?: string | null; status: string; total_amount: number; currency?: string | null; created_at?: string | null }> } }>(`/admin/customers/${id}`)).data }
