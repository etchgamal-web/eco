'use client'

import { useMemo, useState } from 'react'
import type { Product, ProductVariant } from '@/domain/catalog/product'
import { formatPrice } from '@/core/i18n/formatters'
import { useCart } from '@/features/cart/store'
import ProductImage from '@/shared/components/ProductImage'
import { Icon } from '@/shared/components/Icon'

type ProductDetailsProps = { product: Product }
type AttributeGroup = { id: number; name: string; values: Array<{ id: number; value: string }> }

function variantPrice(product: Product, variant?: ProductVariant) {
  return variant?.price ?? product.price
}

function variantAttributes(variant?: ProductVariant) {
  return Object.fromEntries((variant?.attribute_values ?? []).map((item) => [String(item.attribute?.id ?? item.id), item.value]))
}

export default function ProductDetails({ product }: ProductDetailsProps) {
  const variants = useMemo(() => product.variants ?? [], [product.variants])
  const firstVariant = variants[0]
  const [selectedVariantId, setSelectedVariantId] = useState<number | undefined>(firstVariant?.id)
  const [selectedValues, setSelectedValues] = useState<Record<string, string>>(() => variantAttributes(firstVariant))
  const [quantity, setQuantity] = useState(1)
  const [added, setAdded] = useState(false)
  const { addItem } = useCart()
  const selectedVariant = variants.find((variant) => variant.id === selectedVariantId) ?? firstVariant
  const media = selectedVariant?.media?.length ? selectedVariant.media : product.media?.length ? product.media : product.images
  const mainImage = media?.[0]
  const price = useMemo(() => variantPrice(product, selectedVariant), [product, selectedVariant])
  const maxQuantity = selectedVariant?.stock && selectedVariant.stock > 0 ? selectedVariant.stock : 99

  const attributeGroups = useMemo<AttributeGroup[]>(() => {
    const groups = new Map<number, AttributeGroup>()
    for (const variant of variants) {
      for (const item of variant.attribute_values ?? []) {
        const attributeId = item.attribute?.id ?? item.id
        const current = groups.get(attributeId) ?? { id: attributeId, name: item.attribute?.name || 'اختيار', values: [] }
        if (!current.values.some((value) => value.id === item.id)) current.values.push({ id: item.id, value: item.value })
        groups.set(attributeId, current)
      }
    }
    return [...groups.values()]
  }, [variants])

  const changeQuantity = (delta: number) => {
    setQuantity((current) => Math.min(maxQuantity, Math.max(1, current + delta)))
    setAdded(false)
  }

  const chooseAttribute = (attributeId: number, value: string) => {
    const next = { ...selectedValues, [String(attributeId)]: value }
    const matching = variants.find((variant) => {
      const attributes = variantAttributes(variant)
      return Object.entries(next).every(([key, selected]) => !selected || attributes[key] === selected)
    }) ?? variants.find((variant) => variantAttributes(variant)[String(attributeId)] === value)
    setSelectedValues(matching ? variantAttributes(matching) : next)
    setSelectedVariantId(matching?.id)
    setQuantity(1)
    setAdded(false)
  }

  return (
    <article className="product-details">
      <div className="product-gallery">
        <ProductImage src={mainImage?.url} alt={mainImage?.alt || product.name} sizes="(max-width: 800px) 100vw, 55vw" className="product-detail-image" />
        {media && media.length > 1 ? <div className="product-thumbnails" aria-label="صور المنتج">{media.slice(0, 4).map((item) => <ProductImage key={item.id ?? item.url} src={item.url} alt={item.alt || product.name} sizes="72px" className="product-thumbnail" />)}</div> : null}
      </div>

      <div className="product-detail-copy">
        <p className="kicker">{product.category?.name || 'اختيار إيكو'}</p>
        <h1>{product.name}</h1>
        <p className="product-detail-price">{formatPrice(price, product.currency)}</p>
        <p className="product-description">{product.description || 'منتج مختار بعناية ليجمع بين الاستخدام اليومي والتصميم الهادئ.'}</p>

        {attributeGroups.map((group) => <fieldset className="variant-picker" key={group.id}><legend>{group.name}</legend><div>{group.values.map((value) => <button className={selectedValues[String(group.id)] === value.value ? 'variant-option selected' : 'variant-option'} key={value.id} type="button" onClick={() => chooseAttribute(group.id, value.value)}>{value.value}</button>)}</div></fieldset>)}

        <div className="purchase-row">
          <div className="quantity-stepper" aria-label="الكمية"><button type="button" onClick={() => changeQuantity(-1)} aria-label="تقليل الكمية"><Icon name="minus" size={18} /></button><span aria-live="polite">{quantity}</span><button type="button" onClick={() => changeQuantity(1)} aria-label="زيادة الكمية"><Icon name="plus" size={18} /></button></div>
          <button className={`primary-button add-to-cart-button${added ? ' is-added' : ''}`} type="button" disabled={selectedVariant?.stock === 0} onClick={() => { addItem({ productId: product.id, name: product.name, slug: product.slug, price, currency: product.currency, quantity, variantId: selectedVariant?.id, variantName: selectedVariant?.sku || undefined }); setAdded(true) }}><Icon name={added ? 'check' : 'cart'} size={20} key={added ? 'done' : 'cart'} />{added ? 'تمت الإضافة' : 'أضف إلى السلة'}</button>
        </div>
        {selectedVariant?.stock === 0 ? <p className="stock-note">هذا الاختيار غير متاح حاليًا.</p> : <p className="stock-note"><Icon name="truck" size={16} />شحن مجاني للطلبات فوق 1,500 جنيه داخل مصر.</p>}
      </div>
    </article>
  )
}
