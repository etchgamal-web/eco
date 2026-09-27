import { request } from './client'
import type { ApiProduct, ApiProductPage } from './types'

export async function listProducts(params: Record<string, string | number> = {}) { const query = new URLSearchParams(Object.entries(params).map(([key, value]) => [key, String(value)])); return (await request<{ data: ApiProduct[] | ApiProductPage }>(`/products?${query}`)).data }

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
