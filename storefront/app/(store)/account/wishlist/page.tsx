import AnnouncementBar from '@/shared/layout/AnnouncementBar'
import SiteHeader from '@/shared/layout/SiteHeader'
import SiteFooter from '@/shared/layout/SiteFooter'
import CustomerWishlist from '@/features/account/components/CustomerWishlist'

export default function WishlistPage() {
  return (
    <main>
      <AnnouncementBar />
      <SiteHeader />
      <section className="account-page-shell wishlist-page-shell">
        <div className="wishlist-page-heading">
          <div className="wishlist-heading-copy">
            <p className="kicker">حسابي · اختياراتك</p>
            <h1>المفضلة</h1>
            <p>المنتجات التي أحببتها، محفوظة لك لتعود إليها في أي وقت.</p>
          </div>
          <div className="wishlist-heading-mark" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none">
              <path d="M20.8 8.8c0 5.1-8.8 10.1-8.8 10.1S3.2 13.9 3.2 8.8a4.7 4.7 0 0 1 8.8-2.3 4.7 4.7 0 0 1 8.8 2.3Z" stroke="currentColor" strokeWidth="1.4" strokeLinecap="round" strokeLinejoin="round" />
              <path d="M12 2.2v2.2M22 13h-2.2" stroke="currentColor" strokeWidth="1.4" strokeLinecap="round" />
            </svg>
          </div>
        </div>
        <CustomerWishlist />
      </section>
      <SiteFooter />
    </main>
  )
}
