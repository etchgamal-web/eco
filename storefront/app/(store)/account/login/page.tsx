'use client'

import { FormEvent, useState } from 'react'
import Link from 'next/link'
import { useRouter } from 'next/navigation'
import AnnouncementBar from '@/shared/layout/AnnouncementBar'
import SiteHeader from '@/shared/layout/SiteHeader'
import SiteFooter from '@/shared/layout/SiteFooter'
import { useAuth } from '@/features/auth/auth-context'

export default function LoginPage() {
  const router = useRouter()
  const { login } = useAuth()
  const [identifier, setIdentifier] = useState('')
  const [password, setPassword] = useState('')
  const [remember, setRemember] = useState(true)
  const [error, setError] = useState('')
  const [submitting, setSubmitting] = useState(false)

  const submit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    setSubmitting(true)
    setError('')
    try {
      await login(identifier, password, remember)
      router.push('/')
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : 'تعذر تسجيل الدخول')
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <main><AnnouncementBar /><SiteHeader /><div className="auth-page-shell"><div className="auth-card"><p className="kicker">مرحبًا بعودتك</p><h1>تسجيل الدخول</h1><form onSubmit={submit}><label>البريد الإلكتروني أو الهاتف<input required value={identifier} onChange={(event) => setIdentifier(event.target.value)} autoComplete="username" /></label><label>كلمة المرور<input required type="password" value={password} onChange={(event) => setPassword(event.target.value)} autoComplete="current-password" /></label><label className="checkbox-label"><input type="checkbox" checked={remember} onChange={(event) => setRemember(event.target.checked)} /> تذكرني</label>{error ? <p className="form-error" role="alert">{error}</p> : null}<button className="primary-button auth-submit" type="submit" disabled={submitting}>{submitting ? 'جارٍ الدخول...' : 'دخول'}</button></form><p className="auth-help">ليس لديك حساب؟ <Link href="/account/register">إنشاء حساب</Link></p></div></div><SiteFooter /></main>
  )
}
