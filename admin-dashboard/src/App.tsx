import { useEffect, useMemo, useState } from 'react'
import { useLocation, useNavigate } from 'react-router-dom'
import {
  AlertTriangle, Bell, Bot, Boxes, Check, CheckCircle2, ChevronDown, CircleDollarSign, CreditCard, Eye, FileText,
  HelpCircle, Info, LayoutDashboard, Menu, MessageCircle, MoreHorizontal, Package, Pencil, Plus, Printer,
  Search, Settings, Shield, ShoppingCart, Store, Pin, PinOff, Trash2, TrendingUp, Users, X, XCircle,
} from 'lucide-react'
import { ApiError, apiBaseUrl, cancelOrder, createShipment, downloadOrdersCsv, getOrder, getOrderTimeline, getToken, listCustomers, listOrders, listProducts, listShippingMethods, login, recordOrderContact, socialSummary, logout, me, updateOrderStatus, updateShipmentStatus } from './lib/api'
import { CatalogPage } from './pages/CatalogPage'
import { InventoryPage } from './pages/InventoryPage'
import { OrdersPage } from './pages/OrdersPage'
import { ReportsPage } from './pages/ReportsPage'
import { SettingsPage } from './pages/SettingsPage'
import { ProfilePage } from './pages/ProfilePage'
import { ManagementPage } from './pages/ManagementPage'
import { CommercePage } from './pages/CommercePage'
import { CustomersPage } from './pages/CustomersPage'
import { FinancePage } from './pages/FinancePage'
import { SettlementsPage } from './pages/SettlementsPage'
import { TaxonomyPage } from './pages/TaxonomyPage'
import { PaymentsPage } from './pages/PaymentsPage'
import { SocialPage } from './pages/SocialPage'
import { AiPage } from './pages/AiPage'
import { MonitoringPage } from './pages/MonitoringPage'
import './App.css'

type OrderStatus = 'جديد' | 'قيد التجهيز' | 'تم الشحن' | 'مكتمل'
type Order = { id: string; apiId?: number; customer: string; initials: string; date: string; total: string; totalAmount?: number; payment: string; status: OrderStatus }
type SessionUser = { name?: string; email?: string; status?: string; roles?: string[]; permissions?: string[] }

const orders: Order[] = [
  { id: '#ORD-8294', customer: 'سارة العتيبي', initials: 'سع', date: 'اليوم، ١٠:٤٢ ص', total: '٥٩٧ ر.س', payment: 'مدى', status: 'جديد' },
  { id: '#ORD-8293', customer: 'محمد القحطاني', initials: 'مق', date: 'اليوم، ٠٩:١٨ ص', total: '١,٢٤٠ ر.س', payment: 'Apple Pay', status: 'قيد التجهيز' },
  { id: '#ORD-8292', customer: 'نورة الحربي', initials: 'نه', date: 'أمس، ٠٦:٣٥ م', total: '٣٩٩ ر.س', payment: 'بطاقة ائتمانية', status: 'تم الشحن' },
  { id: '#ORD-8291', customer: 'خالد الشهري', initials: 'خش', date: 'أمس، ٠٢:١١ م', total: '٨٧٥ ر.س', payment: 'مدى', status: 'مكتمل' },
  { id: '#ORD-8290', customer: 'ريم الغامدي', initials: 'رغ', date: '٢٠ أغسطس، ١١:٠٣ ص', total: '٢١٠ ر.س', payment: 'الدفع عند الاستلام', status: 'قيد التجهيز' },
]

const navItems = [
  { label: 'الرئيسية', path: '/', icon: LayoutDashboard }, { label: 'الطلبات', path: '/orders', icon: ShoppingCart, permission: 'orders.view' }, { label: 'العملاء', path: '/customers', icon: Users, permission: 'customers.view' },
  { label: 'المنتجات', path: '/catalog', icon: Package, permission: 'products.view' }, { label: 'هيكلة الكتالوج', path: '/catalog/taxonomy', icon: Boxes, permission: 'products.view' }, { label: 'المخزون', path: '/inventory', icon: Boxes, permission: 'inventory.view' }, { label: 'التقارير', path: '/reports', icon: TrendingUp, permission: 'orders.view' }, { label: 'الإدارة', path: '/management', icon: Shield, permission: 'assistants.view' }, { label: 'التجارة', path: '/commerce', icon: Store, permission: 'promotions.view' }, { label: 'التجارة الاجتماعية', path: '/social', icon: MessageCircle, permission: 'social.interactions.view' }, { label: 'الذكاء الاصطناعي', path: '/ai', icon: Bot, permission: 'settings.view' }, { label: 'المراقبة', path: '/monitoring', icon: AlertTriangle, permission: 'monitoring.run' }, { label: 'المالية', path: '/finance', icon: CircleDollarSign, permission: 'settlements.view' }, { label: 'المدفوعات', path: '/payments', icon: CreditCard, permission: 'payments.view' }, { label: 'التسويات', path: '/settlements', icon: FileText, permission: 'settlements.view' }, { label: 'الإعدادات', path: '/settings', icon: Settings, permission: 'settings.view' },
]
const utilityPaths = ['/profile']
const statusOptions: OrderStatus[] = ['جديد', 'قيد التجهيز', 'تم الشحن', 'مكتمل']
const backendStatuses = ['pending', 'processing', 'shipped', 'delivered']
const statusLabels: Record<string, OrderStatus> = { pending: 'جديد', reviewing: 'جديد', confirmed: 'جديد', processing: 'قيد التجهيز', shipped: 'تم الشحن', delivered: 'مكتمل', cancelled: 'مكتمل', refunded: 'مكتمل' }
const paymentMethodLabels: Record<string, string> = { card: 'بطاقة بنكية', cash: 'دفع نقدي', cod: 'الدفع عند الاستلام', mada: 'مدى', apple_pay: 'Apple Pay' }
const displayPaymentMethod = (value: unknown) => paymentMethodLabels[String(value)] ?? String(value ?? 'غير محدد')

