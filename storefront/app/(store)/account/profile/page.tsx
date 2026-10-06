import AnnouncementBar from '@/shared/layout/AnnouncementBar'
import SiteHeader from '@/shared/layout/SiteHeader'
import SiteFooter from '@/shared/layout/SiteFooter'
import ProfileForm from '@/features/account/components/ProfileForm'

export default function ProfilePage() {
  return <main><AnnouncementBar /><SiteHeader /><section className="account-page-shell"><div className="account-heading"><div><p className="kicker">حسابي</p><h1>بياناتي</h1><p>حدّث بيانات التواصل الخاصة بك.</p></div></div><ProfileForm /></section><SiteFooter /></main>
}
