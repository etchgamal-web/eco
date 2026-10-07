import Link from 'next/link'
import type { Metadata } from 'next'

export const metadata: Metadata = {
  title: 'الصفحة غير موجودة',
  robots: { index: false, follow: false },
}

export default function NotFound() {
  return (
    <main className="route-state-shell">
      <div className="state-card" role="status">
        <strong>الصفحة غير موجودة</strong>
        <p>الرابط الذي فتحته غير متاح أو تم نقله.</p>
        <Link className="primary-button" href="/">العودة للمتجر</Link>
      </div>
    </main>
  )
}
