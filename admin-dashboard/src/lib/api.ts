export type ApiOrder = {
  id: number
  order_number?: string | null
  status: string
  total_amount: number
  currency?: string | null
  created_at?: string | null
  user?: { name?: string | null; email?: string | null } | null
  items?: Array<{ name?: string | null; quantity?: number; total_amount?: number; product?: { name?: string | null } | null }>
  shipping_address?: { recipient_name?: string; city?: string; address_line1?: string } | null
  payments?: Array<{ id: number; method?: string | null; amount?: number; currency?: string | null; status?: string | null }>
  shipments?: Array<{ id: number; provider_code?: string; tracking_number?: string | null; status?: string; created_at?: string | null }>
}

export type ApiProduct = { id: number; name: string; type?: 'simple' | 'variable'; status?: string; price?: number; category?: { id?: number; name?: string } | null; brand?: { id?: number; name?: string } | null; variants?: Array<{ inventory?: { on_hand?: number; available?: number } | null }> }
export type ApiProductPage = { data: ApiProduct[]; current_page: number; last_page: number; per_page: number; total: number }
export type ApiInventory = { product_id: number; variant_id?: number | null; on_hand?: number; available?: number; reserved?: number; product?: ApiProduct | null; variant?: { sku?: string | null } | null; movements?: Array<{ id: number; quantity: number; on_hand_after: number; reason?: string; note?: string | null; created_at?: string; actor?: { name?: string | null } | null }> }

