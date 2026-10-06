'use client'

import { useEffect, useState } from 'react'
import Link from 'next/link'
import { useAuth } from '@/features/auth/auth-context'
import { getCustomerAddresses, getCustomerOrders, type CustomerAddress, type CustomerOrder } from '@/infrastructure/api/customer-api'
import { formatPrice } from '@/core/i18n/formatters'

export default function AccountOverview() {
  const { customer, loading: authLoading } = useAuth()
  const [orders, setOrders] = useState<CustomerOrder[]>([])
  const [addresses, setAddresses] = useState<CustomerAddress[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    if (!customer) { setLoading(false); return }
    void Promise.all([getCustomerOrders(), getCustomerAddresses()]).then(([nextOrders, nextAddresses]) => { setOrders(nextOrders); setAddresses(nextAddresses) }).catch((reason) => setError(reason instanceof Error ? reason.message : 'تعذر تحميل بيانات الحساب')).finally(() => setLoading(false))
  }, [customer])

  if (authLoading) return <div className="state-card">جارٍ تحميل الحساب...</div>
  if (!customer) return <div className="state-card"><strong>سجّل الدخول لعرض حسابك</strong><p>يمكنك متابعة الطلبات وإدارة عناوين الشحن بعد تسجيل الدخول.</p><Link className="primary-button" href="/account/login">تسجيل الدخول</Link></div>

  return (
    <section className="account-page-shell">
      <div className="account-heading"><div><p className="kicker">مساحتك في إيكو</p><h1>مرحبًا، {customer.name}</h1><p>{customer.email || customer.phone}</p></div><Link className="secondary-button" href="/products">متابعة التسوق</Link></div>
      {error ? <div className="state-card" role="alert"><strong>{error}</strong><p>تأكد من اتصال Laravel API ثم حاول مرة أخرى.</p></div> : null}
      <div className="account-cards">
        <article className="account-card"><span>الطلبات</span><strong>{loading ? '—' : orders.length}</strong><Link href="/account/orders">عرض الطلبات</Link><p>طلباتك السابقة</p></article>
        <article className="account-card"><span>العناوين</span><strong>{loading ? '—' : addresses.length}</strong><Link href="/account/addresses">إدارة العناوين</Link><p>عناوين الشحن المحفوظة</p></article>
        <article className="account-card"><span>السلة</span><Link href="/cart">عرض السلة</Link><p>راجع اختياراتك الحالية</p></article>
      </div>
      <div className="account-sections">
        <section><div className="account-section-heading"><h2>آخر الطلبات</h2><span>{orders.length} طلب</span></div>{loading ? <p className="muted-text">جارٍ التحميل...</p> : orders.length === 0 ? <p className="muted-text">لا توجد طلبات بعد.</p> : <div className="account-list">{orders.slice(0, 5).map((order) => <div key={order.id}><span>#{order.order_number || order.id}</span><span>{order.status || 'قيد المعالجة'}</span><strong>{formatPrice(order.total_amount || 0, order.currency)}</strong></div>)}</div>}</section>
        <section><div className="account-section-heading"><h2>عناوين الشحن</h2><span>{addresses.length} عنوان</span></div>{loading ? <p className="muted-text">جارٍ التحميل...</p> : addresses.length === 0 ? <p className="muted-text">لم تتم إضافة عناوين بعد.</p> : <div className="account-list">{addresses.slice(0, 3).map((address) => <div key={address.id}><span>{address.recipient_name || 'عنوان الشحن'}</span><span>{address.city || address.country || ''}</span><strong>{address.is_default ? 'افتراضي' : ''}</strong></div>)}</div>}</section>
      </div>
    </section>
  )
}
