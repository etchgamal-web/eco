import { request } from './client'

export type DashboardStats = {
  sales: { total: number }
  orders: { total: number; new: number; by_status: Record<string, number> }
  average_order: number
  currency: string
  social: { orders: number; new_orders: number; messages: number; comments: number; interactions: number; conversations: number; open_conversations: number; unanswered_comments: number }
}

export async function getDashboardStats() {
  return (await request<{ data: DashboardStats }>('/dashboard/stats')).data
}
