import AnnouncementBar from '@/shared/layout/AnnouncementBar'
import SiteHeader from '@/shared/layout/SiteHeader'
import SiteFooter from '@/shared/layout/SiteFooter'
import OrderDetails from '@/features/account/components/OrderDetails'

export default async function OrderPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params
  return <main><AnnouncementBar /><SiteHeader /><section className="account-page-shell"><div className="account-heading"><div><p className="kicker">حسابي</p><h1>تفاصيل الطلب</h1></div></div><OrderDetails orderId={Number(id)} /></section><SiteFooter /></main>
}
