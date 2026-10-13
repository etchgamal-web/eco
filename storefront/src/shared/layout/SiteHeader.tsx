'use client'

import Link from 'next/link'
import { useCart } from '@/features/cart/store'
import { useAuth } from '@/features/auth/auth-context'

export default function SiteHeader() {
  const { itemCount } = useCart()
  const { customer, loading, logout } = useAuth()
  const displayName = customer?.name?.trim() || customer?.email?.split('@')[0] || 'حسابي'
  const avatarInitial = displayName.slice(0, 1).toLocaleUpperCase('ar')

  return (
    <nav className="site-nav" aria-label="التنقل الرئيسي">
      <Link className="brand" href="/">إيكو<span>.</span></Link>
      <div className="nav-links">
        <Link href="/products">المنتجات</Link>
        <a href="#story">قصتنا</a>
        <a href="#contact">تواصل معنا</a>
      </div>
      <div className="header-actions">
        {loading ? (
          <span className="account-loading" aria-live="polite">جارٍ التحقق...</span>
        ) : customer ? (
          <>
            <Link className="account-link account-profile-link" href="/account" aria-label={`حساب ${displayName}`}>
              <span className="account-avatar" aria-hidden="true">{avatarInitial}</span>
              <span className="account-display-name">{displayName}</span>
            </Link>
            <Link className="account-link" href="/account/wishlist">المفضلة</Link>
            <button className="account-link" type="button" onClick={() => void logout()}>خروج</button>
          </>
        ) : (
          <Link className="account-link" href="/account/login">دخول</Link>
        )}
        <Link className="cart-button" href="/cart" aria-label="السلة">السلة <span>{itemCount}</span></Link>
      </div>
    </nav>
  )
}
