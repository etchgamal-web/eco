import AnnouncementBar from '@/shared/layout/AnnouncementBar'
import SiteHeader from '@/shared/layout/SiteHeader'
import SiteFooter from '@/shared/layout/SiteFooter'
import CheckoutForm from '@/features/checkout/components/CheckoutForm'

export default function CheckoutPage() {
  return <main><AnnouncementBar /><SiteHeader /><section className="checkout-page-shell"><div className="section-heading"><div><p className="kicker">الخطوة الأخيرة</p><h1>إتمام الطلب</h1><p>راجع العنوان وطريقة الدفع قبل تأكيد طلبك.</p></div></div><CheckoutForm /></section><SiteFooter /></main>
}
