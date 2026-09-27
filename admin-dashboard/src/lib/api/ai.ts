import { request } from './client'

export async function getAiSettings() { return (await request<{ data: Record<string, unknown> }>('/admin/ai/settings')).data }

export async function updateAiSettings(payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>('/admin/ai/settings', { method: 'PATCH', body: JSON.stringify(payload) })).data }

export async function generateProductDraft(payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>('/admin/ai/products/draft', { method: 'POST', body: JSON.stringify(payload) })).data }

export async function suggestSocialReply(payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>('/admin/ai/social/reply-suggestion', { method: 'POST', body: JSON.stringify(payload) })).data }
