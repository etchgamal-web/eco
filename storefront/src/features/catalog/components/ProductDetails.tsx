'use client'

import { useMemo, useState } from 'react'
import type { Product, ProductVariant } from '@/domain/catalog/product'
import { formatPrice } from '@/core/i18n/formatters'
import { useCart } from '@/features/cart/store'

type ProductDetailsProps = {
  product: Product
}

function variantPrice(product: Product, variant?: ProductVariant) {
  return variant?.price ?? product.price
}

export default function ProductDetails({ product }: ProductDetailsProps) {
  const variants = product.variants ?? []
  const [selectedVariantId, setSelectedVariantId] = useState<number | undefined>(variants[0]?.id)
  const [quantity, setQuantity] = useState(1)
  const [added, setAdded] = useState(false)
  const { addItem } = useCart()
  const selectedVariant = variants.find((variant) => variant.id === selectedVariantId)
  const media = product.media?.length ? product.media : product.images
  const price = useMemo(() => variantPrice(product, selectedVariant), [product, selectedVariant])
  const maxQuantity = selectedVariant?.stock && selectedVariant.stock > 0 ? selectedVariant.stock : 99

  const changeQuantity = (delta: number) => {
    setQuantity((current) => Math.min(maxQuantity, Math.max(1, current + delta)))
    setAdded(false)
  }

  return (
    <article className="product-details">
      <div className="product-gallery">
        <div
          className={`product-detail-image${media?.[0]?.url ? ' has-image' : ''}`}
          role="img"
          aria-label={media?.[0]?.alt || product.name}
          style={media?.[0]?.url ? { backgroundImage: `url(${media[0].url})` } : undefined}
        >
          {!media?.[0]?.url ? <div className="product-shape" /> : null}
        </div>
        {media && media.length > 1 ? (
          <div className="product-thumbnails" aria-label="صور المنتج">
            {media.slice(0, 4).map((item) => <span key={item.id ?? item.url} style={{ backgroundImage: `url(${item.url})` }} />)}
          </div>
        ) : null}
      </div>

      <div className="product-detail-copy">
        <p className="kicker">{product.category?.name || 'اختيار إيكو'}</p>
        <h1>{product.name}</h1>
        <p className="product-detail-price">{formatPrice(price, product.currency)}</p>
        {product.description ? <p className="product-description">{product.description}</p> : <p className="product-description">منتج مختار بعناية ليجمع بين الاستخدام اليومي والتصميم الهادئ.</p>}

        {variants.length > 0 ? (
          <fieldset className="variant-picker">
            <legend>اختيارات المنتج</legend>
            <div>
              {variants.map((variant) => (
                <button className={variant.id === selectedVariantId ? 'variant-option selected' : 'variant-option'} key={variant.id} type="button" onClick={() => { setSelectedVariantId(variant.id); setAdded(false) }}>
                  {variant.name || variant.sku || `اختيار ${variant.id}`}
                </button>
              ))}
            </div>
          </fieldset>
        ) : null}

        <div className="purchase-row">
          <div className="quantity-stepper" aria-label="الكمية">
            <button type="button" onClick={() => changeQuantity(-1)} aria-label="تقليل الكمية">−</button>
            <span aria-live="polite">{quantity}</span>
            <button type="button" onClick={() => changeQuantity(1)} aria-label="زيادة الكمية">+</button>
          </div>
          <button className="primary-button add-to-cart-button" type="button" disabled={selectedVariant?.stock === 0} onClick={() => { addItem({ productId: product.id, name: product.name, slug: product.slug, price, currency: product.currency, quantity, variantId: selectedVariant?.id, variantName: selectedVariant?.name || selectedVariant?.sku || undefined }); setAdded(true) }}>
            {added ? 'تمت الإضافة' : 'أضف إلى السلة'}
          </button>
        </div>
        {selectedVariant?.stock === 0 ? <p className="stock-note">هذا الاختيار غير متاح حاليًا.</p> : <p className="stock-note">شحن مجاني للطلبات فوق 1,500 جنيه داخل مصر.</p>}
      </div>
    </article>
  )
}
