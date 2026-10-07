import type { Metadata } from 'next'
import { Geist, Geist_Mono } from 'next/font/google'
import { env } from '@/core/config/env'
import { siteConfig } from '@/core/config/site'
import './globals.css'
import { CartProvider } from '@/features/cart/store'
import { AuthProvider } from '@/features/auth/auth-context'
import OrganizationStructuredData from '@/shared/seo/OrganizationStructuredData'

const geistSans = Geist({
  variable: '--font-geist-sans',
  subsets: ['latin'],
})

const geistMono = Geist_Mono({
  variable: '--font-geist-mono',
  subsets: ['latin'],
})

export const metadata: Metadata = {
  title: { default: siteConfig.title, template: `%s | ${siteConfig.name}` },
  description: siteConfig.description,
  metadataBase: new URL(env.siteUrl),
  keywords: ['إيكو', 'متجر إيكو', 'منتجات يومية', 'منتجات منزلية', 'تسوق أونلاين', 'مصر'],
  applicationName: siteConfig.name,
  authors: [{ name: siteConfig.name }],
  creator: siteConfig.name,
  publisher: siteConfig.name,
  alternates: { canonical: '/' },
  robots: { index: true, follow: true, googleBot: { index: true, follow: true } },
  openGraph: {
    title: siteConfig.title,
    description: siteConfig.description,
    url: '/',
    siteName: siteConfig.name,
    locale: 'ar_EG',
    type: 'website',
  },
  twitter: {
    card: 'summary',
    title: siteConfig.title,
    description: siteConfig.description,
  },
}

export default function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) {
  return <html lang="ar" dir="rtl" className={`${geistSans.variable} ${geistMono.variable}`}><body><OrganizationStructuredData /><CartProvider><AuthProvider>{children}</AuthProvider></CartProvider></body></html>
}
