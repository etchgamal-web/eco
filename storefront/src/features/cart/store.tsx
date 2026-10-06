'use client'

import { createContext, useContext, useEffect, useMemo, useRef, useState } from 'react'
import type { Cart, CartItem } from '@/domain/cart/cart'
import { cartItemCount } from '@/domain/cart/cart'
import {
  addServerCartItem,
  clearServerCart,
  getServerCart,
  mergeLocalCart,
  removeServerCartItem,
  serverCartToLocalCart,
  updateServerCartItem,
  type ServerCart,
} from '@/infrastructure/api/cart-api'

const STORAGE_KEY = 'eco-cart'
const emptyCart: Cart = { items: [] }

type CartSyncStatus = 'idle' | 'syncing' | 'error'

type CartContextValue = {
  cart: Cart
  itemCount: number
  syncStatus: CartSyncStatus
  syncError: string
  addItem: (item: CartItem) => void
  removeItem: (productId: number, variantId?: number) => void
  updateQuantity: (productId: number, quantity: number, variantId?: number) => void
  clearCart: () => void
  setAuthenticated: (authenticated: boolean) => void
  syncAfterAuthentication: () => Promise<void>
  hydrateFromServer: () => Promise<void>
  retrySync: () => Promise<void>
}

const CartContext = createContext<CartContextValue | null>(null)

function sameItem(left: CartItem, right: Pick<CartItem, 'productId' | 'variantId'>) {
  return left.productId === right.productId && left.variantId === right.variantId
}

function replaceWithServerCart(serverCart: ServerCart, setCart: (cart: Cart) => void) {
  setCart(serverCartToLocalCart(serverCart))
}

export function CartProvider({ children }: Readonly<{ children: React.ReactNode }>) {
  const [cart, setCart] = useState<Cart>(emptyCart)
  const [authenticated, setAuthenticatedState] = useState(false)
  const [syncStatus, setSyncStatus] = useState<CartSyncStatus>('idle')
  const [syncError, setSyncError] = useState('')
  const cartRef = useRef<Cart>(emptyCart)
  const mutationQueue = useRef<Promise<void>>(Promise.resolve())
  const pendingMergeItems = useRef<CartItem[]>([])

  useEffect(() => {
    try {
      const saved = window.localStorage.getItem(STORAGE_KEY)
      if (!saved) return
      const savedCart = JSON.parse(saved) as Cart
      cartRef.current = savedCart
      // eslint-disable-next-line react-hooks/set-state-in-effect
      setCart(savedCart)
    } catch {
      window.localStorage.removeItem(STORAGE_KEY)
    }
  }, [])

  const updateCart = (nextCart: Cart) => {
    cartRef.current = nextCart
    setCart(nextCart)
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(nextCart))
  }

  const value = useMemo<CartContextValue>(() => {
    const queueMutation = (operation: () => Promise<ServerCart>) => {
      mutationQueue.current = mutationQueue.current.then(async () => {
        setSyncStatus('syncing')
        setSyncError('')
        try {
          replaceWithServerCart(await operation(), updateCart)
          setSyncStatus('idle')
        } catch (reason) {
          setSyncStatus('error')
          setSyncError(reason instanceof Error ? reason.message : 'تعذر مزامنة السلة')
        }
      })
    }

    return ({
    cart,
    itemCount: cartItemCount(cart),
    syncStatus,
    syncError,
    addItem: (item) => {
      const existing = cartRef.current.items.find((currentItem) => sameItem(currentItem, item))
      const nextCart = existing
        ? { items: cartRef.current.items.map((currentItem) => sameItem(currentItem, item) ? { ...currentItem, quantity: currentItem.quantity + item.quantity } : currentItem) }
        : { items: [...cartRef.current.items, item] }
      updateCart(nextCart)
      if (authenticated) queueMutation(() => addServerCartItem(item))
    },
    removeItem: (productId, variantId) => {
      updateCart({ items: cartRef.current.items.filter((item) => !sameItem(item, { productId, variantId })) })
      if (authenticated) queueMutation(() => removeServerCartItem(productId, variantId))
    },
    updateQuantity: (productId, quantity, variantId) => {
      const nextQuantity = Math.max(1, quantity)
      const item = cartRef.current.items.find((currentItem) => sameItem(currentItem, { productId, variantId }))
      updateCart({ items: cartRef.current.items.map((currentItem) => sameItem(currentItem, { productId, variantId }) ? { ...currentItem, quantity: nextQuantity } : currentItem) })
      if (authenticated && item) queueMutation(() => updateServerCartItem({ ...item, quantity: nextQuantity }))
    },
    clearCart: () => {
      updateCart(emptyCart)
      if (authenticated) queueMutation(clearServerCart)
    },
    setAuthenticated: (nextAuthenticated) => {
      setAuthenticatedState(nextAuthenticated)
      if (!nextAuthenticated) {
        setSyncStatus('idle')
        setSyncError('')
        pendingMergeItems.current = []
      }
    },
    syncAfterAuthentication: async () => {
      const localItems = pendingMergeItems.current.length > 0 ? pendingMergeItems.current : cartRef.current.items
      setSyncStatus('syncing')
      setSyncError('')
      try {
        const serverCart = await mergeLocalCart(localItems)
        pendingMergeItems.current = []
        replaceWithServerCart(serverCart, updateCart)
        setSyncStatus('idle')
      } catch (reason) {
        if (reason instanceof Error && 'remainingItems' in reason) {
          pendingMergeItems.current = (reason as Error & { remainingItems: CartItem[] }).remainingItems
        }
        setSyncStatus('error')
        setSyncError(reason instanceof Error ? reason.message : 'تعذر دمج السلة المحلية')
      }
    },
    hydrateFromServer: async () => {
      setSyncStatus('syncing')
      setSyncError('')
      try {
        replaceWithServerCart(await getServerCart(), updateCart)
        setSyncStatus('idle')
      } catch (reason) {
        setSyncStatus('error')
        setSyncError(reason instanceof Error ? reason.message : 'تعذر جلب السلة البعيدة')
      }
    },
    retrySync: async () => {
      if (authenticated) {
        const localItems = pendingMergeItems.current.length > 0 ? pendingMergeItems.current : cartRef.current.items
        setSyncStatus('syncing')
        try {
          const serverCart = await mergeLocalCart(localItems)
          pendingMergeItems.current = []
          replaceWithServerCart(serverCart, updateCart)
          setSyncStatus('idle')
          setSyncError('')
        } catch (reason) {
          if (reason instanceof Error && 'remainingItems' in reason) {
            pendingMergeItems.current = (reason as Error & { remainingItems: CartItem[] }).remainingItems
          }
          setSyncStatus('error')
          setSyncError(reason instanceof Error ? reason.message : 'تعذر إعادة مزامنة السلة')
        }
      }
    },
    })
  }, [authenticated, cart, syncError, syncStatus])

  return <CartContext.Provider value={value}>{children}</CartContext.Provider>
}

export function useCart() {
  const context = useContext(CartContext)
  if (!context) throw new Error('useCart must be used inside CartProvider')
  return context
}
