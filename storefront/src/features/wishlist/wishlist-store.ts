'use client'

import { useCallback, useEffect, useSyncExternalStore } from 'react'
import { useAuth } from '@/features/auth/auth-context'
import { getWishlist, removeFromWishlist } from '@/infrastructure/api/customer-api'
import { addToWishlist } from '@/infrastructure/api/wishlist-api'

type WishlistState = { owner: number | null; status: 'idle' | 'loading' | 'ready'; ids: ReadonlySet<number> }

const EMPTY: WishlistState = { owner: null, status: 'idle', ids: new Set() }
let state: WishlistState = EMPTY
const listeners = new Set<() => void>()

function setState(next: WishlistState) {
  state = next
  listeners.forEach((listener) => listener())
}

function subscribe(listener: () => void) {
  listeners.add(listener)
  return () => { listeners.delete(listener) }
}

const getSnapshot = () => state
const getServerSnapshot = () => EMPTY

async function load(owner: number) {
  setState({ owner, status: 'loading', ids: new Set() })
  try {
    const items = await getWishlist()
    if (state.owner !== owner) return
    const ids = new Set(items.map((item) => item.product_id).filter((id): id is number => typeof id === 'number'))
    setState({ owner, status: 'ready', ids })
  } catch {
    if (state.owner === owner) setState({ owner, status: 'ready', ids: new Set() })
  }
}

export function useWishlist() {
  const { customer, loading } = useAuth()
  const snapshot = useSyncExternalStore(subscribe, getSnapshot, getServerSnapshot)
  const customerId = customer?.id ?? null

  useEffect(() => {
    if (loading) return
    if (customerId === null) {
      if (state.owner !== null) setState(EMPTY)
      return
    }
    if (state.owner !== customerId) void load(customerId)
  }, [customerId, loading])

  const toggle = useCallback(async (productId: number) => {
    if (customerId === null || state.owner !== customerId) return
    const had = state.ids.has(productId)
    const apply = (add: boolean) => {
      const ids = new Set(state.ids)
      if (add) ids.add(productId); else ids.delete(productId)
      setState({ ...state, ids })
    }
    apply(!had)
    try {
      if (had) await removeFromWishlist(productId)
      else await addToWishlist(productId)
    } catch (reason) {
      apply(had)
      throw reason
    }
  }, [customerId])

  return {
    signedIn: customerId !== null,
    authLoading: loading,
    ready: snapshot.owner === customerId && snapshot.status === 'ready',
    has: (productId: number) => snapshot.ids.has(productId),
    toggle,
  }
}
