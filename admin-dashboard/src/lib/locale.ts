export type DashboardLocale = 'ar' | 'en'
export type DashboardTheme = 'light' | 'dark'

export const CURRENCY_OPTIONS = [
  { value: 'SAR', label: 'ريال سعودي (SAR)', symbol: 'ر.س', locale: 'ar-SA' },
  { value: 'EGP', label: 'جنيه مصري (EGP)', symbol: 'ج.م', locale: 'ar-EG' },
  { value: 'AED', label: 'درهم إماراتي (AED)', symbol: 'د.إ', locale: 'ar-AE' },
  { value: 'USD', label: 'دولار أمريكي (USD)', symbol: '$', locale: 'en-US' },
  { value: 'EUR', label: 'يورو (EUR)', symbol: '€', locale: 'de-DE' },
]

export const LANGUAGE_OPTIONS: Array<{ value: DashboardLocale; label: string; locale: string }> = [
  { value: 'ar', label: 'العربية', locale: 'ar-SA' },
  { value: 'en', label: 'English', locale: 'en-US' },
]

export function readLocaleSettings() {
  const currency = localStorage.getItem('souqi-currency') || 'SAR'
  const locale = (localStorage.getItem('souqi-locale') || 'ar') as DashboardLocale
  const theme = (localStorage.getItem('souqi-theme') || 'light') as DashboardTheme
  return { currency, locale, theme: theme === 'dark' ? 'dark' : 'light' as DashboardTheme }
}

export function currencyOption(code: string) { return CURRENCY_OPTIONS.find((item) => item.value === code) ?? CURRENCY_OPTIONS[0] }

export function formatMoney(value: number, currency = readLocaleSettings().currency) {
  const option = currencyOption(currency)
  return new Intl.NumberFormat(option.locale, { style: 'currency', currency: option.value, maximumFractionDigits: 2 }).format(Number(value) || 0)
}

export function saveLocaleSettings(currency: string, locale: DashboardLocale) {
  localStorage.setItem('souqi-currency', currency)
  localStorage.setItem('souqi-locale', locale)
  window.dispatchEvent(new CustomEvent('souqi-locale-changed', { detail: { currency, locale } }))
}

export function saveTheme(theme: DashboardTheme) {
  localStorage.setItem('souqi-theme', theme)
  document.documentElement.dataset.theme = theme
  window.dispatchEvent(new CustomEvent('souqi-theme-changed', { detail: { theme } }))
}
