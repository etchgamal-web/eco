import type { LucideIcon } from 'lucide-react'

type Stat = { label: string; value: string | number; hint?: string; icon?: LucideIcon; tone?: 'blue' | 'green' | 'amber' | 'purple' }

export function PageStats({ items }: { items: Stat[] }) {
  return <section className="page-stats" aria-label="إحصائيات الصفحة">{items.map((item) => { const Icon = item.icon; return <article className={`page-stat-card ${item.tone ?? 'blue'}`} key={item.label}>{Icon && <span className="page-stat-icon"><Icon size={16} /></span>}<div><span>{item.label}</span><strong>{item.value}</strong>{item.hint && <small>{item.hint}</small>}</div></article> })}</section>
}
