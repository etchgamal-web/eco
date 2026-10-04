import { request } from './client'

type Row = Record<string, unknown>
export type MonitoringPage<T> = { items: T[]; meta: { current_page: number; last_page: number; per_page: number; total: number } }
type MonitoringResponse = Row[] | { items?: Row[]; data?: Row[]; meta?: MonitoringPage<Row>['meta'] }

export async function operationalDashboard() { return (await request<{ data: Row }>('/operations/dashboard')).data }
export async function acknowledgeAlert(id: number) { return (await request<{ data: Row }>(`/operational-alerts/${id}/acknowledge`, { method: 'PATCH' })).data }
export async function resolveAlert(id: number) { return (await request<{ data: Row }>(`/operational-alerts/${id}/resolve`, { method: 'PATCH' })).data }
export async function listMonitoringSettings() { return (await request<{ data: Row[] }>('/settings/order-monitoring')).data }
export async function updateMonitoringSetting(payload: { rule_type: string; days: number; is_enabled: boolean }) { return (await request<{ data: Row }>('/settings/order-monitoring', { method: 'PUT', body: JSON.stringify(payload) })).data }

const normalizeMonitoringPage = (value: MonitoringResponse): MonitoringPage<Row> => Array.isArray(value)
  ? { items: value, meta: { current_page: 1, last_page: 1, per_page: value.length || 25, total: value.length } }
  : { items: value.items ?? value.data ?? [], meta: value.meta ?? { current_page: 1, last_page: 1, per_page: 25, total: (value.items ?? value.data ?? []).length } }

export async function listDelayedOrders(params: Record<string, string> = {}) { const query = new URLSearchParams(params).toString(); return normalizeMonitoringPage((await request<{ data: MonitoringResponse }>(`/orders/delayed${query ? `?${query}` : ''}`)).data) }
export async function runDelayedOrderDetection() { return (await request<{ data: Row }>('/orders/delayed/detect', { method: 'POST' })).data }
export async function listOperationalAlerts(params: Record<string, string> = {}) { const query = new URLSearchParams(params).toString(); return normalizeMonitoringPage((await request<{ data: MonitoringResponse }>(`/operational-alerts${query ? `?${query}` : ''}`)).data) }
export async function getOperationalAlert(id: number) { return (await request<{ data: Row }>(`/operational-alerts/${id}`)).data }
export async function acknowledgeOperationalAlert(id: number) { return (await request<{ data: Row }>(`/operational-alerts/${id}/acknowledge`, { method: 'PATCH' })).data }
export async function resolveOperationalAlert(id: number) { return (await request<{ data: Row }>(`/operational-alerts/${id}/resolve`, { method: 'PATCH' })).data }
export async function bulkAcknowledgeOperationalAlerts(ids: number[], reason?: string) { return (await request<{ data: Row }>('/operational-alerts/bulk-acknowledge', { method: 'PATCH', body: JSON.stringify({ alert_ids: ids, reason }) })).data }
export async function bulkResolveOperationalAlerts(ids: number[], reason?: string) { return (await request<{ data: Row }>('/operational-alerts/bulk-resolve', { method: 'PATCH', body: JSON.stringify({ alert_ids: ids, reason }) })).data }
