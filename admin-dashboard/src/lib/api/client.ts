export const API_BASE = (import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api/v1').replace(/\/$/, '')
export class ApiError extends Error {
  status: number
  constructor(status: number, message: string) { super(message); this.status = status }
}
export function getToken() { return localStorage.getItem('eco_admin_token') }
export function setToken(token: string) { localStorage.setItem('eco_admin_token', token) }
export function clearToken() { localStorage.removeItem('eco_admin_token') }
export async function request<T>(path: string, init: RequestInit = {}, token = getToken()): Promise<T> {
  const headers = new Headers(init.headers)
  headers.set('Accept', 'application/json')
  if (init.body && !headers.has('Content-Type') && !(init.body instanceof FormData)) headers.set('Content-Type', 'application/json')
  if (token) headers.set('Authorization', `Bearer ${token}`)
  const response = await fetch(`${API_BASE}${path}`, { ...init, headers })
  const payload = await response.json().catch(() => null)
  if (!response.ok) {
    if (response.status === 401 && token) clearToken()
    const serverMessage = typeof payload?.message === 'string' ? payload.message : ''
    const message = response.status === 401 ? 'بيانات الدخول غير صحيحة أو انتهت جلسة الدخول.' : response.status === 403 ? 'ليس لديك صلاحية لتنفيذ هذا الإجراء.' : response.status === 422 ? 'راجع البيانات المدخلة؛ توجد قيمة غير صحيحة.' : serverMessage || `فشل الطلب (${response.status})`
    throw new ApiError(response.status, message)
  }
  return payload as T
}
export function apiBaseUrl() { return API_BASE }
export function listPayload(value: Array<Record<string, unknown>> | { data?: Array<Record<string, unknown>> }) { return Array.isArray(value) ? value : Array.isArray(value.data) ? value.data : [] }
