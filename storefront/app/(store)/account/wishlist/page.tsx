import AnnouncementBar from '@/shared/layout/AnnouncementBar'
import SiteHeader from '@/shared/layout/SiteHeader'
import SiteFooter from '@/shared/layout/SiteFooter'
import CustomerWishlist from '@/features/account/components/CustomerWishlist'

export default function WishlistPage() {
  return <main><AnnouncementBar /><SiteHeader /><section className="account-page-shell"><div className="account-heading"><div><p className="kicker">حسابي</p><h1>المفضلة</h1><p>احتفظ بالمنتجات التي ترغب بالعودة إليها.</p></div></div><CustomerWishlist /></section><SiteFooter /></main>
}
