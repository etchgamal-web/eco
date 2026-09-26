import { useEffect, useMemo, useState } from 'react'
import { LockKeyhole, Save, Settings2, UserRound } from 'lucide-react'
import { ApiError, changePassword, listSettings, updateProfile, updateSetting } from '../lib/api'

type Props = {
  currentUser: { name?: string; email?: string } | null
  onUserUpdated: (user: { name?: string; email?: string }) => void
  onToast: (message: string, type?: 'success' | 'error') => void
  access?: { roles?: string[]; permissions?: string[] }
}
type Setting = Record<string, unknown>
const valueOf = (setting: Setting) => setting.value === null || setting.value === undefined ? '' : typeof setting.value === 'object' ? JSON.stringify(setting.value) : String(setting.value)

export function SettingsPage({ currentUser, onUserUpdated, onToast, access }: Props) {
  const isAdmin = (access?.roles ?? []).some((role) => ['owner', 'admin'].includes(role))
  const canUpdateSettings = isAdmin || (access?.permissions ?? []).includes('settings.update')
  const [settings, setSettings] = useState<Setting[]>([])
  const [values, setValues] = useState<Record<string, string>>({})
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState<string | null>(null)
  const [query, setQuery] = useState('')
  const [group, setGroup] = useState('all')
  const [profile, setProfile] = useState({ name: currentUser?.name ?? '', email: currentUser?.email ?? '' })
  const [passwords, setPasswords] = useState({ current_password: '', password: '', password_confirmation: '' })
  const [profileSaving, setProfileSaving] = useState(false)
  const [passwordSaving, setPasswordSaving] = useState(false)

  const load = () => { setLoading(true); listSettings().then((items) => { setSettings(items); setValues(Object.fromEntries(items.map((item) => [String(item.key), valueOf(item)]))) }).catch((error: unknown) => onToast(error instanceof ApiError ? error.message : 'تعذر تحميل الإعدادات', 'error')).finally(() => setLoading(false)) }
  // Initial synchronization with the external Laravel API.
  // eslint-disable-next-line react-hooks/set-state-in-effect, react-hooks/exhaustive-deps
  useEffect(() => { load() }, [])
  const groups = useMemo(() => Array.from(new Set(settings.map((setting) => String(setting.group ?? 'store')))), [settings])
  const visible = useMemo(() => settings.filter((setting) => { const haystack = `${setting.key} ${setting.description ?? ''}`.toLowerCase(); return (group === 'all' || String(setting.group ?? 'store') === group) && haystack.includes(query.toLowerCase()) }), [settings, group, query])

  const saveProfile = async () => {
    if (!profile.name.trim() || !profile.email.trim()) { onToast('الاسم والبريد الإلكتروني مطلوبان', 'error'); return }
    setProfileSaving(true)
    try { const user = await updateProfile({ name: profile.name.trim(), email: profile.email.trim() }); onUserUpdated(user); onToast('تم تحديث بيانات الملف الشخصي') }
    catch (error) { onToast(error instanceof ApiError ? error.message : 'تعذر تحديث الملف الشخصي', 'error') }
    finally { setProfileSaving(false) }
  }
  const savePassword = async () => {
    if (passwords.password.length < 8 || passwords.password !== passwords.password_confirmation) { onToast('تأكد من أن كلمة المرور 8 أحرف على الأقل ومتطابقة', 'error'); return }
    setPasswordSaving(true)
    try { await changePassword(passwords); setPasswords({ current_password: '', password: '', password_confirmation: '' }); onToast('تم تغيير كلمة المرور بنجاح') }
    catch (error) { onToast(error instanceof ApiError ? error.message : 'تعذر تغيير كلمة المرور', 'error') }
    finally { setPasswordSaving(false) }
  }
  const save = async (setting: Setting) => { const key = String(setting.key); const type = String(setting.type ?? 'string'); setSaving(key); try { let value: unknown = values[key] ?? ''; if (type === 'boolean') value = value === 'true'; if (type === 'integer' || type === 'float') value = Number(value); if (type === 'json') value = JSON.parse(String(value)); await updateSetting({ group: String(setting.group ?? 'store'), key, value, type, description: String(setting.description ?? '') }); onToast('تم حفظ الإعداد') } catch (error) { onToast(error instanceof ApiError ? error.message : 'قيمة الإعداد غير صالحة', 'error') } finally { setSaving(null) } }

  return <div className="screen-page"><div className="screen-header"><div><p className="eyebrow">إدارة المتجر</p><h1>الإعدادات</h1><p className="muted">تحكم في إعدادات حسابك والمتجر من مكان واحد.</p></div><button className="outline-button" onClick={load}><Settings2 size={15} /> إعادة تحميل</button></div>
    <section className="profile-settings-grid"><div className="data-card profile-settings-card"><div className="setting-heading"><div><span className="setting-group">الحساب</span><h2><UserRound size={18} /> بيانات الملف الشخصي</h2></div></div><label>الاسم<input value={profile.name} onChange={(event) => setProfile({ ...profile, name: event.target.value })} autoComplete="name" /></label><label>البريد الإلكتروني<input type="email" value={profile.email} onChange={(event) => setProfile({ ...profile, email: event.target.value })} autoComplete="email" /></label><button className="primary-button save-setting" disabled={profileSaving} onClick={() => void saveProfile()}>{profileSaving ? 'جار الحفظ...' : <><Save size={14} /> حفظ بيانات الحساب</>}</button></div><div className="data-card profile-settings-card"><div className="setting-heading"><div><span className="setting-group">الأمان</span><h2><LockKeyhole size={18} /> تغيير كلمة المرور</h2></div></div><label>كلمة المرور الحالية<input type="password" value={passwords.current_password} onChange={(event) => setPasswords({ ...passwords, current_password: event.target.value })} autoComplete="current-password" /></label><label>كلمة المرور الجديدة<input type="password" value={passwords.password} onChange={(event) => setPasswords({ ...passwords, password: event.target.value })} autoComplete="new-password" /></label><label>تأكيد كلمة المرور<input type="password" value={passwords.password_confirmation} onChange={(event) => setPasswords({ ...passwords, password_confirmation: event.target.value })} autoComplete="new-password" /></label><button className="primary-button save-setting" disabled={passwordSaving} onClick={() => void savePassword()}>{passwordSaving ? 'جار الحفظ...' : <><LockKeyhole size={14} /> تغيير كلمة المرور</>}</button></div></section>
    <div className="screen-toolbar"><label className="screen-search"><Settings2 size={17} /><input value={query} onChange={(event) => setQuery(event.target.value)} placeholder="ابحث في إعدادات المتجر..." /></label><div className="filter-chips"><button className={`filter-chip ${group === 'all' ? 'selected' : ''}`} onClick={() => setGroup('all')}>الكل</button>{groups.map((item) => <button key={item} className={`filter-chip ${group === item ? 'selected' : ''}`} onClick={() => setGroup(item)}>{item}</button>)}</div></div><section className="settings-grid">{loading ? <div className="data-card settings-loading">جار تحميل الإعدادات...</div> : visible.length === 0 ? <div className="data-card empty-state"><Settings2 size={28} /><b>لا توجد إعدادات متاحة</b></div> : visible.map((setting) => { const key = String(setting.key); const type = String(setting.type ?? 'string'); return <div className="setting-card" key={key}><div className="setting-heading"><div><span className="setting-group">{String(setting.group ?? 'store')}</span><h2>{key}</h2></div><span className="type-pill">{type}</span></div><p>{String(setting.description ?? 'إعدادات المتجر')}</p>{type === 'boolean' ? <select disabled={!canUpdateSettings} value={values[key] ?? ''} onChange={(event) => setValues({ ...values, [key]: event.target.value })}><option value="true">مفعل</option><option value="false">غير مفعل</option></select> : type === 'json' ? <textarea disabled={!canUpdateSettings} rows={4} value={values[key] ?? ''} onChange={(event) => setValues({ ...values, [key]: event.target.value })} /> : <input type={type === 'integer' || type === 'float' ? 'number' : 'text'} step={type === 'float' ? '0.01' : undefined} value={values[key] ?? ''} onChange={(event) => setValues({ ...values, [key]: event.target.value })} /> }{canUpdateSettings && <button className="primary-button save-setting" disabled={saving === key} onClick={() => void save(setting)}>{saving === key ? 'جار الحفظ...' : <><Save size={14} /> حفظ</>}</button>}</div> })}</section></div>
}
