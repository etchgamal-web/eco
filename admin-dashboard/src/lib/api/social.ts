import { request } from './client'

export async function socialSummary() { return (await request<{ data: Record<string, unknown> }>('/admin/social/summary')).data }

export async function listSocialTemplates() { return (await request<{ data: Array<Record<string, unknown>> }>('/admin/social/templates')).data }

export async function createSocialTemplate(payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>('/admin/social/templates', { method: 'POST', body: JSON.stringify(payload) })).data }

export async function deleteSocialTemplate(id: number) { await request(`/admin/social/templates/${id}`, { method: 'DELETE' }) }

export async function listSocialInteractions(params: Record<string, string> = {}) { const query = new URLSearchParams(params).toString(); return (await request<{ data: Array<Record<string, unknown>> }>(`/admin/social/interactions${query ? `?${query}` : ''}`)).data }

export async function replySocialInteraction(id: number, body: string) { return (await request<{ data: Record<string, unknown> }>(`/admin/social/interactions/${id}/reply`, { method: 'POST', body: JSON.stringify({ body }) })).data }

export async function getSocialConversation(id: number) { return (await request<{ data: Record<string, unknown> }>(`/admin/social/conversations/${id}`)).data }

export async function listSocialConversationMessages(id: number) { return (await request<{ data: Array<Record<string, unknown>> }>(`/admin/social/conversations/${id}/messages`)).data }

export async function sendSocialConversationMessage(id: number, body: string) { return (await request<{ data: Record<string, unknown> }>(`/admin/social/conversations/${id}/messages`, { method: 'POST', body: JSON.stringify({ body }) })).data }

export async function pauseSocialConversation(id: number) { return (await request<{ data: Record<string, unknown> }>(`/admin/social/conversations/${id}/pause`, { method: 'POST' })).data }

export async function resumeSocialConversation(id: number) { return (await request<{ data: Record<string, unknown> }>(`/admin/social/conversations/${id}/resume`, { method: 'POST' })).data }

export async function updateSocialConnection(id: number, payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>(`/admin/social/connections/${id}`, { method: 'PATCH', body: JSON.stringify(payload) })).data }

export async function updateSocialTemplate(id: number, payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>(`/admin/social/templates/${id}`, { method: 'PATCH', body: JSON.stringify(payload) })).data }

export async function updateAutomationRule(id: number, payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>(`/admin/social/automation-rules/${id}`, { method: 'PATCH', body: JSON.stringify(payload) })).data }

export async function listAutomationRules() { return (await request<{ data: Array<Record<string, unknown>> }>('/admin/social/automation-rules')).data }

export async function createAutomationRule(payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>('/admin/social/automation-rules', { method: 'POST', body: JSON.stringify(payload) })).data }

export async function deleteAutomationRule(id: number) { await request(`/admin/social/automation-rules/${id}`, { method: 'DELETE' }) }

export async function previewSocialTemplate(id: number, values: Record<string, string> = {}) { return (await request<{ data: Record<string, unknown> }>(`/admin/social/templates/${id}/preview`, { method: 'POST', body: JSON.stringify({ values }) })).data }

export async function listSocialConnections() { return (await request<{ data: Array<Record<string, unknown>> }>('/admin/social/connections')).data }

export async function createSocialConnection(payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>('/admin/social/connections', { method: 'POST', body: JSON.stringify(payload) })).data }

export async function deleteSocialConnection(id: number) { await request(`/admin/social/connections/${id}`, { method: 'DELETE' }) }
