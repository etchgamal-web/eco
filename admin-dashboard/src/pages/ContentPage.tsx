import { useEffect, useMemo, useState } from 'react'
import { BookOpen, Eye, FileText, HelpCircle, LoaderCircle, Newspaper, Search, Send, Trash2, XCircle } from 'lucide-react'
import { PageStats } from '../components/PageStats'
import { ApiError, deleteContent, listContent, publishContent, unpublishContent } from '../lib/api'
import type { ApiContent, ContentStatus, ContentType } from '../lib/api/content'

type Props = { onToast: (message: string, type?: 'success' | 'error') => void; access?: { roles?: string[]; permissions?: string[] } }

type FilterType = 'all' | ContentType
type FilterStatus = 'all' | ContentStatus

const typeLabels: Record<ContentType, string> = { article: 'مقال', guide: 'دليل', faq: 'أسئلة شائعة', comparison: 'مقارنة' }
const typeIcons: Record<ContentType, typeof FileText> = { article: Newspaper, guide: BookOpen, faq: HelpCircle, comparison: FileText }

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

  // Synchronize the screen with the Laravel API whenever server-side filters change.
  // eslint-disable-next-line react-hooks/set-state-in-effect, react-hooks/exhaustive-deps
  useEffect(() => { void load() }, [type, status])

  const visibleItems = useMemo(() => {
    const normalizedQuery = query.trim().toLocaleLowerCase()
    if (!normalizedQuery) return items
    return items.filter((item) => [item.title, item.slug, item.excerpt ?? ''].some((value) => value.toLocaleLowerCase().includes(normalizedQuery)))
  }, [items, query])

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

  return <div className="screen-page"><div className="screen-header"><div><p className="eyebrow">إدارة المحتوى</p><h1>المحتوى</h1><p className="muted">إدارة المقالات والأدلة والأسئلة الشائعة والمقارنات قبل ربطها بالمتجر.</p></div><div className="header-actions"><span className="outline-button"><FileText size={16} /> {canManage ? 'صلاحية إدارة مفعلة' : 'للقراءة فقط'}</span></div></div><PageStats items={[{ label: 'إجمالي المحتوى', value: loading ? '...' : items.length }, { label: 'منشور', value: loading ? '...' : publishedCount }, { label: 'مسودات', value: loading ? '...' : draftCount }, { label: 'أنواع المحتوى', value: 4 }]} /><div className="screen-toolbar"><label className="screen-search"><Search size={17} /><input value={query} onChange={(event) => setQuery(event.target.value)} placeholder="ابحث بالعنوان أو الرابط..." /></label><div className="filter-chips"><button className={`filter-chip ${type === 'all' ? 'selected' : ''}`} onClick={() => setType('all')}>كل الأنواع</button>{(Object.keys(typeLabels) as ContentType[]).map((value) => <button key={value} className={`filter-chip ${type === value ? 'selected' : ''}`} onClick={() => setType(value)}>{typeLabels[value]}</button>)}<button className={`filter-chip ${status === 'all' ? 'selected' : ''}`} onClick={() => setStatus('all')}>كل الحالات</button><button className={`filter-chip ${status === 'published' ? 'selected' : ''}`} onClick={() => setStatus('published')}>منشور</button><button className={`filter-chip ${status === 'draft' ? 'selected' : ''}`} onClick={() => setStatus('draft')}>مسودة</button></div></div><section className="data-card"><div className="table-wrap">{loading ? <div className="skeleton-table">{[1, 2, 3, 4, 5].map((row) => <div className="skeleton-row" key={row}><span /><span /><span /><span /></div>)}</div> : visibleItems.length === 0 ? <div className="empty-state"><FileText size={28} /><b>{items.length === 0 ? 'لا يوجد محتوى بعد' : 'لا توجد نتائج مطابقة'}</b><span>{items.length === 0 ? 'ستظهر المقالات والأدلة هنا بعد إضافتها من شاشة الإنشاء.' : 'جرّب تغيير الفلاتر أو عبارة البحث.'}</span></div> : <table><thead><tr><th>المحتوى</th><th>النوع</th><th>العلاقات</th><th>الحالة</th><th>آخر تحديث</th><th>إجراءات</th></tr></thead><tbody>{visibleItems.map((item) => { const Icon = typeIcons[item.type]; const busy = busyId === item.id; return <tr key={item.id}><td><span className="customer"><span className="customer-avatar"><Icon size={16} /></span><span><b>{item.title}</b><small className="muted">/{item.slug}</small></span></span></td><td>{typeLabels[item.type]}</td><td className="muted">{item.products?.length ?? 0} منتجات · {item.categories?.length ?? 0} تصنيفات</td><td><span className={`status ${item.status === 'published' ? 'status-تم-الشحن' : 'status-مكتمل'}`}><i />{item.status === 'published' ? 'منشور' : 'مسودة'}</span></td><td className="muted">{item.updated_at ? new Date(item.updated_at).toLocaleDateString('ar-SA') : '—'}</td><td><div className="quick-actions">{item.status === 'published' ? <button title="إلغاء النشر" aria-label={`إلغاء نشر ${item.title}`} disabled={!canManage || busy} onClick={() => void updateItem(item, 'unpublish')}><XCircle size={16} /></button> : <button title="نشر" aria-label={`نشر ${item.title}`} disabled={!canManage || busy} onClick={() => void updateItem(item, 'publish')}><Send size={16} /></button>}<button title="معاينة" aria-label={`معاينة ${item.title}`} disabled={busy}><Eye size={16} /></button><button title="حذف" aria-label={`حذف ${item.title}`} className="danger" disabled={!canManage || busy} onClick={() => void updateItem(item, 'delete')}>{busy ? <LoaderCircle className="spin" size={16} /> : <Trash2 size={16} />}</button></div></td></tr> })}</tbody></table>}</div><div className="table-footer"><span className="muted">عرض {visibleItems.length} من {items.length} محتوى</span><span className="muted">تتم الفلترة من API حسب النوع والحالة</span></div></section></div>
}
