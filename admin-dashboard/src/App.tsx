import { useEffect, useMemo, useState } from 'react'
import {
  AlertTriangle, Bell, Bot, Boxes, Check, CheckCircle2, ChevronDown, CircleDollarSign, CreditCard, Eye, FileText,
  HelpCircle, Info, LayoutDashboard, Menu, MessageCircle, MoreHorizontal, Package, Pencil, Plus, Printer,
  Search, Settings, Shield, ShoppingCart, Store, Trash2, TrendingUp, Users, X, XCircle,
} from 'lucide-react'
import { ApiError, apiBaseUrl, cancelOrder, getToken, listOrders, login, updateOrderStatus } from './lib/api'
import { CatalogPage } from './pages/CatalogPage'
import { InventoryPage } from './pages/InventoryPage'
import { OrdersPage } from './pages/OrdersPage'
import { ReportsPage } from './pages/ReportsPage'
import { SettingsPage } from './pages/SettingsPage'
import { ManagementPage } from './pages/ManagementPage'
import { CommercePage } from './pages/CommercePage'
import { FinancePage } from './pages/FinancePage'
import { SettlementsPage } from './pages/SettlementsPage'
import { TaxonomyPage } from './pages/TaxonomyPage'
import { PaymentsPage } from './pages/PaymentsPage'
import { SocialPage } from './pages/SocialPage'
import { AiPage } from './pages/AiPage'
import { MonitoringPage } from './pages/MonitoringPage'
import './App.css'

type OrderStatus = 'جديد' | 'قيد التجهيز' | 'تم الشحن' | 'مكتمل'
type Order = { id: string; apiId?: number; customer: string; initials: string; date: string; total: string; payment: string; status: OrderStatus }

const orders: Order[] = [
  { id: '#ORD-8294', customer: 'سارة العتيبي', initials: 'سع', date: 'اليوم، ١٠:٤٢ ص', total: '٥٩٧ ر.س', payment: 'مدى', status: 'جديد' },
  { id: '#ORD-8293', customer: 'محمد القحطاني', initials: 'مق', date: 'اليوم، ٠٩:١٨ ص', total: '١,٢٤٠ ر.س', payment: 'Apple Pay', status: 'قيد التجهيز' },
  { id: '#ORD-8292', customer: 'نورة الحربي', initials: 'نه', date: 'أمس، ٠٦:٣٥ م', total: '٣٩٩ ر.س', payment: 'بطاقة ائتمانية', status: 'تم الشحن' },
  { id: '#ORD-8291', customer: 'خالد الشهري', initials: 'خش', date: 'أمس، ٠٢:١١ م', total: '٨٧٥ ر.س', payment: 'مدى', status: 'مكتمل' },
  { id: '#ORD-8290', customer: 'ريم الغامدي', initials: 'رغ', date: '٢٠ أغسطس، ١١:٠٣ ص', total: '٢١٠ ر.س', payment: 'الدفع عند الاستلام', status: 'قيد التجهيز' },
]

const navItems = [
  { label: 'الرئيسية', icon: LayoutDashboard }, { label: 'الطلبات', icon: ShoppingCart },
  { label: 'المنتجات', icon: Package }, { label: 'هيكلة الكتالوج', icon: Boxes }, { label: 'المخزون', icon: Boxes }, { label: 'التقارير', icon: TrendingUp }, { label: 'الإدارة', icon: Shield }, { label: 'التجارة', icon: Store }, { label: 'التجارة الاجتماعية', icon: MessageCircle }, { label: 'الذكاء الاصطناعي', icon: Bot }, { label: 'المراقبة', icon: AlertTriangle }, { label: 'المالية', icon: CircleDollarSign }, { label: 'المدفوعات', icon: CreditCard }, { label: 'التسويات', icon: FileText }, { label: 'الإعدادات', icon: Settings },
]
const statusOptions: OrderStatus[] = ['جديد', 'قيد التجهيز', 'تم الشحن', 'مكتمل']
const backendStatuses = ['pending', 'processing', 'shipped', 'delivered']
const statusLabels: Record<string, OrderStatus> = { pending: 'جديد', reviewing: 'جديد', confirmed: 'جديد', processing: 'قيد التجهيز', shipped: 'تم الشحن', delivered: 'مكتمل', cancelled: 'مكتمل', refunded: 'مكتمل' }

