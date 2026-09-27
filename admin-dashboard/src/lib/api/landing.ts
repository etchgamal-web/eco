import { request } from './client'

export async function listLandingPages() { return (await request<{ data: Array<Record<string, unknown>> }>('/admin/landing-pages')).data }

export async function createLandingPage(payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>('/admin/landing-pages', { method: 'POST', body: JSON.stringify(payload) })).data }

export async function updateLandingPage(id: number, payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>(`/admin/landing-pages/${id}`, { method: 'PATCH', body: JSON.stringify(payload) })).data }

export async function deleteLandingPage(id: number) { await request(`/admin/landing-pages/${id}`, { method: 'DELETE' }) }

export async function publishLandingPage(id: number, published = true) { return (await request<{ data: Record<string, unknown> }>(`/admin/landing-pages/${id}/${published ? 'publish' : 'unpublish'}`, { method: 'POST' })).data }

export async function listLandingLeads() { return (await request<{ data: Array<Record<string, unknown>> }>('/admin/landing-leads')).data }

export async function updateLandingLead(id: number, payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>(`/admin/landing-leads/${id}`, { method: 'PATCH', body: JSON.stringify(payload) })).data }

export async function getLandingPageStats(id: number) { return (await request<{ data: Record<string, unknown> }>(`/admin/landing-pages/${id}/stats`)).data }
