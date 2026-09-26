import { useState } from 'react'
import { LockKeyhole, Save, UserRound } from 'lucide-react'
import { ApiError, changePassword, updateProfile } from '../lib/api'

type Props = {
  currentUser: { name?: string; email?: string } | null
  onUserUpdated: (user: { name?: string; email?: string }) => void
  onToast: (message: string, type?: 'success' | 'error') => void
}

export function ProfilePage({ currentUser, onUserUpdated, onToast }: Props) {
  const [profile, setProfile] = useState({ name: currentUser?.name ?? '', email: currentUser?.email ?? '' })
  const [passwords, setPasswords] = useState({ current_password: '', password: '', password_confirmation: '' })
  const [profileSaving, setProfileSaving] = useState(false)
  const [passwordSaving, setPasswordSaving] = useState(false)

  const saveProfile = async () => {
    if (!profile.name.trim() || !profile.email.trim()) return onToast('الاسم والبريد الإلكتروني مطلوبان', 'error')
    setProfileSaving(true)
    try { const user = await updateProfile({ name: profile.name.trim(), email: profile.email.trim() }); onUserUpdated(user); onToast('تم تحديث بيانات الملف الشخصي') }
    catch (error) { onToast(error instanceof ApiError ? error.message : 'تعذر تحديث الملف الشخصي', 'error') }
    finally { setProfileSaving(false) }
  }

  const savePassword = async () => {
    if (passwords.password.length < 8 || passwords.password !== passwords.password_confirmation) return onToast('تأكد من أن كلمة المرور 8 أحرف على الأقل ومتطابقة', 'error')
    setPasswordSaving(true)
    try { await changePassword(passwords); setPasswords({ current_password: '', password: '', password_confirmation: '' }); onToast('تم تغيير كلمة المرور بنجاح') }
    catch (error) { onToast(error instanceof ApiError ? error.message : 'تعذر تغيير كلمة المرور', 'error') }
    finally { setPasswordSaving(false) }
  }

  return <div className="screen-page profile-page"><div className="screen-header"><div><p className="eyebrow">حسابي</p><h1>الملف الشخصي</h1><p className="muted">إدارة بياناتك الشخصية وأمان حسابك فقط.</p></div></div><section className="profile-settings-grid"><div className="data-card profile-settings-card"><div className="setting-heading"><div><span className="setting-group">الحساب</span><h2><UserRound size={18} /> بيانات الملف الشخصي</h2></div></div><label>الاسم<input value={profile.name} onChange={(event) => setProfile({ ...profile, name: event.target.value })} autoComplete="name" /></label><label>البريد الإلكتروني<input type="email" value={profile.email} onChange={(event) => setProfile({ ...profile, email: event.target.value })} autoComplete="email" /></label><button className="primary-button save-setting" disabled={profileSaving} onClick={() => void saveProfile()}>{profileSaving ? 'جار الحفظ...' : <><Save size={14} /> حفظ بيانات الحساب</>}</button></div><div className="data-card profile-settings-card"><div className="setting-heading"><div><span className="setting-group">الأمان</span><h2><LockKeyhole size={18} /> تغيير كلمة المرور</h2></div></div><label>كلمة المرور الحالية<input type="password" value={passwords.current_password} onChange={(event) => setPasswords({ ...passwords, current_password: event.target.value })} autoComplete="current-password" /></label><label>كلمة المرور الجديدة<input type="password" value={passwords.password} onChange={(event) => setPasswords({ ...passwords, password: event.target.value })} autoComplete="new-password" /></label><label>تأكيد كلمة المرور<input type="password" value={passwords.password_confirmation} onChange={(event) => setPasswords({ ...passwords, password_confirmation: event.target.value })} autoComplete="new-password" /></label><button className="primary-button save-setting" disabled={passwordSaving} onClick={() => void savePassword()}>{passwordSaving ? 'جار الحفظ...' : <><LockKeyhole size={14} /> تغيير كلمة المرور</>}</button></div></section></div>
}
