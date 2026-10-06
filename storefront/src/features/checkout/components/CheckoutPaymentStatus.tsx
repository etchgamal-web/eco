'use client'

import { useEffect, useState } from 'react'
import { formatPrice } from '@/core/i18n/formatters'
import { getCustomerOrder, type CustomerOrderDetails } from '@/infrastructure/api/customer-api'

function paymentLabel(status?: string) {
  if (status === 'paid' || status === 'confirmed') return 'تم تأكيد الدفع'
  if (status === 'failed') return 'فشل الدفع، يمكنك إعادة المحاولة من تفاصيل الطلب.'
  if (status === 'provider_created') return 'تم إنشاء جلسة الدفع وننتظر تأكيد البوابة.'
  return 'الدفع قيد المعالجة.'
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
        const status = nextOrder.payments?.[0]?.status
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
  return <div className="checkout-payment-status"><p><strong>الإجمالي النهائي:</strong> {formatPrice(order.total_amount || 0, order.currency)}</p>{payment ? <p><strong>حالة الدفع:</strong> {paymentLabel(payment.status)}</p> : <p className="muted-text">الدفع عند الاستلام — سيتم تأكيده عند التسليم.</p>}</div>
}