const API_BASE = (import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api/v1').replace(/\/$/, '')

export class ApiError extends Error {
  status: number
  constructor(status: number, message: string) { super(message); this.status = status }
}

async function request<T>(path: string, init: RequestInit = {}, token = getToken()): Promise<T> {
  const headers = new Headers(init.headers)
  headers.set('Accept', 'application/json')
  if (init.body && !headers.has('Content-Type') && !(init.body instanceof FormData)) headers.set('Content-Type', 'application/json')
  if (token) headers.set('Authorization', `Bearer ${token}`)
  const response = await fetch(`${API_BASE}${path}`, { ...init, headers })
  const payload = await response.json().catch(() => null)
  if (!response.ok) {
    if (response.status === 401 && token) clearToken()
    throw new ApiError(response.status, payload?.message ?? `فشل الطلب (${response.status})`)
  }
  return payload as T
}

export function getToken() { return localStorage.getItem('eco_admin_token') }
export function setToken(token: string) { localStorage.setItem('eco_admin_token', token) }
export function clearToken() { localStorage.removeItem('eco_admin_token') }

export async function login(identifier: string, password: string) {
  const payload = await request<{ token: string; data: { id: number; name?: string; email?: string } }>('/auth/login', { method: 'POST', body: JSON.stringify({ identifier, password, remember: true }) }, null)
  setToken(payload.token)
  return payload.data
}

export async function me() { return (await request<{ data: { id: number; name?: string; email?: string; status?: string; roles?: string[]; permissions?: string[] } }>('/auth/me')).data }
export type ApiCustomer = { id: number; name?: string | null; email?: string | null; phone?: string | null; status?: string | null; created_at?: string | null; orders_count?: number; total_spent?: number }
export async function listCustomers(params: { search?: string; page?: number; per_page?: number } = {}) { const query = new URLSearchParams(); Object.entries(params).forEach(([key, value]) => { if (value !== undefined && value !== '') query.set(key, String(value)) }); return request<{ data: ApiCustomer[]; meta: { current_page: number; last_page: number; per_page: number; total: number } }>(`/admin/customers?${query.toString()}`) }
export async function getCustomer(id: number) { return (await request<{ data: { customer: ApiCustomer; orders: Array<{ id: number; order_number?: string | null; status: string; total_amount: number; currency?: string | null; created_at?: string | null }> } }>(`/admin/customers/${id}`)).data }
export async function updateProfile(payload: { name: string; email: string }) { return (await request<{ data: { id: number; name?: string; email?: string } }>('/auth/me', { method: 'PATCH', body: JSON.stringify(payload) })).data }
export async function changePassword(payload: { current_password: string; password: string; password_confirmation: string }) { return request<{ data: Record<string, unknown> }>('/auth/password', { method: 'POST', body: JSON.stringify(payload) }) }
export async function logout() { await request('/auth/logout', { method: 'POST' }).finally(clearToken) }
export type ApiOrderPage = { data: ApiOrder[]; current_page: number; last_page: number; per_page: number; total: number }
export async function listOrders(params: Record<string, string | number> = {}) { const query = new URLSearchParams(Object.entries(params).map(([key, value]) => [key, String(value)])); return (await request<{ data: ApiOrder[] | ApiOrderPage }>(`/orders?${query}`)).data }
export async function getOrder(id: number) { return (await request<{ data: ApiOrder }>(`/orders/${id}`)).data }
export async function getOrderTimeline(id: number) { return (await request<{ data: unknown[] }>(`/orders/${id}/timeline`)).data }
export async function confirmOrder(id: number) { return (await request<{ data: ApiOrder }>(`/orders/${id}/confirm`, { method: 'POST' })).data }
export async function recordOrderContact(id: number, contact_result: string, notes?: string) { return (await request<{ data: Record<string, unknown> }>(`/orders/${id}/contact`, { method: 'POST', body: JSON.stringify({ contact_result, notes }) })).data }
export async function setOrderShippingCharge(id: number, shipping_amount: number) { return (await request<{ data: ApiOrder }>(`/orders/${id}/shipping-charge`, { method: 'PATCH', body: JSON.stringify({ shipping_amount }) })).data }
export async function createShipment(orderId: number, payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>(`/orders/${orderId}/shipments`, { method: 'POST', body: JSON.stringify(payload), headers: { 'Idempotency-Key': String(payload.idempotency_key) } })).data }
export async function downloadOrdersCsv() { const response = await fetch(`${API_BASE}/orders/export`, { headers: { Accept: 'text/csv', Authorization: `Bearer ${getToken()}` } }); if (!response.ok) throw new ApiError(response.status, 'تعذر تصدير الطلبات'); return response.blob() }
export async function updateOrderStatus(id: number, status: string) { return (await request<{ data: ApiOrder }>(`/orders/${id}/status`, { method: 'PATCH', body: JSON.stringify({ status }) })).data }
export async function cancelOrder(id: number) { return (await request<{ data: ApiOrder }>(`/orders/${id}/cancel`, { method: 'POST' })).data }
export async function updateShipmentStatus(id: number, status: string, note?: string) { return (await request<{ data: Record<string, unknown> }>(`/shipments/${id}/status`, { method: 'PATCH', body: JSON.stringify({ status, note }) })).data }
export async function listProducts(params: Record<string, string | number> = {}) { const query = new URLSearchParams(Object.entries(params).map(([key, value]) => [key, String(value)])); return (await request<{ data: ApiProduct[] | ApiProductPage }>(`/products?${query}`)).data }
export async function listInventory() { return (await request<{ data: ApiInventory[] }>('/inventory')).data }
export async function createProduct(payload: { name: string; description?: string; type: 'simple' | 'variable'; status: string; price?: number; brand_id?: number; category_id?: number }) { return (await request<{ data: ApiProduct }>('/products', { method: 'POST', body: JSON.stringify(payload) })).data }
export async function importProducts(file: File) { const body = new FormData(); body.append('file', file); return (await request<{ data: Record<string, unknown> }>('/products/import', { method: 'POST', body })).data }
export async function updateProduct(id: number, payload: { name: string; description?: string; type: 'simple' | 'variable'; status: string; price?: number; brand_id?: number; category_id?: number }) { return (await request<{ data: ApiProduct }>(`/products/${id}`, { method: 'PATCH', body: JSON.stringify(payload) })).data }
export async function deleteProduct(id: number) { await request(`/products/${id}`, { method: 'DELETE' }) }
export async function listProductVariants(productId: number) { return (await request<{ data: Array<Record<string, unknown>> }>(`/products/${productId}/variants`)).data }
export async function createProductVariant(productId: number, payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>(`/products/${productId}/variants`, { method: 'POST', body: JSON.stringify(payload) })).data }
export async function updateProductVariant(productId: number, variantId: number, payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>(`/products/${productId}/variants/${variantId}`, { method: 'PATCH', body: JSON.stringify(payload) })).data }
export async function deleteProductVariant(productId: number, variantId: number) { await request(`/products/${productId}/variants/${variantId}`, { method: 'DELETE' }) }
export async function listProductMedia(productId: number, variantId?: number) { return (await request<{ data: Array<Record<string, unknown>> }>(variantId ? `/products/${productId}/variants/${variantId}/media` : `/products/${productId}/media`)).data }
export async function uploadProductMedia(productId: number, file: File, variantId?: number) { const body = new FormData(); body.append('file', file); return (await request<{ data: Record<string, unknown> }>(variantId ? `/products/${productId}/variants/${variantId}/media` : `/products/${productId}/media`, { method: 'POST', body })).data }
export async function deleteProductMedia(productId: number, mediaId: number, variantId?: number) { await request(variantId ? `/products/${productId}/variants/${variantId}/media/${mediaId}` : `/products/${productId}/media/${mediaId}`, { method: 'DELETE' }) }
export async function reorderProductMedia(productId: number, mediaId: number, sort_order: number, variantId?: number) { return (await request<{ data: Record<string, unknown> }>(variantId ? `/products/${productId}/variants/${variantId}/media/${mediaId}/order` : `/products/${productId}/media/${mediaId}/order`, { method: 'PATCH', body: JSON.stringify({ sort_order }) })).data }
export async function adjustInventory(payload: { product_id: number; variant_id?: number; quantity: number; reason: string; note?: string }) { return (await request<{ data: ApiInventory }>('/inventory/adjust', { method: 'POST', body: JSON.stringify(payload) })).data }
export async function reserveInventory(payload: { product_id: number; variant_id?: number; quantity: number; note?: string }) { return (await request<{ data: ApiInventory }>('/inventory/reserve', { method: 'POST', body: JSON.stringify(payload) })).data }
export async function releaseInventory(payload: { product_id: number; variant_id?: number; quantity: number; note?: string }) { return (await request<{ data: ApiInventory }>('/inventory/release', { method: 'POST', body: JSON.stringify(payload) })).data }
export async function operationalDashboard() { return (await request<{ data: Record<string, unknown> }>('/operations/dashboard')).data }
export async function acknowledgeAlert(id: number) { return (await request<{ data: Record<string, unknown> }>(`/operational-alerts/${id}/acknowledge`, { method: 'PATCH' })).data }
export async function resolveAlert(id: number) { return (await request<{ data: Record<string, unknown> }>(`/operational-alerts/${id}/resolve`, { method: 'PATCH' })).data }
export async function listSettings() { return (await request<{ data: Array<Record<string, unknown>> }>('/settings')).data }
export async function updateSetting(payload: { group: string; key: string; value: unknown; type: string; description?: string }) { return (await request<{ data: Record<string, unknown> }>(`/settings/${encodeURIComponent(payload.key)}`, { method: 'PUT', body: JSON.stringify(payload) })).data }
function listPayload(value: Array<Record<string, unknown>> | { data?: Array<Record<string, unknown>> }) { return Array.isArray(value) ? value : Array.isArray(value.data) ? value.data : [] }
export async function listStaff() { return listPayload((await request<{ data: Array<Record<string, unknown>> | { data?: Array<Record<string, unknown>> } }>('/staff')).data) }
export async function createStaff(payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>('/staff', { method: 'POST', body: JSON.stringify(payload) })).data }
export async function updateStaff(id: number, payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>(`/staff/${id}`, { method: 'PATCH', body: JSON.stringify(payload) })).data }
export async function deleteStaff(id: number) { await request(`/staff/${id}`, { method: 'DELETE' }) }
export async function listCoupons() { return listPayload((await request<{ data: Array<Record<string, unknown>> | { data?: Array<Record<string, unknown>> } }>('/coupons')).data) }
export async function createCoupon(payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>('/coupons', { method: 'POST', body: JSON.stringify(payload) })).data }
export async function updateCoupon(id: number, payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>(`/coupons/${id}`, { method: 'PATCH', body: JSON.stringify(payload) })).data }
export async function deleteCoupon(id: number) { await request(`/coupons/${id}`, { method: 'DELETE' }) }
export async function listReturns() { return listPayload((await request<{ data: Array<Record<string, unknown>> | { data?: Array<Record<string, unknown>> } }>('/returns')).data) }
export async function updateReturn(id: number, action: 'approve' | 'receive' | 'inspect' | 'reject', payload: Record<string, unknown> = {}) { return (await request<{ data: Record<string, unknown> }>(`/returns/${id}/${action}`, { method: 'PATCH', body: JSON.stringify(payload) })).data }
export async function listReviews() { return listPayload((await request<{ data: Array<Record<string, unknown>> | { data?: Array<Record<string, unknown>> } }>('/reviews')).data) }
export async function moderateReview(id: number, status: string) { return (await request<{ data: Record<string, unknown> }>(`/reviews/${id}/moderate`, { method: 'PATCH', body: JSON.stringify({ status }) })).data }
export async function listShippingMethods() { return (await request<{ data: Array<Record<string, unknown>> }>('/shipping-methods')).data }
export async function createShippingMethod(payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>('/shipping-methods', { method: 'POST', body: JSON.stringify(payload) })).data }
export async function updateShippingMethod(id: number, payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>(`/shipping-methods/${id}`, { method: 'PATCH', body: JSON.stringify(payload) })).data }
export async function deleteShippingMethod(id: number) { await request(`/shipping-methods/${id}`, { method: 'DELETE' }) }
export async function listLandingPages() { return (await request<{ data: Array<Record<string, unknown>> }>('/admin/landing-pages')).data }
export async function createLandingPage(payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>('/admin/landing-pages', { method: 'POST', body: JSON.stringify(payload) })).data }
export async function updateLandingPage(id: number, payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>(`/admin/landing-pages/${id}`, { method: 'PATCH', body: JSON.stringify(payload) })).data }
export async function deleteLandingPage(id: number) { await request(`/admin/landing-pages/${id}`, { method: 'DELETE' }) }
export async function publishLandingPage(id: number, published = true) { return (await request<{ data: Record<string, unknown> }>(`/admin/landing-pages/${id}/${published ? 'publish' : 'unpublish'}`, { method: 'POST' })).data }
export async function listLandingLeads() { return (await request<{ data: Array<Record<string, unknown>> }>('/admin/landing-leads')).data }
export async function updateLandingLead(id: number, payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>(`/admin/landing-leads/${id}`, { method: 'PATCH', body: JSON.stringify(payload) })).data }
export async function getLandingPageStats(id: number) { return (await request<{ data: Record<string, unknown> }>(`/admin/landing-pages/${id}/stats`)).data }
export async function listSocialTemplates() { return (await request<{ data: Array<Record<string, unknown>> }>('/admin/social/templates')).data }
export async function createSocialTemplate(payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>('/admin/social/templates', { method: 'POST', body: JSON.stringify(payload) })).data }
export async function deleteSocialTemplate(id: number) { await request(`/admin/social/templates/${id}`, { method: 'DELETE' }) }
export async function listTaxRules() { return (await request<{ data: Array<Record<string, unknown>> }>('/tax-rules')).data }
export async function createTaxRule(payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>('/tax-rules', { method: 'POST', body: JSON.stringify(payload) })).data }
export async function updateTaxRule(id: number, payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>(`/tax-rules/${id}`, { method: 'PATCH', body: JSON.stringify(payload) })).data }
export async function deleteTaxRule(id: number) { await request(`/tax-rules/${id}`, { method: 'DELETE' }) }
export async function settlementSummary(params: Record<string, string> = {}) { const query = new URLSearchParams(params).toString(); return (await request<{ data: Record<string, unknown> }>(`/reports/settlements/summary${query ? `?${query}` : ''}`)).data }
export async function settlementProviders(params: Record<string, string> = {}) { const query = new URLSearchParams(params).toString(); return (await request<{ data: Record<string, unknown> }>(`/reports/settlements/providers${query ? `?${query}` : ''}`)).data }
export async function listSettlements(params: Record<string, string> = {}) { const query = new URLSearchParams(params).toString(); return (await request<{ data: Array<Record<string, unknown>> | { data?: Array<Record<string, unknown>> } }>(`/shipping/settlements${query ? `?${query}` : ''}`)).data }
export async function finalizeSettlement(id: number) { return (await request<{ data: Record<string, unknown> }>(`/shipping/settlements/${id}/finalize`, { method: 'PATCH' })).data }
export async function listCategories() { return (await request<{ data: Array<Record<string, unknown>> }>('/categories')).data }
export async function createCategory(payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>('/categories', { method: 'POST', body: JSON.stringify(payload) })).data }
export async function updateCategory(id: number, payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>(`/categories/${id}`, { method: 'PATCH', body: JSON.stringify(payload) })).data }
export async function deleteCategory(id: number) { await request(`/categories/${id}`, { method: 'DELETE' }) }
export async function listBrands() { return (await request<{ data: Array<Record<string, unknown>> }>('/brands')).data }
export async function createBrand(payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>('/brands', { method: 'POST', body: JSON.stringify(payload) })).data }
export async function updateBrand(id: number, payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>(`/brands/${id}`, { method: 'PATCH', body: JSON.stringify(payload) })).data }
export async function deleteBrand(id: number) { await request(`/brands/${id}`, { method: 'DELETE' }) }
export async function listAttributes() { return (await request<{ data: Array<Record<string, unknown>> }>('/attributes')).data }
export async function listAttributeValues(attributeId: number) { return (await request<{ data: Array<Record<string, unknown>> }>(`/attributes/${attributeId}/values`)).data }
export async function createAttribute(name: string) { return (await request<{ data: Record<string, unknown> }>('/attributes', { method: 'POST', body: JSON.stringify({ name }) })).data }
export async function updateAttribute(id: number, name: string) { return (await request<{ data: Record<string, unknown> }>(`/attributes/${id}`, { method: 'PATCH', body: JSON.stringify({ name }) })).data }
export async function createAttributeValue(attributeId: number, payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>(`/attributes/${attributeId}/values`, { method: 'POST', body: JSON.stringify(payload) })).data }
export async function updateAttributeValue(attributeId: number, valueId: number, payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>(`/attributes/${attributeId}/values/${valueId}`, { method: 'PATCH', body: JSON.stringify(payload) })).data }
export async function deleteAttributeValue(attributeId: number, valueId: number) { await request(`/attributes/${attributeId}/values/${valueId}`, { method: 'DELETE' }) }
export async function deleteAttribute(id: number) { await request(`/attributes/${id}`, { method: 'DELETE' }) }
export async function listOrderPayments(orderId: number) { return (await request<{ data: Array<Record<string, unknown>> }>(`/orders/${orderId}/payments`)).data }
export async function confirmPayment(id: number) { return (await request<{ data: Record<string, unknown> }>(`/payments/${id}/confirm`, { method: 'POST' })).data }
export async function refundPayment(id: number) { return (await request<{ data: Record<string, unknown> }>(`/payments/${id}/refund`, { method: 'POST' })).data }
export async function socialSummary() { return (await request<{ data: Record<string, unknown> }>('/admin/social/summary')).data }
export async function listSocialConnections() { return (await request<{ data: Array<Record<string, unknown>> }>('/admin/social/connections')).data }
export async function createSocialConnection(payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>('/admin/social/connections', { method: 'POST', body: JSON.stringify(payload) })).data }
export async function deleteSocialConnection(id: number) { await request(`/admin/social/connections/${id}`, { method: 'DELETE' }) }
export async function listSocialInteractions(params: Record<string, string> = {}) { const query = new URLSearchParams(params).toString(); return (await request<{ data: Array<Record<string, unknown>> }>(`/admin/social/interactions${query ? `?${query}` : ''}`)).data }
export async function replySocialInteraction(id: number, body: string) { return (await request<{ data: Record<string, unknown> }>(`/admin/social/interactions/${id}/reply`, { method: 'POST', body: JSON.stringify({ body }) })).data }
export async function getSocialConversation(id: number) { return (await request<{ data: Record<string, unknown> }>(`/admin/social/conversations/${id}`)).data }
export async function listSocialConversationMessages(id: number) { return (await request<{ data: Array<Record<string, unknown>> }>(`/admin/social/conversations/${id}/messages`)).data }
export async function sendSocialConversationMessage(id: number, body: string) { return (await request<{ data: Record<string, unknown> }>(`/admin/social/conversations/${id}/messages`, { method: 'POST', body: JSON.stringify({ body }) })).data }
export async function pauseSocialConversation(id: number) { return (await request<{ data: Record<string, unknown> }>(`/admin/social/conversations/${id}/pause`, { method: 'POST' })).data }
export async function resumeSocialConversation(id: number) { return (await request<{ data: Record<string, unknown> }>(`/admin/social/conversations/${id}/resume`, { method: 'POST' })).data }
export async function updateSocialConnection(id: number, payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>(`/admin/social/connections/${id}`, { method: 'PATCH', body: JSON.stringify(payload) })).data }
export async function updateSocialTemplate(id: number, payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>(`/admin/social/templates/${id}`, { method: 'PATCH', body: JSON.stringify(payload) })).data }
export async function updateAutomationRule(id: number, payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>(`/admin/social/automation-rules/${id}`, { method: 'PATCH', body: JSON.stringify(payload) })).data }
export async function listAutomationRules() { return (await request<{ data: Array<Record<string, unknown>> }>('/admin/social/automation-rules')).data }
export async function createAutomationRule(payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>('/admin/social/automation-rules', { method: 'POST', body: JSON.stringify(payload) })).data }
export async function deleteAutomationRule(id: number) { await request(`/admin/social/automation-rules/${id}`, { method: 'DELETE' }) }
export async function getAiSettings() { return (await request<{ data: Record<string, unknown> }>('/admin/ai/settings')).data }
export async function updateAiSettings(payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>('/admin/ai/settings', { method: 'PATCH', body: JSON.stringify(payload) })).data }
export async function generateProductDraft(payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>('/admin/ai/products/draft', { method: 'POST', body: JSON.stringify(payload) })).data }
export async function suggestSocialReply(payload: Record<string, unknown>) { return (await request<{ data: Record<string, unknown> }>('/admin/ai/social/reply-suggestion', { method: 'POST', body: JSON.stringify(payload) })).data }
export async function listMonitoringSettings() { return (await request<{ data: Array<Record<string, unknown>> }>('/settings/order-monitoring')).data }
export async function updateMonitoringSetting(payload: { rule_type: string; days: number; is_enabled: boolean }) { return (await request<{ data: Record<string, unknown> }>('/settings/order-monitoring', { method: 'PUT', body: JSON.stringify(payload) })).data }
export async function previewSocialTemplate(id: number, values: Record<string, string> = {}) { return (await request<{ data: Record<string, unknown> }>(`/admin/social/templates/${id}/preview`, { method: 'POST', body: JSON.stringify({ values }) })).data }
export async function listDelayedOrders(params: Record<string, string> = {}) { const query = new URLSearchParams(params).toString(); return (await request<{ data: Array<Record<string, unknown>> | { data?: Array<Record<string, unknown>> } }>(`/orders/delayed${query ? `?${query}` : ''}`)).data }
export async function runDelayedOrderDetection() { return (await request<{ data: Record<string, unknown> }>('/orders/delayed/detect', { method: 'POST' })).data }
export async function listOperationalAlerts(params: Record<string, string> = {}) { const query = new URLSearchParams(params).toString(); const result = (await request<{ data: Array<Record<string, unknown>> | { data?: Array<Record<string, unknown>> } }>(`/operational-alerts${query ? `?${query}` : ''}`)).data; return Array.isArray(result) ? result : result.data ?? [] }
export async function acknowledgeOperationalAlert(id: number) { return (await request<{ data: Record<string, unknown> }>(`/operational-alerts/${id}/acknowledge`, { method: 'PATCH' })).data }
export async function resolveOperationalAlert(id: number) { return (await request<{ data: Record<string, unknown> }>(`/operational-alerts/${id}/resolve`, { method: 'PATCH' })).data }
export async function bulkAcknowledgeOperationalAlerts(ids: number[]) { return (await request<{ data: Record<string, unknown> }>('/operational-alerts/bulk-acknowledge', { method: 'PATCH', body: JSON.stringify({ ids }) })).data }
export async function bulkResolveOperationalAlerts(ids: number[]) { return (await request<{ data: Record<string, unknown> }>('/operational-alerts/bulk-resolve', { method: 'PATCH', body: JSON.stringify({ ids }) })).data }
export async function importSettlement(file: File, providerCode: string, periodFrom?: string, periodTo?: string) { const body = new FormData(); body.append('file', file); body.append('provider_code', providerCode); if (periodFrom) body.append('period_from', periodFrom); if (periodTo) body.append('period_to', periodTo); return (await request<{ data: Record<string, unknown> }>('/shipping/settlements/import', { method: 'POST', body })).data }
export async function getSettlementItems(id: number) { return (await request<{ data: Array<Record<string, unknown>> | { data?: Array<Record<string, unknown>> } }>(`/shipping/settlements/${id}/items`)).data }
export async function exportSettlement(id: number) { const response = await fetch(`${API_BASE}/shipping/settlements/${id}/export`, { headers: { Accept: 'text/csv', Authorization: `Bearer ${getToken()}` } }); if (!response.ok) throw new ApiError(response.status, 'تعذر تصدير التسوية'); return response.blob() }
export function apiBaseUrl() { return API_BASE }
