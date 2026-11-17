import { useState, type FormEvent } from 'react'
import { useLocation, useNavigate } from 'react-router'
import { ApiError } from '../api/client'
import { useAuth } from '../auth/useAuth'

export function LoginPage() {
  const { login } = useAuth()
  const navigate = useNavigate()
  const location = useLocation()
  const from = (location.state as { from?: string } | null)?.from

  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState<ApiError | null>(null)
  const [busy, setBusy] = useState(false)

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setBusy(true)
    setError(null)
    try {
      const user = await login(email, password)
      navigate(from ?? (user.role === 'admin' ? '/users' : '/products'), { replace: true })
    } catch (caught) {
      setError(caught instanceof ApiError ? caught : null)
    } finally {
      setBusy(false)
    }
  }

  const fieldError = (field: string) => error?.errors[field]?.[0]

  return (
    <section className="login">
      <form className="card form" onSubmit={handleSubmit} noValidate>
        <h1>Log in</h1>

        {error && Object.keys(error.errors).length === 0 && (
          <div className="alert error" role="alert">
            {error.message}
          </div>
        )}

        <label>
          Email
          <input type="email" value={email} onChange={(e) => setEmail(e.target.value)} autoComplete="email" required />
          {fieldError('email') && <span className="field-error">{fieldError('email')}</span>}
        </label>

        <label>
          Password
          <input
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            autoComplete="current-password"
            required
          />
          {fieldError('password') && <span className="field-error">{fieldError('password')}</span>}
        </label>

        <button type="submit" className="button" disabled={busy}>
          {busy ? 'Logging in…' : 'Log in'}
        </button>

        <p className="muted small">
          Local accounts: <code>admin@urban-nest.test</code> / <code>Admin@123456</code> (admin),{' '}
          <code>user@urban-nest.test</code> / <code>User@1234567</code> (user).
        </p>
      </form>
    </section>
  )
}
