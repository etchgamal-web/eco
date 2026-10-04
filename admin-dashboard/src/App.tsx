import { Suspense, useEffect, useMemo, useState } from 'react'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import { useLocation, useNavigate } from 'react-router-dom'
import { ApiError, cancelOrder, confirmOrder, downloadOrdersCsv, getOrder, getOrderTimeline, getToken, listOrders, listSettings, logout, me, reviewOrder, updateOrderStatus } from './lib/api'
import { ToastViewport } from './components/shared/DashboardWidgets'
import type { ToastMessage } from './components/shared/DashboardWidgets'
import { LoginScreen } from './features/auth/components/LoginScreen'
import { useDashboardKeyboard } from './app/hooks/useDashboardKeyboard'
import { useDashboardPreferences } from './app/hooks/useDashboardPreferences'
import { navItems, utilityPaths } from './app/navigation'
import { NotAuthorized, NotFound } from './app/guards/RouteFallbacks'
import './App.css'

import type { Order, OrderStatus } from './features/orders/types'
import { statusOptions, normalizeApiOrder, useDashboardOrders, statusMap, orderWorkflowPermissions } from './features/orders/hooks/useDashboardOrders'
import { OrderDrawer } from './features/orders/components/OrderDrawer'
import { CommandPalette } from './features/search/components/CommandPalette'
import { DashboardShell } from './features/dashboard/components/DashboardShell'
import { DashboardContent } from './features/dashboard/components/DashboardContent'
import { getDashboardStats } from './lib/api/dashboard'
import { getOperationalDashboard } from './lib/api/payments'
type SessionUser = { name?: string; email?: string; status?: string; roles?: string[]; permissions?: string[] }

