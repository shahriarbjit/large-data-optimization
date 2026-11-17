import { useEffect, useState } from 'react'
import { useAuth } from '../auth/useAuth'
import { apiFetch, ApiError } from './client'

interface State<T> {
  data: T | null
  error: ApiError | null
  loading: boolean
}

/**
 * GETs `path` (relative to /api/v1) and refetches when it changes.
 * Pass null to skip the request.
 */
export function useApi<T>(path: string | null): State<T> {
  const { token } = useAuth()
  const [state, setState] = useState<State<T> & { key: string | null }>({
    data: null,
    error: null,
    loading: path !== null,
    key: null,
  })

  const key = path === null ? null : `${token ?? ''}|${path}`

  useEffect(() => {
    if (path === null) return

    const controller = new AbortController()

    apiFetch<T>(path, { token, signal: controller.signal })
      .then((data) => setState({ data, error: null, loading: false, key }))
      .catch((error: unknown) => {
        if (controller.signal.aborted) return
        setState({
          data: null,
          error: error instanceof ApiError ? error : new ApiError({
            type: '', title: 'Error', status: 0, detail: String(error), code: 'unknown', request_id: '',
          }),
          loading: false,
          key,
        })
      })

    return () => controller.abort()
  }, [path, token, key])

  // While a new request runs, report loading instead of the previous result.
  if (state.key !== key) return { data: null, error: null, loading: path !== null }

  return { data: state.data, error: state.error, loading: state.loading }
}
