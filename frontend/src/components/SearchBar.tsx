import { useState, type FormEvent } from 'react'

interface Props {
  initialQuery: string
  onSearch: (query: string) => void
}

/**
 * UI only. The search API (Elasticsearch) is not built yet, so the parent
 * just keeps the query in the URL and shows a notice.
 */
export function SearchBar({ initialQuery, onSearch }: Props) {
  const [query, setQuery] = useState(initialQuery)

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    onSearch(query.trim())
  }

  return (
    <form className="searchbar" role="search" onSubmit={handleSubmit}>
      <input
        type="search"
        value={query}
        onChange={(event) => setQuery(event.target.value)}
        placeholder="Search products by name, brand, category…"
        aria-label="Search products"
      />
      <button type="submit" className="button">
        Search
      </button>
    </form>
  )
}
