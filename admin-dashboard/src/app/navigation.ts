import { AlertTriangle, Bot, Boxes, CircleDollarSign, CreditCard, FileText, LayoutDashboard, MessageCircle, Package, RefreshCcw, Settings, Shield, ShieldCheck, ShoppingCart, Store, Truck, TrendingUp, Users, Webhook } from 'lucide-react'

export const navItems = [
  { label: 'الرئيسية', path: '/', icon: LayoutDashboard },
  { label: 'الطلبات', path: '/orders', icon: ShoppingCart, permission: 'orders.view' },
  { label: 'الشحنات', path: '/shipments', icon: Truck, permission: 'shipping.view' },
  { label: 'الإرجاعات', path: '/returns', icon: RefreshCcw, permission: 'returns.view' },
  { label: 'العملاء', path: '/customers', icon: Users, permission: 'customers.view' },
  { label: 'المنتجات', path: '/catalog', icon: Package, permission: 'products.view' },
  { label: 'هيكلة الكتالوج', path: '/catalog/taxonomy', icon: Boxes, permission: 'products.view' },
  { label: 'المحتوى', path: '/content', icon: FileText, permission: 'cms.view' },
  { label: 'المخزون', path: '/inventory', icon: Boxes, permission: 'inventory.view' },
  { label: 'التقارير', path: '/reports', icon: TrendingUp, permission: 'orders.view' },
  { label: 'الإدارة', path: '/management', icon: Shield, permission: 'assistants.view' },
  { label: 'الأدوار والصلاحيات', path: '/roles-permissions', icon: ShieldCheck, permission: 'roles.view' },
  { label: 'التجارة', path: '/commerce', icon: Store, permission: 'promotions.view' },
  { label: 'التجارة الاجتماعية', path: '/social', icon: MessageCircle, permission: 'social.interactions.view' },
  { label: 'الذكاء الاصطناعي', path: '/ai', icon: Bot, permission: 'settings.view' },
  { label: 'المراقبة', path: '/monitoring', icon: AlertTriangle, permission: 'monitoring.run' },
  { label: 'التكاملات', path: '/integrations', icon: Webhook, permission: 'integrations.view' },
  { label: 'المالية', path: '/finance', icon: CircleDollarSign, permission: 'settlements.view' },
  { label: 'المدفوعات', path: '/payments', icon: CreditCard, permission: 'payments.view' },
  { label: 'التسويات', path: '/settlements', icon: FileText, permission: 'settlements.view' },
  { label: 'الإعدادات', path: '/settings', icon: Settings, permission: 'settings.view' },
]

export const utilityPaths = ['/profile']
