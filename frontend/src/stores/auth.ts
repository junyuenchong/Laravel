import { defineStore } from 'pinia'
import { api, apiErrorMessage, apiFieldErrors, ensureCsrfCookie } from '../lib/api'
import type { FieldErrors } from '../lib/api'
import { useUiStore } from './ui'

export type User = { id: number; name: string; email: string }

export const useAuthStore = defineStore('auth', {
  state: () => ({
    initialized: false as boolean,
    user: null as User | null,
    loading: false as boolean,
    error: null as string | null,
    fieldErrors: {} as FieldErrors,
  }),
  actions: {
    async bootstrap() {
      try {
        const res = await api.get<{ data: User }>('/api/auth/me')
        this.user = res.data.data
      } catch {
        this.user = null
      } finally {
        this.initialized = true
      }
    },
    async login(email: string, password: string) {
      this.loading = true
      this.error = null
      this.fieldErrors = {}
      try {
        const ui = useUiStore()
        await ensureCsrfCookie()
        const res = await api.post<{ data: User }>('/api/auth/login', { email, password })
        this.user = res.data.data
        ui.showSuccess('Logged in.')
        return true
      } catch (e: unknown) {
        const fe = apiFieldErrors(e)
        if (fe) this.fieldErrors = fe
        this.error = apiErrorMessage(e, 'Login failed.')
        const ui = useUiStore()
        ui.showError(this.error)
        return false
      } finally {
        this.loading = false
      }
    },
    async register(name: string, email: string, password: string) {
      this.loading = true
      this.error = null
      this.fieldErrors = {}
      try {
        const ui = useUiStore()
        await ensureCsrfCookie()
        const res = await api.post<{ data: User }>('/api/auth/register', { name, email, password })
        this.user = res.data.data
        ui.showSuccess('Account created and logged in.')
        return true
      } catch (e: unknown) {
        const fe = apiFieldErrors(e)
        if (fe) this.fieldErrors = fe
        this.error = apiErrorMessage(e, 'Register failed.')
        const ui = useUiStore()
        ui.showError(this.error)
        return false
      } finally {
        this.loading = false
      }
    },
    async logout() {
      try {
        const ui = useUiStore()
        await ensureCsrfCookie()
        await api.post('/api/auth/logout')
        ui.showSuccess('Logged out.')
      } catch (e: unknown) {
        if (
          !(
            typeof e === 'object' &&
            e !== null &&
            'response' in e &&
            (e as { response?: { status?: number } }).response?.status === 401
          )
        ) {
          const ui = useUiStore()
          ui.showError('Logout failed.')
          throw e
        }
      } finally {
        this.user = null
      }
    },
  },
})
