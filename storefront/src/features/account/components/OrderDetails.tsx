'use client'

import { useEffect, useState } from 'react'
import { formatPrice } from '@/core/i18n/formatters'
import { getCustomerOrder, type CustomerOrderDetails } from '@/infrastructure/api/customer-api'

export default function OrderDetails({ orderId }: Readonly<{ orderId: number }>) {
  const [order, setOrder] = useState<CustomerOrderDetails | null>(null)
  const [error, setError] = useState('')
  useEffect(() => { void getCustomerOrder(orderId).then(setOrder).catch((reason) => setError(reason instanceof Error ? reason.message : 'تعذر تحميل تفاصيل الطلب')) }, [orderId])
  if (error) return <div className="state-card" role="alert">{error}</div>
  if (!order) return <div className="state-card">جارٍ تحميل تفاصيل الطلب...</div>
  return <div className="order-details"><div className="order-detail-summary"><span>رقم الطلب</span><strong>#{order.order_number || order.id}</strong><span>الحالة</span><strong>{order.status || 'قيد المعالجة'}</strong><span>الإجمالي</span><strong>{formatPrice(order.total_amount || 0, order.currency)}</strong></div><h2>العناصر</h2>{order.items?.length ? <div className="account-list">{order.items.map((item) => <div key={item.id}><span>{item.product_name || 'منتج'}</span><span>الكمية: {item.quantity || 1}</span><strong>{formatPrice(item.total_amount || item.unit_price || 0, order.currency)}</strong></div>)}</div> : <p className="muted-text">لا توجد تفاصيل عناصر متاحة.</p>}</div>
}
