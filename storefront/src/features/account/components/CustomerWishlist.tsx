'use client'

import Link from 'next/link'
import { useCallback, useEffect, useState } from 'react'
import { formatPrice } from '@/core/i18n/formatters'
import { useAuth } from '@/features/auth/auth-context'
import { getWishlist, removeFromWishlist, type WishlistItem } from '@/infrastructure/api/customer-api'
import ProductImage from '@/shared/components/ProductImage'
import './wishlist.css'

function HeartIcon({ filled = false }: { filled?: boolean }) {
  return (
    <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill={filled ? 'currentColor' : 'none'}>
      <path d="M20.8 8.8c0 5.1-8.8 10.1-8.8 10.1S3.2 13.9 3.2 8.8a4.7 4.7 0 0 1 8.8-2.3 4.7 4.7 0 0 1 8.8 2.3Z" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  )
}

function TrashIcon() {
  return (
    <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none">
      <path d="M4.5 7h15M9 7V4.5h6V7m2.8 0-.8 13H7L6.2 7M10 10.5v6m4-6v6" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  )
}

function WishlistLoading() {
  return (
    <div className="customer-wishlist-grid customer-wishlist-grid-loading" aria-label="جارٍ تحميل المفضلة" aria-busy="true">
      {Array.from({ length: 3 }, (_, index) => (
        <article className="customer-wishlist-skeleton" key={index} aria-hidden="true">
          <div className="customer-wishlist-skeleton-image" />
          <div className="customer-wishlist-skeleton-copy"><span /><span /><span /></div>
        </article>
      ))}
    </div>
  )
}

function WishlistState({
  title,
  description,
  children,
  variant = 'empty',
}: {
  title: string
  description: string
  children?: React.ReactNode
  variant?: 'empty' | 'guest' | 'error'
}) {
  return (
    <section className={`customer-wishlist-state is-${variant}`}>
      <div className="customer-wishlist-state-mark"><HeartIcon filled={variant !== 'error'} /></div>
      <div className="customer-wishlist-state-copy">
        <p className="kicker">{variant === 'guest' ? 'قائمتك الخاصة' : variant === 'error' ? 'حدثت مشكلة' : 'مساحة لاختياراتك'}</p>
        <h2>{title}</h2>
        <p>{description}</p>
        {children}
      </div>
    </section>
  )
}

