import { request } from './client'

export async function listOrderPayments(orderId: number) { return (await request<{ data: Array<Record<string, unknown>> }>(`/orders/${orderId}/payments`)).data }

export async function confirmPayment(id: number) { return (await request<{ data: Record<string, unknown> }>(`/payments/${id}/confirm`, { method: 'POST' })).data }

export async function refundPayment(id: number) { return (await request<{ data: Record<string, unknown> }>(`/payments/${id}/refund`, { method: 'POST' })).data }
