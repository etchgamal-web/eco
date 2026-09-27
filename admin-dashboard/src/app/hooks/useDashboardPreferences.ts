import { useCallback, useEffect, useState } from 'react'
import { readLocaleSettings, saveLocaleSettings, saveTheme, type DashboardTheme } from '../../lib/locale'

export function useDashboardPreferences() {
  const [localeSettings, setLocaleSettings] = useState(readLocaleSettings)
  const [theme, setTheme] = useState<DashboardTheme>(() => readLocaleSettings().theme)

  useEffect(() => {
    document.documentElement.dataset.theme = theme
    document.documentElement.dir = localeSettings.locale === 'ar' ? 'rtl' : 'ltr'
    document.documentElement.lang = localeSettings.locale
  }, [localeSettings.locale, theme])

  useEffect(() => {
    const onThemeChanged = (event: Event) => setTheme((event as CustomEvent<{ theme: DashboardTheme }>).detail.theme)
    const onLocaleChanged = (event: Event) => setLocaleSettings((current) => ({ ...current, ...(event as CustomEvent<{ currency: string; locale: 'ar' | 'en' }>).detail }))
    window.addEventListener('souqi-theme-changed', onThemeChanged)
    window.addEventListener('souqi-locale-changed', onLocaleChanged)
    return () => {
      window.removeEventListener('souqi-theme-changed', onThemeChanged)
      window.removeEventListener('souqi-locale-changed', onLocaleChanged)
    }
  }, [])

  const setLocale = useCallback((currency: string, locale: 'ar' | 'en') => saveLocaleSettings(currency, locale), [])
  const toggleTheme = useCallback(() => saveTheme(theme === 'dark' ? 'light' : 'dark'), [theme])

  return {
    localeSettings,
    theme,
    setLocale,
    toggleTheme,
  }
}
