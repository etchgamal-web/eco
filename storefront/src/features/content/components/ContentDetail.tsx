import Link from 'next/link'
import type { ContentItem, ContentType } from '@/domain/content/content-item'
import { siteConfig } from '@/core/config/site'
import BreadcrumbStructuredData from '@/shared/seo/BreadcrumbStructuredData'
import ContentStructuredData from '@/shared/seo/ContentStructuredData'
import AnnouncementBar from '@/shared/layout/AnnouncementBar'
import SiteHeader from '@/shared/layout/SiteHeader'
import SiteFooter from '@/shared/layout/SiteFooter'

function formatDate(value?: string | null): string | null {
  if (!value) return null
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? null : new Intl.DateTimeFormat('ar-EG', { dateStyle: 'long' }).format(date)
}

function contentRoute(type: ContentType): string {
  return type === 'article' ? '/articles' : '/guides'
}

function paragraphs(body?: string | null): string[] {
  return (body ?? '').split(/\r?\n/).map((paragraph) => paragraph.trim()).filter(Boolean)
}

export default function ContentDetail({ item }: { item: ContentItem }) {
  const sectionPath = contentRoute(item.type)
  const sectionName = item.type === 'article' ? 'المقالات' : 'الأدلة'
  const otherPath = item.type === 'article' ? '/guides' : '/articles'
  const otherName = item.type === 'article' ? 'الأدلة' : 'المقالات'
  const date = formatDate(item.published_at)
  const bodyParagraphs = paragraphs(item.body)
  const hasRelated = Boolean(item.products?.length || item.categories?.length)
  const canonicalPath = item.canonical_url?.trim() || `${sectionPath}/${item.slug}`

  return (
    <main>
      <AnnouncementBar />
      <SiteHeader />
      <article className="content-detail-shell">
        <nav className="breadcrumb" aria-label="مسار التنقل">
          <Link href="/">الرئيسية</Link><span>/</span><Link href={sectionPath}>{sectionName}</Link><span>/</span><span>{item.title}</span>
        </nav>
        <header className="content-detail-heading">
          <p className="kicker">{item.type === 'article' ? 'مقال من إيكو' : 'دليل من إيكو'}</p>
          <h1>{item.title}</h1>
          <div className="content-detail-byline">
            {date ? <time dateTime={item.published_at ?? undefined}>نُشر في {date}</time> : null}
            {item.author?.name ? <span>بقلم {item.author.name}</span> : null}
          </div>
          {item.excerpt ? <p className="content-detail-excerpt">{item.excerpt}</p> : null}
        </header>
        {item.featured_image ? <figure className="content-featured-image"><img src={item.featured_image} alt={item.title} /><figcaption>{item.title}</figcaption></figure> : null}
        <div className="content-detail-layout">
          <div className="content-body">
            {bodyParagraphs.length ? bodyParagraphs.map((paragraph, index) => <p key={`${index}-${paragraph.slice(0, 24)}`}>{paragraph}</p>) : <p className="content-muted">لا يتوفر نص إضافي لهذا المحتوى.</p>}
          </div>
          <aside className="content-related" aria-label="روابط مفيدة">
            <h2>روابط مفيدة</h2>
            {hasRelated ? (
              <>
                {item.products?.length ? <div><h3>منتجات مرتبطة</h3><ul>{item.products.map((product) => <li key={product.id}><Link href={`/products/${product.slug || product.id}`}>{product.name || 'عرض المنتج'}</Link></li>)}</ul></div> : null}
                {item.categories?.length ? <div><h3>تصنيفات ذات صلة</h3><ul>{item.categories.filter((category) => category.slug).map((category) => <li key={category.id}><Link href={`/categories/${category.slug}`}>{category.name || 'عرض التصنيف'}</Link></li>)}</ul></div> : null}
              </>
            ) : <p className="content-muted">استكشف المزيد من محتوى إيكو وروابطه إلى منتجات المتجر.</p>}
            <div className="content-related-footer">
              <Link href={otherPath}>اكتشف {otherName}</Link>
              <Link href="/products">تصفّح المنتجات</Link>
            </div>
          </aside>
        </div>
        <div className="content-detail-backlinks">
          <Link href={sectionPath}>العودة إلى {sectionName}</Link>
          <Link href={otherPath}>اقرأ {otherName}</Link>
        </div>
        <ContentStructuredData item={item} />
        <BreadcrumbStructuredData items={[{ name: 'الرئيسية', path: '/' }, { name: sectionName, path: sectionPath }, { name: item.title, path: canonicalPath }]} />
      </article>
      <SiteFooter />
      <span className="sr-only">{siteConfig.name} — {item.title}</span>
    </main>
  )
}
