import { useEffect, useState } from 'react'
import { Check, Palette } from 'lucide-react'
import { ApiError, listSettings, updateSetting } from '../lib/api'
import './AppearancePage.css'

type Props = { onToast: (message: string, type?: 'success' | 'error') => void; access?: { roles?: string[]; permissions?: string[] } }

const THEME_KEY = 'appearance.storefront_theme'
const DEFAULT_THEME = 'classic'

type Theme = {
  id: string
  name: string
  description: string
  swatches: { bg: string; surface: string; accent: string; text: string }
}

// Keep ids in sync with STOREFRONT_THEMES (storefront) and PublicAppearanceController::THEMES (API).
const THEMES: Theme[] = [
  { id: 'classic', name: 'كلاسيك هادئ', description: 'ألوان فاتحة هادئة بأخضر وكريمي، مناسب لأغلب المتاجر.', swatches: { bg: '#fffdf8', surface: '#f5f2eb', accent: '#315948', text: '#24322c' } },
  { id: 'dark-gold', name: 'داكن فاخر', description: 'خلفية داكنة مع لمسات ذهبية وخط أنيق، مناسب للمنتجات الراقية والأزياء.', swatches: { bg: '#0e0e10', surface: '#17171b', accent: '#c9a24b', text: '#f2eee6' } },
  { id: 'bold', name: 'جريء وملوّن', description: 'أصفر وبنفسجي بزوايا مدوّرة كبيرة، مناسب للعروض والمنتجات الشبابية.', swatches: { bg: '#ffffff', surface: '#ffd23f', accent: '#5b3df5', text: '#1b1b1f' } },
]

function ThemePreview({ swatches }: { swatches: Theme['swatches'] }) {
  return (
    <div className="theme-preview" aria-hidden="true" style={{ background: swatches.bg, borderColor: swatches.text + '22' }}>
      <div className="theme-preview-bar" style={{ background: swatches.surface }}>
        <i style={{ background: swatches.accent }} />
        <b style={{ background: swatches.text, opacity: 0.55 }} />
        <b style={{ background: swatches.text, opacity: 0.3 }} />
      </div>
      <div className="theme-preview-hero" style={{ background: swatches.surface }}>
        <b style={{ background: swatches.text }} />
        <b style={{ background: swatches.text, opacity: 0.45, width: '45%' }} />
        <span style={{ background: swatches.accent }} />
      </div>
      <div className="theme-preview-grid">
        <i style={{ background: swatches.surface }} />
        <i style={{ background: swatches.surface }} />
        <i style={{ background: swatches.surface }} />
      </div>
    </div>
  )
}

export function AppearancePage({ onToast, access }: Props) {
  const canUpdate = (access?.roles ?? []).some((role) => ['owner', 'admin'].includes(role)) || (access?.permissions ?? []).includes('settings.update')
  const [active, setActive] = useState<string>(DEFAULT_THEME)
  const [loading, setLoading] = useState(true)
  const [savingId, setSavingId] = useState<string | null>(null)

  useEffect(() => {
    let cancelled = false
    listSettings()
      .then((items) => {
        if (cancelled) return
        const saved = items.find((item) => item.key === THEME_KEY)?.value
        setActive(typeof saved === 'string' && THEMES.some((theme) => theme.id === saved) ? saved : DEFAULT_THEME)
      })
      .catch((error: unknown) => { if (!cancelled) onToast(error instanceof ApiError ? error.message : 'تعذر تحميل التصميم الحالي', 'error') })
      .finally(() => { if (!cancelled) setLoading(false) })
    return () => { cancelled = true }
    // Load once on mount; onToast is recreated by the parent on every render and must not retrigger the fetch.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  const activate = async (theme: Theme) => {
    if (!canUpdate || savingId || theme.id === active) return
    setSavingId(theme.id)
    try {
      await updateSetting({ group: 'appearance', key: THEME_KEY, value: theme.id, type: 'string', description: 'تصميم واجهة المتجر الظاهر للعملاء' })
      setActive(theme.id)
      onToast(`تم تفعيل تصميم «${theme.name}». سيظهر في المتجر خلال ثوانٍ.`)
    } catch (error) {
      onToast(error instanceof ApiError ? error.message : 'تعذر تفعيل التصميم', 'error')
    } finally {
      setSavingId(null)
    }
  }

  return (
    <div className="screen-page">
      <div className="screen-header">
        <div>
          <p className="eyebrow">المتجر / المظهر</p>
          <h1>تصميم واجهة المتجر</h1>
          <p className="muted">اختر التصميم الذي يراه عملاؤك. التغيير يُطبَّق على كل صفحات المتجر بدون أي كود.</p>
        </div>
      </div>

      {!canUpdate && <p className="muted" role="note">لا تملك صلاحية تغيير التصميم، يمكنك المعاينة فقط.</p>}

      <div className="theme-grid" aria-busy={loading}>
        {THEMES.map((theme) => {
          const isActive = theme.id === active
          return (
            <article key={theme.id} className={`theme-card${isActive ? ' is-active' : ''}`}>
              <ThemePreview swatches={theme.swatches} />
              <div className="theme-card-body">
                <h2><Palette size={16} /> {theme.name}</h2>
                <p className="muted">{theme.description}</p>
                {isActive
                  ? <span className="theme-active-badge"><Check size={14} /> مفعّل الآن</span>
                  : <button className="primary-button" type="button" disabled={!canUpdate || loading || savingId !== null} onClick={() => void activate(theme)}>{savingId === theme.id ? 'جار التفعيل...' : 'تفعيل هذا التصميم'}</button>}
              </div>
            </article>
          )
        })}
      </div>
    </div>
  )
}
