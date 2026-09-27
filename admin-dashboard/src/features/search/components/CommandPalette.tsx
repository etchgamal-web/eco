import { useEffect, useState } from 'react'
import { ChevronDown, Package, Search, ShoppingCart, Users } from 'lucide-react'
import { listCustomers } from '../../../lib/api/customers'
import { listProducts } from '../../../lib/api/catalog'
import type { ApiProduct, ApiProductPage } from '../../../lib/api/types'
import type { Order } from '../../orders/types'

type GlobalSearchResult = { kind: 'order' | 'product' | 'customer'; id: number | string; title: string; subtitle: string }

export function CommandPalette({ orders, onClose, onSelect, onOrderSelect }: { orders: Order[]; onClose: () => void; onSelect: (path: string) => void; onOrderSelect: (order: Order) => void }) {
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
        const productsData = productsResponse as ApiProduct[] | ApiProductPage
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
