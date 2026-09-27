import type { ApiOrder } from '../../lib/api/types'

export type OrderStatus = 'جديد' | 'قيد التجهيز' | 'تم الشحن' | 'مكتمل'
export type Order = { id: string; apiId?: number; customer: string; initials: string; date: string; total: string; totalAmount?: number; payment: string; status: OrderStatus }
export type { ApiOrder }
