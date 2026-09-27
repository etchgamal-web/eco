import { request, setToken, clearToken } from './client'

export async function login(identifier: string, password: string) {
  const payload = await request<{ token: string; data: { id: number; name?: string; email?: string } }>('/auth/login', { method: 'POST', body: JSON.stringify({ identifier, password, remember: true }) }, null)
  setToken(payload.token)
  return payload.data
}

export async function me() { return (await request<{ data: { id: number; name?: string; email?: string; status?: string; roles?: string[]; permissions?: string[] } }>('/auth/me')).data }

export async function updateProfile(payload: { name: string; email: string }) { return (await request<{ data: { id: number; name?: string; email?: string } }>('/auth/me', { method: 'PATCH', body: JSON.stringify(payload) })).data }

export async function changePassword(payload: { current_password: string; password: string; password_confirmation: string }) { return request<{ data: Record<string, unknown> }>('/auth/password', { method: 'POST', body: JSON.stringify(payload) }) }

export async function logout() { await request('/auth/logout', { method: 'POST' }).finally(clearToken) }
