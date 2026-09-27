import { request } from './client'

export async function operationalDashboard() { return (await request<{ data: Record<string, unknown> }>('/operations/dashboard')).data }

export async function acknowledgeAlert(id: number) { return (await request<{ data: Record<string, unknown> }>(`/operational-alerts/${id}/acknowledge`, { method: 'PATCH' })).data }

export async function resolveAlert(id: number) { return (await request<{ data: Record<string, unknown> }>(`/operational-alerts/${id}/resolve`, { method: 'PATCH' })).data }

export async function listMonitoringSettings() { return (await request<{ data: Array<Record<string, unknown>> }>('/settings/order-monitoring')).data }

export async function updateMonitoringSetting(payload: { rule_type: string; days: number; is_enabled: boolean }) { return (await request<{ data: Record<string, unknown> }>('/settings/order-monitoring', { method: 'PUT', body: JSON.stringify(payload) })).data }

export async function listDelayedOrders(params: Record<string, string> = {}) { const query = new URLSearchParams(params).toString(); return (await request<{ data: Array<Record<string, unknown>> | { data?: Array<Record<string, unknown>> } }>(`/orders/delayed${query ? `?${query}` : ''}`)).data }

export async function runDelayedOrderDetection() { return (await request<{ data: Record<string, unknown> }>('/orders/delayed/detect', { method: 'POST' })).data }

export async function listOperationalAlerts(params: Record<string, string> = {}) { const query = new URLSearchParams(params).toString(); const result = (await request<{ data: Array<Record<string, unknown>> | { data?: Array<Record<string, unknown>> } }>(`/operational-alerts${query ? `?${query}` : ''}`)).data; return Array.isArray(result) ? result : result.data ?? [] }

export async function acknowledgeOperationalAlert(id: number) { return (await request<{ data: Record<string, unknown> }>(`/operational-alerts/${id}/acknowledge`, { method: 'PATCH' })).data }

export async function resolveOperationalAlert(id: number) { return (await request<{ data: Record<string, unknown> }>(`/operational-alerts/${id}/resolve`, { method: 'PATCH' })).data }

export async function bulkAcknowledgeOperationalAlerts(ids: number[]) { return (await request<{ data: Record<string, unknown> }>('/operational-alerts/bulk-acknowledge', { method: 'PATCH', body: JSON.stringify({ ids }) })).data }

export async function bulkResolveOperationalAlerts(ids: number[]) { return (await request<{ data: Record<string, unknown> }>('/operational-alerts/bulk-resolve', { method: 'PATCH', body: JSON.stringify({ ids }) })).data }