function normalizeApiOrder(order: import('./lib/api').ApiOrder): Order {
  const customer = order.user?.name || order.user?.email || 'عميل متجر'
  return { id: `#${order.order_number || order.id}`, apiId: order.id, customer, initials: customer.slice(0, 2), date: order.created_at ? new Date(order.created_at).toLocaleString('ar-EG', { dateStyle: 'medium', timeStyle: 'short' }) : '—', total: `${order.total_amount ?? 0} ${order.currency || 'ر.س'}`, payment: 'غير محدد', status: statusLabels[order.status] || 'جديد' }
}

function StatusBadge({ status }: { status: OrderStatus }) {
  return <span className={`status status-${status.replaceAll(' ', '-')}`}><i />{status}</span>
}

function App() {
  const [authenticated, setAuthenticated] = useState(() => Boolean(getToken()))
  const [activeNav, setActiveNav] = useState('الرئيسية')
  const [statusFilter, setStatusFilter] = useState<'الكل' | OrderStatus>('الكل')
  const [paymentFilter, setPaymentFilter] = useState('كل طرق الدفع')
  const [dateFilter, setDateFilter] = useState('كل التواريخ')
  const [search, setSearch] = useState('')
  const [rows, setRows] = useState(orders)
  const [selectedOrder, setSelectedOrder] = useState<Order | null>(null)
  const [toast, setToast] = useState<{ type: 'success' | 'error' | 'info'; message: string } | null>(null)
  const [mobileNav, setMobileNav] = useState(false)
  const [notificationsOpen, setNotificationsOpen] = useState(false)
  const [commandOpen, setCommandOpen] = useState(false)
  const [productModalOpen, setProductModalOpen] = useState(false)
  const [pendingDelete, setPendingDelete] = useState<Order | null>(null)
  const [apiLoading, setApiLoading] = useState(Boolean(getToken()))

  useEffect(() => { if (!toast) return; const timer = window.setTimeout(() => setToast(null), 4000); return () => window.clearTimeout(timer) }, [toast])
  useEffect(() => { document.documentElement.dir = 'rtl'; document.documentElement.lang = 'ar' }, [])
  useEffect(() => { const handler = (event: KeyboardEvent) => { if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') { event.preventDefault(); setCommandOpen(true) } if (event.key === 'Escape') { setCommandOpen(false); setProductModalOpen(false); setPendingDelete(null) } }; window.addEventListener('keydown', handler); return () => window.removeEventListener('keydown', handler) }, [])
  useEffect(() => { if (!authenticated) return; listOrders().then((data) => setRows(data.map(normalizeApiOrder))).catch((error: unknown) => setToast({ type: 'error', message: error instanceof ApiError ? error.message : 'تعذر تحميل الطلبات من الـAPI' })).finally(() => setApiLoading(false)) }, [authenticated])

  const filteredOrders = useMemo(() => rows.filter((order) => {
    const matchesStatus = statusFilter === 'الكل' || order.status === statusFilter
    const matchesPayment = paymentFilter === 'كل طرق الدفع' || order.payment === paymentFilter
    const matchesSearch = `${order.id} ${order.customer}`.includes(search.trim())
    return matchesStatus && matchesPayment && matchesSearch && (dateFilter === 'كل التواريخ' || (dateFilter === 'اليوم' ? order.date.startsWith('اليوم') : !order.date.startsWith('اليوم')))
  }), [rows, statusFilter, paymentFilter, search, dateFilter])

  const updateStatus = async (id: string, status: OrderStatus) => { const order = rows.find((item) => item.id === id); const nextBackendStatus = backendStatuses[statusOptions.indexOf(status)] || 'processing'; try { if (order?.apiId && getToken()) await updateOrderStatus(order.apiId, nextBackendStatus); setRows((current) => current.map((item) => item.id === id ? { ...item, status } : item)); setToast({ type: 'success', message: `تم تغيير حالة الطلب ${id}` }) } catch (error) { setToast({ type: 'error', message: error instanceof ApiError ? error.message : 'تعذر تغيير حالة الطلب' }) } }
  const removeOrder = async (id: string) => { const order = rows.find((item) => item.id === id); try { if (order?.apiId && getToken()) await cancelOrder(order.apiId); setRows((current) => current.filter((item) => item.id !== id)); setPendingDelete(null); setToast({ type: 'success', message: `تمت أرشفة الطلب ${id} ويمكن استرجاعه خلال ٣٠ يوماً` }) } catch (error) { setToast({ type: 'error', message: error instanceof ApiError ? error.message : 'تعذر إلغاء الطلب' }) } }

  if (!authenticated) return <LoginScreen onSuccess={() => setAuthenticated(true)} />

  return <div className="dashboard-shell"><aside className={`compact-sidebar ${mobileNav ? 'mobile-open' : ''}`}><div className="sidebar-top"><div className="logo-mark" aria-label="سوقي"><Store size={21} /></div><button className="mobile-close" onClick={() => setMobileNav(false)} aria-label="إغلاق القائمة"><X size={20} /></button></div><nav className="main-nav" aria-label="التنقل الرئيسي">{navItems.map(({ label, icon: Icon }) => <button key={label} className={`nav-icon ${activeNav === label ? 'active' : ''}`} onClick={() => { setActiveNav(label); setMobileNav(false) }} title={label} aria-label={label}><Icon size={20} /><span className="tooltip">{label}</span></button>)}</nav><div className="sidebar-bottom"><button className="nav-icon" title="المساعدة" aria-label="المساعدة"><HelpCircle size={20} /><span className="tooltip">المساعدة</span></button><button className="nav-icon" title="الإعدادات" aria-label="الإعدادات"><Settings size={20} /><span className="tooltip">الإعدادات</span></button><button className="profile-avatar" title="الملف الشخصي" aria-label="الملف الشخصي">م</button></div></aside>{mobileNav && <button className="sidebar-backdrop" onClick={() => setMobileNav(false)} aria-label="إغلاق القائمة" />}
    <main className="main-content"><header className="topbar"><button className="mobile-menu" onClick={() => setMobileNav(true)} aria-label="فتح القائمة"><Menu size={21} /></button><div className="breadcrumbs"><span>الرئيسية</span><b>/</b><strong>نظرة عامة</strong></div><div className="topbar-actions"><label className="global-search" onClick={() => setCommandOpen(true)}><Search size={17} /><input value={search} onChange={(event) => setSearch(event.target.value)} onFocus={() => setCommandOpen(true)} placeholder="ابحث عن طلب أو عميل..." /><kbd>⌘ K</kbd></label><div className="notification-wrap"><button className="notification-button" onClick={() => setNotificationsOpen(!notificationsOpen)} aria-label="الإشعارات"><Bell size={19} /><i /></button>{notificationsOpen && <NotificationMenu onClose={() => setNotificationsOpen(false)} onInfo={() => { setNotificationsOpen(false); setToast({ type: 'info', message: 'تم تحديث حالة المخزون بنجاح' }) }} />}</div><div className="top-profile"><span className="profile-avatar small">م</span><div><b>محمد أحمد</b><small>مدير المتجر</small></div><ChevronDown size={15} /></div></div></header><div className="content">{activeNav === 'المنتجات' ? <CatalogPage onToast={(message, type) => setToast({ type: type ?? 'success', message })} /> : activeNav === 'هيكلة الكتالوج' ? <TaxonomyPage onToast={(message, type) => setToast({ type: type ?? 'success', message })} /> : activeNav === 'الطلبات' ? <OrdersPage onToast={(message, type) => setToast({ type: type ?? 'success', message })} /> : activeNav === 'المخزون' ? <InventoryPage onToast={(message, type) => setToast({ type: type ?? 'success', message })} /> : activeNav === 'التقارير' ? <ReportsPage onToast={(message, type) => setToast({ type: type ?? 'success', message })} /> : activeNav === 'الإدارة' ? <ManagementPage onToast={(message, type) => setToast({ type: type ?? 'success', message })} /> : activeNav === 'التجارة' ? <CommercePage onToast={(message, type) => setToast({ type: type ?? 'success', message })} /> : activeNav === 'التجارة الاجتماعية' ? <SocialPage onToast={(message, type) => setToast({ type: type ?? 'success', message })} /> : activeNav === 'الذكاء الاصطناعي' ? <AiPage onToast={(message, type) => setToast({ type: type ?? 'success', message })} /> : activeNav === 'المراقبة' ? <MonitoringPage onToast={(message, type) => setToast({ type: type ?? 'success', message })} /> : activeNav === 'المالية' ? <FinancePage onToast={(message, type) => setToast({ type: type ?? 'success', message })} /> : activeNav === 'المدفوعات' ? <PaymentsPage onToast={(message, type) => setToast({ type: type ?? 'success', message })} /> : activeNav === 'التسويات' ? <SettlementsPage onToast={(message, type) => setToast({ type: type ?? 'success', message })} /> : activeNav === 'الإعدادات' ? <SettingsPage onToast={(message, type) => setToast({ type: type ?? 'success', message })} /> : <div className="dashboard-home">{apiLoading && <div className="api-loading" role="status"><span className="skeleton-line" /><span className="skeleton-line short" /><span>جار تحميل بيانات المتجر من الـAPI...</span></div>}<div className="page-title"><div><p className="eyebrow">السبت، ٢٤ أغسطس ٢٠٢٤</p><h1>لوحة التحكم</h1><p className="muted">تابع أداء متجرك وطلباتك من مكان واحد.</p></div><button className="primary-button" onClick={() => setProductModalOpen(true)}><Plus size={17} /> إضافة منتج</button></div><section className="kpi-grid" aria-label="مؤشرات الأداء الرئيسية"><KpiCard icon={CircleDollarSign} label="إجمالي المبيعات" value="٩٨٣,٤١٠ ر.س" change="+١٢.٤٪" positive /><KpiCard icon={ShoppingCart} label="الطلبات الجديدة" value="١,٢٨٤" change="+٨.٢٪" positive /><KpiCard icon={TrendingUp} label="صافي الأرباح" value="٢٣٧,٧٨٢ ر.س" change="+١٥.٧٪" positive /><KpiCard icon={Package} label="الأكثر مبيعاً" value="سماعات لاسلكية Pro" subValue="١,٤٢٠ قطعة مباعة" change="+٢١.٣٪" positive /></section><section className="orders-card"><div className="orders-heading"><div><h2>الطلبات الأخيرة</h2><p className="muted">إدارة ومتابعة طلبات متجرك اليومية</p></div><button className="outline-button" onClick={() => setToast({ type: 'success', message: 'تم تصدير تقرير الطلبات بنجاح' })}><FileText size={16} /> تصدير التقرير</button></div><div className="filters"><div className="filter-chips"><button className={`filter-chip ${statusFilter === 'الكل' ? 'selected' : ''}`} onClick={() => setStatusFilter('الكل')}>كل الحالات</button>{statusOptions.map((status) => <button key={status} className={`filter-chip ${statusFilter === status ? 'selected' : ''}`} onClick={() => setStatusFilter(status)}>{status}</button>)}</div><div className="select-filters"><FilterSelect value={dateFilter} onChange={setDateFilter} options={['كل التواريخ', 'اليوم', 'هذا الأسبوع']} /><FilterSelect value={paymentFilter} onChange={setPaymentFilter} options={['كل طرق الدفع', 'مدى', 'Apple Pay', 'بطاقة ائتمانية', 'الدفع عند الاستلام']} /></div></div><div className="table-wrap">{filteredOrders.length === 0 ? <div className="empty-state"><ShoppingCart size={28} /><b>لا توجد طلبات مطابقة</b><span>جرّب تغيير الفلاتر أو عبارة البحث.</span><button className="outline-button" onClick={() => { setStatusFilter('الكل'); setPaymentFilter('كل طرق الدفع'); setDateFilter('كل التواريخ'); setSearch('') }}>إعادة ضبط الفلاتر</button></div> : <table><thead><tr><th>رقم الطلب</th><th>العميل</th><th>التاريخ</th><th>الإجمالي</th><th>طريقة الدفع</th><th>الحالة</th><th>إجراءات</th></tr></thead><tbody>{filteredOrders.map((order) => <tr key={order.id}><td><b className="order-id">{order.id}</b></td><td><span className="customer"><span className="customer-avatar">{order.initials}</span><b>{order.customer}</b></span></td><td className="muted">{order.date}</td><td><b className="tabular">{order.total}</b></td><td className="muted">{order.payment}</td><td><button className="status-select" onClick={() => updateStatus(order.id, statusOptions[(statusOptions.indexOf(order.status) + 1) % statusOptions.length])}><StatusBadge status={order.status} /><ChevronDown size={14} /></button></td><td><div className="quick-actions"><button title="معاينة" aria-label="معاينة" onClick={() => setSelectedOrder(order)}><Eye size={17} /></button><button title="تعديل" aria-label="تعديل" onClick={() => setProductModalOpen(true)}><Pencil size={16} /></button><button title="طباعة" aria-label="طباعة"><Printer size={16} /></button><button className="danger" title="حذف" aria-label="حذف" onClick={() => setPendingDelete(order)}><Trash2 size={16} /></button></div></td></tr>)}</tbody></table>}</div><div className="table-footer"><span className="muted">عرض {filteredOrders.length} من {rows.length} طلبات</span><div className="pagination"><button disabled>السابق</button><b>١</b><button>التالي</button></div></div></section></div>}</div></main>{selectedOrder && <OrderDrawer order={selectedOrder} onClose={() => setSelectedOrder(null)} />}{commandOpen && <CommandPalette onClose={() => setCommandOpen(false)} onSelect={(message) => { setCommandOpen(false); setToast({ type: 'info', message }) }} />}{productModalOpen && <ProductModal onClose={() => setProductModalOpen(false)} onSave={() => { setProductModalOpen(false); setToast({ type: 'success', message: 'تم حفظ المنتج بنجاح' }) }} />}{pendingDelete && <DeleteModal order={pendingDelete} onCancel={() => setPendingDelete(null)} onConfirm={() => removeOrder(pendingDelete.id)} />}{toast && <ToastViewport toast={toast} onClose={() => setToast(null)} />}</div>
}

function KpiCard({ icon: Icon, label, value, change, positive, subValue }: { icon: typeof CircleDollarSign; label: string; value: string; change: string; positive: boolean; subValue?: string }) { return <article className="kpi-card"><div className="kpi-top"><span className="kpi-icon"><Icon size={19} /></span><span className="kpi-label">{label}</span><button className="more-button" aria-label="المزيد"><MoreHorizontal size={18} /></button></div><strong className={`kpi-value ${subValue ? 'product-value' : ''}`}>{value}</strong>{subValue && <span className="kpi-subvalue">{subValue}</span>}<span className={`kpi-change ${positive ? 'positive' : 'negative'}`}><TrendingUp size={13} />{change}<small>مقارنة بالشهر الماضي</small></span></article> }
function FilterSelect({ value, onChange, options }: { value: string; onChange: (value: string) => void; options: string[] }) { return <label className="filter-select"><select value={value} onChange={(event) => onChange(event.target.value)}>{options.map((option) => <option key={option}>{option}</option>)}</select><ChevronDown size={14} /></label> }
function OrderDrawer({ order, onClose }: { order: Order; onClose: () => void }) { return <div className="drawer-overlay" onClick={onClose}><aside className="order-drawer" onClick={(event) => event.stopPropagation()}><div className="drawer-head"><div><span className="eyebrow">تفاصيل الطلب</span><h2>{order.id}</h2></div><button className="icon-button" onClick={onClose}><X size={20} /></button></div><div className="drawer-status"><StatusBadge status={order.status} /><span className="muted">آخر تحديث منذ ١٥ دقيقة</span></div><div className="timeline"><span className="done"><Check size={13} /></span><i /><span className="done"><Check size={13} /></span><i /><span className="current"><Package size={13} /></span><i /><span><TruckIcon /></span></div><div className="timeline-labels"><small>تم الاستلام</small><small>تم التأكيد</small><small>قيد التجهيز</small><small>الشحن</small></div><h3>بيانات العميل</h3><div className="drawer-info"><div><small>العميل</small><b>{order.customer}</b></div><div><small>طريقة الدفع</small><b>{order.payment}</b></div><div className="wide"><small>عنوان الشحن</small><b>حي النخيل، الرياض، المملكة العربية السعودية</b></div></div><h3>المنتجات</h3><div className="drawer-product"><span className="product-thumb"><Package size={20} /></span><div><b>سماعات لاسلكية Pro</b><small>الكمية: ١</small></div><strong>٣٩٩ ر.س</strong></div><div className="drawer-product"><span className="product-thumb"><Package size={20} /></span><div><b>حافظة جلدية فاخرة</b><small>الكمية: ٢</small></div><strong>١٩٨ ر.س</strong></div><div className="drawer-total"><span>الإجمالي</span><b>{order.total}</b></div><h3>ملاحظات داخلية</h3><textarea placeholder="أضف ملاحظة للفريق..." /><button className="primary-button full">حفظ الملاحظة</button></aside></div> }
function TruckIcon() { return <ShoppingCart size={13} /> }

export default App


type ToastMessage = { type: 'success' | 'error' | 'info'; message: string }

function NotificationMenu({ onClose, onInfo }: { onClose: () => void; onInfo: () => void }) {
  return <div className="notification-menu" onClick={(event) => event.stopPropagation()}><div className="notification-menu-head"><b>مركز التنبيهات</b><button onClick={onClose}>إغلاق</button></div><div className="notification-item unread"><span className="notification-dot blue" /><div><b>طلب جديد #ORD-8294</b><small>سارة العتيبي أتمت طلباً بقيمة ٥٩٧ ر.س</small><em>منذ ٥ دقائق</em></div></div><button className="notification-item" onClick={onInfo}><span className="notification-dot amber" /><div><b>المخزون منخفض</b><small>سماعات لاسلكية Pro · متبقي ٤ قطع</small><em>منذ ٢٠ دقيقة</em></div></button><div className="notification-footer">عرض كل الإشعارات</div></div>
}

function CommandPalette({ onClose, onSelect }: { onClose: () => void; onSelect: (message: string) => void }) {
  return <div className="modal-backdrop" onClick={onClose}><div className="command-palette" onClick={(event) => event.stopPropagation()}><div className="command-search"><Search size={18} /><input autoFocus placeholder="ابحث في المنتجات والعملاء والطلبات..." /><kbd>ESC</kbd></div><p className="command-label">اقتراحات سريعة</p><button onClick={() => onSelect('تم فتح نتائج الطلبات')}><ShoppingCart size={17} /><span><b>البحث في الطلبات</b><small>الوصول إلى كل الطلبات والفلاتر</small></span><ChevronDown size={15} /></button><button onClick={() => onSelect('تم فتح كتالوج المنتجات')}><Package size={17} /><span><b>البحث في المنتجات</b><small>إدارة المنتجات والمخزون</small></span><ChevronDown size={15} /></button><button onClick={() => onSelect('تم فتح قائمة العملاء')}><Users size={17} /><span><b>البحث في العملاء</b><small>عرض بيانات العملاء وطلباتهم</small></span><ChevronDown size={15} /></button></div></div>
}

function ProductModal({ onClose, onSave }: { onClose: () => void; onSave: () => void }) {
  return <div className="modal-backdrop" onClick={onClose}><div className="form-modal" onClick={(event) => event.stopPropagation()}><div className="modal-head"><div><span className="eyebrow">كتالوج المتجر</span><h2>إضافة منتج جديد</h2></div><button className="icon-button" onClick={onClose}><X size={19} /></button></div><form onSubmit={(event) => { event.preventDefault(); onSave() }}><label className="form-field"><span>اسم المنتج <i>*</i></span><input required autoFocus placeholder="مثال: سماعات لاسلكية Pro" /></label><label className="form-field"><span>التصنيف</span><select><option>الإلكترونيات</option><option>الأزياء</option><option>المنزل والمطبخ</option></select></label><div className="form-row"><label className="form-field"><span>السعر</span><input type="number" min="0" placeholder="٠٫٠٠" /></label><label className="form-field"><span>المخزون</span><input type="number" min="0" placeholder="٠" /></label></div><label className="form-field"><span>الوصف</span><textarea placeholder="اكتب وصفاً مختصراً للمنتج..." /></label><div className="form-error"><AlertTriangle size={15} /> سيتم التحقق من الحقول المطلوبة قبل الحفظ</div><div className="modal-actions"><button type="button" className="outline-button" onClick={onClose}>إلغاء</button><button type="submit" className="primary-button">حفظ المنتج</button></div></form></div></div>
}

function DeleteModal({ order, onCancel, onConfirm }: { order: Order; onCancel: () => void; onConfirm: () => void }) {
  return <div className="modal-backdrop" onClick={onCancel}><div className="confirm-modal" onClick={(event) => event.stopPropagation()}><div className="danger-icon"><Trash2 size={21} /></div><h2>أرشفة الطلب؟</h2><p>سيتم نقل الطلب <b>{order.id}</b> إلى الأرشيف. يمكنك استرجاعه خلال ٣٠ يوماً.</p><div className="modal-actions"><button className="outline-button" onClick={onCancel}>إلغاء</button><button className="danger-button" onClick={onConfirm}>تأكيد الأرشفة</button></div></div></div>
}

function ToastViewport({ toast, onClose }: { toast: ToastMessage; onClose: () => void }) {
  const Icon = toast.type === 'success' ? CheckCircle2 : toast.type === 'error' ? XCircle : Info
  return <div className={`toast ${toast.type}`}><Icon size={18} /><span>{toast.message}</span><button onClick={onClose}><X size={15} /></button></div>
}


function LoginScreen({ onSuccess }: { onSuccess: () => void }) {
  const [identifier, setIdentifier] = useState('')
  const [password, setPassword] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const submit = async (event: React.FormEvent) => { event.preventDefault(); setLoading(true); setError(''); try { await login(identifier, password); onSuccess() } catch (reason) { setError(reason instanceof ApiError ? reason.message : 'تعذر تسجيل الدخول إلى الـAPI') } finally { setLoading(false) } }
  return <div className="login-shell"><div className="login-card"><div className="login-brand"><span className="logo-mark"><Store size={21} /></span><b>سوقي Admin</b></div><span className="eyebrow">إدارة المتجر</span><h1>تسجيل الدخول</h1><p className="muted">استخدم حساب الإدارة للوصول إلى بيانات الـAPI.</p><form onSubmit={submit}><label className="form-field"><span>البريد الإلكتروني أو رقم الهاتف</span><input required value={identifier} onChange={(event) => setIdentifier(event.target.value)} placeholder="admin@example.com" autoComplete="username" /></label><label className="form-field"><span>كلمة المرور</span><input required type="password" value={password} onChange={(event) => setPassword(event.target.value)} placeholder="••••••••" autoComplete="current-password" /></label>{error && <div className="form-error"><XCircle size={15} />{error}</div>}<button className="primary-button full" disabled={loading}>{loading ? 'جار تسجيل الدخول...' : 'دخول إلى لوحة التحكم'}</button></form><small className="login-api-url">API: {apiBaseUrl()}</small></div></div>
}
