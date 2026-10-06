import Link from 'next/link'
import AnnouncementBar from '@/shared/layout/AnnouncementBar'
import SiteHeader from '@/shared/layout/SiteHeader'
import SiteFooter from '@/shared/layout/SiteFooter'
import CheckoutPaymentStatus from '@/features/checkout/components/CheckoutPaymentStatus'

export default async function CheckoutSuccessPage({ searchParams }: { searchParams: Promise<{ orderId?: string }> }) {
  const { orderId } = await searchParams
  return <main><AnnouncementBar /><SiteHeader /><section className="checkout-result-shell"><div className="checkout-result-card"><p className="kicker">تم بنجاح</p><h1>تم استلام طلبك</h1><p>شكرًا لك. تم إنشاء الطلب وسيظهر في حسابك لمتابعة حالته.</p>{orderId ? <CheckoutPaymentStatus orderId={orderId} /> : null}{orderId ? <Link className="primary-button" href={`/account/orders/${orderId}`}>عرض تفاصيل الطلب</Link> : null}<Link className="secondary-button" href="/products">متابعة التسوق</Link></div></section><SiteFooter /></main>
}
