import { request, listPayload } from './client'

export async function listCoupons() { return listPayload((await request<{ data: Array<Record<string, unknown>> | { data?: Array<Record<string, unknown>> } }>('/coupons')).data) }

export async function createCoupon(payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>('/coupons', { method: 'POST', body: JSON.stringify(payload) })).data }

export async function updateCoupon(id: number, payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>(`/coupons/${id}`, { method: 'PATCH', body: JSON.stringify(payload) })).data }

export async function deleteCoupon(id: number) { await request(`/coupons/${id}`, { method: 'DELETE' }) }

export async function listTaxRules() { return (await request<{ data: Array<Record<string, unknown>> }>('/tax-rules')).data }

export async function createTaxRule(payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>('/tax-rules', { method: 'POST', body: JSON.stringify(payload) })).data }

export async function updateTaxRule(id: number, payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>(`/tax-rules/${id}`, { method: 'PATCH', body: JSON.stringify(payload) })).data }

export async function deleteTaxRule(id: number) { await request(`/tax-rules/${id}`, { method: 'DELETE' }) }
