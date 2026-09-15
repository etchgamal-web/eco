import { Routes, Route } from 'react-router-dom'
import { DashboardLayout } from '@/components/layout/DashboardLayout'
import { Dashboard } from '@/pages/Dashboard'
import { Login } from '@/pages/Login'
import { NotFound } from '@/pages/NotFound'

function Placeholder({ name }: { name: string }) { return <h1 className="text-3xl font-bold">{name}</h1> }

export function AppRoutes() {
  return <Routes><Route path="/login" element={<Login />} /><Route element={<DashboardLayout />}><Route path="/" element={<Dashboard />} /><Route path="/products" element={<Placeholder name="Products" />} /><Route path="/orders" element={<Placeholder name="Orders" />} /><Route path="/customers" element={<Placeholder name="Customers" />} /><Route path="/settings" element={<Placeholder name="Settings" />} /></Route><Route path="*" element={<NotFound />} /></Routes>
}
