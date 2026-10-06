'use client'

import { FormEvent, useEffect, useState } from 'react'
import { useRouter } from 'next/navigation'
import Link from 'next/link'
import { useAuth } from '@/features/auth/auth-context'
import { useCart } from '@/features/cart/store'
import { checkout, getCustomerAddresses, getShippingMethods, type CustomerAddress, type ShippingMethod } from '@/infrastructure/api/customer-api'
import { formatPrice } from '@/core/i18n/formatters'

export default function CheckoutForm() {
  const router = useRouter()
  const { customer, loading: authLoading } = useAuth()
  const { cart, clearCart } = useCart()
  const [addresses, setAddresses] = useState<CustomerAddress[]>([])
  const [shippingMethods, setShippingMethods] = useState<ShippingMethod[]>([])
  const [shippingMethodId, setShippingMethodId] = useState('')
  const [addressId, setAddressId] = useState('')
  const [paymentMethod, setPaymentMethod] = useState<'cash_on_delivery' | 'paymob' | 'kashier'>('cash_on_delivery')
  const [loading, setLoading] = useState(true)
  const [submitting, setSubmitting] = useState(false)
  const [error, setError] = useState('')
  const total = cart.items.reduce((sum, item) => sum + item.price * item.quantity, 0)
  const selectedShipping = shippingMethods.find((method) => String(method.id) === shippingMethodId)
  const estimatedTotal = total + (selectedShipping?.base_fee || 0)

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    if (!customer) { setLoading(false); return }
    void Promise.all([getCustomerAddresses(), getShippingMethods()]).then(([nextAddresses, nextShipping]) => { setAddresses(nextAddresses); setShippingMethods(nextShipping); setAddressId(String(nextAddresses.find((address) => address.is_default)?.id || nextAddresses[0]?.id || '')); setShippingMethodId(String(nextShipping[0]?.id || '')) }).catch((reason) => setError(reason instanceof Error ? reason.message : 'تعذر تحميل خيارات الدفع والشحن')).finally(() => setLoading(false))
  }, [customer])

  const submit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault(); setSubmitting(true); setError('')
    try { const key = typeof crypto !== 'undefined' && crypto.randomUUID ? crypto.randomUUID() : `checkout-${Date.now()}`; const order = await checkout({ address_id: Number(addressId), shipping_method_id: shippingMethodId ? Number(shippingMethodId) : undefined, currency: cart.items[0]?.currency || 'EGP', payment_method: paymentMethod, idempotency_key: key, payment_idempotency_key: `${key}-payment` }); clearCart(); router.push(`/checkout/success?orderId=${order.id}`) } catch (reason) { setError(reason instanceof Error ? reason.message : 'تعذر إنشاء الطلب') } finally { setSubmitting(false) }
  }

  if (authLoading || loading) return <div className="state-card">جارٍ تجهيز Checkout...</div>
  if (!customer) return <div className="state-card">سجّل الدخول لإتمام الطلب.</div>
  if (cart.items.length === 0) return <div className="state-card">السلة فارغة. أضف منتجات قبل إتمام الطلب.</div>
  return <form className="checkout-form" onSubmit={submit}><div className="checkout-section"><h2>عنوان الشحن</h2>{addresses.length === 0 ? <p className="muted-text">أضف عنوانًا من <Link href="/account/addresses">صفحة العناوين</Link> أولًا.</p> : <select required value={addressId} onChange={(event) => setAddressId(event.target.value)}><option value="">اختر عنوانًا</option>{addresses.map((address) => <option key={address.id} value={address.id}>{address.recipient_name} — {address.city} — {address.address_line1}</option>)}</select>}</div><div className="checkout-section"><h2>طريقة الدفع</h2><div className="payment-options">{(['cash_on_delivery', 'paymob', 'kashier'] as const).map((method) => <label key={method}><input type="radio" name="payment" value={method} checked={paymentMethod === method} onChange={() => setPaymentMethod(method)} /> {method === 'cash_on_delivery' ? 'الدفع عند الاستلام' : method === 'paymob' ? 'Paymob' : 'Kashier'}</label>)}</div></div><div className="checkout-section"><h2>طريقة الشحن</h2>{shippingMethods.length === 0 ? <p className="muted-text">سيتم تحديد طريقة الشحن حسب إعدادات المتجر.</p> : <div className="shipping-options">{shippingMethods.map((method) => <label key={method.id}><input type="radio" name="shipping" value={method.id} checked={shippingMethodId === String(method.id)} onChange={() => setShippingMethodId(String(method.id))} /> <span>{method.name || method.code}</span><strong>{formatPrice(method.base_fee || 0, method.currency || cart.items[0]?.currency)}</strong></label>)}</div>}</div>{error ? <p className="form-error" role="alert">{error} <Link href="/checkout/failure">صفحة إعادة المحاولة</Link></p> : null}<div className="checkout-total"><span>إجمالي المنتجات</span><strong>{formatPrice(total, cart.items[0]?.currency)}</strong><span>الشحن</span><strong>{formatPrice(selectedShipping?.base_fee || 0, selectedShipping?.currency || cart.items[0]?.currency)}</strong><span>الإجمالي التقديري قبل الضريبة</span><strong>{formatPrice(estimatedTotal, cart.items[0]?.currency)}</strong><small className="muted-text">سيعيد الخادم احتساب الضريبة والإجمالي النهائي عند تأكيد الطلب.</small></div><button className="primary-button" disabled={submitting || !addressId || addresses.length === 0 || (shippingMethods.length > 0 && !shippingMethodId)} type="submit">{submitting ? 'جارٍ إنشاء الطلب...' : 'تأكيد الطلب'}</button></form>
}
