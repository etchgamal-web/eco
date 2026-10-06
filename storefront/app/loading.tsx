export default function Loading() {
  return (
    <main className="route-state-shell" aria-busy="true" aria-live="polite">
      <div className="state-card loading-state">
        <div className="loading-mark" aria-hidden="true" />
        <strong>جارٍ تحميل المتجر...</strong>
        <p>لحظات ونجهّز لك المحتوى.</p>
      </div>
    </main>
  )
}
