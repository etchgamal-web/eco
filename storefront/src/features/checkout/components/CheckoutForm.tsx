'use client'

import { FormEvent, useEffect, useMemo, useState } from 'react'
import { useRouter } from 'next/navigation'
import Link from 'next/link'
import { useAuth } from '@/features/auth/auth-context'
import { useCart } from '@/features/cart/store'
import { checkout, CheckoutPreviewStaleError, getCheckoutSettings, getCustomerAddresses, getShippingMethods, previewCheckout, type CheckoutPreview, type CustomerAddress, type GuestCheckoutDetails, type ShippingMethod } from '@/infrastructure/api/customer-api'
import { formatPrice } from '@/core/i18n/formatters'

const emptyGuest: GuestCheckoutDetails = { name: '', email: '', phone: '', address_line1: '', address_line2: '', city: '', state: '', postal_code: '', country: 'EG' }

export default function CheckoutForm() {
  const router = useRouter()
  const { customer, loading: authLoading } = useAuth()
  const { cart, clearCart } = useCart()
  const [allowGuest, setAllowGuest] = useState(false)
  const [settingsLoading, setSettingsLoading] = useState(true)
  const [addresses, setAddresses] = useState<CustomerAddress[]>([])
  const [shippingMethods, setShippingMethods] = useState<ShippingMethod[]>([])
  const [shippingMethodId, setShippingMethodId] = useState('')
  const [addressId, setAddressId] = useState('')
  const [guest, setGuest] = useState<GuestCheckoutDetails>(emptyGuest)
  const [couponCode, setCouponCode] = useState('')
  const [paymentMethod, setPaymentMethod] = useState<'cash_on_delivery' | 'paymob' | 'kashier'>('cash_on_delivery')
  const [loading, setLoading] = useState(true)
  const [previewLoading, setPreviewLoading] = useState(false)
  const [preview, setPreview] = useState<CheckoutPreview | null>(null)
  const [previewError, setPreviewError] = useState('')
  const [submitting, setSubmitting] = useState(false)
  const [error, setError] = useState('')

  const isGuest = !customer && allowGuest
  const guestValid = guest.name.trim() && guest.phone.trim() && guest.address_line1.trim() && guest.city.trim()
  const localGuestTotal = useMemo(() => cart.items.reduce((total, item) => total + item.price * item.quantity, 0), [cart.items])

  useEffect(() => {
    void getCheckoutSettings().then((settings) => setAllowGuest(!settings.require_authentication)).catch(() => setAllowGuest(false)).finally(() => setSettingsLoading(false))
  }, [])

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    if (!customer) { setLoading(false); return }
    void Promise.all([getCustomerAddresses(), getShippingMethods()])
      .then(([nextAddresses, nextShipping]) => { setAddresses(nextAddresses); setShippingMethods(nextShipping); setAddressId(String(nextAddresses.find((address) => address.is_default)?.id || nextAddresses[0]?.id || '')); setShippingMethodId(String(nextShipping[0]?.id || '')) })
      .catch((reason) => setError(reason instanceof Error ? reason.message : 'تعذر تحميل خيارات الدفع والشحن'))
      .finally(() => setLoading(false))
  }, [customer])

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    if (!customer || loading || !addressId || (shippingMethods.length > 0 && !shippingMethodId) || cart.items.length === 0) { setPreview(null); setPreviewLoading(false); return }
    let cancelled = false
    setPreviewLoading(true); setPreviewError('')
    void previewCheckout({ address_id: Number(addressId), shipping_method_id: shippingMethodId ? Number(shippingMethodId) : undefined, currency: cart.items[0]?.currency || 'EGP', coupon_code: couponCode.trim() || undefined })
      .then((nextPreview) => { if (!cancelled) setPreview(nextPreview) })
      .catch((reason) => { if (!cancelled) { setPreview(null); setPreviewError(reason instanceof Error ? reason.message : 'تعذر حساب إجمالي الطلب من الخادم') } })
      .finally(() => { if (!cancelled) setPreviewLoading(false) })
    return () => { cancelled = true }
  }, [customer, loading, addressId, shippingMethodId, shippingMethods.length, cart.items, couponCode])

  const submit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    if (!customer && !isGuest) { setError('يجب تسجيل الدخول لإتمام الطلب حسب إعدادات المتجر.'); return }
    if (customer && !preview) return
    if (isGuest && !guestValid) { setError('أكمل الاسم والهاتف والعنوان والمدينة لإتمام الطلب.'); return }
    setSubmitting(true); setError('')
    try {
      const key = typeof crypto !== 'undefined' && crypto.randomUUID ? crypto.randomUUID() : `checkout-${Date.now()}`
      const order = await checkout({
        address_id: customer ? Number(addressId) : undefined,
        shipping_method_id: shippingMethodId ? Number(shippingMethodId) : undefined,
        currency: cart.items[0]?.currency || 'EGP', payment_method: paymentMethod,
        idempotency_key: key, payment_idempotency_key: `${key}-payment`, coupon_code: customer ? couponCode.trim() || undefined : undefined,
        preview_token: customer ? preview?.preview_token : undefined,
        items: isGuest ? cart.items.map((item) => ({ product_id: item.productId, variant_id: item.variantId, quantity: item.quantity })) : undefined,
        guest: isGuest ? guest : undefined,
      })
      clearCart(); router.push(`/checkout/success?orderId=${order.id}`)
    } catch (reason) {
      if (reason instanceof CheckoutPreviewStaleError) { setPreview(reason.preview); setError('تغيّر السعر أو تفاصيل الطلب منذ المعاينة. راجع الإجمالي المحدّث واضغط «تأكيد الطلب» مرة أخرى.'); return }
      setError(reason instanceof Error ? reason.message : 'تعذر إنشاء الطلب')
    } finally { setSubmitting(false) }
  }

  if (authLoading || settingsLoading || loading) return <div className="state-card">جارٍ تجهيز Checkout...</div>
  if (!customer && !allowGuest) return <div className="state-card">سجّل الدخول لإتمام الطلب حسب إعدادات المتجر. <Link href="/account/login">تسجيل الدخول</Link></div>
  if (cart.items.length === 0) return <div className="state-card">السلة فارغة. أضف منتجات قبل إتمام الطلب.</div>
  const paymentMethods = isGuest ? (['cash_on_delivery'] as const) : (['cash_on_delivery', 'paymob', 'kashier'] as const)

  return <form className="checkout-form" onSubmit={submit}>
    {isGuest ? <div className="checkout-section"><h2>بيانات العميل والتوصيل</h2><p className="muted-text">يمكنك إتمام الطلب دون إنشاء حساب.</p><input required placeholder="الاسم الكامل" value={guest.name} onChange={(event) => setGuest({ ...guest, name: event.target.value })} /><input required placeholder="رقم الهاتف" value={guest.phone} onChange={(event) => setGuest({ ...guest, phone: event.target.value })} /><input type="email" placeholder="البريد الإلكتروني (اختياري)" value={guest.email} onChange={(event) => setGuest({ ...guest, email: event.target.value })} /><input required placeholder="العنوان" value={guest.address_line1} onChange={(event) => setGuest({ ...guest, address_line1: event.target.value })} /><input placeholder="العنوان التفصيلي (اختياري)" value={guest.address_line2} onChange={(event) => setGuest({ ...guest, address_line2: event.target.value })} /><input required placeholder="المدينة" value={guest.city} onChange={(event) => setGuest({ ...guest, city: event.target.value })} /></div> : <div className="checkout-section"><h2>عنوان الشحن</h2>{addresses.length === 0 ? <p className="muted-text">أضف عنوانًا من <Link href="/account/addresses">صفحة العناوين</Link> أولًا.</p> : <select required value={addressId} onChange={(event) => setAddressId(event.target.value)}><option value="">اختر عنوانًا</option>{addresses.map((address) => <option key={address.id} value={address.id}>{address.recipient_name} — {address.city} — {address.address_line1}</option>)}</select>}</div>}
    <div className="checkout-section"><h2>طريقة الدفع</h2><div className="payment-options">{paymentMethods.map((method) => { const title = method === 'cash_on_delivery' ? 'الدفع عند الاستلام' : method === 'paymob' ? 'الدفع الإلكتروني — Paymob' : 'الدفع الإلكتروني — Kashier'; return <label key={method} className={`payment-option${paymentMethod === method ? ' is-selected' : ''}`}><input type="radio" name="payment" value={method} checked={paymentMethod === method} onChange={() => setPaymentMethod(method)} /><span className="payment-option-copy"><strong>{title}</strong><small>{method === 'cash_on_delivery' ? 'ادفع للمندوب عند وصول الطلب.' : 'ستنتقل إلى بوابة دفع آمنة.'}</small></span></label> })}</div></div>
    {!isGuest && <div className="checkout-section"><h2>طريقة الشحن</h2>{shippingMethods.length === 0 ? <p className="muted-text">سيتم تحديد طريقة الشحن حسب إعدادات المتجر.</p> : <div className="shipping-options">{shippingMethods.map((method) => <label key={method.id}><input type="radio" name="shipping" value={method.id} checked={shippingMethodId === String(method.id)} onChange={() => setShippingMethodId(String(method.id))} /> <span>{method.name || method.code}</span></label>)}</div>}</div>}
    {!isGuest && <div className="checkout-section"><h2>القسيمة</h2><input value={couponCode} onChange={(event) => setCouponCode(event.target.value)} placeholder="اختياري" aria-label="رمز القسيمة" /></div>}
    {error ? <p className="form-error" role="alert">{error}</p> : null}{previewError ? <p className="form-error" role="alert">{previewError}</p> : null}
    {previewLoading ? <p className="state-card" role="status">جارٍ حساب الإجمالي النهائي من الخادم...</p> : null}
    {preview ? <section className="checkout-invoice" aria-label="ملخص فاتورة الطلب"><header className="checkout-invoice-header"><div><p className="kicker">ملخص الطلب</p><h2>تفاصيل الفاتورة</h2></div><span className="checkout-invoice-tag">معاينة</span></header><div className="checkout-invoice-payable"><div><strong>المبلغ المطلوب عند الاستلام</strong><small>الإجمالي النهائي شامل الضريبة والشحن</small></div><strong className="checkout-invoice-amount">{formatPrice(preview.total_amount, preview.currency)}</strong></div></section> : isGuest ? <section className="checkout-invoice" aria-label="ملخص فاتورة الطلب"><div className="checkout-invoice-payable"><div><strong>إجمالي المنتجات</strong><small>سيتم احتساب الضريبة النهائية عند إنشاء الطلب</small></div><strong className="checkout-invoice-amount">{formatPrice(localGuestTotal, cart.items[0]?.currency || 'EGP')}</strong></div></section> : null}
    <button className="primary-button" disabled={submitting || previewLoading || (customer && !preview) || (!customer && !guestValid) || (!!customer && (!addressId || addresses.length === 0))} type="submit">{submitting ? 'جارٍ إنشاء الطلب...' : 'تأكيد الطلب'}</button>
  </form>
}
