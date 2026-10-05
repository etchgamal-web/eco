import type { Product } from '@/domain/catalog/product'
import ProductCard from '@/shared/components/ProductCard'

type ProductGridProps = {
  products: Product[]
  loading?: boolean
  error?: string
  onRetry?: () => void
  searchTerm?: string
}

function LoadingGrid() {
  return (
    <div className="product-grid" aria-label="جاري تحميل المنتجات" aria-busy="true">
      {[1, 2, 3, 4].map((item) => (
        <div className="skeleton-card" key={item}>
          <div />
          <span />
          <span />
        </div>
      ))}
    </div>
  )
}

export default function ProductGrid({ products, loading = false, error, onRetry, searchTerm = '' }: ProductGridProps) {
  if (loading) return <LoadingGrid />

  if (error) {
    return (
      <div className="state-card" role="alert">
        <strong>{error}</strong>
        <p>تأكد من تشغيل Laravel API وضبط NEXT_PUBLIC_API_URL.</p>
        {onRetry ? <button className="secondary-button" type="button" onClick={onRetry}>إعادة المحاولة</button> : null}
      </div>
    )
  }

  if (products.length === 0) {
    return (
      <div className="state-card">
        <strong>لا توجد منتجات مطابقة</strong>
        <p>{searchTerm ? 'جرّب البحث بكلمة أخرى.' : 'ستظهر المنتجات هنا عند توفرها.'}</p>
      </div>
    )
  }

  return (
    <div className="product-grid">
      {products.map((product) => <ProductCard key={product.id} product={product} />)}
    </div>
  )
}
