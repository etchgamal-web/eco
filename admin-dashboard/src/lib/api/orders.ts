import { request, getToken, ApiError, API_BASE } from './client'
import type { ApiOrder, ApiOrderPage } from './types'

export async function listOrders(params: Record<string, string | number> = {}) { const query = new URLSearchParams(Object.entries(params).map(([key, value]) => [key, String(value)])); return (await request<{ data: ApiOrder[] | ApiOrderPage }>(`/orders?${query}`)).data }

export async function getOrder(id: number) { return (await request<{ data: ApiOrder }>(`/orders/${id}`)).data }

export async function getOrderTimeline(id: number) { return (await request<{ data: unknown[] }>(`/orders/${id}/timeline`)).data }

export async function confirmOrder(id: number) { return (await request<{ data: ApiOrder }>(`/orders/${id}/confirm`, { method: 'POST' })).data }

export async function recordOrderContact(id: number, contact_result: string, notes?: string) { return (await request<{ data: Record<string, unknown> }>(`/orders/${id}/contact`, { method: 'POST', body: JSON.stringify({ contact_result, notes }) })).data }

export async function setOrderShippingCharge(id: number, shipping_amount: number) { return (await request<{ data: ApiOrder }>(`/orders/${id}/shipping-charge`, { method: 'PATCH', body: JSON.stringify({ shipping_amount }) })).data }

export async function createShipment(orderId: number, payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>(`/orders/${orderId}/shipments`, { method: 'POST', body: JSON.stringify(payload), headers: { 'Idempotency-Key': String(payload.idempotency_key) } })).data }

export async function downloadOrdersCsv() { const response = await fetch(`${API_BASE}/orders/export`, { headers: { Accept: 'text/csv', Authorization: `Bearer ${getToken()}` } }); if (!response.ok) throw new ApiError(response.status, 'تعذر تصدير الطلبات'); return response.blob() }

export async function updateOrderStatus(id: number, status: string) { return (await request<{ data: ApiOrder }>(`/orders/${id}/status`, { method: 'PATCH', body: JSON.stringify({ status }) })).data }

export async function cancelOrder(id: number) { return (await request<{ data: ApiOrder }>(`/orders/${id}/cancel`, { method: 'POST' })).data }
