import { defineStore } from 'pinia'
import { api, apiErrorMessage, ensureCsrfCookie } from '../lib/api'
import { useUiStore } from './ui'

export type Item = {
  id: number
  name: string
  sku: string
  description: string | null
  price_cents: number
  is_active: boolean
  created_at: string
  updated_at: string
}

type ItemsResponse = {
  data: Item[]
  next_cursor: string | null
  prev_cursor: string | null
  per_page: number | string
}

export const useItemsStore = defineStore('items', {
  state: () => ({
    items: [] as Item[],
    perPage: 20 as number,
    statusFilter: 'all' as 'all' | 'yes' | 'no',
    nextCursor: null as string | null,
    loading: false as boolean,
    error: null as string | null,
  }),
  actions: {
    buildParams(extra: Record<string, unknown> = {}) {
      const params: Record<string, unknown> = {
        per_page: this.perPage,
        ...extra,
      }
      // Server-side status filter so the first page matches the dropdown selection.
      // Use 1/0 to satisfy Laravel `boolean` validator for query params.
      if (this.statusFilter === 'yes') params.is_active = 1
      if (this.statusFilter === 'no') params.is_active = 0
      return params
    },
    async fetchFirstPage() {
      this.loading = true
      this.error = null
      try {
        const res = await api.get<ItemsResponse>('/api/items', {
          params: this.buildParams(),
        })
        this.items = res.data.data
        this.nextCursor = res.data.next_cursor
      } catch (e: unknown) {
        const msg = apiErrorMessage(e, 'Failed to load items.')
        this.error = msg
        useUiStore().showError(msg)
      } finally {
        this.loading = false
      }
    },
    async fetchNextPage() {
      if (!this.nextCursor) return
      this.loading = true
      this.error = null
      try {
        const res = await api.get<ItemsResponse>('/api/items', {
          params: this.buildParams({ cursor: this.nextCursor }),
        })
        // append: good perf for infinite/cursor pagination
        this.items = this.items.concat(res.data.data)
        this.nextCursor = res.data.next_cursor
      } catch (e: unknown) {
        const msg = apiErrorMessage(e, 'Failed to load more items.')
        this.error = msg
        useUiStore().showError(msg)
      } finally {
        this.loading = false
      }
    },
    async createItem(payload: Omit<Item, 'id' | 'created_at' | 'updated_at'>) {
      await ensureCsrfCookie()
      const res = await api.post<{ data: Item }>('/api/items', payload)
      this.items.unshift(res.data.data)
    },
    async updateItem(id: number, payload: Partial<Omit<Item, 'id' | 'created_at' | 'updated_at'>>) {
      await ensureCsrfCookie()
      const res = await api.put<{ data: Item }>(`/api/items/${id}`, payload)
      const idx = this.items.findIndex((x) => x.id === id)
      if (idx >= 0) this.items[idx] = res.data.data
    },
    async deleteItem(id: number) {
      await ensureCsrfCookie()
      await api.delete(`/api/items/${id}`)
      this.items = this.items.filter((x) => x.id !== id)
    },
    setStatusFilter(value: 'all' | 'yes' | 'no') {
      if (this.statusFilter === value) return
      this.statusFilter = value
      this.items = []
      this.nextCursor = null
      void this.fetchFirstPage()
    },
  },
})
