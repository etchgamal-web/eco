import { request } from './client'

export type DashboardStats = {
  sales: { total: number }
  orders: { total: number; new: number; by_status: Record<string, number> }
  average_order: number
  currency: string
}

export async function getDashboardStats() {
  return (await request<{ data: DashboardStats }>('/dashboard/stats')).data
}
