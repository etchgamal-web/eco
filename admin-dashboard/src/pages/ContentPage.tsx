import { useEffect, useMemo, useState, type FormEvent } from 'react'
import { BookOpen, Edit3, Eye, FileText, HelpCircle, LoaderCircle, Newspaper, Plus, Search, Send, Trash2, X, XCircle } from 'lucide-react'
import { PageStats } from '../components/PageStats'
import { ApiError, createContent, deleteContent, listCategories, listContent, listProducts, publishContent, unpublishContent, updateContent } from '../lib/api'
import type { ApiContent, ContentPayload, ContentStatus, ContentType } from '../lib/api/content'
import type { ApiProduct, ApiProductPage } from '../lib/api/types'

type Props = { onToast: (message: string, type?: 'success' | 'error') => void; access?: { roles?: string[]; permissions?: string[] } }
type FilterType = 'all' | ContentType
type FilterStatus = 'all' | ContentStatus
type ContentForm = { type: ContentType; title: string; slug: string; excerpt: string; body: string; status: ContentStatus; seo_title: string; seo_description: string; canonical_url: string; featured_image: string; product_ids: string[]; category_ids: string[] }

type SelectOption = { id: number; name?: string | null }
const typeLabels: Record<ContentType, string> = { article: 'مقال', guide: 'دليل', faq: 'أسئلة شائعة', comparison: 'مقارنة' }
const typeIcons: Record<ContentType, typeof FileText> = { article: Newspaper, guide: BookOpen, faq: HelpCircle, comparison: FileText }
const emptyForm: ContentForm = { type: 'article', title: '', slug: '', excerpt: '', body: '', status: 'draft', seo_title: '', seo_description: '', canonical_url: '', featured_image: '', product_ids: [], category_ids: [] }

function toForm(item: ApiContent): ContentForm {
  return { type: item.type, title: item.title, slug: item.slug, excerpt: item.excerpt ?? '', body: item.body ?? '', status: item.status, seo_title: item.seo_title ?? '', seo_description: item.seo_description ?? '', canonical_url: item.canonical_url ?? '', featured_image: item.featured_image ?? '', product_ids: (item.products ?? []).map((product) => String(product.id)), category_ids: (item.categories ?? []).map((category) => String(category.id)) }
}

