'use client'

import { useEffect, useState } from 'react'
import { formatPrice } from '@/core/i18n/formatters'
import { getCustomerOrder, type CustomerOrderDetails } from '@/infrastructure/api/customer-api'

function paymentLabel(status?: string, method?: string) {
  if (method === 'cash_on_delivery' && ['pending', 'processing', 'initiating'].includes(status ?? '')) {
    return 'الدفع عند الاستلام — سيتم تحصيل المبلغ عند تسليم الطلب.'
  }
  if (status === 'paid' || status === 'confirmed') return 'تم تأكيد الدفع بنجاح.'
  if (status === 'failed') return 'فشل الدفع، يمكنك إعادة المحاولة من تفاصيل الطلب.'
  if (status === 'provider_created') return 'تم تجهيز جلسة الدفع وننتظر تأكيد بوابة الدفع.'
  return 'الدفع قيد المعالجة.'
}

function paymentStatusLabel(status?: string, method?: string) {
  if (method === 'cash_on_delivery' && ['pending', 'processing', 'initiating'].includes(status ?? '')) return 'الدفع عند الاستلام'
  if (status === 'paid' || status === 'confirmed') return 'تم التأكيد'
  if (status === 'failed') return 'فشل الدفع'
  if (status === 'provider_created') return 'بانتظار البوابة'
  if (status === 'refunded') return 'تم الاسترداد'
  return 'قيد المعالجة'
}

function methodLabel(method?: string) {
  if (method === 'cash_on_delivery') return 'الدفع عند الاستلام'
  if (method === 'paymob') return 'الدفع الإلكتروني — Paymob'
  if (method === 'kashier') return 'الدفع الإلكتروني — Kashier'
  return method || '—'
}

export default function CheckoutPaymentStatus({ orderId }: { orderId: string }) {
  const [order, setOrder] = useState<CustomerOrderDetails | null>(null)
  const [error, setError] = useState('')

  useEffect(() => {
    let cancelled = false
    let attempts = 0
    const load = async () => {
      try {
        const nextOrder = await getCustomerOrder(Number(orderId))
        if (cancelled) return
        setOrder(nextOrder)
        const payment = nextOrder.payments?.[0]
        const status = payment?.status
        if (payment?.checkout_url && status === 'provider_created') {
          window.location.assign(payment.checkout_url)
          return
        }
        if (status && !['pending', 'processing', 'initiating', 'provider_created'].includes(status)) return
        if (attempts < 5) {
          attempts += 1
          window.setTimeout(() => void load(), 3000)
        }
      } catch (reason) {
        if (!cancelled) setError(reason instanceof Error ? reason.message : 'تعذر قراءة حالة الدفع')
      }
    }
    void load()
    return () => { cancelled = true }
  }, [orderId])

  if (error) return <p className="muted-text">{error}</p>
  if (!order) return <p className="muted-text">جارٍ قراءة حالة الدفع...</p>

  const payment = order.payments?.[0]
  const currentStatus = paymentStatusLabel(payment?.status, payment?.method)
  return <section className="checkout-payment-status" aria-label="إيصال الدفع">
    <header className="checkout-payment-status-header">
      <div><p className="kicker">تفاصيل الطلب</p><h2>إيصال الدفع</h2></div>
      <span className="checkout-payment-status-badge" data-state={payment?.status ?? 'pending'}>{currentStatus}</span>
    </header>
    <div className="checkout-payment-amount"><span>قيمة الطلب</span><strong>{formatPrice(order.total_amount || 0, order.currency)}</strong></div>
    <dl className="checkout-payment-meta">
      <div><dt>رقم الطلب</dt><dd dir="auto">{order.order_number || `#${order.id}`}</dd></div>
      <div><dt>طريقة الدفع</dt><dd>{methodLabel(payment?.method)}</dd></div>
      {payment?.provider_reference ? <div className="checkout-payment-reference"><dt>مرجع الدفع</dt><dd dir="auto">{payment.provider_reference}</dd></div> : null}
      <div><dt>حالة العملية</dt><dd>{currentStatus}</dd></div>
    </dl>
    <p className="checkout-payment-message">{paymentLabel(payment?.status, payment?.method)}</p>
    {payment?.checkout_url ? <a className="primary-button checkout-payment-cta" href={payment.checkout_url}>متابعة الدفع الآمن</a> : null}
    {!payment ? <p className="muted-text">الدفع عند الاستلام — سيتم تأكيده عند التسليم.</p> : null}
  </section>
}