function PageLoading() {
  return <div className="page-loading" role="status" aria-live="polite"><span className="skeleton-line" /><span className="skeleton-line short" /><span>جار تحميل الصفحة...</span></div>
}
function App() {
  const location = useLocation()
  const navigate = useNavigate()
  const [authenticated, setAuthenticated] = useState(() => Boolean(getToken()))
  const orderDetailId = /^\/orders\/([1-9]\d*)$/.exec(location.pathname)?.[1] ?? null
  const isFullOrderPage = orderDetailId !== null && Number.isSafeInteger(Number(orderDetailId))
  const activeNav = navItems.find((item) => item.path === location.pathname)?.label ?? (location.pathname.startsWith('/settings/') ? 'الإعدادات' : isFullOrderPage ? (navItems.find((item) => item.path === '/orders')?.label ?? 'الطلبات') : 'الرئيسية')
  const [selectedOrder, setSelectedOrder] = useState<Order | null>(null)
  const [selectedOrderDetails, setSelectedOrderDetails] = useState<import('./lib/api').ApiOrder | null>(null)
  const [selectedOrderTimeline, setSelectedOrderTimeline] = useState<unknown[]>([])
  const [ordersTotal, setOrdersTotal] = useState(0)
  const [orderDetailsLoading, setOrderDetailsLoading] = useState(false)
  const [orderPageErrorId, setOrderPageErrorId] = useState<number | null>(null)
  const [toast, setToast] = useState<ToastMessage | null>(null)
  const [mobileNav, setMobileNav] = useState(false)
  const [sidebarPinned, setSidebarPinned] = useState(() => window.localStorage.getItem('souqi-sidebar-pinned') === 'true')
  const [notificationsOpen, setNotificationsOpen] = useState(false)
  const [profileMenuOpen, setProfileMenuOpen] = useState(false)
  const [commandOpen, setCommandOpen] = useState(false)
  const [apiLoading, setApiLoading] = useState(Boolean(getToken()))
  const [currentUser, setCurrentUser] = useState<SessionUser | null>(null)
  const [socialStats] = useState<Record<string, unknown>>({})
  const { localeSettings, theme, setLocale, toggleTheme } = useDashboardPreferences()
  const queryClient = useQueryClient()
  const dashboardQuery = useQuery({ queryKey: ['dashboard', 'stats', currentUser?.email ?? 'anonymous'], queryFn: getDashboardStats, enabled: authenticated && currentUser !== null })
  const operationsQuery = useQuery({ queryKey: ['dashboard', 'operations', currentUser?.email ?? 'anonymous'], queryFn: getOperationalDashboard, enabled: authenticated && currentUser !== null, staleTime: 30_000 })
  const visibleNavItems = useMemo(() => { if (!authenticated || !currentUser) return navItems; const roles = currentUser.roles ?? []; const permissions = new Set(currentUser.permissions ?? []); if (roles.some((role) => ['owner', 'admin'].includes(role))) return navItems; return navItems.filter((item) => !item.permission || permissions.has(item.permission)) }, [authenticated, currentUser])

  useEffect(() => { if (!toast) return; const timer = window.setTimeout(() => setToast(null), toast.duration ?? (toast.type === 'error' ? 8000 : 4000)); return () => window.clearTimeout(timer) }, [toast])
  useDashboardKeyboard(() => setCommandOpen(true), () => { setCommandOpen(false) })
  const { rows, setRows, filteredOrders, dashboardStats, statusFilter, setStatusFilter, paymentFilter, setPaymentFilter, dateFilter, setDateFilter, search, setSearch } = useDashboardOrders()

  // The effect synchronizes the authenticated view with the external Laravel API.
  // eslint-disable-next-line react-hooks/set-state-in-effect
  useEffect(() => { if (!authenticated) { setApiLoading(false); setCurrentUser(null); setRows([]); setOrdersTotal(0); setSelectedOrder(null); setSelectedOrderDetails(null); setSelectedOrderTimeline([]); return } Promise.all([me(), listOrders({ page: 1, per_page: 20 }), listSettings()]).then(([user, data, settings]) => { setCurrentUser(user); const ordersPage = Array.isArray(data) ? data : data.data; setOrdersTotal(Array.isArray(data) ? data.length : data.total); setRows(ordersPage.map(normalizeApiOrder)); const currency = String(settings.find((item) => item.key === 'store.currency')?.value ?? localeSettings.currency); const locale = String(settings.find((item) => item.key === 'store.locale')?.value ?? localeSettings.locale) as 'ar' | 'en'; if (currency !== localeSettings.currency || locale !== localeSettings.locale) setLocale(currency, locale) }).catch((error: unknown) => { if (error instanceof ApiError && error.status === 401) { setAuthenticated(false); setToast({ type: 'info', message: 'انتهت جلسة الدخول، يرجى تسجيل الدخول مجددًا' }) } else setToast({ type: 'error', message: error instanceof ApiError ? error.message : 'تعذر تحميل بيانات الحساب من الـAPI' }) }).finally(() => setApiLoading(false)) }, [authenticated, localeSettings.currency, localeSettings.locale, setLocale, setRows])

  // Load order details directly when a user opens /orders/{id} from a bookmark or the drawer.
  useEffect(() => {
    if (!authenticated || !isFullOrderPage || !orderDetailId) return
    let active = true
    const apiOrderId = Number(orderDetailId)
    Promise.all([getOrder(apiOrderId), getOrderTimeline(apiOrderId)])
      .then(([details, timeline]) => {
        if (!active) return
        setSelectedOrder(normalizeApiOrder(details))
        setOrderPageErrorId(null)
        setSelectedOrderDetails(details)
        setSelectedOrderTimeline(Array.isArray(timeline) ? timeline : [])
      })
      .catch((error: unknown) => {
        if (!active) return
        setOrderPageErrorId(apiOrderId)
        setToast({ type: 'error', message: error instanceof ApiError ? error.message : 'تعذر تحميل تفاصيل الطلب' })
      })
    return () => { active = false }
  }, [authenticated, isFullOrderPage, orderDetailId])

  const todayLabel = new Date().toLocaleDateString('ar-EG', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })

  const canEditOrders = currentUser?.roles?.some((role) => ['owner', 'admin'].includes(role)) || currentUser?.permissions?.includes('orders.edit') === true
  const canAdvanceOrder = (order: Order) => { const permission = orderWorkflowPermissions[statusMap[order.status]]; return Boolean(currentUser?.roles?.some((role) => ['owner', 'admin'].includes(role)) || (permission && currentUser?.permissions?.includes(permission))) }
  const updateStatus = async (id: string, status: OrderStatus) => { const order = rows.find((item) => item.id === id); if (!order || !canAdvanceOrder(order)) { setToast({ type: 'error', message: 'لا تملك صلاحية تنفيذ انتقال هذه المرحلة' }); return } const nextBackendStatus = statusMap[status]; try { if (order.apiId && getToken()) { if (statusMap[order.status] === 'pending') await reviewOrder(order.apiId); else if (statusMap[order.status] === 'reviewing') await confirmOrder(order.apiId); else await updateOrderStatus(order.apiId, nextBackendStatus) } setRows((current) => current.map((item) => item.id === id ? { ...item, status } : item)); setToast({ type: 'success', message: `تم تغيير حالة الطلب ${id}` }) } catch (error) { setToast({ type: 'error', message: error instanceof ApiError ? error.message : 'تعذر تغيير حالة الطلب' }) } }
  const handleLogout = async () => { try { await logout() } catch { /* The local token is cleared even if the remote logout is unavailable. */ } queryClient.removeQueries({ queryKey: ['dashboard', 'stats'] }); setCurrentUser(null); setRows([]); setOrdersTotal(0); setSelectedOrder(null); setSelectedOrderDetails(null); setSelectedOrderTimeline([]); setAuthenticated(false); navigate('/'); setToast({ type: 'success', message: 'تم تسجيل الخروج بنجاح' }) }
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
  const openOrderFullPage = (order: Order) => { if (!order.apiId) { setToast({ type: 'error', message: 'لا يتوفر معرّف لفتح هذا الطلب' }); return } navigate(`/orders/${order.apiId}`) }
  const closeFullOrderPage = () => { closeOrderDrawer(); navigate('/orders') }
  const handleOrderAction = async (action: 'confirm' | 'cancel') => {
    if (!selectedOrderDetails?.id) return
    try {
      const updated = action === 'confirm' ? await confirmOrder(selectedOrderDetails.id) : await cancelOrder(selectedOrderDetails.id)
      setSelectedOrderDetails(updated)
      setRows((current) => current.map((item) => item.apiId === updated.id ? normalizeApiOrder(updated) : item))
      const nextTimeline = await getOrderTimeline(updated.id)
      setSelectedOrderTimeline(Array.isArray(nextTimeline) ? nextTimeline : [])
      setToast({ type: 'success', message: action === 'confirm' ? 'تم تأكيد الطلب' : 'تم إلغاء الطلب' })
    } catch (error: unknown) { setToast({ type: 'error', message: error instanceof ApiError ? error.message : 'تعذر تنفيذ إجراء الطلب' }); throw error }
  }
  const printDashboardOrder = (order: Order) => { const popup = window.open('', '_blank', 'width=760,height=800'); if (!popup) { setToast({ type: 'error', message: 'السماح بالنوافذ المنبثقة مطلوب للطباعة' }); return } const safe = (value: unknown) => String(value ?? '').replace(/[&<>"']/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[character] ?? character)); popup.document.write(`<html dir="rtl"><head><title>طلب ${safe(order.id)}</title><style>body{font-family:Arial,sans-serif;padding:36px;color:#172033}h1{font-size:24px}table{width:100%;border-collapse:collapse;margin-top:24px}th,td{border-bottom:1px solid #ddd;padding:12px;text-align:right}small{color:#667085}</style></head><body><h1>ملخص الطلب ${safe(order.id)}</h1><small>${safe(order.date)}</small><table><tbody><tr><th>العميل</th><td>${safe(order.customer)}</td></tr><tr><th>طريقة الدفع</th><td>${safe(order.payment)}</td></tr><tr><th>الحالة</th><td>${safe(order.status)}</td></tr><tr><th>الإجمالي</th><td>${safe(order.total)}</td></tr></tbody></table></body></html>`); popup.document.close(); popup.focus(); popup.print() }

  if (!authenticated) return <LoginScreen onSuccess={() => setAuthenticated(true)} />
  const isSettingsSection = location.pathname === '/settings' || location.pathname.startsWith('/settings/')
  const canViewSettings = currentUser?.roles?.some((role) => ['owner', 'admin'].includes(role)) || currentUser?.permissions?.includes('settings.view')
  const routeAccessPath = isFullOrderPage ? '/orders' : location.pathname
  if (location.pathname !== '/' && !navItems.some((item) => item.path === location.pathname) && !isSettingsSection && !utilityPaths.includes(location.pathname) && !isFullOrderPage) return <NotFound onHome={() => navigate('/')} />
  if (isSettingsSection && !canViewSettings) return <NotAuthorized onHome={() => navigate('/')} />
  if (routeAccessPath !== '/' && !visibleNavItems.some((item) => item.path === routeAccessPath) && !isSettingsSection && !utilityPaths.includes(location.pathname)) return <NotAuthorized onHome={() => navigate('/')} />


  const fullPageOrderMatches = Boolean(selectedOrder && selectedOrderDetails && selectedOrderDetails.id === Number(orderDetailId))
  const renderOrderDrawer = (fullPage: boolean) => selectedOrder ? <OrderDrawer key={`${selectedOrder.id}-${selectedOrderDetails ? 'loaded' : 'loading'}-${fullPage ? 'page' : 'drawer'}`} order={selectedOrder} details={selectedOrderDetails} timeline={selectedOrderTimeline} loading={orderDetailsLoading} canCreateShipment={currentUser?.roles?.some((role) => ['owner', 'admin'].includes(role)) || currentUser?.permissions?.includes('shipments.create') === true} canManageShipment={currentUser?.roles?.some((role) => ['owner', 'admin'].includes(role)) || currentUser?.permissions?.includes('shipping.manage') === true} canManageOrders={canEditOrders} fullPage={fullPage} onOpenFullPage={() => openOrderFullPage(selectedOrder)} onOrderAction={handleOrderAction} onClose={fullPage ? closeFullOrderPage : closeOrderDrawer} onToast={(message, type) => setToast({ message, type })} /> : null
  return <DashboardShell navItems={visibleNavItems} activeNav={activeNav} currentUser={currentUser} orders={rows} localeSettings={localeSettings} theme={theme} search={search} mobileNav={mobileNav} sidebarPinned={sidebarPinned} notificationsOpen={notificationsOpen} profileMenuOpen={profileMenuOpen}
    onNavigate={navigate} onMobileNavChange={setMobileNav} onSidebarPinnedChange={(next) => { setSidebarPinned(next); window.localStorage.setItem('souqi-sidebar-pinned', String(next)) }} onToast={(message, type) => setToast({ type: type ?? 'info', message })} onLocaleChange={setLocale} onToggleTheme={toggleTheme} onSearchChange={setSearch} onCommandOpen={() => setCommandOpen(true)} onNotificationsChange={setNotificationsOpen} onProfileMenuChange={setProfileMenuOpen} onLogout={() => void handleLogout()}>{isFullOrderPage ? (fullPageOrderMatches ? renderOrderDrawer(true) : orderPageErrorId === Number(orderDetailId) ? <div className="order-page-error"><b>تعذر فتح تفاصيل الطلب</b><p>تأكد من صحة رقم الطلب أو ارجع إلى قائمة الطلبات.</p><button className="outline-button" onClick={closeFullOrderPage}>العودة إلى الطلبات</button></div> : <PageLoading />) : <><Suspense fallback={<PageLoading />}><DashboardContent
      activeNav={activeNav} pathname={location.pathname} currentUser={currentUser} onToast={(message, type) => setToast({ type: type ?? 'success', message })} onUserUpdated={setCurrentUser}
      navigate={navigate} apiLoading={apiLoading || dashboardQuery.isLoading} todayLabel={todayLabel} dashboardStats={dashboardQuery.data ? { totalSales: dashboardQuery.data.sales.total, newOrders: dashboardQuery.data.orders.new, averageOrder: dashboardQuery.data.average_order, totalOrders: dashboardQuery.data.orders.total } : dashboardStats} currency={localeSettings.currency} socialStats={dashboardQuery.data?.social ?? socialStats} operationalDashboard={operationsQuery.data}
      rows={rows} ordersTotal={ordersTotal} filteredOrders={filteredOrders} statusFilter={statusFilter} setStatusFilter={setStatusFilter} paymentFilter={paymentFilter} setPaymentFilter={setPaymentFilter}
      dateFilter={dateFilter} setDateFilter={setDateFilter} setSearch={setSearch} statusOptions={statusOptions} updateStatus={updateStatus}
      exportOrders={exportOrders} openOrderDrawer={openOrderDrawer} printDashboardOrder={printDashboardOrder} canEditOrders={canEditOrders} canAdvanceOrder={canAdvanceOrder}
    /></Suspense>{selectedOrder && renderOrderDrawer(false)}</>}{commandOpen && <CommandPalette orders={rows} onClose={() => setCommandOpen(false)} onSelect={(path) => { setCommandOpen(false); navigate(path) }} onOrderSelect={(order) => { setCommandOpen(false); void openOrderDrawer(order) }} />}{toast && <ToastViewport toast={toast} onClose={() => setToast(null)} />}</DashboardShell>
}
export default App
