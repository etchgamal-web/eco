import { useState } from 'react'
import { Store, XCircle } from 'lucide-react'
import { ApiError, apiBaseUrl } from '../../../lib/api/client'
import { login } from '../../../lib/api/auth'
import type { ToastMessage } from '../../../components/shared/DashboardWidgets'
import { ToastViewport } from '../../../components/shared/DashboardWidgets'

export function LoginScreen({ onSuccess }: { onSuccess: () => void }) {
  const [identifier, setIdentifier] = useState('')
  const [password, setPassword] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const [notice, setNotice] = useState<ToastMessage | null>(null)
  const submit = async (event: React.FormEvent) => { event.preventDefault(); setLoading(true); setError(''); setNotice(null); try { await login(identifier, password); onSuccess() } catch (reason) { const message = reason instanceof ApiError ? reason.message : 'تعذر الاتصال بالخادم. تحقق من الاتصال ثم حاول مرة أخرى.'; setError(message); if (!(reason instanceof ApiError && reason.status === 401)) setNotice({ type: 'error', title: 'تعذر تسجيل الدخول', message, detail: 'تحقق من البيانات واتصال الخادم.' }) } finally { setLoading(false) } }
  return <div className="login-shell">{notice && <ToastViewport toast={notice} onClose={() => setNotice(null)} />}<div className="login-card"><div className="login-brand"><span className="logo-mark"><Store size={21} /></span><b>سوقي Admin</b></div><span className="eyebrow">إدارة المتجر</span><h1>تسجيل الدخول</h1><p className="muted">استخدم حساب الإدارة للوصول إلى بيانات الـAPI.</p><form onSubmit={submit}><label className="form-field"><span>البريد الإلكتروني أو رقم الهاتف</span><input required value={identifier} onChange={(event) => setIdentifier(event.target.value)} placeholder="admin@example.com" autoComplete="username" /></label><label className="form-field"><span>كلمة المرور</span><input required type="password" value={password} onChange={(event) => setPassword(event.target.value)} placeholder="••••••••" autoComplete="current-password" aria-invalid={Boolean(error)} />{error && <span className="field-error"><XCircle size={14} /><span><b>كلمة المرور أو بيانات الدخول غير صحيحة</b><small>{error}</small></span></span>}</label><button className="primary-button full" disabled={loading}>{loading ? 'جار تسجيل الدخول...' : 'دخول إلى لوحة التحكم'}</button></form><small className="login-api-url">API: {apiBaseUrl()}</small></div></div>
}
