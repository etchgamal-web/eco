import { Bell, CheckCircle2, ChevronDown, CircleDollarSign, Info, MoreHorizontal, Trash2, TrendingUp, X, XCircle } from 'lucide-react'
import type { Order } from '../../features/orders/types'

export type ToastMessage = { type: 'success' | 'error' | 'info'; message: string; title?: string; detail?: string; duration?: number }

export function KpiCard({ icon: Icon, label, value, change, positive, subValue, onClick }: { icon: typeof CircleDollarSign; label: string; value: string; change: string; positive: boolean; subValue?: string; onClick?: () => void }) { return <article className={`kpi-card ${onClick ? 'is-clickable' : ''}`} onClick={onClick} onKeyDown={(event) => { if (onClick && (event.key === 'Enter' || event.key === ' ')) { event.preventDefault(); onClick() } }} tabIndex={onClick ? 0 : undefined} role={onClick ? 'link' : undefined}><div className="kpi-top"><span className="kpi-icon"><Icon size={19} /></span><span className="kpi-label">{label}</span><button className="more-button" aria-label={`فتح ${label}`} onClick={(event) => { event.stopPropagation(); onClick?.() }}><MoreHorizontal size={18} /></button></div><strong className={`kpi-value ${subValue ? 'product-value' : ''}`}>{value}</strong>{subValue && <span className="kpi-subvalue">{subValue}</span>}<span className={`kpi-change ${positive ? 'positive' : 'negative'}`}><TrendingUp size={13} />{change}<small>فتح الصفحة المختصة</small></span></article> }

export function FilterSelect({ value, onChange, options }: { value: string; onChange: (value: string) => void; options: string[] }) { return <label className="filter-select"><select value={value} onChange={(event) => onChange(event.target.value)}>{options.map((option) => <option key={option}>{option}</option>)}</select><ChevronDown size={14} /></label> }

export function NotificationMenu({ orders, onClose, onInfo }: { orders: Order[]; onClose: () => void; onInfo: () => void }) {
  const newOrders = orders.filter((order) => order.status === 'جديد').slice(0, 3)
  return <div className="notification-menu" onClick={(event) => event.stopPropagation()}><div className="notification-menu-head"><b>مركز التنبيهات</b><button onClick={onClose}>إغلاق</button></div>{newOrders.length === 0 ? <div className="notification-empty"><Bell size={20} /><span>لا توجد تنبيهات جديدة</span></div> : newOrders.map((order) => <button className="notification-item unread" key={order.id} onClick={() => { onClose(); onInfo() }}><span className="notification-dot blue" /><div><b>طلب جديد {order.id}</b><small>{order.customer} · {order.total}</small><em>{order.date}</em></div></button>)}<button className="notification-footer" onClick={onInfo}>فتح المتابعة والتنبيهات</button></div>
}

export function DeleteModal({ order, onCancel, onConfirm }: { order: Order; onCancel: () => void; onConfirm: () => void }) {
  return <div className="modal-backdrop" onClick={onCancel}><div className="confirm-modal" onClick={(event) => event.stopPropagation()}><div className="danger-icon"><Trash2 size={21} /></div><h2>أرشفة الطلب؟</h2><p>سيتم نقل الطلب <b>{order.id}</b> إلى الأرشيف. يمكنك استرجاعه خلال ٣٠ يوماً.</p><div className="modal-actions"><button className="outline-button" onClick={onCancel}>إلغاء</button><button className="danger-button" onClick={onConfirm}>تأكيد الأرشفة</button></div></div></div>
}

export function ToastViewport({ toast, onClose }: { toast: ToastMessage; onClose: () => void }) {
  const Icon = toast.type === 'success' ? CheckCircle2 : toast.type === 'error' ? XCircle : Info
  const title = toast.title ?? (toast.type === 'success' ? 'تم بنجاح' : toast.type === 'error' ? 'تعذر تنفيذ الطلب' : 'تنبيه')
  return <div className={`toast ${toast.type}`} role={toast.type === 'error' ? 'alert' : 'status'} aria-live="polite"><span className="toast-icon"><Icon size={18} /></span><span className="toast-copy"><b>{title}</b><small>{toast.message}</small>{toast.detail && <em>{toast.detail}</em>}</span><span className="toast-progress" aria-hidden="true" /><button className="toast-close" onClick={onClose} aria-label="إغلاق الإشعار"><X size={15} /></button></div>
}
