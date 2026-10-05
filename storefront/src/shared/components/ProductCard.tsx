import Link from 'next/link'
import type { Product } from '@/domain/catalog/product'
import { formatPrice } from '@/core/i18n/formatters'

type ProductCardProps = {
  product: Product
  href?: string
}

export default function ProductCard({ product, href }: ProductCardProps) {
  const productHref = href ?? `/products/${product.slug ?? product.id}`

  return (
    <Link className="product-card" href={productHref} aria-label={`عرض ${product.name}`}>
      <div className="product-image">
        <span>{product.category?.name || 'إيكو'}</span>
        <div className="product-shape" />
      </div>
      <div className="product-info">
        <p>{product.brand?.name || 'اختيار إيكو'}</p>
        <h3>{product.name}</h3>
        <strong>{formatPrice(product.price, product.currency)}</strong>
      </div>
    </Link>
  )
}
