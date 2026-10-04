import { useState, type ReactNode } from 'react'
import { Bell, ChevronDown, HelpCircle, Menu, Search, Settings, Store, Pin, PinOff, X, Moon, Sun } from 'lucide-react'
import type { Order } from '../../orders/types'
import { NotificationMenu } from '../../../components/shared/DashboardWidgets'
import type { NavGroup } from '../../../app/navigation'

type SessionUser = { name?: string; email?: string; status?: string; roles?: string[]; permissions?: string[] }
type LocaleSettings = { locale: 'ar' | 'en'; currency: string }
type NavItem = { label: string; path: string; icon: React.ComponentType<{ size?: number }>; permission?: string }
type Props = {
  children: ReactNode; navItems: NavItem[]; navGroups: NavGroup[]; activeNav: string; currentUser: SessionUser | null; orders: Order[]; localeSettings: LocaleSettings; theme: 'light' | 'dark'; search: string;
  mobileNav: boolean; sidebarPinned: boolean; notificationsOpen: boolean; profileMenuOpen: boolean; commandOpen?: boolean;
  onNavigate: (path: string) => void; onMobileNavChange: (open: boolean) => void; onSidebarPinnedChange: (pinned: boolean) => void;
  onToast: (message: string, type?: 'success' | 'error' | 'info') => void; onLocaleChange: (currency: string, locale: 'ar' | 'en') => void; onToggleTheme: () => void;
  onSearchChange: (value: string) => void; onCommandOpen: () => void; onNotificationsChange: (open: boolean) => void; onProfileMenuChange: (open: boolean) => void; onLogout: () => void;
}

