'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import { useWishlist } from '@/features/wishlist/wishlist-store'
import './wishlist-button.css'

type WishlistButtonProps = { productId: number; productName: string }

export default function WishlistButton({ productId, productName }: WishlistButtonProps) {
  const router = useRouter()
  const { signedIn, authLoading, ready, has, toggle } = useWishlist()
  const [busy, setBusy] = useState(false)
  const [failed, setFailed] = useState(false)
  const active = signedIn && has(productId)

  const onClick = async () => {
    if (authLoading || busy) return
    if (!signedIn) { router.push('/account/login'); return }
    if (!ready) return
    setBusy(true)
    setFailed(false)
    try { await toggle(productId) } catch { setFailed(true) } finally { setBusy(false) }
  }

  const label = active ? `إزالة ${productName} من المفضلة` : `إضافة ${productName} إلى المفضلة`

  return (
    <button
      type="button"
      className={`wishlist-button${active ? ' is-active' : ''}${failed ? ' is-failed' : ''}`}
      aria-pressed={active}
      aria-label={label}
      title={failed ? 'تعذر تحديث المفضلة، حاول مرة أخرى' : label}
      disabled={busy}
      onClick={() => void onClick()}
    >
      <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">
        <path d="M12 20.5s-7.5-4.6-9.6-9.3C1 8 2.9 4.5 6.3 4.5c2 0 3.5 1 5.7 3.2 2.2-2.2 3.7-3.2 5.7-3.2 3.4 0 5.3 3.5 3.9 6.7-2.1 4.7-9.6 9.3-9.6 9.3z" fill={active ? 'currentColor' : 'none'} stroke="currentColor" strokeWidth="1.7" strokeLinejoin="round" />
      </svg>
    </button>
  )
}
