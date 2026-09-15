import { Bell, ChevronDown, Menu, Search } from 'lucide-react'

export function Header({ onMenu }: { onMenu: () => void }) {
  return <header className="topbar"><button className="header-menu" onClick={onMenu} aria-label="فتح القائمة"><Menu size={22} /></button><div className="breadcrumb"><span>لوحة التحكم</span><b>نظرة عامة</b></div><div className="header-actions"><label className="search"><Search size={18} /><input placeholder="ابحث عن طلب، منتج..." /></label><button className="icon-button notification"><Bell size={20} /><i /></button><div className="profile"><div className="profile-avatar">م</div><div><b>محمد أحمد</b><small>مدير المتجر</small></div><ChevronDown size={16} /></div></div></header>
}
