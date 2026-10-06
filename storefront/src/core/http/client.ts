import { env } from '@/core/config/env'

export class ApiError extends Error {
  status: number
  payload?: unknown

  constructor(message: string, status: number, payload?: unknown) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.payload = payload
  }
}

export async function requestJson<T>(path: string, init?: RequestInit): Promise<T> {
  const headers = new Headers(init?.headers)
  const token = typeof window !== 'undefined' ? window.localStorage.getItem('eco-auth-token') : null
  if (token && !headers.has('Authorization')) headers.set('Authorization', `Bearer ${token}`)
  headers.set('Accept', 'application/json')

  const response = await fetch(`${env.apiUrl}${path}`, {
    ...init,
    credentials: init?.credentials ?? 'include',
    headers,
  })

  if (!response.ok) {
    let message = 'تعذر الاتصال بالمتجر'
    let payload: unknown
    try {
      payload = await response.json()
      if (payload && typeof payload === 'object' && 'message' in payload && typeof payload.message === 'string') message = payload.message
    } catch {
      // Keep the safe fallback when the API does not return JSON.
    }
    throw new ApiError(message, response.status, payload)
  }

  return (await response.json()) as T
}
