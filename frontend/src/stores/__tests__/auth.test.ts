import { beforeEach, describe, expect, it, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '../auth'

vi.mock('../../lib/api', () => {
  return {
    api: {
      get: vi.fn(),
      post: vi.fn(),
    },
    ensureCsrfCookie: vi.fn(async () => undefined),
  }
})

describe('auth store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('bootstrap sets user when /me returns data', async () => {
    const { api } = await import('../../lib/api')
    ;(api.get as any).mockResolvedValueOnce({
      data: { data: { id: 1, name: 'U', email: 'u@e.com' } },
    })

    const auth = useAuthStore()
    await auth.bootstrap()

    expect(auth.initialized).toBe(true)
    expect(auth.user?.email).toBe('u@e.com')
  })
})
