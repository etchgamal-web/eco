import { request, listPayload } from './client'

export async function listStaff() { return listPayload((await request<{ data: Array<Record<string, unknown>> | { data?: Array<Record<string, unknown>> } }>('/staff')).data) }

export async function createStaff(payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>('/staff', { method: 'POST', body: JSON.stringify(payload) })).data }

export async function updateStaff(id: number, payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>(`/staff/${id}`, { method: 'PATCH', body: JSON.stringify(payload) })).data }

export async function deleteStaff(id: number) { await request(`/staff/${id}`, { method: 'DELETE' }) }
