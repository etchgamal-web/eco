import { useEffect, useState } from 'react'
import { Search, Users, X } from 'lucide-react'
import { ApiError, getCustomer, listCustomers } from '../lib/api'
import type { ApiCustomer } from '../lib/api'

type Props = { onToast: (message: string, type?: 'success' | 'error') => void }
type CustomerDetail = { customer: ApiCustomer; orders: Array<{ id: number; order_number?: string | null; status: string; total_amount: number; currency?: string | null; created_at?: string | null }> }

export function CustomersPage({ onToast }: Props) {
  const [customers, setCustomers] = useState<ApiCustomer[]>([])
  const [query, setQuery] = useState('')
  const [page, setPage] = useState(1)
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, per_page: 20, total: 0 })
  const [loading, setLoading] = useState(true)
  const [detail, setDetail] = useState<CustomerDetail | null>(null)
  const [detailLoading, setDetailLoading] = useState(false)

  const load = async (nextPage = page, search = query) => { setLoading(true); try { const result = await listCustomers({ search: search.trim(), page: nextPage, per_page: 20 }); setCustomers(result.data); setMeta(result.meta); setPage(result.meta.current_page) } catch (error) { onToast(error instanceof ApiError ? error.message : 'تعذر تحميل العملاء', 'error') } finally { setLoading(false) } }
  // Initial synchronization with the external Laravel API.
  // eslint-disable-next-line react-hooks/set-state-in-effect, react-hooks/exhaustive-deps
  useEffect(() => { void load(1, '') }, [])
  const submitSearch = (event: React.FormEvent) => { event.preventDefault(); void load(1, query) }
  const openDetail = async (id: number) => { setDetailLoading(true); try { setDetail(await getCustomer(id)) } catch (error) { onToast(error instanceof ApiError ? error.message : 'تعذر تحميل تفاصيل العميل', 'error') } finally { setDetailLoading(false) } }

  return <div className="screen-page"><div className="screen-header"><div><p className="eyebrow">العلاقات</p><h1>العملاء</h1><p className="muted">عرض العملاء المسجلين وإجمالي طلباتهم وقيمة مشترياتهم.</p></div></div><form className="screen-toolbar" onSubmit={submitSearch}><label className="screen-search"><Search size={17} /><input value={query} onChange={(event) => setQuery(event.target.value)} placeholder="ابحث بالاسم أو البريد أو الهاتف..." /></label><button className="primary-button"><Search size={15} /> بحث</button></form><section className="data-card"><div className="table-wrap">{loading ? <div className="settings-loading">جار تحميل العملاء...</div> : customers.length === 0 ? <div className="empty-state"><Users size={28} /><b>لا توجد نتائج</b><span>لم نعثر على عملاء مطابقين للبحث.</span></div> : <table><thead><tr><th>العميل</th><th>البريد</th><th>الهاتف</th><th>الطلبات</th><th>إجمالي المشتريات</th><th>الحالة</th><th>تاريخ التسجيل</th></tr></thead><tbody>{customers.map((customer) => <tr key={customer.id} className="clickable-row" onClick={() => void openDetail(customer.id)}><td><span className="customer"><span className="customer-avatar">{(customer.name ?? 'عميل').slice(0, 2)}</span><b>{customer.name ?? 'بدون اسم'}</b></span></td><td className="muted">{customer.email ?? '—'}</td><td className="muted">{customer.phone ?? '—'}</td><td><b>{customer.orders_count ?? 0}</b></td><td><b>{Number(customer.total_spent ?? 0).toLocaleString('ar-SA')} ر.س</b></td><td><span className={`status status-${customer.status === 'active' ? 'تم-الشحن' : 'مكتمل'}`}><i />{customer.status === 'active' ? 'نشط' : 'غير نشط'}</span></td><td className="muted">{customer.created_at ? new Date(customer.created_at).toLocaleDateString('ar-EG') : '—'}</td></tr>)}</tbody></table>}</div><div className="table-footer"><span className="muted">إجمالي العملاء: {meta.total}</span><div className="pagination"><button disabled={page <= 1 || loading} onClick={() => void load(page - 1)}>السابق</button><b>{page} / {meta.last_page}</b><button disabled={page >= meta.last_page || loading} onClick={() => void load(page + 1)}>التالي</button></div></div></section>{detailLoading && <div className="modal-backdrop"><div className="confirm-modal">جار تحميل تفاصيل العميل...</div></div>}{detail && <CustomerDrawer detail={detail} onClose={() => setDetail(null)} />}</div>
}

function CustomerDrawer({ detail, onClose }: { detail: CustomerDetail; onClose: () => void }) { const { customer, orders } = detail; return <div className="drawer-overlay" onClick={onClose}><aside className="order-drawer" onClick={(event) => event.stopPropagation()}><div className="drawer-head"><div><span className="eyebrow">ملف العميل</span><h2>{customer.name ?? 'بدون اسم'}</h2></div><button className="icon-button" onClick={onClose}><X size={20} /></button></div><div className="drawer-info"><div><small>البريد الإلكتروني</small><b>{customer.email ?? '—'}</b></div><div><small>الهاتف</small><b>{customer.phone ?? '—'}</b></div><div><small>الحالة</small><b>{customer.status === 'active' ? 'نشط' : 'غير نشط'}</b></div><div><small>تاريخ التسجيل</small><b>{customer.created_at ? new Date(customer.created_at).toLocaleDateString('ar-EG') : '—'}</b></div></div><h3>آخر الطلبات</h3>{orders.length === 0 ? <p className="muted">لا توجد طلبات لهذا العميل.</p> : <div className="customer-order-list">{orders.map((order) => <div className="customer-order-row" key={order.id}><div><b>#{order.order_number ?? order.id}</b><small>{order.created_at ? new Date(order.created_at).toLocaleDateString('ar-EG') : '—'}</small></div><div><b>{order.total_amount} {order.currency ?? 'ر.س'}</b><small>{order.status}</small></div></div>)}</div>}</aside></div> }
