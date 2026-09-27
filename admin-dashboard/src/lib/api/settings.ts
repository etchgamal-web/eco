import { request } from './client'

export async function listSettings() { return (await request<{ data: Array<Record<string, unknown>> }>('/settings')).data }

export async function updateSetting(payload: { group: string; key: string; value: unknown; type: string; description?: string }) { return (await request<{ data: Record<string, unknown> }>(`/settings/${encodeURIComponent(payload.key)}`, { method: 'PUT', body: JSON.stringify(payload) })).data }
