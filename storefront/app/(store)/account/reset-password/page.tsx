'use client'

import { FormEvent, Suspense, useState } from 'react'
import Link from 'next/link'
import { useRouter, useSearchParams } from 'next/navigation'
import AnnouncementBar from '@/shared/layout/AnnouncementBar'
import SiteHeader from '@/shared/layout/SiteHeader'
import SiteFooter from '@/shared/layout/SiteFooter'
import { resetPassword } from '@/infrastructure/api/auth-api'

function ResetPasswordForm() {
  const router = useRouter()
  const params = useSearchParams()
  const token = params.get('token') ?? ''
  const [email, setEmail] = useState(params.get('email') ?? '')
  const [password, setPassword] = useState('')
  const [confirmation, setConfirmation] = useState('')
  const [error, setError] = useState('')
  const [done, setDone] = useState(false)
  const [submitting, setSubmitting] = useState(false)

  const submit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    setError('')
    if (password.length < 8) { setError('كلمة المرور يجب ألا تقل عن 8 أحرف'); return }
    if (password !== confirmation) { setError('تأكيد كلمة المرور غير مطابق'); return }
    setSubmitting(true)
    try {
      await resetPassword({ email, token, password, password_confirmation: confirmation })
      setDone(true)
      window.setTimeout(() => router.push('/account/login'), 2500)
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : 'الرابط غير صالح أو منتهي')
    } finally {
      setSubmitting(false)
    }
  }

  if (!token) {
    return <p className="form-error" role="alert">رابط الاستعادة غير مكتمل. <Link href="/account/forgot-password">اطلب رابطًا جديدًا</Link></p>
  }

  if (done) {
    return <p className="auth-help" role="status">تم تغيير كلمة المرور بنجاح. سيتم تحويلك لتسجيل الدخول...</p>
  }

  return (
    <form onSubmit={submit}>
      <label>البريد الإلكتروني<input required type="email" value={email} onChange={(event) => setEmail(event.target.value)} autoComplete="email" /></label>
      <label>كلمة المرور الجديدة<input required type="password" minLength={8} value={password} onChange={(event) => setPassword(event.target.value)} autoComplete="new-password" /></label>
      <label>تأكيد كلمة المرور<input required type="password" minLength={8} value={confirmation} onChange={(event) => setConfirmation(event.target.value)} autoComplete="new-password" /></label>
      {error ? <p className="form-error" role="alert">{error}</p> : null}
      <button className="primary-button auth-submit" type="submit" disabled={submitting}>{submitting ? 'جارٍ الحفظ...' : 'تغيير كلمة المرور'}</button>
    </form>
  )
}

export default function ResetPasswordPage() {
  return (
    <main>
      <AnnouncementBar />
      <SiteHeader />
      <div className="auth-page-shell">
        <div className="auth-card">
          <p className="kicker">استعادة الحساب</p>
          <h1>كلمة مرور جديدة</h1>
          <Suspense fallback={null}><ResetPasswordForm /></Suspense>
          <p className="auth-help"><Link href="/account/login">العودة لتسجيل الدخول</Link></p>
        </div>
      </div>
      <SiteFooter />
    </main>
  )
}
