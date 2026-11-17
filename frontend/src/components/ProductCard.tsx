import { Link } from 'react-router'
import type { Product } from '../api/types'
import { formatMoney, formatNumber } from '../lib/format'
import { ProductImage } from './ProductImage'
import { Rating } from './Rating'

export function ProductCard({ product }: { product: Product }) {
  return (
    <Link to={`/products/${product.id}`} className="card product-card">
      <ProductImage src={product.image} alt={product.name} className="product-card-image" />
      <div className="product-card-body">
        <span className="muted small">
          {product.brand} · {product.category}
        </span>
        <h3 className="product-card-title">{product.name}</h3>
        <Rating value={product.rating} count={product.reviews_count} />
        <div className="product-card-footer">
          <strong className="price">{formatMoney(product.price, product.currency)}</strong>
          <span className={product.stock > 0 ? 'stock in' : 'stock out'}>
            {product.stock > 0 ? `${formatNumber(product.stock)} in stock` : 'Out of stock'}
          </span>
        </div>
      </div>
    </Link>
  )
}
