'use client'

import { FormEvent, useState } from 'react'
import Link from 'next/link'
import AnnouncementBar from '@/shared/layout/AnnouncementBar'
import SiteHeader from '@/shared/layout/SiteHeader'
import SiteFooter from '@/shared/layout/SiteFooter'
import { forgotPassword } from '@/infrastructure/api/auth-api'

export default function ForgotPasswordPage() {
  const [email, setEmail] = useState('')
  const [error, setError] = useState('')
  const [sent, setSent] = useState(false)
  const [submitting, setSubmitting] = useState(false)

  const submit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    setSubmitting(true)
    setError('')
    try {
      await forgotPassword(email)
      setSent(true)
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : 'تعذر إرسال رابط الاستعادة')
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <main>
      <AnnouncementBar />
      <SiteHeader />
      <div className="auth-page-shell">
        <div className="auth-card">
          <p className="kicker">استعادة الحساب</p>
          <h1>نسيت كلمة المرور</h1>
          {sent ? (
            <p className="auth-help" role="status">إذا كان البريد مسجلًا لدينا فسيصلك رابط لإعادة تعيين كلمة المرور خلال دقائق. تحقق من الوارد والرسائل غير المرغوبة.</p>
          ) : (
            <form onSubmit={submit}>
              <label>البريد الإلكتروني<input required type="email" value={email} onChange={(event) => setEmail(event.target.value)} autoComplete="email" /></label>
              {error ? <p className="form-error" role="alert">{error}</p> : null}
              <button className="primary-button auth-submit" type="submit" disabled={submitting}>{submitting ? 'جارٍ الإرسال...' : 'إرسال رابط الاستعادة'}</button>
            </form>
          )}
          <p className="auth-help"><Link href="/account/login">العودة لتسجيل الدخول</Link></p>
        </div>
      </div>
      <SiteFooter />
    </main>
  )
}
