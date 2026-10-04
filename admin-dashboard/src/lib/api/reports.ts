import { request } from './client'

export type SalesAnalytics = { from: string; to: string; current: { summary: { sales: number; orders: number; average_order: number; refunds: number; net_sales: number; currency: string }; daily: Array<{ date: string; orders: number; sales: number }>; products: Array<{ name: string; quantity: number; sales: number }>; customers: Array<{ name: string; orders: number; sales: number }> }; previous?: { from: string; to: string; data: SalesAnalytics['current'] } | null }

export async function salesAnalytics(params: { from?: string; to?: string; compare?: boolean } = {}) {
  const query = new URLSearchParams()
  Object.entries(params).forEach(([key, value]) => { if (value !== undefined) query.set(key, typeof value === 'boolean' ? (value ? '1' : '0') : String(value)) })
  return (await request<{ data: SalesAnalytics }>(`/reports/sales?${query.toString()}`)).data
}
