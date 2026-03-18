import axios from 'axios'

function normalizeApiBaseUrl(input: unknown): string {
  if (typeof input !== 'string') return ''
  let url = input.trim()
  if (!url) return ''

  // Avoid double "/api/api/*" when callers use "/api/..." paths.
  if (url.endsWith('/api')) url = url.slice(0, -4)

  // Trim trailing slash to keep axios URL joining predictable.
  if (url.endsWith('/')) url = url.slice(0, -1)

  return url
}

const baseURL = normalizeApiBaseUrl((import.meta as any).env?.VITE_API_BASE_URL)

export const api = axios.create({
  // Dev: keep empty to use Vite proxy (`/api` -> backend).
  // Prod: set VITE_API_BASE_URL (e.g. https://api.example.com).
  baseURL,
  withCredentials: true,
  xsrfCookieName: 'XSRF-TOKEN',
  // Laravel expects `X-XSRF-TOKEN` to be encrypted. The SPA uses the readable `XSRF-TOKEN` cookie,
  // so we send it via `X-CSRF-TOKEN` instead.
  xsrfHeaderName: 'X-CSRF-TOKEN',
  headers: { Accept: 'application/json' },
})

export async function ensureCsrfCookie() {
  await api.get('/api/csrf-cookie')
}

export function apiErrorMessage(error: unknown, fallback: string) {
  if (axios.isAxiosError(error)) {
    const msg = (error.response?.data as any)?.message
    if (typeof msg === 'string' && msg.trim().length > 0) return msg
  }
  return fallback
}

export type FieldErrors = Record<string, string[]>

export function apiFieldErrors(error: unknown): FieldErrors | null {
  if (!axios.isAxiosError(error)) return null
  const data = error.response?.data as unknown
  if (!data || typeof data !== 'object') return null

  const errors = (data as { errors?: unknown }).errors
  if (!errors || typeof errors !== 'object') return null

  return errors as FieldErrors
}
