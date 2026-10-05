'use client'

import { createContext, useContext, useEffect, useMemo, useState } from 'react'
import type { Customer } from '@/domain/customer/customer'
import { getCurrentCustomer, login as loginRequest, logout as logoutRequest } from '@/infrastructure/api/auth-api'
import { useCart } from '@/features/cart/store'

const TOKEN_KEY = 'eco-auth-token'

type AuthContextValue = {
  customer: Customer | null
  loading: boolean
  login: (identifier: string, password: string, remember?: boolean) => Promise<void>
  logout: () => Promise<void>
}

const AuthContext = createContext<AuthContextValue | null>(null)

export function AuthProvider({ children }: Readonly<{ children: React.ReactNode }>) {
  const [customer, setCustomer] = useState<Customer | null>(null)
  const [loading, setLoading] = useState(true)
  const { syncAfterAuthentication } = useCart()

  useEffect(() => {
    const token = window.localStorage.getItem(TOKEN_KEY)
    if (!token) {
      // eslint-disable-next-line react-hooks/set-state-in-effect
      setLoading(false)
      return
    }
    void getCurrentCustomer().then(setCustomer).catch(() => window.localStorage.removeItem(TOKEN_KEY)).finally(() => setLoading(false))
  }, [])

  const value = useMemo<AuthContextValue>(() => ({
    customer,
    loading,
    login: async (identifier, password, remember = false) => {
      const response = await loginRequest(identifier, password, remember)
      window.localStorage.setItem(TOKEN_KEY, response.token)
      setCustomer(response.data)
      await syncAfterAuthentication()
    },
    logout: async () => {
      try { await logoutRequest() } finally { window.localStorage.removeItem(TOKEN_KEY); setCustomer(null) }
    },
  }), [customer, loading, syncAfterAuthentication])

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth() {
  const context = useContext(AuthContext)
  if (!context) throw new Error('useAuth must be used inside AuthProvider')
  return context
}
