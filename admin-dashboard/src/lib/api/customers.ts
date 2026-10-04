import { API_BASE, getToken, request } from './client'
import type { ApiCustomer } from './types'

export type CustomerAddress = { id: number; label?: string; recipient_name: string; phone: string; address_line1: string; address_line2?: string | null; city: string; state?: string | null; postal_code?: string | null; country?: string; is_default?: boolean }
export type CustomerDetail = { customer: ApiCustomer; orders: Array<{ id: number; order_number?: string | null; status: string; total_amount: number; currency?: string | null; created_at?: string | null }>; addresses: CustomerAddress[]; audit_logs: Array<{ id: number; action: string; created_at?: string }> }

export async function listCustomers(params: { search?: string; page?: number; per_page?: number } = {}) { const query = new URLSearchParams(); Object.entries(params).forEach(([key, value]) => { if (value !== undefined && value !== '') query.set(key, String(value)) }); return request<{ data: ApiCustomer[]; meta: { current_page: number; last_page: number; per_page: number; total: number } }>(`/admin/customers?${query.toString()}`) }
export async function downloadCustomersCsv(search = '') { const query = new URLSearchParams(); if (search.trim()) query.set('search', search.trim()); const response = await fetch(`${API_BASE}/admin/customers/export?${query.toString()}`, { headers: { Accept: 'text/csv', Authorization: `Bearer ${getToken()}` } }); if (!response.ok) throw new Error('تعذر تصدير العملاء'); const blob = await response.blob(); const url = URL.createObjectURL(blob); const anchor = document.createElement('a'); anchor.href = url; anchor.download = 'customers.csv'; anchor.click(); URL.revokeObjectURL(url) }

export async function getCustomer(id: number) { return (await request<{ data: CustomerDetail }>(`/admin/customers/${id}`)).data }
export async function updateCustomer(id: number, data: Pick<ApiCustomer, 'name' | 'email' | 'phone' | 'status'>) { return (await request<{ data: ApiCustomer }>(`/admin/customers/${id}`, { method: 'PATCH', body: JSON.stringify(data) })).data }
export async function createCustomerAddress(id: number, data: Omit<CustomerAddress, 'id'>) { return (await request<{ data: CustomerAddress }>(`/admin/customers/${id}/addresses`, { method: 'POST', body: JSON.stringify(data) })).data }
export async function updateCustomerAddress(id: number, addressId: number, data: Omit<CustomerAddress, 'id'>) { return (await request<{ data: CustomerAddress }>(`/admin/customers/${id}/addresses/${addressId}`, { method: 'PATCH', body: JSON.stringify(data) })).data }
export async function deleteCustomerAddress(id: number, addressId: number) { return request(`/admin/customers/${id}/addresses/${addressId}`, { method: 'DELETE' }) }
