import Link from 'next/link'
import { notFound } from 'next/navigation'
import { ApiError } from '@/core/http/client'
import { getProduct } from '@/src/lib/api/catalog'
import AnnouncementBar from '@/shared/layout/AnnouncementBar'
import SiteHeader from '@/shared/layout/SiteHeader'
import SiteFooter from '@/shared/layout/SiteFooter'
import ProductDetails from '@/features/catalog/components/ProductDetails'

type ProductPageProps = {
  params: Promise<{ slug: string }>
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
      </div>
      <SiteFooter />
    </main>
  )
}