export function ContentPage({ onToast, access }: Props) {
  const isAdmin = (access?.roles ?? []).some((role) => ['owner', 'admin'].includes(role))
  const canView = isAdmin || (access?.permissions ?? []).includes('cms.view')
  const canManage = isAdmin || (access?.permissions ?? []).includes('cms.manage')
  const [items, setItems] = useState<ApiContent[]>([])
  const [query, setQuery] = useState('')
  const [type, setType] = useState<FilterType>('all')
  const [status, setStatus] = useState<FilterStatus>('all')
  const [loading, setLoading] = useState(true)
  const [busyId, setBusyId] = useState<number | null>(null)
  const [formOpen, setFormOpen] = useState(false)
  const [editing, setEditing] = useState<ApiContent | null>(null)
  const [form, setForm] = useState<ContentForm>(emptyForm)
  const [saving, setSaving] = useState(false)
  const [products, setProducts] = useState<SelectOption[]>([])
  const [categories, setCategories] = useState<SelectOption[]>([])

  const load = async () => {
    setLoading(true)
    try {
      const data = await listContent({ type: type === 'all' ? undefined : type, status: status === 'all' ? undefined : status })
      setItems(data)
    } catch (error) {
      onToast(error instanceof ApiError ? error.message : 'تعذر تحميل المحتوى', 'error')
    } finally {
      setLoading(false)
    }
  }

  // Synchronize the screen with Laravel whenever server-side filters change.
  // eslint-disable-next-line react-hooks/set-state-in-effect, react-hooks/exhaustive-deps
  useEffect(() => { void load() }, [type, status])

  const visibleItems = useMemo(() => {
    const normalizedQuery = query.trim().toLocaleLowerCase()
    if (!normalizedQuery) return items
    return items.filter((item) => [item.title, item.slug, item.excerpt ?? ''].some((value) => value.toLocaleLowerCase().includes(normalizedQuery)))
  }, [items, query])

  const openCreate = async () => {
    setEditing(null)
    setForm(emptyForm)
    setFormOpen(true)
    await loadRelationOptions()
  }

  const openEdit = async (item: ApiContent) => {
    setEditing(item)
    setForm(toForm(item))
    setFormOpen(true)
    await loadRelationOptions()
  }

  const loadRelationOptions = async () => {
    try {
      const [categoryRows, productData] = await Promise.all([listCategories(), listProducts({ per_page: 100 })])
      const productRows = Array.isArray(productData) ? productData : (productData as ApiProductPage).data ?? []
      setCategories(categoryRows.map((category) => ({ id: Number(category.id), name: String(category.name ?? category.id) })))
      setProducts((productRows as ApiProduct[]).map((product) => ({ id: product.id, name: product.name })))
    } catch (error) {
      onToast(error instanceof ApiError ? error.message : 'تعذر تحميل المنتجات والتصنيفات', 'error')
    }
  }

  const save = async (event: FormEvent) => {
    event.preventDefault()
    setSaving(true)
    const payload: ContentPayload = { type: form.type, title: form.title.trim(), slug: form.slug.trim(), excerpt: form.excerpt.trim() || null, body: form.body || null, status: form.status, seo_title: form.seo_title.trim() || null, seo_description: form.seo_description.trim() || null, canonical_url: form.canonical_url.trim() || null, featured_image: form.featured_image.trim() || null, product_ids: form.product_ids.map(Number), category_ids: form.category_ids.map(Number) }
    try {
      const saved = editing ? await updateContent(editing.id, payload) : await createContent(payload)
      setItems((current) => editing ? current.map((item) => item.id === saved.id ? saved : item) : [saved, ...current])
      setFormOpen(false)
      setEditing(null)
      onToast(editing ? 'تم تحديث المحتوى' : 'تم إنشاء المحتوى كمسودة')
    } catch (error) {
      onToast(error instanceof ApiError ? error.message : 'تعذر حفظ المحتوى', 'error')
    } finally {
      setSaving(false)
    }
  }

  const updateItem = async (item: ApiContent, action: 'publish' | 'unpublish' | 'delete') => {
    if (action === 'delete' && !window.confirm(`هل تريد حذف «${item.title}»؟ لا يمكن التراجع عن هذا الإجراء.`)) return
    setBusyId(item.id)
    try {
      if (action === 'publish') {
        const updated = await publishContent(item.id)
        setItems((current) => current.map((entry) => entry.id === item.id ? updated : entry))
        onToast('تم نشر المحتوى')
      } else if (action === 'unpublish') {
        const updated = await unpublishContent(item.id)
        setItems((current) => current.map((entry) => entry.id === item.id ? updated : entry))
        onToast('تم إلغاء نشر المحتوى')
      } else {
        await deleteContent(item.id)
        setItems((current) => current.filter((entry) => entry.id !== item.id))
        onToast('تم حذف المحتوى')
      }
    } catch (error) {
      onToast(error instanceof ApiError ? error.message : 'تعذر تنفيذ الإجراء', 'error')
    } finally {
      setBusyId(null)
    }
  }

  if (!canView) return <div className="screen-page"><div className="empty-state"><XCircle size={30} /><b>لا تملك صلاحية عرض المحتوى</b><span>اطلب صلاحية cms.view من مسؤول النظام.</span></div></div>

  const publishedCount = items.filter((item) => item.status === 'published').length
  const draftCount = items.filter((item) => item.status === 'draft').length

  return <div className="screen-page"><div className="screen-header"><div><p className="eyebrow">إدارة المحتوى</p><h1>المحتوى</h1><p className="muted">إدارة المقالات والأدلة والأسئلة الشائعة والمقارنات قبل ربطها بالمتجر.</p></div><div className="header-actions">{canManage && <button className="primary-button" onClick={() => void openCreate()}><Plus size={17} /> إضافة محتوى</button>}<span className="outline-button"><FileText size={16} /> {canManage ? 'صلاحية إدارة مفعلة' : 'للقراءة فقط'}</span></div></div><PageStats items={[{ label: 'إجمالي المحتوى', value: loading ? '...' : items.length }, { label: 'منشور', value: loading ? '...' : publishedCount }, { label: 'مسودات', value: loading ? '...' : draftCount }, { label: 'أنواع المحتوى', value: 4 }]} /><div className="screen-toolbar"><label className="screen-search"><Search size={17} /><input value={query} onChange={(event) => setQuery(event.target.value)} placeholder="ابحث بالعنوان أو الرابط..." /></label><div className="filter-chips"><button className={`filter-chip ${type === 'all' ? 'selected' : ''}`} onClick={() => setType('all')}>كل الأنواع</button>{(Object.keys(typeLabels) as ContentType[]).map((value) => <button key={value} className={`filter-chip ${type === value ? 'selected' : ''}`} onClick={() => setType(value)}>{typeLabels[value]}</button>)}<button className={`filter-chip ${status === 'all' ? 'selected' : ''}`} onClick={() => setStatus('all')}>كل الحالات</button><button className={`filter-chip ${status === 'published' ? 'selected' : ''}`} onClick={() => setStatus('published')}>منشور</button><button className={`filter-chip ${status === 'draft' ? 'selected' : ''}`} onClick={() => setStatus('draft')}>مسودة</button></div></div><section className="data-card"><div className="table-wrap">{loading ? <div className="skeleton-table">{[1, 2, 3, 4, 5].map((row) => <div className="skeleton-row" key={row}><span /><span /><span /><span /></div>)}</div> : visibleItems.length === 0 ? <div className="empty-state"><FileText size={28} /><b>{items.length === 0 ? 'لا يوجد محتوى بعد' : 'لا توجد نتائج مطابقة'}</b><span>{items.length === 0 ? 'أنشئ أول مقال أو دليل أو سؤال شائع من زر إضافة محتوى.' : 'جرّب تغيير الفلاتر أو عبارة البحث.'}</span>{canManage && items.length === 0 && <button className="primary-button" onClick={() => void openCreate()}><Plus size={16} /> إضافة محتوى</button>}</div> : <table><thead><tr><th>المحتوى</th><th>النوع</th><th>العلاقات</th><th>الحالة</th><th>آخر تحديث</th><th>إجراءات</th></tr></thead><tbody>{visibleItems.map((item) => { const Icon = typeIcons[item.type]; const busy = busyId === item.id; return <tr key={item.id}><td><span className="customer"><span className="customer-avatar"><Icon size={16} /></span><span><b>{item.title}</b><small className="muted">/{item.slug}</small></span></span></td><td>{typeLabels[item.type]}</td><td className="muted">{item.products?.length ?? 0} منتجات · {item.categories?.length ?? 0} تصنيفات</td><td><span className={`status ${item.status === 'published' ? 'status-تم-الشحن' : 'status-مكتمل'}`}><i />{item.status === 'published' ? 'منشور' : 'مسودة'}</span></td><td className="muted">{item.updated_at ? new Date(item.updated_at).toLocaleDateString('ar-SA') : '—'}</td><td><div className="quick-actions">{canManage && <button title="تعديل" aria-label={`تعديل ${item.title}`} disabled={busy} onClick={() => void openEdit(item)}><Edit3 size={16} /></button>}{item.status === 'published' ? <button title="إلغاء النشر" aria-label={`إلغاء نشر ${item.title}`} disabled={!canManage || busy} onClick={() => void updateItem(item, 'unpublish')}><XCircle size={16} /></button> : <button title="نشر" aria-label={`نشر ${item.title}`} disabled={!canManage || busy} onClick={() => void updateItem(item, 'publish')}><Send size={16} /></button>}<button title="معاينة" aria-label={`معاينة ${item.title}`} disabled={busy}><Eye size={16} /></button><button title="حذف" aria-label={`حذف ${item.title}`} className="danger" disabled={!canManage || busy} onClick={() => void updateItem(item, 'delete')}>{busy ? <LoaderCircle className="spin" size={16} /> : <Trash2 size={16} />}</button></div></td></tr> })}</tbody></table>}</div><div className="table-footer"><span className="muted">عرض {visibleItems.length} من {items.length} محتوى</span><span className="muted">تتم الفلترة من API حسب النوع والحالة</span></div></section>{formOpen && <ContentFormModal form={form} setForm={setForm} editing={editing} saving={saving} products={products} categories={categories} onClose={() => setFormOpen(false)} onSubmit={save} />}</div>
}

