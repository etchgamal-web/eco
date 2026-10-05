'use client'

import Link from 'next/link'
import AnnouncementBar from '@/shared/layout/AnnouncementBar'
import SiteHeader from '@/shared/layout/SiteHeader'
import SiteFooter from '@/shared/layout/SiteFooter'
import { useCart } from '@/features/cart/store'
import { formatPrice } from '@/core/i18n/formatters'

export default function CartPage() {
  const { cart, itemCount, removeItem, updateQuantity, clearCart } = useCart()
  const total = cart.items.reduce((sum, item) => sum + item.price * item.quantity, 0)

  return (
    <main>
      <AnnouncementBar />
      <SiteHeader />
      <div className="cart-page-shell">
        <div className="section-heading"><div><p className="kicker">مراجعة اختياراتك</p><h1>السلة</h1></div><span className="cart-count-label">{itemCount} منتجات</span></div>
        {cart.items.length === 0 ? (
          <div className="state-card"><strong>السلة فارغة</strong><p>أضف منتجات من المجموعة لتظهر هنا.</p><Link className="primary-button" href="/#products">تصفح المنتجات</Link></div>
        ) : (
          <div className="cart-layout">
            <div className="cart-items">
              {cart.items.map((item) => (
                <article className="cart-item" key={`${item.productId}-${item.variantId ?? 'base'}`}>
                  <div><p className="cart-item-category">{item.variantName || 'اختيار إيكو'}</p><h2>{item.name}</h2><strong>{formatPrice(item.price, item.currency)}</strong></div>
                  <div className="cart-item-actions"><div className="quantity-stepper"><button type="button" onClick={() => updateQuantity(item.productId, item.quantity - 1, item.variantId)} aria-label="تقليل الكمية">−</button><span>{item.quantity}</span><button type="button" onClick={() => updateQuantity(item.productId, item.quantity + 1, item.variantId)} aria-label="زيادة الكمية">+</button></div><button className="remove-button" type="button" onClick={() => removeItem(item.productId, item.variantId)}>حذف</button></div>
                </article>
              ))}
            </div>
            <aside className="cart-summary"><h2>ملخص الطلب</h2><div><span>الإجمالي</span><strong>{formatPrice(total, cart.items[0]?.currency)}</strong></div><button className="primary-button checkout-button" type="button">متابعة الدفع</button><button className="clear-cart-button" type="button" onClick={clearCart}>تفريغ السلة</button></aside>
          </div>
        )}
      </div>
      <SiteFooter />
    </main>
  )
}
