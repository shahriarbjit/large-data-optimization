import { Link, useNavigate, useParams } from 'react-router'
import type { Item, Product } from '../api/types'
import { useApi } from '../api/useApi'
import { ProductImage } from '../components/ProductImage'
import { Rating } from '../components/Rating'
import { ErrorBox, Loading } from '../components/Status'
import { formatDate, formatMoney, formatNumber } from '../lib/format'

export function ProductDetailsPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const valid = id !== undefined && /^\d+$/.test(id)
  const { data, error, loading } = useApi<Item<Product>>(valid ? `/products/${id}` : null)

  const back = (
    <button type="button" className="link back" onClick={() => (window.history.length > 1 ? navigate(-1) : navigate('/products'))}>
      ‹ Back to products
    </button>
  )

  if (!valid) {
    return (
      <section>
        {back}
        <div className="alert error">This is not a valid product id.</div>
      </section>
    )
  }

  if (loading) return <Loading label="Loading product…" />

  if (error) {
    return (
      <section>
        {back}
        <ErrorBox error={error}>
          {error.status === 404 && <p>The product does not exist or is not available.</p>}
        </ErrorBox>
      </section>
    )
  }

  if (!data) return null
  const product = data.data

  return (
    <section>
      {back}
      <article className="details">
        <ProductImage src={product.image} alt={product.name} className="details-image" />

        <div className="details-info">
          <span className="muted">
            <Link to="/products">Products</Link> / {product.category}
          </span>
          <h1>{product.name}</h1>
          <Rating value={product.rating} count={product.reviews_count} />

          <p className="details-price">{formatMoney(product.price, product.currency)}</p>
          <p className={product.stock > 0 ? 'stock in' : 'stock out'}>
            {product.stock > 0 ? `${formatNumber(product.stock)} in stock` : 'Out of stock'}
          </p>

          <p className="details-description">{product.description}</p>

          {product.tags.length > 0 && (
            <ul className="tags" aria-label="Tags">
              {product.tags.map((tag) => (
                <li key={tag} className="tag">
                  {tag}
                </li>
              ))}
            </ul>
          )}

          <dl className="specs">
            <dt>SKU</dt>
            <dd><code>{product.sku}</code></dd>
            <dt>Brand</dt>
            <dd>{product.brand}</dd>
            <dt>Category</dt>
            <dd>{product.category}</dd>
            <dt>Colour</dt>
            <dd>{product.color}</dd>
            <dt>Added</dt>
            <dd>{formatDate(product.created_at)}</dd>
            <dt>Updated</dt>
            <dd>{formatDate(product.updated_at)}</dd>
          </dl>
        </div>
      </article>
    </section>
  )
}
