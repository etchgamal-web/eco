import { request } from './client'

export async function updateShipmentStatus(id: number, status: string, note?: string) { return (await request<{ data: Record<string, unknown> }>(`/shipments/${id}/status`, { method: 'PATCH', body: JSON.stringify({ status, note }) })).data }

export async function listShippingMethods() { return (await request<{ data: Array<Record<string, unknown>> }>('/shipping-methods')).data }

export async function createShippingMethod(payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>('/shipping-methods', { method: 'POST', body: JSON.stringify(payload) })).data }

export async function updateShippingMethod(id: number, payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>(`/shipping-methods/${id}`, { method: 'PATCH', body: JSON.stringify(payload) })).data }

export async function deleteShippingMethod(id: number) { await request(`/shipping-methods/${id}`, { method: 'DELETE' }) }
