import { requestJson } from '@/core/http/client'

type ChangePasswordInput = {
  current_password: string
  password: string
  password_confirmation: string
}

type ChangePasswordResponse = { data: unknown }

export async function changePassword(input: ChangePasswordInput): Promise<void> {
  await requestJson<ChangePasswordResponse>('/auth/password', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(input),
  })
}
