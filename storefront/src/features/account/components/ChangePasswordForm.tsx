'use client'
import { FormEvent, useState } from 'react'
import { useAuth } from '@/features/auth/auth-context'
import { changePassword } from '@/infrastructure/api/password-api'
import './account-forms.css'
export default function ChangePasswordForm() {
  const { customer, loading } = useAuth()
  const [current, setCurrent] = useState('')
  const [next, setNext] = useState('')
  const [confirmation, setConfirmation] = useState('')
  const [error, setError] = useState('')
  const [success, setSuccess] = useState('')
  const [saving, setSaving] = useState(false)
  if (loading || !customer) return null
  const submit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    setError('')
    setSuccess('')
    if (next.length < 8) { setError('كلمة المرور الجديدة يجب ألا تقل عن 8 أحرف'); return }
    if (next !== confirmation) { setError('تأكيد كلمة المرور غير مطابق'); return }
    if (next === current) { setError('اختر كلمة مرور مختلفة عن الحالية'); return }
    setSaving(true)
    try {
      await changePassword({ current_password: current, password: next, password_confirmation: confirmation })
      setCurrent('')
      setNext('')
      setConfirmation('')
      setSuccess('تم تغيير كلمة المرور بنجاح')
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : 'تعذر تغيير كلمة المرور')
    } finally {
      setSaving(false)
    }
  }
  return (
    <section className="password-section">
      <h2>تغيير كلمة المرور</h2>
      <p>استخدم كلمة مرور قوية لا تستخدمها في مواقع أخرى.</p>
      <form className="profile-form password-form" onSubmit={submit}>
        <label>كلمة المرور الحالية<input required type="password" value={current} onChange={(event) => setCurrent(event.target.value)} autoComplete="current-password" /></label>
        <label>كلمة المرور الجديدة<input required type="password" minLength={8} value={next} onChange={(event) => setNext(event.target.value)} autoComplete="new-password" /></label>
        <label>تأكيد كلمة المرور الجديدة<input required type="password" minLength={8} value={confirmation} onChange={(event) => setConfirmation(event.target.value)} autoComplete="new-password" /></label>
        {error ? <p className="form-error" role="alert">{error}</p> : null}
        {success ? <p className="form-success" role="status">{success}</p> : null}
        <button className="primary-button" type="submit" disabled={saving}>{saving ? 'جارٍ الحفظ...' : 'تغيير كلمة المرور'}</button>
      </form>
    </section>
  )
}
