import AnnouncementBar from '@/shared/layout/AnnouncementBar'
import SiteHeader from '@/shared/layout/SiteHeader'
import SiteFooter from '@/shared/layout/SiteFooter'
import ProductListing from '@/features/catalog/components/ProductListing'

export default function ProductsPage() {
  return <main><AnnouncementBar /><SiteHeader /><ProductListing /><SiteFooter /></main>
}
