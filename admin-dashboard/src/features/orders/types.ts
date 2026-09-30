import type { ApiOrder } from '../../lib/api/types'

export type OrderStatus = 'جديد' | 'مراجعة' | 'مؤكد' | 'قيد التجهيز' | 'تم الشحن' | 'تم التسليم' | 'ملغي' | 'مسترد'
export type Order = { id: string; apiId?: number; customer: string; initials: string; date: string; total: string; totalAmount?: number; payment: string; status: OrderStatus }
export type { ApiOrder }