export function DashboardShell({ children, navItems, navGroups, activeNav, currentUser, orders, localeSettings, theme, search, mobileNav, sidebarPinned, notificationsOpen, profileMenuOpen, onNavigate, onMobileNavChange, onSidebarPinnedChange, onToast, onLocaleChange, onToggleTheme, onSearchChange, onNotificationsChange, onProfileMenuChange, onLogout, onCommandOpen }: Props) {
  const activeGroup = navGroups.find((group) => group.paths.some((path) => navItems.some((item) => item.path === path && item.label === activeNav)))?.label
  const [expandedGroups, setExpandedGroups] = useState<Record<string, boolean>>(() => {
    try { return JSON.parse(window.localStorage.getItem('souqi-sidebar-groups') ?? '{}') as Record<string, boolean> } catch { return {} }
  })
  const toggleGroup = (label: string) => setExpandedGroups((current) => { const next = { ...current, [label]: !current[label] }; window.localStorage.setItem('souqi-sidebar-groups', JSON.stringify(next)); return next })
  return (
    <div className="dashboard-shell">
      <aside className={`compact-sidebar ${mobileNav ? 'mobile-open' : ''} ${sidebarPinned ? 'pinned' : 'collapsed'}`}>
        <div className="sidebar-top">
          <div className="sidebar-brand">
            <div className="logo-mark" aria-label="سوقي"><Store size={21} /></div>
            <div><strong>سوقي</strong><small>إدارة المتجر</small></div>
          </div>
          <div className="sidebar-controls">
            <button className="sidebar-pin" onClick={() => { const next = !sidebarPinned; onSidebarPinnedChange(next); window.localStorage.setItem('souqi-sidebar-pinned', String(next)) }} aria-label={sidebarPinned ? 'إلغاء تثبيت القائمة' : 'تثبيت القائمة'} title={sidebarPinned ? 'إلغاء تثبيت القائمة' : 'تثبيت القائمة'} aria-pressed={sidebarPinned}>
              {sidebarPinned ? <PinOff size={15} /> : <Pin size={15} />}
            </button>
            <button className="mobile-close" onClick={() => onMobileNavChange(false)} aria-label="إغلاق القائمة"><X size={20} /></button>
          </div>
        </div>
        <nav className="main-nav" aria-label="التنقل الرئيسي">
          {navGroups.map((group) => {
            const items = navItems.filter((item) => group.paths.includes(item.path))
            if (!items.length) return null
            const expanded = expandedGroups[group.label] ?? (group.label === activeGroup || group.label === 'نظرة عامة')
            return <div className="nav-group" key={group.label}><button className={`nav-group-toggle ${expanded ? 'expanded' : ''}`} onClick={() => toggleGroup(group.label)} aria-expanded={expanded}><span className="nav-label">{group.label}</span><ChevronDown size={14} /></button><div className={`nav-group-items ${expanded ? 'expanded' : ''}`}>{items.map(({ label, path, icon: Icon }) => <button key={label} className={`nav-icon ${activeNav === label ? 'active' : ''}`} onClick={() => { onNavigate(path); onMobileNavChange(false) }} title={label} aria-label={label}><Icon size={20} /><span className="nav-label">{label}</span><span className="tooltip">{label}</span></button>)}</div></div>
          })}
        </nav>
        <div className="sidebar-bottom">
          <button className="nav-icon" title="المساعدة" aria-label="المساعدة" onClick={() => onToast('للدعم، افتح صفحة المراقبة أو تواصل مع مدير النظام', 'info')}>
            <HelpCircle size={20} /><span className="tooltip">المساعدة</span>
          </button>
          <button className="nav-icon" title="الإعدادات" aria-label="الإعدادات" onClick={() => onNavigate('/settings')}>
            <Settings size={20} /><span className="tooltip">الإعدادات</span>
          </button>
          <button className="profile-avatar" title="فتح إعدادات الملف الشخصي" aria-label="فتح إعدادات الملف الشخصي" onClick={() => onNavigate('/profile')}>
            {(currentUser?.name ?? 'م').slice(0, 1)}
          </button>
        </div>
      </aside>
      {mobileNav && <button className="sidebar-backdrop" onClick={() => onMobileNavChange(false)} aria-label="إغلاق القائمة" />}
      <div className="dashboard-main">
        <header className="topbar">
          <button className="mobile-menu" onClick={() => onMobileNavChange(true)} aria-label="فتح القائمة"><Menu size={21} /></button>
          <div className="breadcrumbs"><span>الرئيسية</span><b>/</b><strong>{activeNav}</strong></div>
          <div className="topbar-actions">
            <div className="appearance-controls">
              <select className="language-select" value={localeSettings.locale} aria-label="Language" onChange={(event) => { const locale = event.target.value as 'ar' | 'en'; onLocaleChange(localeSettings.currency, locale) }}>
                <option value="ar">العربية</option><option value="en">English</option>
              </select>
              <button className="theme-toggle" onClick={() => onToggleTheme()} aria-label={theme === 'dark' ? 'تفعيل الوضع الفاتح' : 'تفعيل الوضع المظلم'} title={theme === 'dark' ? 'الوضع الفاتح' : 'الوضع المظلم'}>
                {theme === 'dark' ? <Sun size={17} /> : <Moon size={17} />}
              </button>
            </div>
            <label className="global-search" onClick={() => onCommandOpen()}>
              <Search size={17} />
              <input value={search} onChange={(event) => onSearchChange(event.target.value)} onFocus={() => onCommandOpen()} placeholder="ابحث عن طلب أو عميل..." />
              <kbd>⌘ K</kbd>
            </label>
            <div className="notification-wrap">
              <button className="notification-button" onClick={() => onNotificationsChange(!notificationsOpen)} aria-label="الإشعارات">
                <Bell size={19} />{orders.some((order) => order.status === 'جديد') && <i />}
              </button>
              {notificationsOpen && <NotificationMenu orders={orders} onClose={() => onNotificationsChange(false)} onInfo={() => { onNotificationsChange(false); onNavigate('/monitoring') }} />}
            </div>
            <div className="profile-menu-wrap">
              <button className="top-profile" onClick={() => onProfileMenuChange(!profileMenuOpen)} aria-expanded={profileMenuOpen} title="فتح قائمة الملف الشخصي">
                <span className="profile-avatar small">{(currentUser?.name ?? 'م').slice(0, 1)}</span>
                <div><b>{currentUser?.name ?? 'حساب الإدارة'}</b><small>{currentUser?.email ?? 'الملف الشخصي'}</small></div>
                <ChevronDown size={15} />
              </button>
              {profileMenuOpen && <div className="profile-menu" role="menu">
                <button role="menuitem" onClick={() => { onProfileMenuChange(false); onNavigate('/profile') }}><Settings size={15} /><span>الملف الشخصي</span></button>
                <button role="menuitem" onClick={() => { onProfileMenuChange(false); void onLogout() }}><X size={15} /><span>تسجيل الخروج</span></button>
              </div>}
            </div>
          </div>
        </header>
        <main className="main-content">{children}</main>
      </div>
    </div>
  )
}
