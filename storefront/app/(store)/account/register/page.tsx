'use client'

import { FormEvent, useState } from 'react'
import Link from 'next/link'
import { useRouter } from 'next/navigation'
import AnnouncementBar from '@/shared/layout/AnnouncementBar'
import SiteHeader from '@/shared/layout/SiteHeader'
import SiteFooter from '@/shared/layout/SiteFooter'
import { useAuth } from '@/features/auth/auth-context'
import { requestJson } from '@/core/http/client'
import type { Customer } from '@/domain/customer/customer'

type RegisterResponse = { data: Customer; token: string }

export default function RegisterPage() {
  const router = useRouter()
  const { login } = useAuth()
  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [confirmation, setConfirmation] = useState('')
  const [error, setError] = useState('')
  const [submitting, setSubmitting] = useState(false)

  const submit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    setSubmitting(true)
    setError('')
    try {
      const response = await requestJson<RegisterResponse>('/auth/register', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ name, email, password, password_confirmation: confirmation }) })
      await login(email, password, true)
      void response
      router.push('/')
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : 'تعذر إنشاء الحساب')
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <main><AnnouncementBar /><SiteHeader /><div className="auth-page-shell"><div className="auth-card"><p className="kicker">ابدأ رحلتك</p><h1>إنشاء حساب</h1><form onSubmit={submit}><label>الاسم<input required value={name} onChange={(event) => setName(event.target.value)} autoComplete="name" /></label><label>البريد الإلكتروني<input required type="email" value={email} onChange={(event) => setEmail(event.target.value)} autoComplete="email" /></label><label>كلمة المرور<input required minLength={8} type="password" value={password} onChange={(event) => setPassword(event.target.value)} autoComplete="new-password" /></label><label>تأكيد كلمة المرور<input required minLength={8} type="password" value={confirmation} onChange={(event) => setConfirmation(event.target.value)} autoComplete="new-password" /></label>{error ? <p className="form-error" role="alert">{error}</p> : null}<button className="primary-button auth-submit" type="submit" disabled={submitting}>{submitting ? 'جارٍ إنشاء الحساب...' : 'إنشاء الحساب'}</button></form><p className="auth-help">لديك حساب؟ <Link href="/account/login">تسجيل الدخول</Link></p></div></div><SiteFooter /></main>
  )
}
