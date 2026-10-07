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
  const variants = product.variants ?? []
  const variantPrices = variants
    .map((variant) => variant.price)
    .filter((price): price is number => typeof price === 'number' && Number.isFinite(price))
  const uniquePrices = [...new Set(variantPrices)].sort((a, b) => a - b)
  const hasStock = variants.length === 0 || variants.some((variant) => variant.stock === undefined || (variant.stock ?? 0) > 0)
  const productUrl = `${env.siteUrl}/products/${product.slug ?? slug}`
  const baseOffer = {
    url: productUrl,
    priceCurrency: product.currency || 'EGP',
    availability: `https://schema.org/${hasStock ? 'InStock' : 'OutOfStock'}`,
    seller: { '@type': 'Organization', name: siteConfig.name },
  }
  const offers = uniquePrices.length > 1
    ? {
        '@type': 'AggregateOffer',
        ...baseOffer,
        lowPrice: uniquePrices[0],
        highPrice: uniquePrices[uniquePrices.length - 1],
        offerCount: variants.length,
      }
    : {
        '@type': 'Offer',
        ...baseOffer,
        price: uniquePrices[0] ?? product.price,
      }

  const data = {
    '@context': 'https://schema.org',
    '@type': 'Product',
    name: product.name,
    description,
    url: productUrl,
    ...(images?.length ? { image: images } : {}),
    ...(product.brand?.name ? { brand: { '@type': 'Brand', name: product.brand.name } } : {}),
    ...(product.category?.name ? { category: product.category.name } : {}),
    offers,
  }

  return <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(data) }} />
}
