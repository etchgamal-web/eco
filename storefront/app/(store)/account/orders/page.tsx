import AnnouncementBar from '@/shared/layout/AnnouncementBar'
import SiteHeader from '@/shared/layout/SiteHeader'
import SiteFooter from '@/shared/layout/SiteFooter'
import CustomerOrders from '@/features/account/components/CustomerOrders'

export default function OrdersPage() {
  return <main><AnnouncementBar /><SiteHeader /><section className="account-page-shell"><div className="account-heading"><div><p className="kicker">حسابي</p><h1>طلباتي</h1><p>تابع حالة طلباتك السابقة.</p></div></div><CustomerOrders /></section><SiteFooter /></main>
}
