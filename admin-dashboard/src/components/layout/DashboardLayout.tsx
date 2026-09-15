import { Outlet } from 'react-router-dom'
import { useState } from 'react'
import { AppSidebar } from './AppSidebar'
import { Header } from './Header'

export function DashboardLayout() {
  const [open, setOpen] = useState(false)
  return <div className="app-shell"><AppSidebar open={open} onClose={() => setOpen(false)} /><div className="app-main"><Header onMenu={() => setOpen(true)} /><main className="dashboard-content"><Outlet /></main></div></div>
}
