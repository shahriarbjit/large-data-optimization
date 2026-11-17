// Response shapes of the Laravel API (see docs/api-conventions.md).

export interface Product {
  id: number
  sku: string
  name: string
  description: string
  brand: string
  category: string
  color: string
  tags: string[]
  /** Decimal string with two decimals, e.g. "1250.00". */
  price: string
  currency: string
  stock: number
  rating: number
  reviews_count: number
  image: string
  is_active: boolean
  created_at: string
  updated_at: string
}

export type Role = 'user' | 'admin' | 'guest'

export interface User {
  id: number
  name: string
  email: string
  phone: string
  role: Role
  created_at: string
}

export interface AuthToken {
  access_token: string
  token_type: 'Bearer'
  expires_at: string | null
  user: User
}

export interface Item<T> {
  data: T
}

export interface CursorPage<T> {
  data: T[]
  meta: {
    per_page: number
    next_cursor: string | null
    prev_cursor: string | null
  }
}

/** The standard error body (application/problem+json). */
export interface Problem {
  type: string
  title: string
  status: number
  detail: string
  code: string
  request_id: string
  errors?: Record<string, string[]>
}
