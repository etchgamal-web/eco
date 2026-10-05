export function formatPrice(price: number, currency = 'ج.م'): string {
  return `${new Intl.NumberFormat('ar-EG').format(price)} ${currency}`
}
