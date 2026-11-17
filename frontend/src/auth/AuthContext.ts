import { createContext } from 'react'
import type { User } from '../api/types'

export interface AuthState {
  token: string | null
  user: User | null
  login: (email: string, password: string) => Promise<User>
  logout: () => Promise<void>
}

export const AuthContext = createContext<AuthState | null>(null)
