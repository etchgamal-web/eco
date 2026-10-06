'use client'

import { FormEvent, useEffect, useState } from 'react'
import { createCustomerAddress, deleteCustomerAddress, getCustomerAddresses, type CustomerAddress } from '@/infrastructure/api/customer-api'
import { useAuth } from '@/features/auth/auth-context'

const initialForm = { recipient_name: '', phone: '', address_line1: '', city: '', country: 'EG', is_default: false }

export default function CustomerAddresses() {
  const { customer, loading: authLoading } = useAuth()
  const [addresses, setAddresses] = useState<CustomerAddress[]>([])
  const [form, setForm] = useState(initialForm)
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [message, setMessage] = useState('')

  const load = () => void getCustomerAddresses().then(setAddresses).catch(() => setMessage('تعذر تحميل العناوين')).finally(() => setLoading(false))
  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    if (customer) load(); else setLoading(false)
  }, [customer])

  const submit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault(); setSaving(true); setMessage('')
    try { await createCustomerAddress(form); setForm(initialForm); setMessage('تم حفظ العنوان بنجاح'); load() } catch (reason) { setMessage(reason instanceof Error ? reason.message : 'تعذر حفظ العنوان') } finally { setSaving(false) }
  }

  const remove = async (id: number) => { try { await deleteCustomerAddress(id); setAddresses((current) => current.filter((address) => address.id !== id)); setMessage('تم حذف العنوان') } catch (reason) { setMessage(reason instanceof Error ? reason.message : 'تعذر حذف العنوان') } }

  if (authLoading || loading && customer) return <div className="state-card">جارٍ تحميل العناوين...</div>
  if (!customer) return <div className="state-card">سجّل الدخول لإدارة عناوينك.</div>
  return <div className="addresses-layout"><form className="address-form" onSubmit={submit}><h2>إضافة عنوان</h2><label>اسم المستلم<input required value={form.recipient_name} onChange={(event) => setForm({ ...form, recipient_name: event.target.value })} /></label><label>الهاتف<input required value={form.phone} onChange={(event) => setForm({ ...form, phone: event.target.value })} /></label><label>العنوان<input required value={form.address_line1} onChange={(event) => setForm({ ...form, address_line1: event.target.value })} /></label><label>المدينة<input required value={form.city} onChange={(event) => setForm({ ...form, city: event.target.value })} /></label><label>الدولة — رمز ISO<input required maxLength={2} value={form.country} onChange={(event) => setForm({ ...form, country: event.target.value.toUpperCase() })} /></label><label className="checkbox-label"><input type="checkbox" checked={form.is_default} onChange={(event) => setForm({ ...form, is_default: event.target.checked })} /> جعله العنوان الافتراضي</label><button className="primary-button" disabled={saving} type="submit">{saving ? 'جارٍ الحفظ...' : 'حفظ العنوان'}</button>{message ? <p className="muted-text" role="status">{message}</p> : null}</form><section><h2>العناوين المحفوظة</h2>{loading ? <p className="muted-text">جارٍ التحميل...</p> : addresses.length === 0 ? <p className="muted-text">لا توجد عناوين محفوظة.</p> : <div className="address-list">{addresses.map((address) => <article key={address.id}><div><strong>{address.recipient_name || 'عنوان الشحن'}</strong>{address.is_default ? <span>افتراضي</span> : null}</div><p>{address.address_line1} — {address.city} — {address.country}</p><button className="text-button" type="button" onClick={() => void remove(address.id)}>حذف</button></article>)}</div>}</section></div>
}
