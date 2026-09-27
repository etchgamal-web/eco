import { request, listPayload } from './client'

export async function listReturns() { return listPayload((await request<{ data: Array<Record<string, unknown>> | { data?: Array<Record<string, unknown>> } }>('/returns')).data) }

export async function updateReturn(id: number, action: 'approve' | 'receive' | 'inspect' | 'reject', payload: Record<string, unknown> = {}) { return (await request<{ data: Record<string, unknown> }>(`/returns/${id}/${action}`, { method: 'PATCH', body: JSON.stringify(payload) })).data }

export async function listReviews() { return listPayload((await request<{ data: Array<Record<string, unknown>> | { data?: Array<Record<string, unknown>> } }>('/reviews')).data) }

export async function moderateReview(id: number, status: string) { return (await request<{ data: Record<string, unknown> }>(`/reviews/${id}/moderate`, { method: 'PATCH', body: JSON.stringify({ status }) })).data }
