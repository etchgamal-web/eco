'use client'

import Link from 'next/link'
import { useEffect, useState } from 'react'
import { getWishlist, removeFromWishlist, type WishlistItem } from '@/infrastructure/api/customer-api'

export default function CustomerWishlist() {
  const [items, setItems] = useState<WishlistItem[]>([])
  const [loading, setLoading] = useState(true)
  const [message, setMessage] = useState('')
  useEffect(() => { void getWishlist().then(setItems).catch(() => setMessage('تعذر تحميل المفضلة')).finally(() => setLoading(false)) }, [])
  const remove = async (item: WishlistItem) => { try { await removeFromWishlist(item.product_id || item.id); setItems((current) => current.filter((entry) => entry.id !== item.id)); setMessage('تمت إزالة المنتج من المفضلة') } catch { setMessage('تعذر إزالة المنتج') } }
  if (loading) return <div className="state-card">جارٍ تحميل المفضلة...</div>
  if (message && items.length === 0) return <div className="state-card" role="alert">{message}</div>
  if (items.length === 0) return <div className="state-card">لم تضف منتجات إلى المفضلة بعد.</div>
  return <div className="wishlist-grid">{items.map((item) => <article className="wishlist-card" key={item.id}><div><strong>{item.product?.name || `منتج #${item.product_id || item.id}`}</strong><p>{item.product?.price ? `${item.product.price} ${item.product.currency || ''}` : ''}</p></div><div><Link href={`/products/${item.product?.slug || item.product_id || item.id}`}>عرض المنتج</Link><button className="text-button" type="button" onClick={() => void remove(item)}>إزالة</button></div></article>)}</div>
}
