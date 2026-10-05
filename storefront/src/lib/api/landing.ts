import { requestJson } from '@/core/http/client'

export type HeroContent = {
  eyebrow?: string
  title?: string
  highlight?: string
  description?: string
  cta_label?: string
  cta_href?: string
}

export type PublishedLandingPage = {
  slug: string
  title?: string
  excerpt?: string | null
  sections?: Array<{ type?: string; data?: HeroContent }>
  settings?: Record<string, unknown>
}

export async function getPublishedLandingPage(slug: string): Promise<PublishedLandingPage> {
  const payload = await requestJson<{ data: PublishedLandingPage }>(`/landing-pages/${encodeURIComponent(slug)}`, { cache: 'no-store' })
  return payload.data
}

export function heroFromLanding(page: PublishedLandingPage): HeroContent {
  const section = page.sections?.find((item) => item.type === 'hero')
  return section?.data ?? {}
}
