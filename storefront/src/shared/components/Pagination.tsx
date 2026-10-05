type PaginationProps = {
  page: number
  totalPages: number
  onChange: (page: number) => void
}

export default function Pagination({ page, totalPages, onChange }: PaginationProps) {
  if (totalPages <= 1) return null

  return (
    <nav className="pagination" aria-label="صفحات المنتجات">
      <button type="button" disabled={page === 1} onClick={() => onChange(page - 1)}>السابق</button>
      <span>صفحة {page} من {totalPages}</span>
      <button type="button" disabled={page === totalPages} onClick={() => onChange(page + 1)}>التالي</button>
    </nav>
  )
}
