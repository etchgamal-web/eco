import { request } from './client'
import type { ApiInventory } from './types'

export async function listInventory() { return (await request<{ data: ApiInventory[] }>('/inventory')).data }

export async function adjustInventory(payload: { product_id: number; variant_id?: number; quantity: number; reason: string; note?: string }) { return (await request<{ data: ApiInventory }>('/inventory/adjust', { method: 'POST', body: JSON.stringify(payload) })).data }

export async function reserveInventory(payload: { product_id: number; variant_id?: number; quantity: number; note?: string }) { return (await request<{ data: ApiInventory }>('/inventory/reserve', { method: 'POST', body: JSON.stringify(payload) })).data }

export async function releaseInventory(payload: { product_id: number; variant_id?: number; quantity: number; note?: string }) { return (await request<{ data: ApiInventory }>('/inventory/release', { method: 'POST', body: JSON.stringify(payload) })).data }
