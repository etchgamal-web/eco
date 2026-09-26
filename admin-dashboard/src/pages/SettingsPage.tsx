import { useEffect, useState } from 'react'
import { Save, Settings2 } from 'lucide-react'
import { ApiError, listSettings, updateSetting } from '../lib/api'

type Props = { onToast: (message: string, type?: 'success' | 'error') => void }
type Setting = Record<string, unknown>
const valueOf = (setting: Setting) => setting.value === null || setting.value === undefined ? '' : typeof setting.value === 'object' ? JSON.stringify(setting.value) : String(setting.value)
export function SettingsPage({ onToast }: Props) {
  const [settings, setSettings] = useState<Setting[]>([])
  const [values, setValues] = useState<Record<string, string>>({})
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState<string | null>(null)
  const load = () => { setLoading(true); listSettings().then((items) => { setSettings(items); setValues(Object.fromEntries(items.map((item) => [String(item.key), valueOf(item)]))) }).catch((error: unknown) => onToast(error instanceof ApiError ? error.message : 'تعذر تحميل الإعدادات', 'error')).finally(() => setLoading(false)) }
  // Initial synchronization with the external Laravel API.
  // eslint-disable-next-line react-hooks/set-state-in-effect, react-hooks/exhaustive-deps
  useEffect(() => { load() }, [])
  const save = async (setting: Setting) => { const key = String(setting.key); const type = String(setting.type ?? 'string'); setSaving(key); try { let value: unknown = values[key] ?? ''; if (type === 'boolean') value = value === 'true'; if (type === 'integer') value = Number(value); if (type === 'float') value = Number(value); if (type === 'json') value = JSON.parse(String(value)); await updateSetting({ group: String(setting.group ?? 'store'), key, value, type, description: String(setting.description ?? '') }); onToast('تم حفظ الإعداد') } catch (error) { onToast(error instanceof ApiError ? error.message : 'قيمة الإعداد غير صالحة', 'error') } finally { setSaving(null) } }
  return <div className="screen-page"><div className="screen-header"><div><p className="eyebrow">إدارة المتجر</p><h1>الإعدادات</h1><p className="muted">تحكم في قيم المتجر التي يديرها Laravel من مكان واحد.</p></div><button className="outline-button" onClick={load}><Settings2 size={15} /> إعادة تحميل</button></div><section className="settings-grid">{loading ? <div className="data-card settings-loading">جار تحميل الإعدادات...</div> : settings.length === 0 ? <div className="data-card empty-state"><Settings2 size={28} /><b>لا توجد إعدادات متاحة</b></div> : settings.map((setting) => { const key = String(setting.key); const type = String(setting.type ?? 'string'); return <div className="setting-card" key={key}><div className="setting-heading"><div><span className="setting-group">{String(setting.group ?? 'store')}</span><h2>{key}</h2></div><span className="type-pill">{type}</span></div><p>{String(setting.description ?? 'إعدادات المتجر')}</p>{type === 'boolean' ? <select value={values[key] ?? ''} onChange={(event) => setValues({ ...values, [key]: event.target.value })}><option value="true">مفعل</option><option value="false">غير مفعل</option></select> : <input value={values[key] ?? ''} onChange={(event) => setValues({ ...values, [key]: event.target.value })} /> }<button className="primary-button save-setting" disabled={saving === key} onClick={() => void save(setting)}>{saving === key ? 'جار الحفظ...' : <><Save size={14} /> حفظ</>}</button></div> })}</section></div>
}
