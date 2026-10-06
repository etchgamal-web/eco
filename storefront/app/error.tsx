'use client'

export default function Error({ reset }: { error: Error & { digest?: string }; reset: () => void }) {
  return (
    <main className="route-state-shell">
      <div className="state-card" role="alert">
        <strong>حدث خطأ غير متوقع</strong>
        <p>تعذر تحميل هذه الصفحة. يمكنك المحاولة مرة أخرى.</p>
        <button className="primary-button" type="button" onClick={() => reset()}>إعادة المحاولة</button>
      </div>
    </main>
  )
}
