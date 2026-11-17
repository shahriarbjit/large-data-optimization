import { formatNumber } from '../lib/format'

export function Rating({ value, count }: { value: number; count: number }) {
  const full = Math.round(value)

  return (
    <span className="rating" aria-label={`Rated ${value} out of 5 from ${count} reviews`}>
      <span className="stars" aria-hidden="true">
        {'★'.repeat(full)}
        <span className="stars-empty">{'★'.repeat(5 - full)}</span>
      </span>
      <span className="muted small">
        {value.toFixed(1)} ({formatNumber(count)})
      </span>
    </span>
  )
}
