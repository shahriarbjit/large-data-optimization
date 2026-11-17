import type { Problem } from './types'

/** An error answer from the API, in the standard error format. */
export class ApiError extends Error {
  readonly status: number
  readonly code: string
  readonly errors: Record<string, string[]>

  constructor(problem: Problem) {
    super(problem.detail || problem.title)
    this.name = 'ApiError'
    this.status = problem.status
    this.code = problem.code
    this.errors = problem.errors ?? {}
  }
}

interface RequestOptions {
  method?: 'GET' | 'POST'
  body?: unknown
  token?: string | null
  signal?: AbortSignal
}

/**
 * Calls the API under /api/v1. In development Vite forwards /api to Laravel
 * (vite.config.ts); in Docker nginx does the same.
 */
export async function apiFetch<T>(path: string, options: RequestOptions = {}): Promise<T> {
  const headers: Record<string, string> = { Accept: 'application/json' }
  if (options.body !== undefined) headers['Content-Type'] = 'application/json'
  if (options.token) headers.Authorization = `Bearer ${options.token}`

  let response: Response
  try {
    response = await fetch(`/api/v1${path}`, {
      method: options.method ?? 'GET',
      headers,
      body: options.body === undefined ? undefined : JSON.stringify(options.body),
      signal: options.signal,
    })
  } catch (error) {
    if (error instanceof DOMException && error.name === 'AbortError') throw error
    throw new ApiError({
      type: 'urn:urban-nest:error:network_error',
      title: 'Network error',
      status: 0,
      detail: 'The API is not reachable. Is the Laravel server running?',
      code: 'network_error',
      request_id: '',
    })
  }

  if (response.status === 204) return undefined as T

  const body: unknown = await response.json().catch(() => null)

  if (!response.ok) {
    const problem = body as Partial<Problem> | null
    throw new ApiError({
      type: problem?.type ?? '',
      title: problem?.title ?? response.statusText,
      status: response.status,
      detail: problem?.detail ?? `Request failed with status ${response.status}.`,
      code: problem?.code ?? 'http_error',
      request_id: problem?.request_id ?? response.headers.get('X-Request-Id') ?? '',
      errors: problem?.errors,
    })
  }

  return body as T
}
