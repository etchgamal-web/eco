export const STOREFRONT_THEMES = ['classic', 'dark-gold', 'bold'] as const

export type StorefrontTheme = (typeof STOREFRONT_THEMES)[number]

export const DEFAULT_STOREFRONT_THEME: StorefrontTheme = 'classic'

export function isStorefrontTheme(value: unknown): value is StorefrontTheme {
  return typeof value === 'string' && (STOREFRONT_THEMES as readonly string[]).includes(value)
}
