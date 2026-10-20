import type { Product } from '@/domain/catalog/product'
import ProductCard from '@/shared/components/ProductCard'
import { Icon } from '@/shared/components/Icon'

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
        <Icon name="alert" size={36} className="state-icon is-danger" />
        <strong>{error}</strong>
        <p>تأكد من تشغيل Laravel API وضبط NEXT_PUBLIC_API_URL.</p>
        {onRetry ? <button className="secondary-button" type="button" onClick={onRetry}><Icon name="refresh" size={18} />إعادة المحاولة</button> : null}
      </div>
    )
  }

  if (products.length === 0) {
    return (
      <div className="state-card">
        <Icon name="inbox" size={36} className="state-icon" />
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
