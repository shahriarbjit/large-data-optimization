import { Link, useSearchParams } from 'react-router'
import type { CursorPage, User } from '../api/types'
import { useApi } from '../api/useApi'
import { useAuth } from '../auth/useAuth'
import { Pager } from '../components/Pager'
import { ErrorBox, Loading } from '../components/Status'
import { formatDate } from '../lib/format'

const PER_PAGE = 25

export function UsersPage() {
  const { token } = useAuth()
  const [params, setParams] = useSearchParams()
  const cursor = params.get('cursor')

  const path = token ? `/users?per_page=${PER_PAGE}${cursor ? `&cursor=${encodeURIComponent(cursor)}` : ''}` : null
  const { data, error, loading } = useApi<CursorPage<User>>(path)

  if (!token) {
    return (
      <section>
        <h1>Users</h1>
        <div className="alert info">
          Only admins can see users. <Link to="/login" state={{ from: '/users' }}>Log in</Link> as an admin
          (for example <code>admin@urban-nest.test</code>).
        </div>
      </section>
    )
  }

  return (
    <section>
      <h1>Users</h1>

      {error && (
        <ErrorBox error={error}>
          {error.status === 403 && <p>Your account is not an admin. Log in as an admin to see users.</p>}
          {error.status === 401 && <p>Your session has expired. Please log in again.</p>}
        </ErrorBox>
      )}
      {loading && <Loading label="Loading users…" />}

      {data && (
        <>
          <div className="table-wrap">
            <table className="table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Phone</th>
                  <th>Role</th>
                  <th>Joined</th>
                </tr>
              </thead>
              <tbody>
                {data.data.map((user) => (
                  <tr key={user.id}>
                    <td className="muted">{user.id}</td>
                    <td>{user.name}</td>
                    <td>{user.email}</td>
                    <td>{user.phone}</td>
                    <td>
                      <span className={`badge role-${user.role}`}>{user.role}</span>
                    </td>
                    <td className="muted">{formatDate(user.created_at)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <Pager
            prevCursor={data.meta.prev_cursor}
            nextCursor={data.meta.next_cursor}
            onPage={(next) => {
              setParams(next ? { cursor: next } : {})
              window.scrollTo({ top: 0 })
            }}
          />
        </>
      )}
    </section>
  )
}
