import Link from 'next/link'
import { redirect } from 'next/navigation'
import { listPublishedContent } from '@/features/content/api'
import type { ContentItem, ContentType } from '@/domain/content/content-item'
import type { ContentListResult } from '@/application/content/content-types'
import { CONTENT_PAGE_SIZE } from '@/features/content/pagination'
import AnnouncementBar from '@/shared/layout/AnnouncementBar'
import SiteHeader from '@/shared/layout/SiteHeader'
import SiteFooter from '@/shared/layout/SiteFooter'

const copy: Record<ContentType, { title: string; intro: string; otherType: ContentType; otherLabel: string }> = {
  article: {
    title: 'المقالات',
    intro: 'أفكار ومعلومات عملية تساعدك على اختيار ما يناسب بيتك وحياتك اليومية.',
    otherType: 'guide',
    otherLabel: 'تصفّح الأدلة',
  },
  guide: {
    title: 'الأدلة',
    intro: 'خطوات ونصائح واضحة للاستفادة من المنتجات والعناية بها واختيار الأنسب لك.',
    otherType: 'article',
    otherLabel: 'اقرأ المقالات',
  },
}

function routeFor(type: ContentType, slug: string): string {
  return `/${type === 'article' ? 'articles' : 'guides'}/${slug}`
}

function formatDate(value?: string | null): string | null {
  if (!value) return null
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? null : new Intl.DateTimeFormat('ar-EG', { dateStyle: 'long' }).format(date)
}

function ContentCard({ item }: { item: ContentItem }) {
  const href = routeFor(item.type, item.slug)
  const date = formatDate(item.published_at)
  return (
    <article className="content-card">
      {item.featured_image ? <Link className="content-card-image" href={href} tabIndex={-1} aria-hidden="true"><img src={item.featured_image} alt="" loading="lazy" /></Link> : null}
      <div className="content-card-copy">
        <div className="content-card-meta">
          <span>{item.type === 'article' ? 'مقال' : 'دليل'}</span>
          {date ? <time dateTime={item.published_at ?? undefined}>{date}</time> : null}
        </div>
        <h2><Link href={href}>{item.title}</Link></h2>
        {item.excerpt ? <p>{item.excerpt}</p> : null}
        <Link className="content-read-link" href={href}>اقرأ {item.type === 'article' ? 'المقال' : 'الدليل'} <span aria-hidden="true">←</span></Link>
      </div>
    </article>
  )
}

function pageHref(type: ContentType, page: number): string {
  return `/${type === 'article' ? 'articles' : 'guides'}?page=${page}`
}

export default async function ContentListing({ type, page }: { type: ContentType; page: number }) {
  const pageCopy = copy[type]
  let result: ContentListResult | null = null
  let unavailable = false
  try {
    result = await listPublishedContent(type, { page, perPage: CONTENT_PAGE_SIZE })
  } catch {
    unavailable = true
  }
  if (result && page > result.lastPage) redirect(pageHref(type, result.lastPage))
  const items: ContentItem[] = result?.items ?? []

  return (
    <main>
      <AnnouncementBar />
      <SiteHeader />
      <section className="content-page-shell">
        <nav className="content-section-nav" aria-label="أقسام المحتوى">
          <Link className={type === 'article' ? 'is-current' : ''} href="/articles">المقالات</Link>
          <Link className={type === 'guide' ? 'is-current' : ''} href="/guides">الأدلة</Link>
          <Link href="/products">تصفّح المنتجات</Link>
        </nav>
        <header className="content-listing-heading">
          <p className="kicker">من إيكو</p>
          <h1>{pageCopy.title}</h1>
          <p>{pageCopy.intro}</p>
        </header>
        {unavailable ? (
          <div className="content-state-card" role="alert">
            <strong>تعذّر تحميل المحتوى الآن</strong>
            <p>تأكد من اتصال خدمة المحتوى ثم أعد المحاولة لاحقاً.</p>
          </div>
        ) : items.length === 0 ? (
          <div className="content-state-card">
            <strong>لا يوجد {type === 'article' ? 'مقالات' : 'أدلة'} منشورة حالياً</strong>
            <p>يمكنك تصفّح القسم الآخر أو العودة إلى منتجات المتجر.</p>
            <div className="content-inline-links"><Link href={`/${pageCopy.otherType === 'article' ? 'articles' : 'guides'}`}>{pageCopy.otherLabel}</Link><Link href="/products">عرض المنتجات</Link></div>
          </div>
        ) : (
          <div className="content-card-grid">{items.map((item) => <ContentCard item={item} key={item.id} />)}</div>
        )}
        {!unavailable && result && result.lastPage > 1 ? (
          <nav className="content-pagination" aria-label="التنقل بين صفحات المحتوى">
            {result.page > 1 ? <Link href={pageHref(type, result.page - 1)}>السابق</Link> : <span aria-disabled="true">السابق</span>}
            <span>صفحة {result.page} من {result.lastPage} · {result.total} محتوى</span>
            {result.page < result.lastPage ? <Link href={pageHref(type, result.page + 1)}>التالي</Link> : <span aria-disabled="true">التالي</span>}
          </nav>
        ) : null}
        <nav className="content-cross-links" aria-label="اكتشف المزيد">
          <Link href={`/${pageCopy.otherType === 'article' ? 'articles' : 'guides'}`}>{pageCopy.otherLabel}</Link>
          <Link href="/products">اكتشف منتجات إيكو</Link>
        </nav>
      </section>
      <SiteFooter />
    </main>
  )
}
