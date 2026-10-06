'use client'

import { FormEvent, useEffect, useState } from 'react'
import { useRouter } from 'next/navigation'
import Link from 'next/link'
import { useAuth } from '@/features/auth/auth-context'
import { useCart } from '@/features/cart/store'
import { checkout, CheckoutPreviewStaleError, getCustomerAddresses, getShippingMethods, previewCheckout, type CheckoutPreview, type CustomerAddress, type ShippingMethod } from '@/infrastructure/api/customer-api'
import { formatPrice } from '@/core/i18n/formatters'

export default function CheckoutForm() {
  const router = useRouter()
  const { customer, loading: authLoading } = useAuth()
  const { cart, clearCart } = useCart()
  const [addresses, setAddresses] = useState<CustomerAddress[]>([])
  const [shippingMethods, setShippingMethods] = useState<ShippingMethod[]>([])
  const [shippingMethodId, setShippingMethodId] = useState('')
  const [addressId, setAddressId] = useState('')
  const [couponCode, setCouponCode] = useState('')
  const [paymentMethod, setPaymentMethod] = useState<'cash_on_delivery' | 'paymob' | 'kashier'>('cash_on_delivery')
  const [loading, setLoading] = useState(true)
  const [previewLoading, setPreviewLoading] = useState(false)
  const [preview, setPreview] = useState<CheckoutPreview | null>(null)
  const [previewError, setPreviewError] = useState('')
  const [submitting, setSubmitting] = useState(false)
  const [error, setError] = useState('')

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    if (!customer) { setLoading(false); return }
    void Promise.all([getCustomerAddresses(), getShippingMethods()])
      .then(([nextAddresses, nextShipping]) => {
        setAddresses(nextAddresses)
        setShippingMethods(nextShipping)
        setAddressId(String(nextAddresses.find((address) => address.is_default)?.id || nextAddresses[0]?.id || ''))
        setShippingMethodId(String(nextShipping[0]?.id || ''))
      })
      .catch((reason) => setError(reason instanceof Error ? reason.message : 'تعذر تحميل خيارات الدفع والشحن'))
      .finally(() => setLoading(false))
  }, [customer])

  useEffect(() => {
    if (!customer || loading || !addressId || (shippingMethods.length > 0 && !shippingMethodId) || cart.items.length === 0) {
      // eslint-disable-next-line react-hooks/set-state-in-effect
      setPreview(null)
      setPreviewLoading(false)
      return
    }
    let cancelled = false
    setPreviewLoading(true)
    setPreviewError('')
    void previewCheckout({
      address_id: Number(addressId),
      shipping_method_id: shippingMethodId ? Number(shippingMethodId) : undefined,
      currency: cart.items[0]?.currency || 'EGP',
      coupon_code: couponCode.trim() || undefined,
    })
      .then((nextPreview) => { if (!cancelled) setPreview(nextPreview) })
      .catch((reason) => { if (!cancelled) { setPreview(null); setPreviewError(reason instanceof Error ? reason.message : 'تعذر حساب إجمالي الطلب من الخادم') } })
      .finally(() => { if (!cancelled) setPreviewLoading(false) })
    return () => { cancelled = true }
  }, [customer, loading, addressId, shippingMethodId, shippingMethods.length, cart.items, couponCode])

  const submit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    if (!preview) return
    setSubmitting(true)
    setError('')
    try {
      const key = typeof crypto !== 'undefined' && crypto.randomUUID ? crypto.randomUUID() : `checkout-${Date.now()}`
      const order = await checkout({
        address_id: Number(addressId),
        shipping_method_id: shippingMethodId ? Number(shippingMethodId) : undefined,
        currency: cart.items[0]?.currency || 'EGP',
        payment_method: paymentMethod,
        idempotency_key: key,
        payment_idempotency_key: `${key}-payment`,
        coupon_code: couponCode.trim() || undefined,
        preview_token: preview.preview_token,
      })
      clearCart()
      router.push(`/checkout/success?orderId=${order.id}`)
    } catch (reason) {
      if (reason instanceof CheckoutPreviewStaleError) {
        setPreview(reason.preview)
        setError('تغيّر السعر أو تفاصيل الطلب منذ المعاينة. راجع الإجمالي المحدّث واضغط «تأكيد الطلب» مرة أخرى.')
        return
      }
      setError(reason instanceof Error ? reason.message : 'تعذر إنشاء الطلب')
    } finally {
      setSubmitting(false)
    }
  }

  if (authLoading || loading) return <div className="state-card">جارٍ تجهيز Checkout...</div>
  if (!customer) return <div className="state-card">سجّل الدخول لإتمام الطلب.</div>
  if (cart.items.length === 0) return <div className="state-card">السلة فارغة. أضف منتجات قبل إتمام الطلب.</div>

  return <form className="checkout-form" onSubmit={submit}>
    <div className="checkout-section">
      <h2>عنوان الشحن</h2>
      {addresses.length === 0 ? <p className="muted-text">أضف عنوانًا من <Link href="/account/addresses">صفحة العناوين</Link> أولًا.</p> : <select required value={addressId} onChange={(event) => setAddressId(event.target.value)}><option value="">اختر عنوانًا</option>{addresses.map((address) => <option key={address.id} value={address.id}>{address.recipient_name} — {address.city} — {address.address_line1}</option>)}</select>}
    </div>
    <div className="checkout-section">
      <h2>طريقة الدفع</h2>
      <div className="payment-options">{(['cash_on_delivery', 'paymob', 'kashier'] as const).map((method) => <label key={method}><input type="radio" name="payment" value={method} checked={paymentMethod === method} onChange={() => setPaymentMethod(method)} /> {method === 'cash_on_delivery' ? 'الدفع عند الاستلام' : method === 'paymob' ? 'Paymob' : 'Kashier'}</label>)}</div>
    </div>
    <div className="checkout-section">
      <h2>طريقة الشحن</h2>
      {shippingMethods.length === 0 ? <p className="muted-text">سيتم تحديد طريقة الشحن حسب إعدادات المتجر.</p> : <div className="shipping-options">{shippingMethods.map((method) => <label key={method.id}><input type="radio" name="shipping" value={method.id} checked={shippingMethodId === String(method.id)} onChange={() => setShippingMethodId(String(method.id))} /> <span>{method.name || method.code}</span><strong>{formatPrice(method.base_fee || 0, method.currency || cart.items[0]?.currency)}</strong></label>)}</div>}
    </div>
    <div className="checkout-section">
      <h2>القسيمة</h2>
      <input value={couponCode} onChange={(event) => setCouponCode(event.target.value)} placeholder="اختياري" aria-label="رمز القسيمة" />
    </div>
    {error ? <p className="form-error" role="alert">{error} <Link href="/checkout/failure">صفحة إعادة المحاولة</Link></p> : null}
    {previewError ? <p className="form-error" role="alert">{previewError}</p> : null}
    {previewLoading ? <p className="state-card" role="status">جارٍ حساب الإجمالي النهائي من الخادم...</p> : null}
    {preview ? <div className="checkout-total"><span>إجمالي المنتجات</span><strong>{formatPrice(preview.subtotal_amount, preview.currency)}</strong><span>الخصم</span><strong>{formatPrice(preview.discount_amount, preview.currency)}</strong><span>الضريبة ({preview.tax_rate ?? 0}%)</span><strong>{formatPrice(preview.tax_amount, preview.currency)}</strong><span>الشحن</span><strong>{formatPrice(preview.shipping_amount, preview.currency)}</strong><span>الإجمالي النهائي المتوقع</span><strong>{formatPrice(preview.total_amount, preview.currency)}</strong><small className="muted-text">هذه معاينة فقط؛ سيعيد Laravel الحساب ويتحقق من السعر والمخزون مرة أخرى عند التأكيد.</small></div> : null}
    <button className="primary-button" disabled={submitting || previewLoading || !preview || !addressId || addresses.length === 0 || (shippingMethods.length > 0 && !shippingMethodId)} type="submit">{submitting ? 'جارٍ إنشاء الطلب...' : 'تأكيد الطلب'}</button>
  </form>
}
