interface Props {
  prevCursor: string | null
  nextCursor: string | null
  onPage: (cursor: string | null) => void
  disabled?: boolean
}

/** Previous / Next buttons for cursor-paginated lists. */
export function Pager({ prevCursor, nextCursor, onPage, disabled = false }: Props) {
  return (
    <nav className="pager" aria-label="Pagination">
      <button type="button" className="button ghost" onClick={() => onPage(null)} disabled={disabled || prevCursor === null}>
        « First
      </button>
      <button type="button" className="button ghost" onClick={() => onPage(prevCursor)} disabled={disabled || prevCursor === null}>
        ‹ Previous
      </button>
      <button type="button" className="button ghost" onClick={() => onPage(nextCursor)} disabled={disabled || nextCursor === null}>
        Next ›
      </button>
    </nav>
  )
}
