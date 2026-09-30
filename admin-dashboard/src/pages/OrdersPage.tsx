import { useEffect, useState } from 'react'
import { ChevronDown, Eye, FileText, Pencil, Printer, Search, ShoppingCart, X } from 'lucide-react'
import { ApiError, cancelOrder, confirmOrder, downloadOrdersCsv, getOrder, getOrderTimeline, listOrders, reviewOrder, updateOrderStatus } from '../lib/api'
import type { ApiOrder, ApiOrderPage } from '../lib/api'
import { nextOrderStatus, normalizeApiOrder, orderWorkflowPermissions } from '../features/orders/hooks/useDashboardOrders'
import { OrderDrawer } from '../features/orders/components/OrderDrawer'
import { PageStats } from '../components/PageStats'

type Props = { onToast: (message: string, type?: 'success' | 'error') => void; access?: { roles?: string[]; permissions?: string[] } }
const labels: Record<string, string> = { pending: 'جديد', reviewing: 'مراجعة', confirmed: 'مؤكد', processing: 'قيد التجهيز', shipped: 'تم الشحن', delivered: 'تم التسليم', cancelled: 'ملغي', refunded: 'مسترد' }

export function OrdersPage({ onToast, access }: Props) {
  const isAdmin = (access?.roles ?? []).some((role) => ['owner', 'admin'].includes(role))
  const can = (permission: string) => isAdmin || (access?.permissions ?? []).includes(permission)
  const [orders, setOrders] = useState<ApiOrder[]>([])
  const [loading, setLoading] = useState(true)
  const [query, setQuery] = useState('')
  const [status, setStatus] = useState('all')
  const [selected, setSelected] = useState<ApiOrder | null>(null)
  const [timeline, setTimeline] = useState<unknown[]>([])
  const [detailsLoading, setDetailsLoading] = useState(false)
  const [cancelTarget, setCancelTarget] = useState<ApiOrder | null>(null)
  const [page, setPage] = useState(1)
  const [pageMeta, setPageMeta] = useState({ current_page: 1, last_page: 1, per_page: 10, total: 0 })
  const load = (nextPage = page) => {
    setPage(nextPage)
    setLoading(true)
    listOrders({ page: nextPage, per_page: 10, search: query, ...(status !== 'all' ? { status } : {}) })
      .then((data) => { const result = data as ApiOrder[] | ApiOrderPage; if (Array.isArray(result)) { setOrders(result); setPageMeta({ current_page: nextPage, last_page: 1, per_page: 10, total: result.length }) } else { setOrders(result.data ?? []); setPageMeta({ current_page: result.current_page, last_page: result.last_page, per_page: result.per_page, total: result.total }) } })
      .catch((error: unknown) => onToast(error instanceof ApiError ? error.message : 'تعذر تحميل الطلبات', 'error'))
      .finally(() => setLoading(false))
  }
  // Filters are sent to Laravel; pagination remains server-side.
  // eslint-disable-next-line react-hooks/set-state-in-effect, react-hooks/exhaustive-deps
  useEffect(() => { load(1) }, [query, status])
  const canAdvance = (order: ApiOrder) => can(orderWorkflowPermissions[order.status] ?? 'orders.edit')
  const changeStatus = async (order: ApiOrder) => {
    const target = nextOrderStatus[order.status]
    if (!target) return onToast('لا توجد حالة انتقال تالية لهذا الطلب', 'error')
    if (!canAdvance(order)) return onToast('لا تملك صلاحية تنفيذ انتقال هذه المرحلة', 'error')
    try { const updated = order.status === 'pending' ? await reviewOrder(order.id) : order.status === 'reviewing' ? await confirmOrder(order.id) : await updateOrderStatus(order.id, target);  setOrders((items) => items.map((item) => item.id === order.id ? updated : item)); onToast(`تم نقل الطلب إلى ${labels[target] ?? target}`) }
    catch (error) { onToast(error instanceof ApiError ? error.message : 'تعذر تحديث الحالة', 'error') }
  }
  const openDetails = async (order: ApiOrder) => {
    setSelected(order); setTimeline([]); setDetailsLoading(true)
    try { const [details, events] = await Promise.all([getOrder(order.id), getOrderTimeline(order.id)]); setSelected(details); setTimeline(Array.isArray(events) ? events : []) }
    catch (error) { onToast(error instanceof ApiError ? error.message : 'تعذر تحميل تفاصيل الطلب', 'error') }
    finally { setDetailsLoading(false) }
  }
  const cancel = async () => {
    if (!cancelTarget) return
    try { const updated = await cancelOrder(cancelTarget.id); setOrders((items) => items.map((item) => item.id === cancelTarget.id ? updated : item)); setCancelTarget(null); onToast('تم إلغاء الطلب') }
    catch (error) { onToast(error instanceof ApiError ? error.message : 'تعذر إلغاء الطلب', 'error') }
  }
  const exportOrders = async () => { try { const blob = await downloadOrdersCsv(); const url = URL.createObjectURL(blob); const link = document.createElement('a'); link.href = url; link.download = `orders-${new Date().toISOString().slice(0, 10)}.csv`; link.click(); URL.revokeObjectURL(url); onToast('تم تصدير الطلبات') } catch (error) { onToast(error instanceof ApiError ? error.message : 'تعذر تصدير الطلبات', 'error') } }
  const closeDetails = () => { setSelected(null); setTimeline([]) }
  return <div className="screen-page"><div className="screen-header"><div><p className="eyebrow">المبيعات</p><h1>الطلبات</h1><p className="muted">تابع دورة الطلب: مراجعة، تواصل، تأكيد، تجهيز، شحن، وتسليم.</p></div>{can('orders.view') && <button className="outline-button" onClick={() => void exportOrders()}><FileText size={16} /> تصدير CSV</button>}</div><PageStats items={[{ label: 'إجمالي الطلبات', value: loading ? '...' : pageMeta.total }, { label: 'في الصفحة الحالية', value: loading ? '...' : orders.length }, { label: 'الطلبات الجديدة', value: loading ? '...' : orders.filter((item) => ['pending', 'reviewing'].includes(item.status)).length }, { label: 'الصفحة', value: `${page} / ${Math.max(1, pageMeta.last_page)}` }]} /><div className="screen-toolbar"><label className="screen-search"><Search size={17} /><input value={query} onChange={(event) => setQuery(event.target.value)} placeholder="ابحث برقم الطلب أو اسم العميل..." /></label><div className="filter-chips"><button className={`filter-chip ${status === 'all' ? 'selected' : ''}`} onClick={() => setStatus('all')}>الكل</button>{Object.keys(labels).map((value) => <button key={value} className={`filter-chip ${status === value ? 'selected' : ''}`} onClick={() => setStatus(value)}>{labels[value]}</button>)}</div></div><section className="data-card"><div className="table-wrap">{loading ? <SkeletonRows /> : orders.length === 0 ? <div className="empty-state"><ShoppingCart size={28} /><b>لا توجد طلبات</b><span>لم نعثر على طلبات مطابقة.</span></div> : <table><thead><tr><th>رقم الطلب</th><th>العميل</th><th>التاريخ</th><th>الإجمالي</th><th>الحالة</th><th>إجراءات</th></tr></thead><tbody>{orders.map((order) => <tr key={order.id}><td><b className="order-id">#{order.order_number ?? order.id}</b></td><td><span className="customer"><span className="customer-avatar">{(order.user?.name ?? 'عميل').slice(0, 2)}</span><b>{order.user?.name ?? order.user?.email ?? 'عميل متجر'}</b></span></td><td className="muted">{order.created_at ? new Date(order.created_at).toLocaleString('ar-EG') : '—'}</td><td><b>{order.total_amount} {order.currency ?? 'ر.س'}</b></td><td>{canAdvance(order) ? <button className="status-select" onClick={() => void changeStatus(order)}><span className={`status status-${order.status}`}><i />{labels[order.status] ?? order.status}</span><ChevronDown size={14} /></button> : <span className="status-select"><span className={`status status-${order.status}`}><i />{labels[order.status] ?? order.status}</span></span>}</td><td><div className="quick-actions"><button title="معاينة" onClick={() => void openDetails(order)}><Eye size={16} /></button>{can('orders.edit') && <button title="تعديل" onClick={() => void openDetails(order)}><Pencil size={16} /></button>}<button title="طباعة" onClick={() => window.print()}><Printer size={16} /></button>{can('orders.cancel') && <button className="danger" title="إلغاء" onClick={() => setCancelTarget(order)}><X size={16} /></button>}</div></td></tr>)}</tbody></table>}</div><div className="table-footer"><span className="muted">عرض {orders.length} من {pageMeta.total} طلبات</span><div className="pagination"><button disabled={page <= 1} onClick={() => { const next = page - 1; setPage(next); load(next) }}>السابق</button><b>{page} / {Math.max(1, pageMeta.last_page)}</b><button disabled={page >= pageMeta.last_page} onClick={() => { const next = page + 1; setPage(next); load(next) }}>التالي</button></div></div></section>{selected && <OrderDrawer order={normalizeApiOrder(selected)} details={selected} timeline={timeline} loading={detailsLoading} canCreateShipment={can('shipments.create')} canManageShipment={can('shipping.manage')} canManageOrders={can('orders.edit')} onClose={closeDetails} onToast={onToast} />}{cancelTarget && <ConfirmCancel order={cancelTarget} onCancel={() => setCancelTarget(null)} onConfirm={() => void cancel()} />}</div>
}
function SkeletonRows() { return <div className="skeleton-table">{[1, 2, 3, 4, 5].map((row) => <div className="skeleton-row" key={row}><span /><span /><span /><span /></div>)}</div> }
function ConfirmCancel({ order, onCancel, onConfirm }: { order: ApiOrder; onCancel: () => void; onConfirm: () => void }) { return <div className="modal-backdrop" onClick={onCancel}><div className="confirm-modal" onClick={(event) => event.stopPropagation()}><div className="danger-icon"><X size={20} /></div><h2>إلغاء الطلب؟</h2><p>سيتم إلغاء الطلب <b>#{order.order_number ?? order.id}</b>. هل تريد المتابعة؟</p><div className="modal-actions"><button className="outline-button" onClick={onCancel}>العودة</button><button className="danger-button" onClick={onConfirm}>تأكيد الإلغاء</button></div></div></div> }
