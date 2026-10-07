import type { Product } from '@/domain/catalog/product'
import { env } from '@/core/config/env'
import { siteConfig } from '@/core/config/site'

type ProductStructuredDataProps = {
  product: Product
  slug: string
}

function absoluteUrl(value: string) {
  try {
    return new URL(value, env.siteUrl).toString()
  } catch {
    return value
  }
}

export default function ProductStructuredData({ product, slug }: ProductStructuredDataProps) {
  const media = product.media?.length ? product.media : product.images
  const images = media?.map((item) => absoluteUrl(item.url)).filter(Boolean)
  const description = product.description?.trim() || `اشترِ ${product.name} من متجر ${siteConfig.name}.`
  const hasStock = product.variants?.some((variant) => variant.stock === undefined || (variant.stock ?? 0) > 0) ?? true
  const productUrl = `${env.siteUrl}/products/${product.slug ?? slug}`

  const data = {
    '@context': 'https://schema.org',
    '@type': 'Product',
    name: product.name,
    description,
    url: productUrl,
    ...(images?.length ? { image: images } : {}),
    ...(product.brand?.name ? { brand: { '@type': 'Brand', name: product.brand.name } } : {}),
    ...(product.category?.name ? { category: product.category.name } : {}),
    offers: {
      '@type': 'Offer',
      url: productUrl,
      priceCurrency: product.currency || 'EGP',
      price: product.price,
      availability: `https://schema.org/${hasStock ? 'InStock' : 'OutOfStock'}`,
      seller: { '@type': 'Organization', name: siteConfig.name },
    },
  }

  return <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(data) }} />
}
