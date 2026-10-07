import Link from 'next/link'
import { notFound } from 'next/navigation'
import { ApiError } from '@/core/http/client'
import { getPublishedContentBySlug } from '@/features/content/api'
import type { ContentItem, ContentType } from '@/domain/content/content-item'
import ContentDetail from '@/features/content/components/ContentDetail'
import AnnouncementBar from '@/shared/layout/AnnouncementBar'
import SiteHeader from '@/shared/layout/SiteHeader'
import SiteFooter from '@/shared/layout/SiteFooter'

export default async function ContentDetailRoute({ type, slug }: { type: ContentType; slug: string }) {
  let item: ContentItem | null = null
  try {
    item = await getPublishedContentBySlug(type, slug)
  } catch (error) {
    if (error instanceof ApiError && error.status === 404) notFound()
  }

  if (!item) {
    const contentLabel = type === 'article' ? 'المقال' : 'الدليل'
    return (
      <main>
        <AnnouncementBar />
        <SiteHeader />
        <section className="content-page-shell">
          <div className="content-state-card" role="alert">
            <strong>تعذّر تحميل {contentLabel}</strong>
            <p>تحقق من اتصال خدمة المحتوى ثم حاول مرة أخرى.</p>
            <Link href={type === 'article' ? '/articles' : '/guides'}>العودة إلى {type === 'article' ? 'المقالات' : 'الأدلة'}</Link>
          </div>
        </section>
        <SiteFooter />
      </main>
    )
  }

  return <ContentDetail item={item} />
}
