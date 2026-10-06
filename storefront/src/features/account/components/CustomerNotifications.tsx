'use client'

import { useEffect, useState } from 'react'
import { getNotifications, markNotificationRead, type CustomerNotification } from '@/infrastructure/api/customer-api'

export default function CustomerNotifications() {
  const [items, setItems] = useState<CustomerNotification[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  useEffect(() => { void getNotifications().then(setItems).catch((reason) => setError(reason instanceof Error ? reason.message : 'تعذر تحميل الإشعارات')).finally(() => setLoading(false)) }, [])
  const read = async (item: CustomerNotification) => { try { const updated = await markNotificationRead(item.id); setItems((current) => current.map((entry) => entry.id === item.id ? { ...entry, ...updated, read_at: updated.read_at || new Date().toISOString() } : entry)) } catch { setError('تعذر تحديث الإشعار') } }
  if (loading) return <div className="state-card">جارٍ تحميل الإشعارات...</div>
  if (error) return <div className="state-card" role="alert">{error}</div>
  if (items.length === 0) return <div className="state-card">لا توجد إشعارات جديدة.</div>
  return <div className="notifications-list">{items.map((item) => <article className={item.read_at ? 'notification-card is-read' : 'notification-card'} key={item.id}><div><strong>{item.title || 'إشعار من إيكو'}</strong><p>{item.body || ''}</p></div>{item.read_at ? <span>تمت القراءة</span> : <button className="text-button" type="button" onClick={() => void read(item)}>تحديد كمقروء</button>}</article>)}</div>
}
