import { request } from './client'

type IntegrationEvent = { id: number; source: string; provider: string | null; event_id: string; event_type: string; status: string; reference: string | null; error: string | null; created_at: string }
export async function listIntegrationEvents(params: { source?: string; status?: string } = {}) { const query = new URLSearchParams(); if (params.source) query.set('source', params.source); if (params.status) query.set('status', params.status); return (await request<{ data: IntegrationEvent[] }>(`/integrations/events?${query.toString()}`)).data }
export async function retryIntegrationEvent(source: string, id: number) { return (await request<{ data: IntegrationEvent }>(`/integrations/events/${source}/${id}/retry`, { method: 'POST' })).data }