export default function CustomerWishlist() {
  const { customer, loading: authLoading } = useAuth()
  const [items, setItems] = useState<WishlistItem[]>([])
  const [loading, setLoading] = useState(true)
  const [loadError, setLoadError] = useState(false)
  const [removingId, setRemovingId] = useState<number | null>(null)
  const [notice, setNotice] = useState<{ type: 'success' | 'error'; text: string } | null>(null)

  const loadWishlist = useCallback(async () => {
    setLoading(true)
    setLoadError(false)
    try {
      setItems(await getWishlist())
    } catch {
      setLoadError(true)
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    if (authLoading || !customer) return
    let cancelled = false
    getWishlist()
      .then((nextItems) => {
        if (!cancelled) {
          setItems(nextItems)
          setLoadError(false)
        }
      })
      .catch(() => {
        if (!cancelled) setLoadError(true)
      })
      .finally(() => {
        if (!cancelled) setLoading(false)
      })

    return () => { cancelled = true }
  }, [authLoading, customer])

  const remove = async (item: WishlistItem) => {
    const productId = item.product_id ?? item.id
    setRemovingId(productId)
    setNotice(null)
    try {
      await removeFromWishlist(productId)
      setItems((current) => current.filter((entry) => entry.id !== item.id))
      setNotice({ type: 'success', text: 'تمت إزالة المنتج من المفضلة' })
    } catch {
      setNotice({ type: 'error', text: 'تعذر إزالة المنتج. حاول مرة أخرى.' })
    } finally {
      setRemovingId(null)
    }
  }

  if (authLoading || (customer && loading)) return <WishlistLoading />

  if (!customer) {
    return (
      <WishlistState
        variant="guest"
        title="احتفظ باختياراتك المفضلة"
        description="سجّل الدخول لتحفظ المنتجات التي أعجبتك وتجدها هنا كلما عدت إلى المتجر."
      >
        <div className="customer-wishlist-state-actions">
          <Link className="primary-button" href="/account/login">تسجيل الدخول <span aria-hidden="true">←</span></Link>
          <Link className="secondary-button" href="/account/register">إنشاء حساب</Link>
        </div>
      </WishlistState>
    )
  }

  if (loadError) {
    return (
      <WishlistState variant="error" title="لم نتمكن من تحميل قائمتك" description="تحقق من اتصالك ثم أعد المحاولة.">
        <button className="customer-wishlist-retry" type="button" onClick={() => void loadWishlist()}>إعادة المحاولة</button>
      </WishlistState>
    )
  }

  const count = new Intl.NumberFormat('ar-EG').format(items.length)

  return (
    <div className="customer-wishlist">
      <div className="customer-wishlist-toolbar">
        <div className="customer-wishlist-count">
          <span className="customer-wishlist-count-icon"><HeartIcon filled /></span>
          <div><strong>{count} {items.length === 1 ? 'منتج محفوظ' : 'منتجات محفوظة'}</strong><span>اختياراتك في مكان واحد</span></div>
        </div>
        <Link className="customer-wishlist-continue" href="/products">متابعة التسوق <span aria-hidden="true">←</span></Link>
      </div>

      {notice && <p className={`customer-wishlist-notice is-${notice.type}`} role={notice.type === 'error' ? 'alert' : 'status'} aria-live="polite">{notice.text}</p>}

      {items.length === 0 ? (
        <WishlistState title="لم تحفظ أي منتج بعد" description="تصفّح المتجر واضغط على رمز القلب بجوار أي منتج لتحفظه هنا.">
          <Link className="primary-button" href="/products">اكتشف المنتجات <span aria-hidden="true">←</span></Link>
        </WishlistState>
      ) : (
        <div className="customer-wishlist-grid">
          {items.map((item) => {
            const productId = item.product_id ?? item.id
            const productName = item.product?.name || `منتج #${productId}`
            const productHref = `/products/${item.product?.slug || productId}`
            const image = item.product?.media?.[0]

            return (
              <article className="customer-wishlist-card" key={item.id}>
                <Link className="customer-wishlist-media" href={productHref} aria-label={`عرض ${productName}`}>
                  <ProductImage src={image?.url} alt={image?.alt || productName} sizes="(max-width: 640px) 100vw, (max-width: 980px) 50vw, 33vw" className="customer-wishlist-image" />
                  <span className="customer-wishlist-saved"><HeartIcon filled /> محفوظ في المفضلة</span>
                </Link>
                <div className="customer-wishlist-content">
                  <span className="customer-wishlist-category">{item.product?.category?.name || 'اختيار إيكو'}</span>
                  <h2><Link href={productHref}>{productName}</Link></h2>
                  {typeof item.product?.price === 'number' && <strong className="customer-wishlist-price">{formatPrice(item.product.price, item.product.currency)}</strong>}
                  <div className="customer-wishlist-actions">
                    <Link className="customer-wishlist-view" href={productHref}>عرض التفاصيل <span aria-hidden="true">←</span></Link>
                    <button
                      className="customer-wishlist-remove"
                      type="button"
                      onClick={() => void remove(item)}
                      disabled={removingId === productId}
                      aria-label={`إزالة ${productName} من المفضلة`}
                    >
                      <TrashIcon />
                      <span>{removingId === productId ? 'جارٍ الإزالة' : 'إزالة'}</span>
                    </button>
                  </div>
                </div>
              </article>
            )
          })}
        </div>
      )}
    </div>
  )
}
