import Link from 'next/link'
import AnnouncementBar from '@/shared/layout/AnnouncementBar'
import SiteHeader from '@/shared/layout/SiteHeader'
import SiteFooter from '@/shared/layout/SiteFooter'

export default function CheckoutFailurePage() {
  return <main><AnnouncementBar /><SiteHeader /><section className="checkout-result-shell"><div className="checkout-result-card is-failure"><p className="kicker">لم يكتمل الطلب</p><h1>تعذر إنشاء الطلب</h1><p>لم يتم خصم أي مبلغ من خلال هذه المحاولة. راجع السلة والعنوان ثم حاول مرة أخرى.</p><Link className="primary-button" href="/checkout">إعادة المحاولة</Link><Link className="secondary-button" href="/cart">العودة إلى السلة</Link></div></section><SiteFooter /></main>
}
