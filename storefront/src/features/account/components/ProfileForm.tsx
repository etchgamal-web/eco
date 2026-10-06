'use client'

import { FormEvent, useState } from 'react'
import { useAuth } from '@/features/auth/auth-context'

export default function ProfileForm() {
  const { customer, updateProfile } = useAuth()
  const [name, setName] = useState(customer?.name || '')
  const [email, setEmail] = useState(customer?.email || '')
  const [phone, setPhone] = useState(customer?.phone || '')
  const [message, setMessage] = useState('')
  const [saving, setSaving] = useState(false)

  if (!customer) return <div className="state-card">سجّل الدخول لتعديل بياناتك.</div>
  const submit = async (event: FormEvent<HTMLFormElement>) => { event.preventDefault(); setSaving(true); setMessage(''); try { await updateProfile({ name, email, phone }); setMessage('تم تحديث بياناتك بنجاح') } catch (reason) { setMessage(reason instanceof Error ? reason.message : 'تعذر تحديث البيانات') } finally { setSaving(false) } }
  return <form className="profile-form" onSubmit={submit}><label>الاسم<input required value={name} onChange={(event) => setName(event.target.value)} /></label><label>البريد الإلكتروني<input required type="email" value={email} onChange={(event) => setEmail(event.target.value)} /></label><label>الهاتف<input value={phone} onChange={(event) => setPhone(event.target.value)} /></label><button className="primary-button" disabled={saving} type="submit">{saving ? 'جارٍ الحفظ...' : 'حفظ التغييرات'}</button>{message ? <p className="muted-text" role="status">{message}</p> : null}</form>
}
