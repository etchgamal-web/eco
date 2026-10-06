import { env } from '@/core/config/env'

export class ApiError extends Error {
  status: number

  constructor(message: string, status: number) {
    super(message)
    this.name = 'ApiError'
    this.status = status
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
    try {
      const payload = (await response.json()) as { message?: string }
      if (payload.message) message = payload.message
    } catch {
      // Keep the safe fallback when the API does not return JSON.
    }
    throw new ApiError(message, response.status)
  }

  return (await response.json()) as T
}
