import { request } from './client'

export type OperationalDashboard = {
  ambiguous_payments?: Array<Record<string, unknown>>
  ambiguous_refunds?: Array<Record<string, unknown>>
  stuck_returns?: Array<Record<string, unknown>>
  failed_outbox?: Array<Record<string, unknown>>
  circuits?: Array<Record<string, unknown>>
}

export async function getOperationalDashboard() {
  return (await request<{ data: OperationalDashboard }>('/operations/dashboard')).data
}

export async function listOrderPayments(orderId: number) { return (await request<{ data: Array<Record<string, unknown>> }>(`/orders/${orderId}/payments`)).data }

export async function confirmPayment(id: number) { return (await request<{ data: Record<string, unknown> }>(`/payments/${id}/confirm`, { method: 'POST' })).data }

export async function refundPayment(id: number) { return (await request<{ data: Record<string, unknown> }>(`/payments/${id}/refund`, { method: 'POST' })).data }
