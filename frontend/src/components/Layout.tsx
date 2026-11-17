import { Link, NavLink, Outlet, useNavigate } from 'react-router'
import { useAuth } from '../auth/useAuth'

export function Layout() {
  const { user, logout } = useAuth()
  const navigate = useNavigate()

  async function handleLogout() {
    await logout()
    navigate('/products')
  }

  return (
    <div className="app">
      <header className="topbar">
        <div className="topbar-inner">
          <Link to="/products" className="brand">
            Urban Nest
          </Link>
          <nav className="nav">
            <NavLink to="/products">Products</NavLink>
            <NavLink to="/users">Users</NavLink>
          </nav>
          <div className="account">
            {user ? (
              <>
                <span className="muted">
                  {user.name} <span className={`badge role-${user.role}`}>{user.role}</span>
                </span>
                <button type="button" className="button ghost" onClick={handleLogout}>
                  Log out
                </button>
              </>
            ) : (
              <NavLink to="/login" className="button ghost">
                Log in
              </NavLink>
            )}
          </div>
        </div>
      </header>
      <main className="container">
        <Outlet />
      </main>
    </div>
  )
}
