'use client'

import { FormEvent, useEffect, useState } from 'react'
import { listProducts } from '@/infrastructure/repositories/repository-factories'
import type { Product } from '@/domain/catalog/product'
import { getPublishedLandingPage, heroFromLanding } from '@/src/lib/api/landing'
import type { HeroContent } from '@/src/lib/api/landing'
import ProductGrid from '@/features/catalog/components/ProductGrid'
import AnnouncementBar from '@/shared/layout/AnnouncementBar'
import SiteHeader from '@/shared/layout/SiteHeader'
import SiteFooter from '@/shared/layout/SiteFooter'
import HomeHero from './HomeHero'
import BenefitsStrip from './BenefitsStrip'

const defaultHero: Required<HeroContent> = {
  eyebrow: 'اختيارات تعيش معك',
  title: 'أشياء أجمل',
  highlight: 'لحياة أبسط.',
  description: 'منتجات مختارة بعناية تجمع بين التصميم الهادئ، الجودة العملية، والأثر الأفضل على يومك.',
  cta_label: 'اكتشف المجموعة',
  cta_href: '#products',
}

export default function StorefrontHome() {
  const [products, setProducts] = useState<Product[]>([])
  const [search, setSearch] = useState('')
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [hero, setHero] = useState<Required<HeroContent>>(defaultHero)

  const loadProducts = async (term = '') => {
    setLoading(true)
    setError('')
    try {
      const result = await listProducts(term)
      setProducts(result.items)
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : 'تعذر الاتصال بالمتجر')
    } finally {
      setLoading(false)
    }
  }

  const loadHero = async () => {
    try {
      const page = await getPublishedLandingPage('home')
      setHero({ ...defaultHero, ...heroFromLanding(page) })
    } catch {
      setHero(defaultHero)
    }
  }

  // eslint-disable-next-line react-hooks/set-state-in-effect
  useEffect(() => { void loadProducts(); void loadHero() }, [])

  const submitSearch = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    void loadProducts(search)
  }

  return (
    <main>
      <AnnouncementBar />
      <SiteHeader />
      <HomeHero content={hero} />
      <BenefitsStrip />

      <section className="products-section" id="products">
        <div className="section-heading">
          <div><p className="kicker">المجموعة الحالية</p><h2>اختياراتنا لك</h2></div>
          <form className="search-form" onSubmit={submitSearch}>
            <input value={search} onChange={(event) => setSearch(event.target.value)} placeholder="ابحث عن منتج..." aria-label="ابحث عن منتج" />
            <button type="submit">بحث</button>
          </form>
        </div>
        <ProductGrid products={products} loading={loading} error={error} searchTerm={search} onRetry={() => void loadProducts(search)} />
      </section>
      <SiteFooter />
    </main>
  )
}
