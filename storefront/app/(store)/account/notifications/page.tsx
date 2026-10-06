import AnnouncementBar from '@/shared/layout/AnnouncementBar'
import SiteHeader from '@/shared/layout/SiteHeader'
import SiteFooter from '@/shared/layout/SiteFooter'
import CustomerNotifications from '@/features/account/components/CustomerNotifications'

export default function NotificationsPage() {
  return <main><AnnouncementBar /><SiteHeader /><section className="account-page-shell"><div className="account-heading"><div><p className="kicker">حسابي</p><h1>الإشعارات</h1><p>آخر التحديثات المتعلقة بطلباتك وحسابك.</p></div></div><CustomerNotifications /></section><SiteFooter /></main>
}