function ContentFormModal({ form, setForm, editing, saving, products, categories, onClose, onSubmit }: { form: ContentForm; setForm: React.Dispatch<React.SetStateAction<ContentForm>>; editing: ApiContent | null; saving: boolean; products: SelectOption[]; categories: SelectOption[]; onClose: () => void; onSubmit: (event: FormEvent) => void }) {
  const set = <K extends keyof ContentForm>(key: K, value: ContentForm[K]) => setForm((current) => ({ ...current, [key]: value }))
  const setMulti = (key: 'product_ids' | 'category_ids', event: React.ChangeEvent<HTMLSelectElement>) => set(key, Array.from(event.target.selectedOptions, (option) => option.value))
  return <div className="modal-backdrop" onClick={onClose}><div className="form-modal content-form-modal" onClick={(event) => event.stopPropagation()}><div className="modal-head"><div><span className="eyebrow">إدارة المحتوى</span><h2>{editing ? 'تعديل المحتوى' : 'إضافة محتوى'}</h2></div><button className="icon-button" type="button" onClick={onClose} aria-label="إغلاق"><X size={18} /></button></div><form onSubmit={onSubmit}><div className="form-row"><label className="form-field"><span>نوع المحتوى *</span><select value={form.type} onChange={(event) => set('type', event.target.value as ContentType)}><option value="article">مقال</option><option value="guide">دليل</option><option value="faq">أسئلة شائعة</option><option value="comparison">مقارنة</option></select></label><label className="form-field"><span>الحالة</span><select value={form.status} onChange={(event) => set('status', event.target.value as ContentStatus)}><option value="draft">مسودة</option><option value="published">منشور</option></select></label></div><label className="form-field"><span>العنوان *</span><input required maxLength={255} value={form.title} onChange={(event) => set('title', event.target.value)} placeholder="عنوان المحتوى" /></label><label className="form-field"><span>الرابط المختصر *</span><input required pattern="[a-z0-9]+(?:-[a-z0-9]+)*" value={form.slug} onChange={(event) => set('slug', event.target.value.toLowerCase())} placeholder="example-guide" /><small className="muted">حروف إنجليزية صغيرة وأرقام وشرطات فقط.</small></label><label className="form-field"><span>المقتطف</span><textarea rows={2} maxLength={2000} value={form.excerpt} onChange={(event) => set('excerpt', event.target.value)} placeholder="ملخص يظهر في بطاقات المحتوى." /></label><label className="form-field"><span>المحتوى</span><textarea rows={7} value={form.body} onChange={(event) => set('body', event.target.value)} placeholder="اكتب محتوى المقال أو الدليل..." /></label><div className="form-row"><label className="form-field"><span>المنتجات المرتبطة</span><select multiple value={form.product_ids} onChange={(event) => setMulti('product_ids', event)}>{products.map((product) => <option key={product.id} value={product.id}>{product.name ?? product.id}</option>)}</select><small className="muted">استخدم Ctrl أو Cmd لاختيار أكثر من منتج.</small></label><label className="form-field"><span>التصنيفات المرتبطة</span><select multiple value={form.category_ids} onChange={(event) => setMulti('category_ids', event)}>{categories.map((category) => <option key={category.id} value={category.id}>{category.name ?? category.id}</option>)}</select><small className="muted">استخدم Ctrl أو Cmd لاختيار أكثر من تصنيف.</small></label></div><div className="form-section-title"><b>تحسين الظهور SEO</b><span className="muted">اختياري</span></div><label className="form-field"><span>عنوان SEO</span><input maxLength={255} value={form.seo_title} onChange={(event) => set('seo_title', event.target.value)} placeholder="عنوان نتيجة البحث" /></label><label className="form-field"><span>وصف SEO</span><textarea rows={3} maxLength={320} value={form.seo_description} onChange={(event) => set('seo_description', event.target.value)} placeholder="وصف مختصر لمحركات البحث" /></label><div className="form-row"><label className="form-field"><span>Canonical URL</span><input type="url" value={form.canonical_url} onChange={(event) => set('canonical_url', event.target.value)} placeholder="https://example.com/..." /></label><label className="form-field"><span>الصورة البارزة URL</span><input type="url" value={form.featured_image} onChange={(event) => set('featured_image', event.target.value)} placeholder="https://..." /></label></div><div className="modal-actions"><button type="button" className="outline-button" onClick={onClose}>إلغاء</button><button className="primary-button" disabled={saving}>{saving ? 'جار الحفظ...' : editing ? 'حفظ التعديلات' : 'إنشاء المحتوى'}</button></div></form></div></div>
}
