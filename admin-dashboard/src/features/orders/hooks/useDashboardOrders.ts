import { useMemo, useState } from 'react'
import { getToken } from '../../../lib/api'
import type { ApiOrder } from '../../../lib/api/types'
import type { Order, OrderStatus } from '../types'

export const statusOptions: OrderStatus[] = ['جديد', 'مراجعة', 'مؤكد', 'قيد التجهيز', 'تم الشحن', 'تم التسليم']
export const statusMap: Record<OrderStatus, string> = { 'جديد': 'pending', 'مراجعة': 'reviewing', 'مؤكد': 'confirmed', 'قيد التجهيز': 'processing', 'تم الشحن': 'shipped', 'تم التسليم': 'delivered', 'ملغي': 'cancelled', 'مسترد': 'refunded' }
const statusLabels: Record<string, OrderStatus> = {
  pending: 'جديد', reviewing: 'مراجعة', confirmed: 'مؤكد', processing: 'قيد التجهيز', shipped: 'تم الشحن',
  delivered: 'تم التسليم', cancelled: 'ملغي', refunded: 'مسترد',
}

export const demoOrders: Order[] = [
  { id: '#ORD-8294', customer: 'سارة العتيبي', initials: 'سع', date: 'اليوم، ١٠:٤٢ ص', total: '٥٩٧ ر.س', payment: 'مدى', status: 'جديد' },
  { id: '#ORD-8293', customer: 'محمد القحطاني', initials: 'مق', date: 'اليوم، ٠٩:١٨ ص', total: '١,٢٤٠ ر.س', payment: 'Apple Pay', status: 'قيد التجهيز' },
  { id: '#ORD-8292', customer: 'نورة الحربي', initials: 'نه', date: 'أمس، ٠٦:٣٥ م', total: '٣٩٩ ر.س', payment: 'بطاقة ائتمانية', status: 'تم الشحن' },
  { id: '#ORD-8291', customer: 'خالد الشهري', initials: 'خش', date: 'أمس، ٠٢:١١ م', total: '٨٧٥ ر.س', payment: 'مدى', status: 'تم التسليم' },
  { id: '#ORD-8290', customer: 'ريم الغامدي', initials: 'رغ', date: '٢٠ أغسطس، ١١:٠٣ ص', total: '٢١٠ ر.س', payment: 'الدفع عند الاستلام', status: 'قيد التجهيز' },
]

export function normalizeApiOrder(order: ApiOrder): Order {
  const customer = order.user?.name || order.user?.email || 'عميل متجر'
  return {
    id: `#${order.order_number || order.id}`, apiId: order.id, customer, initials: customer.slice(0, 2), totalAmount: Number(order.total_amount ?? 0),
    date: order.created_at ? new Date(order.created_at).toLocaleString('ar-EG', { dateStyle: 'medium', timeStyle: 'short' }) : '—',
    total: `${order.total_amount ?? 0} ${order.currency || 'ر.س'}`, payment: 'غير محدد', status: statusLabels[order.status] || 'جديد',
  }
}

export function useDashboardOrders() {
  const [statusFilter, setStatusFilter] = useState<'الكل' | OrderStatus>('الكل')
  const [paymentFilter, setPaymentFilter] = useState('كل طرق الدفع')
  const [dateFilter, setDateFilter] = useState('كل التواريخ')
  const [search, setSearch] = useState('')
  const [rows, setRows] = useState<Order[]>(() => getToken() ? [] : [])
  const filteredOrders = useMemo(() => rows.filter((order) => {
    const matchesStatus = statusFilter === 'الكل' || order.status === statusFilter
    const matchesPayment = paymentFilter === 'كل طرق الدفع' || order.payment === paymentFilter
    const matchesSearch = `${order.id} ${order.customer}`.includes(search.trim())
    return matchesStatus && matchesPayment && matchesSearch && (dateFilter === 'كل التواريخ' || (dateFilter === 'اليوم' ? order.date.startsWith('اليوم') : !order.date.startsWith('اليوم')))
  }), [rows, statusFilter, paymentFilter, search, dateFilter])
  const dashboardStats = useMemo(() => {
    const totalSales = rows.reduce((sum, order) => sum + (order.totalAmount ?? Number.parseFloat(order.total.replace(/[^0-9.]/g, '') || '0')), 0)
    const newOrders = rows.filter((order) => order.status === 'جديد').length
    return { totalSales, newOrders, averageOrder: rows.length ? totalSales / rows.length : 0 }
  }, [rows])
  return { rows, setRows, filteredOrders, dashboardStats, statusFilter, setStatusFilter, paymentFilter, setPaymentFilter, dateFilter, setDateFilter, search, setSearch }
}
