import type { Metadata } from 'next'
import { Geist, Geist_Mono } from 'next/font/google'
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
  title: 'إيكو — أشياء أجمل لحياة أبسط',
  description: 'منتجات يومية مختارة بعناية، بجودة تدوم وأثر أفضل.',
  metadataBase: new URL(process.env.NEXT_PUBLIC_SITE_URL || 'http://localhost:3000'),
  openGraph: { title: 'إيكو — أشياء أجمل لحياة أبسط', description: 'منتجات يومية مدروسة لحياة أبسط.', locale: 'ar_EG', type: 'website' },
}

export default function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) {
  return <html lang="ar" dir="rtl" className={`${geistSans.variable} ${geistMono.variable}`}><body><CartProvider><AuthProvider>{children}</AuthProvider></CartProvider></body></html>
}
