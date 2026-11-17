import { useCallback, useMemo, useState, type ReactNode } from 'react'
import { apiFetch } from '../api/client'
import type { AuthToken, Item, User } from '../api/types'
import { AuthContext, type AuthState } from './AuthContext'

const STORAGE_KEY = 'urban-nest.auth'

interface Stored {
  token: string
  user: User
}

function readStored(): Stored | null {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    return raw ? (JSON.parse(raw) as Stored) : null
  } catch {
    return null
  }
}

function writeStored(value: Stored | null): void {
  try {
    if (value) localStorage.setItem(STORAGE_KEY, JSON.stringify(value))
    else localStorage.removeItem(STORAGE_KEY)
  } catch {
    // Storage blocked (private mode): the login lasts until the tab closes.
  }
}

/** Keeps the Sanctum bearer token (and the signed-in user) in localStorage. */
export function AuthProvider({ children }: { children: ReactNode }) {
  const [stored, setStored] = useState<Stored | null>(readStored)

  const login = useCallback(async (email: string, password: string): Promise<User> => {
    const { data } = await apiFetch<Item<AuthToken>>('/auth/login', {
      method: 'POST',
      body: { email, password },
    })
    const next = { token: data.access_token, user: data.user }
    writeStored(next)
    setStored(next)
    return data.user
  }, [])

  const logout = useCallback(async (): Promise<void> => {
    const token = stored?.token
    writeStored(null)
    setStored(null)
    if (token) {
      // Revoke the token on the server; ignore errors (it may have expired).
      await apiFetch('/auth/logout', { method: 'POST', token }).catch(() => undefined)
    }
  }, [stored])

  const value = useMemo<AuthState>(
    () => ({ token: stored?.token ?? null, user: stored?.user ?? null, login, logout }),
    [stored, login, logout],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}
