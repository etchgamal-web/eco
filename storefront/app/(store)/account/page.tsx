import AnnouncementBar from '@/shared/layout/AnnouncementBar'
import SiteHeader from '@/shared/layout/SiteHeader'
import SiteFooter from '@/shared/layout/SiteFooter'
import AccountOverview from '@/features/account/components/AccountOverview'

export default function AccountPage() {
  return <main><AnnouncementBar /><SiteHeader /><AccountOverview /><SiteFooter /></main>
}
