import type { ReactNode } from 'react'
import type { ApiError } from '../api/client'

export function Loading({ label = 'Loading…' }: { label?: string }) {
  return (
    <div className="status" role="status">
      <span className="spinner" aria-hidden="true" /> {label}
    </div>
  )
}

export function ErrorBox({ error, children }: { error: ApiError; children?: ReactNode }) {
  return (
    <div className="alert error" role="alert">
      <strong>{error.status ? `${error.status} · ` : ''}{error.message}</strong>
      {children}
    </div>
  )
}
