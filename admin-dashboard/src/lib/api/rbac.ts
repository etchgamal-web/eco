import { request } from './client'

export type RbacPermission = { name: string; slug: string; group: string }
export type RbacRole = { id: number; name: string; slug: string; description?: string; is_system: boolean; is_active: boolean; users_count: number; permissions: RbacPermission[] }
export type RbacMatrix = { roles: RbacRole[]; permission_groups: Array<{ group: string; permissions: RbacPermission[] }> }

export async function getRbacMatrix() { return (await request<{ data: RbacMatrix }>('/roles')).data }
export async function updateRolePermissions(roleId: number, permissions: string[]) { return (await request<{ data: RbacRole }>(`/roles/${roleId}/permissions`, { method: 'PATCH', body: JSON.stringify({ permissions }) })).data }
export async function updateRoleStatus(roleId: number, isActive: boolean) { return (await request<{ data: RbacRole }>(`/roles/${roleId}/status`, { method: 'PATCH', body: JSON.stringify({ is_active: isActive }) })).data }
export async function getRoleAudit(roleId: number, params: Record<string, string> = {}) { const query = new URLSearchParams(params).toString(); return (await request<{ data: { items: Array<Record<string, unknown>>; meta: { current_page: number; last_page: number; per_page: number; total: number } } }>(`/roles/${roleId}/audit${query ? `?${query}` : ''}`)).data }
