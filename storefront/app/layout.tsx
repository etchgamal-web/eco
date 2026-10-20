import type { Metadata, Viewport } from 'next'
import { Cairo, El_Messiri, Geist, Geist_Mono, Tajawal } from 'next/font/google'
import { env } from '@/core/config/env'
import { siteConfig } from '@/core/config/site'
import './globals.css'
import './ui-polish.css'
import { CartProvider } from '@/features/cart/store'
import { AuthProvider } from '@/features/auth/auth-context'
import OrganizationStructuredData from '@/shared/seo/OrganizationStructuredData'
import { getStorefrontTheme } from '@/src/lib/api/appearance'

// Fonts used by the selectable storefront themes (see the theme blocks in globals.css).
const elMessiri = El_Messiri({ variable: '--font-messiri', subsets: ['arabic', 'latin'], weight: ['500', '600', '700'], display: 'swap' })
const tajawal = Tajawal({ variable: '--font-tajawal', subsets: ['arabic', 'latin'], weight: ['400', '500', '700'], display: 'swap' })
const cairo = Cairo({ variable: '--font-cairo', subsets: ['arabic', 'latin'], weight: ['400', '600', '700', '900'], display: 'swap' })

const geistSans = Geist({
  variable: '--font-geist-sans',
  subsets: ['latin'],
})

const geistMono = Geist_Mono({
  variable: '--font-geist-mono',
  subsets: ['latin'],
})

export const viewport: Viewport = {
  width: 'device-width',
  initialScale: 1,
  viewportFit: 'cover',
  themeColor: '#315948',
}

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

export default async function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) {
  const theme = await getStorefrontTheme()
  return <html lang="ar" dir="rtl" data-theme={theme} className={`${geistSans.variable} ${geistMono.variable} ${elMessiri.variable} ${tajawal.variable} ${cairo.variable}`}><body><OrganizationStructuredData /><CartProvider><AuthProvider>{children}</AuthProvider></CartProvider></body></html>
}
