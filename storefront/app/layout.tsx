import type { Metadata } from 'next'
import { Geist, Geist_Mono } from 'next/font/google'
import { env } from '@/core/config/env'
import { siteConfig } from '@/core/config/site'
import './globals.css'
import { CartProvider } from '@/features/cart/store'
import { AuthProvider } from '@/features/auth/auth-context'

const geistSans = Geist({
  variable: '--font-geist-sans',
  subsets: ['latin'],
})

const geistMono = Geist_Mono({
  variable: '--font-geist-mono',
  subsets: ['latin'],
})

export const metadata: Metadata = {
  title: siteConfig.title,
  description: siteConfig.description,
  metadataBase: new URL(env.siteUrl),
  openGraph: { title: siteConfig.title, description: siteConfig.description, locale: 'ar_EG', type: 'website' },
}

export default function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) {
  return <html lang="ar" dir="rtl" className={`${geistSans.variable} ${geistMono.variable}`}><body><CartProvider><AuthProvider>{children}</AuthProvider></CartProvider></body></html>
}
