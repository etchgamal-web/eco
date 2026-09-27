import { LayoutDashboard } from 'lucide-react'

export function NotFound({ onHome }: { onHome: () => void }) {
  return <div className="not-found-page"><div className="not-found-card"><span className="not-found-code">404</span><h1>الصفحة غير موجودة</h1><p>يبدو أن الرابط الذي فتحته غير صحيح أو أن الصفحة نُقلت.</p><button className="primary-button" onClick={onHome}><LayoutDashboard size={16} /> العودة للرئيسية</button></div></div>
}

export function NotAuthorized({ onHome }: { onHome: () => void }) {
  return <div className="not-found-page"><div className="not-found-card"><span className="not-found-code">403</span><h1>غير مصرح بالوصول</h1><p>لا تملك الصلاحية اللازمة لفتح هذا القسم. تواصل مع مدير النظام إذا كنت تحتاج الوصول.</p><button className="primary-button" onClick={onHome}><LayoutDashboard size={16} /> العودة للرئيسية</button></div></div>
}
