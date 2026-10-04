'use client'

import { FormEvent, useEffect, useState } from 'react'
import Link from 'next/link'
import { listProducts, Product } from '../../src/lib/api/catalog'
import { getPublishedLandingPage, heroFromLanding, HeroContent } from '../../src/lib/api/landing'
const formatPrice = (price: number, currency = 'ج.م') => `${new Intl.NumberFormat('ar-EG').format(price)} ${currency}`
const defaultHero: Required<HeroContent> = { eyebrow: 'اختيارات تعيش معك', title: 'أشياء أجمل', highlight: 'لحياة أبسط.', description: 'منتجات مختارة بعناية تجمع بين التصميم الهادئ، الجودة العملية، والأثر الأفضل على يومك.', cta_label: 'اكتشف المجموعة', cta_href: '#products' }
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
    } catch { setHero(defaultHero) }
  }
  // eslint-disable-next-line react-hooks/set-state-in-effect
  useEffect(() => { void loadProducts(); void loadHero() }, [])

  const submitSearch = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    void loadProducts(search)
  }

  return (
    <main>
      <section className="announcement">شحن مجاني للطلبات فوق 1,500 جنيه داخل مصر</section>
      <nav className="site-nav" aria-label="التنقل الرئيسي">
        <Link className="brand" href="/">إيكو<span>.</span></Link>
        <div className="nav-links"><a href="#products">المنتجات</a><a href="#story">قصتنا</a><a href="#contact">تواصل معنا</a></div>
        <button className="cart-button" type="button" aria-label="السلة">السلة <span>0</span></button>
      </nav>

      <section className="hero">
        <div className="hero-copy"><p className="kicker">{hero.eyebrow}</p><h1>{hero.title}<br /><em>{hero.highlight}</em></h1><p className="hero-text">{hero.description}</p><a className="primary-button" href={hero.cta_href}>{hero.cta_label} <span>←</span></a></div>
        <div className="hero-art" aria-label="صورة زخرفية للمنتجات"><div className="sun" /><div className="arch"><div className="arch-card">ECO<br /><small>everyday objects</small></div></div><div className="leaf leaf-one" /><div className="leaf leaf-two" /></div>
      </section>

      <section className="category-strip" id="story"><span>منتجات يومية مدروسة</span><span>مصادر مسؤولة</span><span>تغليف أقل</span><span>جودة تدوم</span></section>

      <section className="products-section" id="products"><div className="section-heading"><div><p className="kicker">المجموعة الحالية</p><h2>اختياراتنا لك</h2></div><form className="search-form" onSubmit={submitSearch}><input value={search} onChange={(event) => setSearch(event.target.value)} placeholder="ابحث عن منتج..." aria-label="ابحث عن منتج" /><button type="submit">بحث</button></form></div>
        {loading ? <div className="product-grid">{[1, 2, 3, 4].map((item) => <div className="skeleton-card" key={item}><div /><span /><span /></div>)}</div> : error ? <div className="state-card"><strong>{error}</strong><p>تأكد من تشغيل Laravel API وضبط NEXT_PUBLIC_API_URL.</p><button className="secondary-button" type="button" onClick={() => void loadProducts(search)}>إعادة المحاولة</button></div> : products.length === 0 ? <div className="state-card"><strong>لا توجد منتجات مطابقة</strong><p>جرّب البحث بكلمة أخرى.</p></div> : <div className="product-grid">{products.map((product) => <article className="product-card" key={product.id}><div className="product-image"><span>{product.category?.name || 'إيكو'}</span><div className="product-shape" /></div><div className="product-info"><p>{product.brand?.name || 'اختيار إيكو'}</p><h3>{product.name}</h3><strong>{formatPrice(product.price, product.currency)}</strong></div></article>)}</div>}
      </section>
      <footer id="contact"><div className="brand">إيكو<span>.</span></div><p>منتجات أفضل، ليوم أفضل.</p><small>© 2026 Eco. جميع الحقوق محفوظة.</small></footer>
    </main>
  )
}
