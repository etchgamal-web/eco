import { env } from '@/core/config/env'
import { DEFAULT_STOREFRONT_THEME, isStorefrontTheme } from '@/core/theme/themes'
import type { StorefrontTheme } from '@/core/theme/themes'

type AppearanceResponse = { data?: { theme?: unknown } }

/**
 * Reads the storefront theme chosen in the admin dashboard.
 * Any failure falls back to the default theme so the store never breaks because of this call.
 */
export async function getStorefrontTheme(): Promise<StorefrontTheme> {
  try {
    const response = await fetch(`${env.apiUrl}/public/appearance`, {
      headers: { Accept: 'application/json' },
      next: { revalidate: 30 },
    })
    if (!response.ok) return DEFAULT_STOREFRONT_THEME
    const payload = (await response.json()) as AppearanceResponse
    const theme = payload.data?.theme
    return isStorefrontTheme(theme) ? theme : DEFAULT_STOREFRONT_THEME
  } catch {
    return DEFAULT_STOREFRONT_THEME
  }
}
