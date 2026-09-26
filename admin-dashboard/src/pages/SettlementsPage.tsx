import { useEffect, useState } from 'react'
import { CheckCircle2, FileCheck2, RefreshCw } from 'lucide-react'
import { ApiError, finalizeSettlement, listSettlements } from '../lib/api'

type Props = { onToast: (message: string, type?: 'success' | 'error') => void }
type Row = Record<string, unknown>
const text = (v: unknown, f = '—') => v === null || v === undefined || v === '' ? f : String(v)
export function SettlementsPage({ onToast }: Props) {
  const [rows, setRows] = useState<Row[]>([])
  const [status, setStatus] = useState('')
  const [loading, setLoading] = useState(true)
  const load = async () => { setLoading(true); try { const result = await listSettlements(status ? { status } : {}); setRows(Array.isArray(result) ? result : Array.isArray(result.data) ? result.data : []) } catch (error) { onToast(error instanceof ApiError ? error.message : 'تعذر تحميل التسويات', 'error') } finally { setLoading(false) } }
  // Synchronize settlement records with the external Laravel API.
  // eslint-disable-next-line react-hooks/set-state-in-effect, react-hooks/exhaustive-deps
  useEffect(() => { void load() }, [status])
  const finalize = async (row: Row) => { try { await finalizeSettlement(Number(row.id)); onToast('تم إنهاء التسوية'); load() } catch (error) { onToast(error instanceof ApiError ? error.message : 'تعذر إنهاء التسوية', 'error') } }
  return <div className="screen-page"><div className="screen-header"><div><p className="eyebrow">الشحن والمالية</p><h1>سجلات التسويات</h1><p className="muted">راجع التسويات القادمة من شركات الشحن وأنهِ السجلات المكتملة.</p></div><button className="outline-button" onClick={() => void load()}><RefreshCw size={15} /> تحديث</button></div><div className="filter-bar"><select value={status} onChange={(event) => setStatus(event.target.value)}><option value="">كل الحالات</option><option value="draft">مسودة</option><option value="processing">قيد المعالجة</option><option value="completed">مكتملة</option><option value="finalized">منتهية</option></select></div><section className="data-card"><div className="table-wrap">{loading ? <div className="settings-loading">جار التحميل...</div> : rows.length === 0 ? <div className="empty-state"><FileCheck2 size={28} /><b>لا توجد سجلات تسوية</b><span>ستظهر السجلات بعد استيراد ملفات شركات الشحن.</span></div> : <table><thead><tr><th>المزود</th><th>الفترة</th><th>عدد العناصر</th><th>الحالة</th><th>الإجمالي</th><th>إجراء</th></tr></thead><tbody>{rows.map((row) => <tr key={String(row.id)}><td><b>{text(row.provider_name, text(row.provider_code))}</b></td><td>{text(row.period_from)} — {text(row.period_to)}</td><td>{text(row.items_count, text(row.item_count, '0'))}</td><td>{text(row.status)}</td><td>{text(row.total_amount, text(row.actual_total, '0'))} {text(row.currency, 'SAR')}</td><td>{text(row.status) === 'finalized' ? <span className="success-label"><CheckCircle2 size={14} /> منتهية</span> : <button className="outline-button small-button" onClick={() => void finalize(row)}><CheckCircle2 size={13} /> إنهاء</button>}</td></tr>)}</tbody></table>}</div></section></div>
}
