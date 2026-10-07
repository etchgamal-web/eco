import Link from 'next/link'
import type { Metadata } from 'next'
import { notFound } from 'next/navigation'
import { ApiError } from '@/core/http/client'
import { getProduct } from '@/features/catalog/api'
import AnnouncementBar from '@/shared/layout/AnnouncementBar'
import SiteHeader from '@/shared/layout/SiteHeader'
import SiteFooter from '@/shared/layout/SiteFooter'
import ProductDetails from '@/features/catalog/components/ProductDetails'
import ProductStructuredData from '@/shared/seo/ProductStructuredData'
import BreadcrumbStructuredData from '@/shared/seo/BreadcrumbStructuredData'
import { env } from '@/core/config/env'
import { siteConfig } from '@/core/config/site'

type ProductPageProps = {
  params: Promise<{ slug: string }>
}

export async function generateMetadata({ params }: ProductPageProps): Promise<Metadata> {
  const { slug } = await params
  try {
    const product = await getProduct(slug)
    const description = (product.description?.trim() || `اشترِ ${product.name} من متجر ${siteConfig.name}.`).slice(0, 160)
    const image = (product.media?.length ? product.media : product.images)?.[0]
    const canonical = `/products/${product.slug ?? slug}`

    return {
      title: product.name,
      description,
      alternates: { canonical },
      openGraph: {
        title: product.name,
        description,
        url: `${env.siteUrl}${canonical}`,
        siteName: siteConfig.name,
        type: 'website',
        ...(image ? { images: [{ url: image.url, alt: image.alt || product.name }] } : {}),
      },
      twitter: {
        card: image ? 'summary_large_image' : 'summary',
        title: product.name,
        description,
        ...(image ? { images: [image.url] } : {}),
      },
    }
  } catch {
    return { title: 'المنتج غير متاح', robots: { index: false, follow: false } }
  }
}

export default async function ProductPage({ params }: ProductPageProps) {
  const { slug } = await params
  let product
  let failed = false

  try {
    product = await getProduct(slug)
  } catch (reason) {
    if (reason instanceof ApiError && reason.status === 404) notFound()
    failed = true
  }

  if (failed || !product) {
    return (
      <main>
        <AnnouncementBar />
        <SiteHeader />
        <div className="product-page-shell"><div className="state-card" role="alert"><strong>تعذر تحميل المنتج</strong><p>تأكد من تشغيل Laravel API ثم حاول مرة أخرى.</p><Link className="secondary-button" href="/">العودة للمتجر</Link></div></div>
        <SiteFooter />
      </main>
    )
  }

  return (
    <main>
      <AnnouncementBar />
      <SiteHeader />
      <div className="product-page-shell">
        <nav className="breadcrumb" aria-label="مسار التنقل">
          <Link href="/">الرئيسية</Link><span>/</span><span>{product.name}</span>
        </nav>
        <ProductDetails product={product} />
        <ProductStructuredData product={product} slug={slug} />
        <BreadcrumbStructuredData items={[{ name: 'الرئيسية', path: '/' }, { name: 'المنتجات', path: '/products' }, { name: product.name, path: `/products/${product.slug ?? slug}` }]} />
      </div>
      <SiteFooter />
    </main>
  )
}
