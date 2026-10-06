'use client'

import { useEffect, useState } from 'react'
import { formatPrice } from '@/core/i18n/formatters'
import { getCustomerOrders, type CustomerOrder } from '@/infrastructure/api/customer-api'
import { useAuth } from '@/features/auth/auth-context'
import Link from 'next/link'

export default function CustomerOrders() {
  const { customer, loading: authLoading } = useAuth()
  const [orders, setOrders] = useState<CustomerOrder[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    if (!customer) { setLoading(false); return }
    void getCustomerOrders().then(setOrders).catch((reason) => setError(reason instanceof Error ? reason.message : 'تعذر تحميل الطلبات')).finally(() => setLoading(false))
  }, [customer])

  if (authLoading || loading && customer) return <div className="state-card">جارٍ تحميل الطلبات...</div>
  if (!customer) return <div className="state-card">سجّل الدخول لعرض طلباتك.</div>
  if (error) return <div className="state-card" role="alert">{error}</div>
  if (orders.length === 0) return <div className="state-card">لا توجد طلبات حتى الآن.</div>

  return <div className="account-list orders-list">{orders.map((order) => <div key={order.id}><Link href={`/account/orders/${order.id}`}>#{order.order_number || order.id}</Link><span>{order.status || 'قيد المعالجة'}</span><strong>{formatPrice(order.total_amount || 0, order.currency)}</strong></div>)}</div>
}
