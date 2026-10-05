 'use client'

import Link from 'next/link'
import { useCart } from '@/features/cart/store'
import { useAuth } from '@/features/auth/auth-context'

export default function SiteHeader() {
  const { itemCount } = useCart()
  const { customer, logout } = useAuth()

  return (
    <nav className="site-nav" aria-label="التنقل الرئيسي">
      <Link className="brand" href="/">إيكو<span>.</span></Link>
      <div className="nav-links">
        <Link href="/products">المنتجات</Link>
        <a href="#story">قصتنا</a>
        <a href="#contact">تواصل معنا</a>
      </div>
      <div className="header-actions">
        {customer ? <><Link className="account-link" href="/account">حسابي</Link><button className="account-link" type="button" onClick={() => void logout()}>خروج</button></> : <Link className="account-link" href="/account/login">دخول</Link>}
        <Link className="cart-button" href="/cart" aria-label="السلة">السلة <span>{itemCount}</span></Link>
      </div>
    </nav>
  )
}
