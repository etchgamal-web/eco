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

const apiUrl = (process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api/v1').replace(/\/$/, '')

export async function getPublishedLandingPage(slug: string): Promise<PublishedLandingPage> {
  const response = await fetch(`${apiUrl}/landing-pages/${encodeURIComponent(slug)}`, { headers: { Accept: 'application/json' }, cache: 'no-store' })
  if (!response.ok) throw new Error('لا توجد إعدادات منشورة للواجهة')
  const payload = (await response.json()) as { data: PublishedLandingPage }
  return payload.data
}

export function heroFromLanding(page: PublishedLandingPage): HeroContent {
  const section = page.sections?.find((item) => item.type === 'hero')
  return section?.data ?? {}
}
