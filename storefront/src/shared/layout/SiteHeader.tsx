'use client'

import './site-header.css'
import { useEffect, useRef, useState } from 'react'
import type { MouseEvent } from 'react'
import Link from 'next/link'
import { usePathname } from 'next/navigation'
import { useCart } from '@/features/cart/store'
import { useAuth } from '@/features/auth/auth-context'
import { Icon } from '@/shared/components/Icon'
import type { IconName } from '@/shared/components/Icon'

function DrawerLink({ href, icon, label, active, badge }: { href: string; icon: IconName; label: string; active?: boolean; badge?: number }) {
  return (
    <li>
      <Link className="app-drawer-link" href={href} aria-current={active ? 'page' : undefined}>
        <Icon name={icon} />
        <span>{label}</span>
        {badge ? <span className="app-drawer-badge" key={badge}>{badge}</span> : null}
      </Link>
    </li>
  )
}

export default function SiteHeader() {
  const { itemCount } = useCart()
  const { customer, loading, logout } = useAuth()
  const path = usePathname() ?? ''
  // القائمة مفتوحة فقط على المسار اللي اتفتحت فيه، فبتتقفل لوحدها عند أي تنقل.
  const [openPath, setOpenPath] = useState<string | null>(null)
  const menuOpen = openPath === path
  const closeButtonRef = useRef<HTMLButtonElement>(null)

  const displayName = customer?.name?.trim() || customer?.email?.split('@')[0] || 'حسابي'
  const avatarInitial = displayName.slice(0, 1).toLocaleUpperCase('ar')
  const isActive = (href: string) => (href === '/' ? path === '/' : path === href || path.startsWith(`${href}/`))

  useEffect(() => {
    if (!menuOpen) return
    const previousOverflow = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    closeButtonRef.current?.focus()
    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') setOpenPath(null)
    }
    document.addEventListener('keydown', onKeyDown)
    return () => {
      document.body.style.overflow = previousOverflow
      document.removeEventListener('keydown', onKeyDown)
    }
  }, [menuOpen])

  const onDrawerClick = (event: MouseEvent<HTMLElement>) => {
    if ((event.target as HTMLElement).closest('a')) setOpenPath(null)
  }

  const tabs: { href: string; label: string; icon: IconName; active: boolean; badge?: number }[] = [
    { href: '/', label: 'الرئيسية', icon: 'home', active: path === '/' },
    { href: '/products', label: 'المنتجات', icon: 'grid', active: isActive('/products') || isActive('/categories') || isActive('/brands') },
    { href: '/cart', label: 'السلة', icon: 'cart', active: isActive('/cart') || isActive('/checkout'), badge: itemCount > 0 ? itemCount : undefined },
    { href: customer ? '/account' : '/account/login', label: customer ? 'حسابي' : 'دخول', icon: 'user', active: isActive('/account') },
  ]

  return (
    <>
      <nav className="site-nav" aria-label="التنقل الرئيسي">
        <button
          className="app-menu-button"
          type="button"
          aria-expanded={menuOpen}
          aria-controls="app-drawer"
          aria-label="فتح القائمة"
          onClick={() => setOpenPath(path)}
        >
          <span />
          <span />
          <span />
        </button>
        <Link className="brand" href="/">إيكو<span>.</span></Link>
        <span className="app-header-spacer" aria-hidden="true" />
        <div className="nav-links">
          <Link href="/products">المنتجات</Link>
          <Link href="/#story">قصتنا</Link>
          <Link href="/#contact">تواصل معنا</Link>
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
              <Link className="account-link" href="/account/wishlist"><Icon name="heart" size={16} />المفضلة</Link>
              <button className="account-link" type="button" onClick={() => void logout()}><Icon name="logout" size={16} />خروج</button>
            </>
          ) : (
            <Link className="account-link" href="/account/login"><Icon name="user" size={16} />دخول</Link>
          )}
          <Link className="cart-button" href="/cart" aria-label="السلة"><Icon name="cart" size={18} />السلة <span key={itemCount}>{itemCount}</span></Link>
        </div>
      </nav>

      <div className={`app-drawer-backdrop${menuOpen ? ' is-open' : ''}`} onClick={() => setOpenPath(null)} aria-hidden="true" />
      <aside
        className={`app-drawer${menuOpen ? ' is-open' : ''}`}
        id="app-drawer"
        role="dialog"
        aria-modal="true"
        aria-label="القائمة الرئيسية"
        aria-hidden={!menuOpen}
        onClick={onDrawerClick}
      >
        <div className="app-drawer-head">
          <Link className="brand" href="/">إيكو<span>.</span></Link>
          <button className="app-drawer-close" type="button" aria-label="إغلاق القائمة" onClick={() => setOpenPath(null)} ref={closeButtonRef}>
            <Icon name="close" />
          </button>
        </div>

        {loading ? null : customer ? (
          <Link className="app-drawer-user" href="/account">
            <span className="account-avatar" aria-hidden="true">{avatarInitial}</span>
            <span className="app-drawer-user-copy">
              <strong>{displayName}</strong>
              <small>عرض حسابي وطلباتي</small>
            </span>
          </Link>
        ) : (
          <div className="app-drawer-user is-guest">
            <strong>أهلاً بك في إيكو</strong>
            <small>سجّل دخولك لمتابعة طلباتك وحفظ مفضلتك.</small>
            <Link className="primary-button" href="/account/login">تسجيل الدخول</Link>
          </div>
        )}

        <ul className="app-drawer-list">
          <DrawerLink href="/" icon="home" label="الرئيسية" active={path === '/'} />
          <DrawerLink href="/products" icon="grid" label="المنتجات" active={isActive('/products')} />
          <DrawerLink href="/cart" icon="cart" label="السلة" active={isActive('/cart')} badge={itemCount > 0 ? itemCount : undefined} />
          {customer ? <DrawerLink href="/account/wishlist" icon="heart" label="المفضلة" active={isActive('/account/wishlist')} /> : null}
        </ul>

        <ul className="app-drawer-list">
          <li className="app-drawer-label">اكتشف</li>
          <DrawerLink href="/articles" icon="article" label="المقالات" active={isActive('/articles')} />
          <DrawerLink href="/guides" icon="book" label="الأدلة" active={isActive('/guides')} />
          <DrawerLink href="/#story" icon="info" label="قصتنا" />
          <DrawerLink href="/#contact" icon="chat" label="تواصل معنا" />
        </ul>

        {customer ? (
          <div className="app-drawer-footer">
            <button className="app-drawer-logout" type="button" onClick={() => { setOpenPath(null); void logout() }}>
              <Icon name="logout" />
              تسجيل الخروج
            </button>
          </div>
        ) : null}
      </aside>

      <nav className="app-tabbar" aria-label="التنقل السريع">
        {tabs.map((tab) => (
          <Link key={tab.label} className="app-tab" href={tab.href} aria-current={tab.active ? 'page' : undefined}>
            <Icon name={tab.icon} />
            <span>{tab.label}</span>
            {tab.badge ? <span className="app-tab-badge" key={tab.badge}>{tab.badge}</span> : null}
          </Link>
        ))}
      </nav>
    </>
  )
}
