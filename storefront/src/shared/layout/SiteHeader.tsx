'use client'

import { useEffect, useRef, useState } from 'react'
import Link from 'next/link'
import { useCart } from '@/features/cart/store'
import { useAuth } from '@/features/auth/auth-context'

export default function SiteHeader() {
  const { itemCount } = useCart()
  const { customer, loading, logout } = useAuth()
  const [menuOpen, setMenuOpen] = useState(false)
  const navRef = useRef<HTMLElement>(null)
  const displayName = customer?.name?.trim() || customer?.email?.split('@')[0] || 'حسابي'
  const avatarInitial = displayName.slice(0, 1).toLocaleUpperCase('ar')

  // قفل قايمة الموبايل بـ Escape أو بالضغط خارجها.
  useEffect(() => {
    if (!menuOpen) return
    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') setMenuOpen(false)
    }
    const onPointerDown = (event: PointerEvent) => {
      if (!navRef.current?.contains(event.target as Node)) setMenuOpen(false)
    }
    document.addEventListener('keydown', onKeyDown)
    document.addEventListener('pointerdown', onPointerDown)
    return () => {
      document.removeEventListener('keydown', onKeyDown)
      document.removeEventListener('pointerdown', onPointerDown)
    }
  }, [menuOpen])

  return (
    <nav className="site-nav" aria-label="التنقل الرئيسي" ref={navRef}>
      <button
        className="nav-toggle"
        type="button"
        aria-expanded={menuOpen}
        aria-controls="primary-nav-links"
        aria-label={menuOpen ? 'إغلاق القائمة' : 'فتح القائمة'}
        onClick={() => setMenuOpen((open) => !open)}
      >
        <span />
        <span />
        <span />
      </button>
      <Link className="brand" href="/">إيكو<span>.</span></Link>
      <div className={`nav-links${menuOpen ? ' is-open' : ''}`} id="primary-nav-links" onClick={() => setMenuOpen(false)}>
        <Link href="/products">المنتجات</Link>
        <Link href="/#story">قصتنا</Link>
        <Link href="/#contact">تواصل معنا</Link>
        {customer ? <Link className="nav-mobile-only" href="/account/wishlist">المفضلة</Link> : null}
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
            <Link className="account-link account-wishlist-link" href="/account/wishlist">المفضلة</Link>
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
