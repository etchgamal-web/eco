import { useState } from 'react'
import { Store, XCircle } from 'lucide-react'
import { ApiError } from '../../../lib/api/client'
import { login } from '../../../lib/api/auth'

export function LoginScreen({ onSuccess }: { onSuccess: () => void }) {
  const [identifier, setIdentifier] = useState('')
  const [password, setPassword] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const submit = async (event: React.FormEvent) => { event.preventDefault(); setLoading(true); setError(''); try { await login(identifier, password); onSuccess() } catch (reason) { const message = reason instanceof ApiError ? reason.message : 'تعذر الاتصال بالخادم. تحقق من الاتصال ثم حاول مرة أخرى.'; setError(message) } finally { setLoading(false) } }
  return <div className="login-shell"><div className="login-card"><div className="login-brand"><span className="logo-mark"><Store size={21} /></span><b>سوقي Admin</b></div><span className="eyebrow">إدارة المتجر</span><h1>تسجيل الدخول</h1><p className="muted">استخدم حساب الإدارة للوصول إلى بيانات الـAPI.</p><form onSubmit={submit}><label className="form-field"><span>البريد الإلكتروني أو رقم الهاتف</span><input required value={identifier} onChange={(event) => setIdentifier(event.target.value)} placeholder="admin@example.com" autoComplete="username" /></label><label className="form-field"><span>كلمة المرور</span><input required type="password" value={password} onChange={(event) => setPassword(event.target.value)} placeholder="••••••••" autoComplete="current-password" aria-invalid={Boolean(error)} />{error && <span className="field-error"><XCircle size={14} /><span><b>كلمة المرور أو بيانات الدخول غير صحيحة</b><small>{error}</small></span></span>}</label><button className="primary-button full" disabled={loading}>{loading ? 'جار تسجيل الدخول...' : 'دخول إلى لوحة التحكم'}</button></form><small className="login-help">تحتاج إلى صلاحية مدير المتجر للدخول.</small></div></div>
}