function normalizeApiOrder(order: import('./lib/api').ApiOrder): Order {
  const customer = order.user?.name || order.user?.email || 'عميل متجر'
  return { id: `#${order.order_number || order.id}`, apiId: order.id, customer, initials: customer.slice(0, 2), totalAmount: Number(order.total_amount ?? 0), date: order.created_at ? new Date(order.created_at).toLocaleString('ar-EG', { dateStyle: 'medium', timeStyle: 'short' }) : '—', total: `${order.total_amount ?? 0} ${order.currency || 'ر.س'}`, payment: 'غير محدد', status: statusLabels[order.status] || 'جديد' }
}

function StatusBadge({ status }: { status: OrderStatus }) {
  return <span className={`status status-${status.replaceAll(' ', '-')}`}><i />{status}</span>
}

function App() {
  const location = useLocation()
  const navigate = useNavigate()
  const [authenticated, setAuthenticated] = useState(() => Boolean(getToken()))
  const activeNav = navItems.find((item) => item.path === location.pathname)?.label ?? (location.pathname.startsWith('/settings/') ? 'الإعدادات' : 'الرئيسية')
  const [statusFilter, setStatusFilter] = useState<'الكل' | OrderStatus>('الكل')
  const [paymentFilter, setPaymentFilter] = useState('كل طرق الدفع')
  const [dateFilter, setDateFilter] = useState('كل التواريخ')
  const [search, setSearch] = useState('')
  const [rows, setRows] = useState(() => getToken() ? [] : orders)
  const [selectedOrder, setSelectedOrder] = useState<Order | null>(null)
  const [selectedOrderDetails, setSelectedOrderDetails] = useState<import('./lib/api').ApiOrder | null>(null)
  const [selectedOrderTimeline, setSelectedOrderTimeline] = useState<unknown[]>([])
  const [orderDetailsLoading, setOrderDetailsLoading] = useState(false)
  const [toast, setToast] = useState<{ type: 'success' | 'error' | 'info'; message: string } | null>(null)
  const [mobileNav, setMobileNav] = useState(false)
  const [sidebarPinned, setSidebarPinned] = useState(() => window.localStorage.getItem('souqi-sidebar-pinned') === 'true')
  const [notificationsOpen, setNotificationsOpen] = useState(false)
  const [profileMenuOpen, setProfileMenuOpen] = useState(false)
  const [commandOpen, setCommandOpen] = useState(false)
  const [pendingDelete, setPendingDelete] = useState<Order | null>(null)
  const [apiLoading, setApiLoading] = useState(Boolean(getToken()))
  const [currentUser, setCurrentUser] = useState<SessionUser | null>(null)
  const [socialStats, setSocialStats] = useState<Record<string, unknown>>({})
  const visibleNavItems = useMemo(() => { if (!authenticated || !currentUser) return navItems; const roles = currentUser.roles ?? []; const permissions = new Set(currentUser.permissions ?? []); if (roles.some((role) => ['owner', 'admin'].includes(role))) return navItems; return navItems.filter((item) => !item.permission || permissions.has(item.permission)) }, [authenticated, currentUser])

  useEffect(() => { if (!toast) return; const timer = window.setTimeout(() => setToast(null), 4000); return () => window.clearTimeout(timer) }, [toast])
  useEffect(() => { document.documentElement.dir = 'rtl'; document.documentElement.lang = 'ar' }, [])
  useEffect(() => { const handler = (event: KeyboardEvent) => { if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') { event.preventDefault(); setCommandOpen(true) } if (event.key === 'Escape') { setCommandOpen(false); setPendingDelete(null) } }; window.addEventListener('keydown', handler); return () => window.removeEventListener('keydown', handler) }, [])
  // The effect synchronizes the authenticated view with the external Laravel API.
  // eslint-disable-next-line react-hooks/set-state-in-effect
  useEffect(() => { if (!authenticated) { setApiLoading(false); setCurrentUser(null); return } Promise.all([me(), listOrders({ page: 1, per_page: 100 }), socialSummary()]).then(([user, data, social]) => { setCurrentUser(user); const ordersPage = Array.isArray(data) ? data : data.data; setRows(ordersPage.map(normalizeApiOrder)); setSocialStats(social) }).catch((error: unknown) => { if (error instanceof ApiError && error.status === 401) { setAuthenticated(false); setToast({ type: 'info', message: 'انتهت جلسة الدخول، يرجى تسجيل الدخول مجددًا' }) } else setToast({ type: 'error', message: error instanceof ApiError ? error.message : 'تعذر تحميل بيانات الحساب من الـAPI' }) }).finally(() => setApiLoading(false)) }, [authenticated])

  const filteredOrders = useMemo(() => rows.filter((order) => {
    const matchesStatus = statusFilter === 'الكل' || order.status === statusFilter
    const matchesPayment = paymentFilter === 'كل طرق الدفع' || order.payment === paymentFilter
    const matchesSearch = `${order.id} ${order.customer}`.includes(search.trim())
    return matchesStatus && matchesPayment && matchesSearch && (dateFilter === 'كل التواريخ' || (dateFilter === 'اليوم' ? order.date.startsWith('اليوم') : !order.date.startsWith('اليوم')))
  }), [rows, statusFilter, paymentFilter, search, dateFilter])

  const dashboardStats = useMemo(() => { const totalSales = rows.reduce((sum, order) => sum + (order.totalAmount ?? Number.parseFloat(order.total.replace(/[^0-9.]/g, '') || '0')), 0); const newOrders = rows.filter((order) => order.status === 'جديد').length; const averageOrder = rows.length ? totalSales / rows.length : 0; return { totalSales, newOrders, averageOrder } }, [rows])
  const todayLabel = new Date().toLocaleDateString('ar-EG', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })

  const updateStatus = async (id: string, status: OrderStatus) => { const order = rows.find((item) => item.id === id); const nextBackendStatus = backendStatuses[statusOptions.indexOf(status)] || 'processing'; try { if (order?.apiId && getToken()) await updateOrderStatus(order.apiId, nextBackendStatus); setRows((current) => current.map((item) => item.id === id ? { ...item, status } : item)); setToast({ type: 'success', message: `تم تغيير حالة الطلب ${id}` }) } catch (error) { setToast({ type: 'error', message: error instanceof ApiError ? error.message : 'تعذر تغيير حالة الطلب' }) } }
  const handleLogout = async () => { try { await logout() } catch { /* The local token is cleared even if the remote logout is unavailable. */ } setCurrentUser(null); setAuthenticated(false); navigate('/'); setToast({ type: 'success', message: 'تم تسجيل الخروج بنجاح' }) }
  const exportOrders = async () => { try { const blob = await downloadOrdersCsv(); const url = URL.createObjectURL(blob); const link = document.createElement('a'); link.href = url; link.download = `orders-${new Date().toISOString().slice(0, 10)}.csv`; document.body.appendChild(link); link.click(); link.remove(); URL.revokeObjectURL(url); setToast({ type: 'success', message: 'تم تصدير تقرير الطلبات بنجاح' }) } catch (error) { setToast({ type: 'error', message: error instanceof ApiError ? error.message : 'تعذر تصدير تقرير الطلبات' }) } }
  const openOrderDrawer = async (order: Order) => {
    setSelectedOrder(order); setSelectedOrderDetails(null); setSelectedOrderTimeline([])
    if (!order.apiId || !getToken()) return
    setOrderDetailsLoading(true)
    try { const [details, timeline] = await Promise.all([getOrder(order.apiId), getOrderTimeline(order.apiId)]); setSelectedOrderDetails(details); setSelectedOrderTimeline(Array.isArray(timeline) ? timeline : []) }
    catch (error: unknown) { setToast({ type: 'error', message: error instanceof ApiError ? error.message : 'تعذر تحميل تفاصيل الطلب' }) }
    finally { setOrderDetailsLoading(false) }
  }
  const closeOrderDrawer = () => { setSelectedOrder(null); setSelectedOrderDetails(null); setSelectedOrderTimeline([]) }
  const printDashboardOrder = (order: Order) => { const popup = window.open('', '_blank', 'width=760,height=800'); if (!popup) { setToast({ type: 'error', message: 'السماح بالنوافذ المنبثقة مطلوب للطباعة' }); return } const safe = (value: unknown) => String(value ?? '').replace(/[&<>"']/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[character] ?? character)); popup.document.write(`<html dir="rtl"><head><title>طلب ${safe(order.id)}</title><style>body{font-family:Arial,sans-serif;padding:36px;color:#172033}h1{font-size:24px}table{width:100%;border-collapse:collapse;margin-top:24px}th,td{border-bottom:1px solid #ddd;padding:12px;text-align:right}small{color:#667085}</style></head><body><h1>ملخص الطلب ${safe(order.id)}</h1><small>${safe(order.date)}</small><table><tbody><tr><th>العميل</th><td>${safe(order.customer)}</td></tr><tr><th>طريقة الدفع</th><td>${safe(order.payment)}</td></tr><tr><th>الحالة</th><td>${safe(order.status)}</td></tr><tr><th>الإجمالي</th><td>${safe(order.total)}</td></tr></tbody></table></body></html>`); popup.document.close(); popup.focus(); popup.print() }
  const removeOrder = async (id: string) => { const order = rows.find((item) => item.id === id); try { if (order?.apiId && getToken()) await cancelOrder(order.apiId); setRows((current) => current.filter((item) => item.id !== id)); setPendingDelete(null); setToast({ type: 'success', message: `تمت أرشفة الطلب ${id} ويمكن استرجاعه خلال ٣٠ يوماً` }) } catch (error) { setToast({ type: 'error', message: error instanceof ApiError ? error.message : 'تعذر إلغاء الطلب' }) } }

  if (!authenticated) return <LoginScreen onSuccess={() => setAuthenticated(true)} />
  const isSettingsSection = location.pathname.startsWith('/settings/')
  if (location.pathname !== '/' && !navItems.some((item) => item.path === location.pathname) && !isSettingsSection && !utilityPaths.includes(location.pathname)) return <NotFound onHome={() => navigate('/')} />
  if (location.pathname !== '/' && !visibleNavItems.some((item) => item.path === location.pathname) && !isSettingsSection && !utilityPaths.includes(location.pathname)) return <NotAuthorized onHome={() => navigate('/')} />

  return <div className="dashboard-shell"><aside className={`compact-sidebar ${mobileNav ? 'mobile-open' : ''} ${sidebarPinned ? 'pinned' : 'collapsed'}`}><div className="sidebar-top"><div className="sidebar-brand"><div className="logo-mark" aria-label="سوقي"><Store size={21} /></div><div><strong>سوقي</strong><small>إدارة المتجر</small></div></div><div className="sidebar-controls"><button className="sidebar-pin" onClick={() => { const next = !sidebarPinned; setSidebarPinned(next); window.localStorage.setItem('souqi-sidebar-pinned', String(next)) }} aria-label={sidebarPinned ? 'إلغاء تثبيت القائمة' : 'تثبيت القائمة'} title={sidebarPinned ? 'إلغاء تثبيت القائمة' : 'تثبيت القائمة'} aria-pressed={sidebarPinned}>{sidebarPinned ? <PinOff size={15} /> : <Pin size={15} />}</button><button className="mobile-close" onClick={() => setMobileNav(false)} aria-label="إغلاق القائمة"><X size={20} /></button></div></div><nav className="main-nav" aria-label="التنقل الرئيسي">{visibleNavItems.map(({ label, path, icon: Icon }) => <button key={label} className={`nav-icon ${activeNav === label ? 'active' : ''}`} onClick={() => { navigate(path); setMobileNav(false) }} title={label} aria-label={label}><Icon size={20} /><span className="nav-label">{label}</span><span className="tooltip">{label}</span></button>)}</nav><div className="sidebar-bottom"><button className="nav-icon" title="المساعدة" aria-label="المساعدة" onClick={() => setToast({ type: 'info', message: 'للدعم، افتح صفحة المراقبة أو تواصل مع مدير النظام' })}><HelpCircle size={20} /><span className="tooltip">المساعدة</span></button><button className="nav-icon" title="الإعدادات" aria-label="الإعدادات" onClick={() => navigate('/settings')}><Settings size={20} /><span className="tooltip">الإعدادات</span></button><button className="profile-avatar" title="فتح إعدادات الملف الشخصي" aria-label="فتح إعدادات الملف الشخصي" onClick={() => navigate('/profile')}>{(currentUser?.name ?? 'م').slice(0, 1)}</button></div></aside>{mobileNav && <button className="sidebar-backdrop" onClick={() => setMobileNav(false)} aria-label="إغلاق القائمة" />}
    <main className="main-content"><header className="topbar"><button className="mobile-menu" onClick={() => setMobileNav(true)} aria-label="فتح القائمة"><Menu size={21} /></button><div className="breadcrumbs"><span>الرئيسية</span><b>/</b><strong>{activeNav}</strong></div><div className="topbar-actions"><label className="global-search" onClick={() => setCommandOpen(true)}><Search size={17} /><input value={search} onChange={(event) => setSearch(event.target.value)} onFocus={() => setCommandOpen(true)} placeholder="ابحث عن طلب أو عميل..." /><kbd>⌘ K</kbd></label><div className="notification-wrap"><button className="notification-button" onClick={() => setNotificationsOpen(!notificationsOpen)} aria-label="الإشعارات"><Bell size={19} />{rows.some((order) => order.status === 'جديد') && <i />}</button>{notificationsOpen && <NotificationMenu orders={rows} onClose={() => setNotificationsOpen(false)} onInfo={() => { setNotificationsOpen(false); navigate('/monitoring') }} />}</div><div className="profile-menu-wrap"><button className="top-profile" onClick={() => setProfileMenuOpen((open) => !open)} aria-expanded={profileMenuOpen} title="فتح قائمة الملف الشخصي"><span className="profile-avatar small">{(currentUser?.name ?? 'م').slice(0, 1)}</span><div><b>{currentUser?.name ?? 'حساب الإدارة'}</b><small>{currentUser?.email ?? 'الملف الشخصي'}</small></div><ChevronDown size={15} /></button>{profileMenuOpen && <div className="profile-menu" role="menu"><button role="menuitem" onClick={() => { setProfileMenuOpen(false); navigate('/profile') }}><Settings size={15} /><span>الملف الشخصي</span></button><button role="menuitem" onClick={() => { setProfileMenuOpen(false); void handleLogout() }}><X size={15} /><span>تسجيل الخروج</span></button></div>}</div></div></header><div className="content">{activeNav === 'المنتجات' ? <CatalogPage access={currentUser ?? undefined} onToast={(message, type) => setToast({ type: type ?? 'success', message })} /> : activeNav === 'هيكلة الكتالوج' ? <TaxonomyPage access={currentUser ?? undefined} onToast={(message, type) => setToast({ type: type ?? 'success', message })} /> : activeNav === 'الطلبات' ? <OrdersPage access={currentUser ?? undefined} onToast={(message, type) => setToast({ type: type ?? 'success', message })} /> : activeNav === 'العملاء' ? <CustomersPage onToast={(message, type) => setToast({ type: type ?? 'success', message })} /> : activeNav === 'المخزون' ? <InventoryPage access={currentUser ?? undefined} onToast={(message, type) => setToast({ type: type ?? 'success', message })} /> : activeNav === 'التقارير' ? <ReportsPage onToast={(message, type) => setToast({ type: type ?? 'success', message })} /> : activeNav === 'الإدارة' ? <ManagementPage access={currentUser ?? undefined} onToast={(message, type) => setToast({ type: type ?? 'success', message })} /> : activeNav === 'التجارة' ? <CommercePage access={currentUser ?? undefined} onToast={(message, type) => setToast({ type: type ?? 'success', message })} /> : activeNav === 'التجارة الاجتماعية' ? <SocialPage onToast={(message, type) => setToast({ type: type ?? 'success', message })} /> : activeNav === 'الذكاء الاصطناعي' ? <AiPage onToast={(message, type) => setToast({ type: type ?? 'success', message })} /> : activeNav === 'المراقبة' ? <MonitoringPage access={currentUser ?? undefined} onToast={(message, type) => setToast({ type: type ?? 'success', message })} /> : activeNav === 'المالية' ? <FinancePage access={currentUser ?? undefined} onToast={(message, type) => setToast({ type: type ?? 'success', message })} /> : activeNav === 'المدفوعات' ? <PaymentsPage access={currentUser ?? undefined} onToast={(message, type) => setToast({ type: type ?? 'success', message })} /> : activeNav === 'التسويات' ? <SettlementsPage access={currentUser ?? undefined} onToast={(message, type) => setToast({ type: type ?? 'success', message })} /> : location.pathname === '/profile' ? <ProfilePage currentUser={currentUser} onUserUpdated={setCurrentUser} onToast={(message, type) => setToast({ type: type ?? 'success', message })} /> : activeNav === 'الإعدادات' ? <SettingsPage access={currentUser ?? undefined} onToast={(message, type) => setToast({ type: type ?? 'success', message })} /> : <div className="dashboard-home">{apiLoading && <div className="api-loading" role="status"><span className="skeleton-line" /><span className="skeleton-line short" /><span>جار تحميل بيانات المتجر من الـAPI...</span></div>}<div className="page-title"><div><p className="eyebrow">{todayLabel}</p><h1>لوحة التحكم</h1><p className="muted">تابع أداء متجرك وطلباتك من مكان واحد.</p></div><button className="primary-button" onClick={() => navigate('/catalog')}><Plus size={17} /> إضافة منتج</button></div><section className="kpi-grid" aria-label="مؤشرات الأداء الرئيسية"><KpiCard icon={CircleDollarSign} label="إجمالي المبيعات" value={apiLoading ? '...' : `${dashboardStats.totalSales.toLocaleString('ar-SA')} ر.س`} change="من الطلبات الحالية" positive onClick={() => navigate('/reports')} /><KpiCard icon={ShoppingCart} label="الطلبات الجديدة" value={apiLoading ? '...' : dashboardStats.newOrders.toLocaleString('ar-SA')} change="تحتاج متابعة" positive onClick={() => navigate('/orders')} /><KpiCard icon={TrendingUp} label="متوسط قيمة الطلب" value={apiLoading ? '...' : `${dashboardStats.averageOrder.toLocaleString('ar-SA', { maximumFractionDigits: 2 })} ر.س`} change="من الطلبات الحالية" positive onClick={() => navigate('/reports')} /><KpiCard icon={Package} label="إجمالي الطلبات" value={apiLoading ? '...' : Number(socialStats.orders ?? rows.length).toLocaleString('ar-SA')} subValue="بيانات مباشرة من الـAPI" change="محدث الآن" positive onClick={() => navigate('/orders')} /><KpiCard icon={MessageCircle} label="الرسائل" value={apiLoading ? '...' : Number(socialStats.messages ?? 0).toLocaleString('ar-SA')} change="من قنوات التواصل" positive onClick={() => navigate('/social')} /><KpiCard icon={MessageCircle} label="التعليقات" value={apiLoading ? '...' : Number(socialStats.comments ?? 0).toLocaleString('ar-SA')} change="التفاعلات الاجتماعية" positive onClick={() => navigate('/social')} /></section><section className="orders-card"><div className="orders-heading"><div><h2>الطلبات الأخيرة</h2><p className="muted">إدارة ومتابعة طلبات متجرك اليومية</p></div><button className="outline-button" onClick={() => void exportOrders()}><FileText size={16} /> تصدير التقرير</button></div><div className="filters"><div className="filter-chips"><button className={`filter-chip ${statusFilter === 'الكل' ? 'selected' : ''}`} onClick={() => setStatusFilter('الكل')}>كل الحالات</button>{statusOptions.map((status) => <button key={status} className={`filter-chip ${statusFilter === status ? 'selected' : ''}`} onClick={() => setStatusFilter(status)}>{status}</button>)}</div><div className="select-filters"><FilterSelect value={dateFilter} onChange={setDateFilter} options={['كل التواريخ', 'اليوم', 'هذا الأسبوع']} /><FilterSelect value={paymentFilter} onChange={setPaymentFilter} options={['كل طرق الدفع', 'مدى', 'Apple Pay', 'بطاقة ائتمانية', 'الدفع عند الاستلام']} /></div></div><div className="table-wrap">{filteredOrders.length === 0 ? <div className="empty-state"><ShoppingCart size={28} /><b>{rows.length === 0 ? 'لا توجد طلبات بعد' : 'لا توجد طلبات مطابقة'}</b><span>{rows.length === 0 ? 'ستظهر الطلبات هنا عند وصولها من المتجر.' : 'جرّب تغيير الفلاتر أو عبارة البحث.'}</span><button className="outline-button" onClick={() => { setStatusFilter('الكل'); setPaymentFilter('كل طرق الدفع'); setDateFilter('كل التواريخ'); setSearch('') }}>إعادة ضبط الفلاتر</button></div> : <table><thead><tr><th>رقم الطلب</th><th>العميل</th><th>التاريخ</th><th>الإجمالي</th><th>طريقة الدفع</th><th>الحالة</th><th>إجراءات</th></tr></thead><tbody>{filteredOrders.map((order) => <tr key={order.id}><td><b className="order-id">{order.id}</b></td><td><span className="customer"><span className="customer-avatar">{order.initials}</span><b>{order.customer}</b></span></td><td className="muted">{order.date}</td><td><b className="tabular">{order.total}</b></td><td className="muted">{order.payment}</td><td><button className="status-select" onClick={() => updateStatus(order.id, statusOptions[(statusOptions.indexOf(order.status) + 1) % statusOptions.length])}><StatusBadge status={order.status} /><ChevronDown size={14} /></button></td><td><div className="quick-actions"><button title="معاينة" aria-label="معاينة" onClick={() => void openOrderDrawer(order)}><Eye size={17} /></button><button title="تعديل" aria-label="تعديل" onClick={() => navigate('/orders')}><Pencil size={16} /></button><button title="طباعة" aria-label="طباعة" onClick={() => printDashboardOrder(order)}><Printer size={16} /></button><button className="danger" title="حذف" aria-label="حذف" onClick={() => setPendingDelete(order)}><Trash2 size={16} /></button></div></td></tr>)}</tbody></table>}</div><div className="table-footer"><span className="muted">عرض {filteredOrders.length} من {rows.length} طلبات</span><span className="muted">أحدث الطلبات</span></div></section></div>}</div></main>{selectedOrder && <OrderDrawer key={`${selectedOrder.id}-${selectedOrderDetails ? 'loaded' : 'loading'}`} order={selectedOrder} details={selectedOrderDetails} timeline={selectedOrderTimeline} loading={orderDetailsLoading} canManageShipping={!currentUser || currentUser.roles?.some((role) => ['owner', 'admin'].includes(role)) || currentUser.permissions?.includes('shipping.manage') === true} onClose={closeOrderDrawer} onToast={(message, type) => setToast({ message, type })} />}{commandOpen && <CommandPalette orders={rows} onClose={() => setCommandOpen(false)} onSelect={(path) => { setCommandOpen(false); navigate(path) }} onOrderSelect={(order) => { setCommandOpen(false); void openOrderDrawer(order) }} />}{pendingDelete && <DeleteModal order={pendingDelete} onCancel={() => setPendingDelete(null)} onConfirm={() => removeOrder(pendingDelete.id)} />}{toast && <ToastViewport toast={toast} onClose={() => setToast(null)} />}</div>
}

function KpiCard({ icon: Icon, label, value, change, positive, subValue, onClick }: { icon: typeof CircleDollarSign; label: string; value: string; change: string; positive: boolean; subValue?: string; onClick?: () => void }) { return <article className={`kpi-card ${onClick ? 'is-clickable' : ''}`} onClick={onClick} onKeyDown={(event) => { if (onClick && (event.key === 'Enter' || event.key === ' ')) { event.preventDefault(); onClick() } }} tabIndex={onClick ? 0 : undefined} role={onClick ? 'link' : undefined}><div className="kpi-top"><span className="kpi-icon"><Icon size={19} /></span><span className="kpi-label">{label}</span><button className="more-button" aria-label={`فتح ${label}`} onClick={(event) => { event.stopPropagation(); onClick?.() }}><MoreHorizontal size={18} /></button></div><strong className={`kpi-value ${subValue ? 'product-value' : ''}`}>{value}</strong>{subValue && <span className="kpi-subvalue">{subValue}</span>}<span className={`kpi-change ${positive ? 'positive' : 'negative'}`}><TrendingUp size={13} />{change}<small>فتح الصفحة المختصة</small></span></article> }
function FilterSelect({ value, onChange, options }: { value: string; onChange: (value: string) => void; options: string[] }) { return <label className="filter-select"><select value={value} onChange={(event) => onChange(event.target.value)}>{options.map((option) => <option key={option}>{option}</option>)}</select><ChevronDown size={14} /></label> }
function OrderDrawer({ order, details, timeline, loading, canManageShipping, onClose, onToast }: { order: Order; details: import('./lib/api').ApiOrder | null; timeline: unknown[]; loading: boolean; canManageShipping: boolean; onClose: () => void; onToast: (message: string, type: 'success' | 'error') => void }) {
  const [shippingMethods, setShippingMethods] = useState<Record<string, unknown>[]>([])
  const [shipmentForm, setShipmentForm] = useState({ shipping_method_id: '', provider_code: 'manual', tracking_number: '' })
  const [shipmentStatuses, setShipmentStatuses] = useState<Record<number, string>>({})
  const [shipmentRows, setShipmentRows] = useState<NonNullable<import('./lib/api').ApiOrder['shipments']>>(() => details?.shipments ?? [])
  const [shipmentBusy, setShipmentBusy] = useState(false)
  const [internalNote, setInternalNote] = useState('')
  const [noteBusy, setNoteBusy] = useState(false)
  useEffect(() => { if (!canManageShipping) return; void listShippingMethods().then(setShippingMethods).catch(() => undefined) }, [canManageShipping])
  const items = details?.items ?? []
  const shipments = shipmentRows
  const address = details?.shipping_address
  const events = timeline.map((entry) => { const row = (entry && typeof entry === 'object' ? entry : {}) as Record<string, unknown>; return { label: String(row.label ?? row.title ?? statusLabels[String(row.status ?? row.to_status)] ?? row.status ?? row.to_status ?? 'تحديث الطلب'), date: String(row.created_at ?? row.updated_at ?? '') } })
  const submitShipment = async (event: React.FormEvent) => { event.preventDefault(); if (!details?.id || !shipmentForm.shipping_method_id) return; setShipmentBusy(true); try { const shipment = await createShipment(details.id, { ...shipmentForm, shipping_method_id: Number(shipmentForm.shipping_method_id), idempotency_key: `admin-${details.id}-${Date.now()}` }); setShipmentRows((current) => [...current, shipment as NonNullable<import('./lib/api').ApiOrder['shipments']>[number]]); setShipmentForm({ shipping_method_id: '', provider_code: 'manual', tracking_number: '' }); onToast('تم إنشاء الشحنة بنجاح', 'success') } catch (error: unknown) { onToast(error instanceof ApiError ? error.message : 'تعذر إنشاء الشحنة', 'error') } finally { setShipmentBusy(false) } }
  const saveInternalNote = async () => {
    if (!details?.id || !internalNote.trim()) return
    setNoteBusy(true)
    try {
      await recordOrderContact(details.id, 'confirmed', internalNote.trim())
      setInternalNote('')
      onToast('تم حفظ الملاحظة في سجل الطلب', 'success')
    } catch (error: unknown) {
      onToast(error instanceof ApiError ? error.message : 'تعذر حفظ الملاحظة', 'error')
    } finally { setNoteBusy(false) }
  }
  const saveShipmentStatus = async (shipmentId: number) => { const status = shipmentStatuses[shipmentId]; if (!status) return; setShipmentBusy(true); try { await updateShipmentStatus(shipmentId, status); setShipmentRows((current) => current.map((shipment) => shipment.id === shipmentId ? { ...shipment, status } : shipment)); onToast('تم تحديث حالة الشحنة', 'success') } catch (error: unknown) { onToast(error instanceof ApiError ? error.message : 'تعذر تحديث حالة الشحنة', 'error') } finally { setShipmentBusy(false) } }
  const shipmentStatusLabels: Record<string, string> = { pending: 'قيد الانتظار', picked_up: 'تم الاستلام', in_transit: 'قيد النقل', out_for_delivery: 'خرج للتسليم', delivered: 'تم التسليم', cancelled: 'ملغاة' }
  return <div className="drawer-overlay" onClick={onClose}><aside className="order-drawer" onClick={(event) => event.stopPropagation()}><div className="drawer-head"><div><span className="eyebrow">تفاصيل الطلب</span><h2>{order.id}</h2></div><button className="icon-button" onClick={onClose} aria-label="إغلاق التفاصيل"><X size={20} /></button></div><div className="drawer-status"><StatusBadge status={order.status} /><span className="muted">{loading ? 'جار تحميل التفاصيل...' : details?.created_at ? new Date(details.created_at).toLocaleString('ar-EG') : 'بيانات مباشرة من الـAPI'}</span></div>{events.length > 0 ? <div className="order-events">{events.map((event, index) => <div key={`${event.label}-${index}`}><b>{event.label}</b>{event.date && <small>{new Date(event.date).toLocaleString('ar-EG')}</small>}</div>)}</div> : <><div className="timeline"><span className="done"><Check size={13} /></span><i /><span className="done"><Check size={13} /></span><i /><span className="current"><Package size={13} /></span><i /><span><TruckIcon /></span></div><div className="timeline-labels"><small>تم الاستلام</small><small>تم التأكيد</small><small>قيد التجهيز</small><small>الشحن</small></div></>}<h3>بيانات العميل</h3><div className="drawer-info"><div><small>العميل</small><b>{details?.user?.name ?? order.customer}</b></div><div><small>طريقة الدفع</small><b>{displayPaymentMethod(details?.payments?.[0]?.method ?? order.payment)}</b></div><div className="wide"><small>عنوان الشحن</small><b>{address ? [address.recipient_name, address.address_line1, address.city].filter(Boolean).join('، ') : 'غير متوفر'}</b></div></div><h3>المنتجات</h3>{items.length > 0 ? items.map((item, index) => <div className="drawer-product" key={`${item.name ?? item.product?.name ?? 'item'}-${index}`}><span className="product-thumb"><Package size={20} /></span><div><b>{item.name ?? item.product?.name ?? 'منتج'}</b><small>الكمية: {item.quantity ?? 1}</small></div><strong>{item.total_amount ?? 0} {details?.currency ?? 'ر.س'}</strong></div>) : <p className="muted">لا توجد تفاصيل منتجات في الاستجابة.</p>}<h3>الشحنات</h3>{canManageShipping && <form className="shipment-create" onSubmit={submitShipment}><select required value={shipmentForm.shipping_method_id} onChange={(event) => setShipmentForm({ ...shipmentForm, shipping_method_id: event.target.value })}><option value="">اختر طريقة الشحن</option>{shippingMethods.filter((method) => String(method.is_active ?? true) === 'true').map((method) => <option key={String(method.id)} value={String(method.id)}>{String(method.name ?? method.code)} · {String(method.base_fee ?? 0)} ر.س</option>)}</select><select value={shipmentForm.provider_code} onChange={(event) => setShipmentForm({ ...shipmentForm, provider_code: event.target.value })}><option value="manual">يدوي</option><option value="bosta">Bosta</option><option value="aramex">Aramex</option></select>{shipmentForm.provider_code === 'manual' && <input required placeholder="رقم التتبع" value={shipmentForm.tracking_number} onChange={(event) => setShipmentForm({ ...shipmentForm, tracking_number: event.target.value })} />}<button className="primary-button small-button" disabled={shipmentBusy}>إنشاء شحنة</button></form>}{shipments.length > 0 ? shipments.map((shipment) => <div className="shipment-control" key={shipment.id}><div><b>{shipment.provider_code ?? 'شركة الشحن'}</b><small>{shipment.tracking_number ?? 'بدون رقم تتبع'}</small></div>{canManageShipping ? <><select value={shipmentStatuses[shipment.id] ?? shipment.status ?? 'pending'} onChange={(event) => setShipmentStatuses({ ...shipmentStatuses, [shipment.id]: event.target.value })}>{Object.entries(shipmentStatusLabels).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select><button className="outline-button small-button" disabled={shipmentBusy} onClick={() => void saveShipmentStatus(shipment.id)}>حفظ</button></> : <strong>{shipmentStatusLabels[shipment.status ?? 'pending'] ?? shipment.status}</strong>}</div>) : <p className="muted">لا توجد شحنات مسجلة لهذا الطلب.</p>}<div className="drawer-total"><span>الإجمالي</span><b>{details ? `${details.total_amount} ${details.currency ?? 'ر.س'}` : order.total}</b></div><h3>ملاحظات داخلية</h3><textarea value={internalNote} onChange={(event) => setInternalNote(event.target.value)} placeholder="أضف ملاحظة للفريق..." /><button className="primary-button full" disabled={noteBusy || !internalNote.trim()} onClick={() => void saveInternalNote()}>{noteBusy ? 'جار الحفظ...' : 'حفظ الملاحظة'}</button></aside></div>
}
function TruckIcon() { return <ShoppingCart size={13} /> }

function NotFound({ onHome }: { onHome: () => void }) {
  return <div className="not-found-page"><div className="not-found-card"><span className="not-found-code">404</span><h1>الصفحة غير موجودة</h1><p>يبدو أن الرابط الذي فتحته غير صحيح أو أن الصفحة نُقلت.</p><button className="primary-button" onClick={onHome}><LayoutDashboard size={16} /> العودة للرئيسية</button></div></div>
}

function NotAuthorized({ onHome }: { onHome: () => void }) {
  return <div className="not-found-page"><div className="not-found-card"><span className="not-found-code">403</span><h1>غير مصرح بالوصول</h1><p>لا تملك الصلاحية اللازمة لفتح هذا القسم. تواصل مع مدير النظام إذا كنت تحتاج الوصول.</p><button className="primary-button" onClick={onHome}><LayoutDashboard size={16} /> العودة للرئيسية</button></div></div>
}

export default App


type ToastMessage = { type: 'success' | 'error' | 'info'; message: string }

function NotificationMenu({ orders, onClose, onInfo }: { orders: Order[]; onClose: () => void; onInfo: () => void }) {
  const newOrders = orders.filter((order) => order.status === 'جديد').slice(0, 3)
  return <div className="notification-menu" onClick={(event) => event.stopPropagation()}><div className="notification-menu-head"><b>مركز التنبيهات</b><button onClick={onClose}>إغلاق</button></div>{newOrders.length === 0 ? <div className="notification-empty"><Bell size={20} /><span>لا توجد تنبيهات جديدة</span></div> : newOrders.map((order) => <button className="notification-item unread" key={order.id} onClick={() => { onClose(); onInfo() }}><span className="notification-dot blue" /><div><b>طلب جديد {order.id}</b><small>{order.customer} · {order.total}</small><em>{order.date}</em></div></button>)}<button className="notification-footer" onClick={onInfo}>فتح المتابعة والتنبيهات</button></div>
}

type GlobalSearchResult = { kind: 'order' | 'product' | 'customer'; id: number | string; title: string; subtitle: string }

function CommandPalette({ orders, onClose, onSelect, onOrderSelect }: { orders: Order[]; onClose: () => void; onSelect: (path: string) => void; onOrderSelect: (order: Order) => void }) {
  const [query, setQuery] = useState('')
  const [remoteResults, setRemoteResults] = useState<GlobalSearchResult[]>([])
  const localOrders = orders.filter((order) => `${order.id} ${order.customer}`.toLocaleLowerCase().includes(query.trim().toLocaleLowerCase())).slice(0, 5).map((order) => ({ kind: 'order' as const, id: order.id, title: `${order.id} · ${order.customer}`, subtitle: `${order.total} · ${order.status}`, order }))
  useEffect(() => {
    const term = query.trim()
    if (!term) { const reset = window.setTimeout(() => setRemoteResults([]), 0); return () => window.clearTimeout(reset) }
    let active = true
    const timer = window.setTimeout(() => {
      void Promise.all([listProducts({ search: term, per_page: 5 }), listCustomers({ search: term, page: 1, per_page: 5 })]).then(([productsResponse, customersResponse]) => {
        if (!active) return
        const productsData = productsResponse as import('./lib/api').ApiProduct[] | import('./lib/api').ApiProductPage
        const products = Array.isArray(productsData) ? productsData : productsData.data ?? []
        const customers = customersResponse.data ?? []
        setRemoteResults([
          ...products.map((product) => ({ kind: 'product' as const, id: product.id, title: product.name, subtitle: 'منتج في الكتالوج' })),
          ...customers.map((customer) => ({ kind: 'customer' as const, id: customer.id, title: customer.name ?? customer.email ?? 'عميل', subtitle: customer.email ?? customer.phone ?? 'ملف عميل' })),
        ])
      }).catch(() => { if (active) setRemoteResults([]) })
    }, 250)
    return () => { active = false; window.clearTimeout(timer) }
  }, [query])
  const openResult = (result: GlobalSearchResult) => {
    if (result.kind === 'product') onSelect('/catalog')
    else if (result.kind === 'customer') onSelect('/customers')
  }
  return <div className="modal-backdrop" onClick={onClose}><div className="command-palette" onClick={(event) => event.stopPropagation()}><div className="command-search"><Search size={18} /><input autoFocus value={query} onChange={(event) => setQuery(event.target.value)} placeholder="ابحث عن طلب أو عميل أو منتج..." /><kbd>ESC</kbd></div>{query.trim() && <><p className="command-label">الطلبات المطابقة</p>{localOrders.length === 0 ? <div className="command-empty">لا توجد طلبات مطابقة</div> : localOrders.map((result) => <button key={result.id} onClick={() => onOrderSelect(result.order)}><ShoppingCart size={17} /><span><b>{result.title}</b><small>{result.subtitle}</small></span><ChevronDown size={15} /></button>)}<p className="command-label">المنتجات والعملاء</p>{remoteResults.length === 0 ? <div className="command-empty">جار البحث أو لا توجد نتائج</div> : remoteResults.map((result) => <button key={`${result.kind}-${result.id}`} onClick={() => openResult(result)}><span className="search-result-kind">{result.kind === 'product' ? <Package size={17} /> : <Users size={17} />}</span><span><b>{result.title}</b><small>{result.subtitle}</small></span><ChevronDown size={15} /></button>)}</>}<p className="command-label">اختصارات سريعة</p><button onClick={() => onSelect('/orders')}><ShoppingCart size={17} /><span><b>الطلبات</b><small>الوصول إلى كل الطلبات والفلاتر</small></span><ChevronDown size={15} /></button><button onClick={() => onSelect('/catalog')}><Package size={17} /><span><b>المنتجات</b><small>إدارة المنتجات والمخزون</small></span><ChevronDown size={15} /></button><button onClick={() => onSelect('/customers')}><Users size={17} /><span><b>العملاء</b><small>عرض بيانات العملاء وطلباتهم</small></span><ChevronDown size={15} /></button></div></div>
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
