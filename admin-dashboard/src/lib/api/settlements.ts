import { request, getToken, ApiError, API_BASE } from './client'

export async function settlementSummary(params: Record<string, string> = {}) { const query = new URLSearchParams(params).toString(); return (await request<{ data: Record<string, unknown> }>(`/reports/settlements/summary${query ? `?${query}` : ''}`)).data }

export async function settlementProviders(params: Record<string, string> = {}) { const query = new URLSearchParams(params).toString(); return (await request<{ data: Record<string, unknown> }>(`/reports/settlements/providers${query ? `?${query}` : ''}`)).data }

export async function listSettlements(params: Record<string, string> = {}) { const query = new URLSearchParams(params).toString(); return (await request<{ data: Array<Record<string, unknown>> | { data?: Array<Record<string, unknown>> } }>(`/shipping/settlements${query ? `?${query}` : ''}`)).data }

export async function finalizeSettlement(id: number) { return (await request<{ data: Record<string, unknown> }>(`/shipping/settlements/${id}/finalize`, { method: 'PATCH' })).data }

export async function importSettlement(file: File, providerCode: string, periodFrom?: string, periodTo?: string) { const body = new FormData(); body.append('file', file); body.append('provider_code', providerCode); if (periodFrom) body.append('period_from', periodFrom); if (periodTo) body.append('period_to', periodTo); return (await request<{ data: Record<string, unknown> }>('/shipping/settlements/import', { method: 'POST', body })).data }

export async function getSettlementItems(id: number) { return (await request<{ data: Array<Record<string, unknown>> | { data?: Array<Record<string, unknown>> } }>(`/shipping/settlements/${id}/items`)).data }

export async function exportSettlement(id: number) { const response = await fetch(`${API_BASE}/shipping/settlements/${id}/export`, { headers: { Accept: 'text/csv', Authorization: `Bearer ${getToken()}` } }); if (!response.ok) throw new ApiError(response.status, 'تعذر تصدير التسوية'); return response.blob() }
