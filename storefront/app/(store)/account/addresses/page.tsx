import AnnouncementBar from '@/shared/layout/AnnouncementBar'
import SiteHeader from '@/shared/layout/SiteHeader'
import SiteFooter from '@/shared/layout/SiteFooter'
import CustomerAddresses from '@/features/account/components/CustomerAddresses'

export default function AddressesPage() {
  return <main><AnnouncementBar /><SiteHeader /><section className="account-page-shell"><div className="account-heading"><div><p className="kicker">حسابي</p><h1>عناوين الشحن</h1><p>أدر عناوينك لاستخدامها في الطلبات القادمة.</p></div></div><CustomerAddresses /></section><SiteFooter /></main>
}
