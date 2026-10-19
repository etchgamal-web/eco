import Link from 'next/link'
import type { Product } from '@/domain/catalog/product'
import { formatPrice } from '@/core/i18n/formatters'
import ProductImage from './ProductImage'
import WishlistButton from './WishlistButton'

type ProductCardProps = {
  product: Product
  href?: string
}

export default function ProductCard({ product, href }: ProductCardProps) {
  const productHref = href ?? `/products/${product.slug ?? product.id}`
  const image = product.media?.[0] ?? product.images?.[0]

  return (
    <div className="product-card">
      <Link className="product-card-link" href={productHref} aria-label={`عرض ${product.name}`}>
        <div className="product-image">
          <span>{product.category?.name || 'إيكو'}</span>
          <ProductImage src={image?.url} alt={image?.alt || product.name} sizes="(max-width: 800px) 50vw, 25vw" />
        </div>
        <div className="product-info">
          <p>{product.brand?.name || 'اختيار إيكو'}</p>
          <h3>{product.name}</h3>
          <strong>{formatPrice(product.price, product.currency)}</strong>
        </div>
      </Link>
      <WishlistButton productId={product.id} productName={product.name} />
    </div>
  )
}
