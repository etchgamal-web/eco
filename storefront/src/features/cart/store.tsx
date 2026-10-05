'use client'

import { createContext, useContext, useEffect, useMemo, useState } from 'react'
import type { Cart, CartItem } from '@/domain/cart/cart'
import { cartItemCount } from '@/domain/cart/cart'
import { syncCartAfterAuthentication } from './api'

const STORAGE_KEY = 'eco-cart'
const emptyCart: Cart = { items: [] }

type CartContextValue = {
  cart: Cart
  itemCount: number
  addItem: (item: CartItem) => void
  removeItem: (productId: number, variantId?: number) => void
  updateQuantity: (productId: number, quantity: number, variantId?: number) => void
  clearCart: () => void
  syncAfterAuthentication: () => Promise<void>
}

const CartContext = createContext<CartContextValue | null>(null)

function sameItem(left: CartItem, right: Pick<CartItem, 'productId' | 'variantId'>) {
  return left.productId === right.productId && left.variantId === right.variantId
}

export function CartProvider({ children }: Readonly<{ children: React.ReactNode }>) {
  const [cart, setCart] = useState<Cart>(emptyCart)

  // Hydrate only in the browser so the server and initial client render match.
  useEffect(() => {
    try {
      const saved = window.localStorage.getItem(STORAGE_KEY)
      // eslint-disable-next-line react-hooks/set-state-in-effect
      if (saved) setCart(JSON.parse(saved) as Cart)
    } catch {
      window.localStorage.removeItem(STORAGE_KEY)
    }
  }, [])

  useEffect(() => {
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(cart))
  }, [cart])

  const value = useMemo<CartContextValue>(() => ({
    cart,
    itemCount: cartItemCount(cart),
    addItem: (item) => setCart((current) => {
      const existing = current.items.find((currentItem) => sameItem(currentItem, item))
      if (existing) {
        return { items: current.items.map((currentItem) => sameItem(currentItem, item) ? { ...currentItem, quantity: currentItem.quantity + item.quantity } : currentItem) }
      }
      return { items: [...current.items, item] }
    }),
    removeItem: (productId, variantId) => setCart((current) => ({ items: current.items.filter((item) => !sameItem(item, { productId, variantId })) })),
    updateQuantity: (productId, quantity, variantId) => setCart((current) => ({ items: current.items.map((item) => sameItem(item, { productId, variantId }) ? { ...item, quantity: Math.max(1, quantity) } : item) })),
    clearCart: () => setCart(emptyCart),
    syncAfterAuthentication: async () => {
      await syncCartAfterAuthentication(cart.items)
      setCart(emptyCart)
    },
  }), [cart])

  return <CartContext.Provider value={value}>{children}</CartContext.Provider>
}

export function useCart() {
  const context = useContext(CartContext)
  if (!context) throw new Error('useCart must be used inside CartProvider')
  return context
}
