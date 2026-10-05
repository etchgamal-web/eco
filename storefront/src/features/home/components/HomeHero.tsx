import type { HeroContent } from '@/src/lib/api/landing'

type HomeHeroProps = {
  content: Required<HeroContent>
}

export default function HomeHero({ content }: HomeHeroProps) {
  return (
    <section className="hero">
      <div className="hero-copy">
        <p className="kicker">{content.eyebrow}</p>
        <h1>{content.title}<br /><em>{content.highlight}</em></h1>
        <p className="hero-text">{content.description}</p>
        <a className="primary-button" href={content.cta_href}>{content.cta_label} <span>←</span></a>
      </div>
      <div className="hero-art" aria-label="صورة زخرفية للمنتجات">
        <div className="sun" />
        <div className="arch"><div className="arch-card">ECO<br /><small>everyday objects</small></div></div>
        <div className="leaf leaf-one" />
        <div className="leaf leaf-two" />
      </div>
    </section>
  )
}
