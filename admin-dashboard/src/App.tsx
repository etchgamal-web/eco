import { useEffect, useState } from 'react'
import { BrowserRouter } from 'react-router-dom'
import { AppSidebar } from '@/components/layout/AppSidebar'
import { Header } from '@/components/layout/Header'
import { Dashboard } from '@/pages/Dashboard'
import { Menu } from 'lucide-react'
import './index.css'

function App() {
  const [sidebarOpen, setSidebarOpen] = useState(false)
  useEffect(() => { document.documentElement.dir = 'rtl'; document.documentElement.lang = 'ar' }, [])
  return <BrowserRouter><div className="app-shell"><AppSidebar open={sidebarOpen} onClose={() => setSidebarOpen(false)} /><div className="app-main"><Header onMenu={() => setSidebarOpen(true)} /><main className="dashboard-content"><button className="mobile-menu" onClick={() => setSidebarOpen(true)} aria-label="فتح القائمة"><Menu size={20} /></button><Dashboard /></main></div></div></BrowserRouter>
}

export default App
