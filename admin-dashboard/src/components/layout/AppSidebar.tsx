import { NavLink } from 'react-router-dom'
import { BarChart3, Boxes, ChevronLeft, CircleHelp, ClipboardList, LayoutDashboard, Percent, Settings, ShoppingBag, Users, X } from 'lucide-react'

const primary = [{ label: 'لوحة التحكم', icon: LayoutDashboard }, { label: 'الطلبات', icon: ClipboardList }, { label: 'المنتجات', icon: ShoppingBag }, { label: 'العملاء', icon: Users }, { label: 'التقارير', icon: BarChart3 }, { label: 'الخصومات', icon: Percent }]
const secondary = [{ label: 'التكاملات', icon: Boxes }, { label: 'المساعدة', icon: CircleHelp }, { label: 'الإعدادات', icon: Settings }]

export function AppSidebar({ open, onClose }: { open: boolean; onClose: () => void }) {
  const nav = (items: typeof primary) => items.map(({ label, icon: Icon }, index) => <NavLink key={label} to={index === 0 ? '/' : '#'} onClick={onClose} className={({ isActive }) => `nav-item ${index === 0 && isActive ? 'active' : ''}`}><Icon size={19} /><span>{label}</span>{index === 0 && <ChevronLeft className="nav-arrow" size={16} />}</NavLink>)
  return <><div className={`sidebar-overlay ${open ? 'visible' : ''}`} onClick={onClose} /><aside className={`sidebar ${open ? 'open' : ''}`}><div className="brand"><div className="brand-mark"><span /><span /><span /><span /></div><strong>سوقي</strong><button className="close-sidebar" onClick={onClose}><X size={20} /></button></div><nav><div className="nav-section">الرئيسية</div>{nav(primary)}<div className="nav-divider" />{nav(secondary)}</nav><div className="sidebar-footer"><div className="store-avatar">م</div><div><b>متجري الإلكتروني</b><small>الإدارة</small></div><ChevronLeft size={16} /></div></aside></>
}
