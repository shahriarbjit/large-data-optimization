import { useSearchParams } from 'react-router'
import type { CursorPage, Product } from '../api/types'
import { useApi } from '../api/useApi'
import { Pager } from '../components/Pager'
import { ProductCard } from '../components/ProductCard'
import { SearchBar } from '../components/SearchBar'
import { ErrorBox, Loading } from '../components/Status'

const PER_PAGE = 24

export function ProductsPage() {
  const [params, setParams] = useSearchParams()
  const cursor = params.get('cursor')
  const query = params.get('q') ?? ''

  const path = `/products?per_page=${PER_PAGE}${cursor ? `&cursor=${encodeURIComponent(cursor)}` : ''}`
  const { data, error, loading } = useApi<CursorPage<Product>>(path)

  function update(next: { cursor?: string | null; q?: string }) {
    const merged = new URLSearchParams(params)
    for (const [key, value] of Object.entries(next)) {
      if (value) merged.set(key, value)
      else merged.delete(key)
    }
    setParams(merged)
    window.scrollTo({ top: 0 })
  }

  return (
    <section>
      <div className="page-head">
        <h1>Products</h1>
        <SearchBar key={query} initialQuery={query} onSearch={(q) => update({ q, cursor: null })} />
      </div>

      {query && (
        <div className="alert info">
          Search for <strong>“{query}”</strong> is not connected yet (Elasticsearch comes next). Showing all products.{' '}
          <button type="button" className="link" onClick={() => update({ q: '' })}>
            Clear
          </button>
        </div>
      )}

      {error && <ErrorBox error={error} />}
      {loading && <Loading label="Loading products…" />}

      {data && (
        <>
          {data.data.length === 0 ? (
            <p className="muted">No products yet. Seed them with the ProductSeeder (see README).</p>
          ) : (
            <div className="product-grid">
              {data.data.map((product) => (
                <ProductCard key={product.id} product={product} />
              ))}
            </div>
          )}
          <Pager
            prevCursor={data.meta.prev_cursor}
            nextCursor={data.meta.next_cursor}
            onPage={(next) => update({ cursor: next })}
          />
        </>
      )}
    </section>
  )
}
