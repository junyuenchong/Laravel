<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useItemsStore } from '../stores/items'
import { apiErrorMessage, apiFieldErrors } from '../lib/api'
import type { FieldErrors } from '../lib/api'
import { useUiStore } from '../stores/ui'
import ItemsFilterBar from '../components/items/ItemsFilterBar.vue'
import ItemCreateForm from '../components/items/ItemCreateForm.vue'
import ItemsTable from '../components/items/ItemsTable.vue'

const auth = useAuthStore()
const items = useItemsStore()
const router = useRouter()
const ui = useUiStore()

// Client-side search (no API call on each keystroke).
const searchInput = ref<string>('')

const displayedItems = computed(() => {
  const q = searchInput.value.trim().toLowerCase()
  let list = items.items

  if (items.statusFilter === 'yes') list = list.filter((it) => it.is_active === true)
  if (items.statusFilter === 'no') list = list.filter((it) => it.is_active === false)

  if (q) {
    list = list.filter((it) => {
      const name = it.name?.toLowerCase?.() ?? ''
      const sku = it.sku?.toLowerCase?.() ?? ''
      const desc = (it.description ?? '').toLowerCase()
      return name.includes(q) || sku.includes(q) || desc.includes(q)
    })
  }

  // Stable sort: active first, then id (matches backend default order).
  return [...list].sort((a, b) => {
    if (a.is_active !== b.is_active) return a.is_active ? -1 : 1
    return a.id - b.id
  })
})

onMounted(() => {
  void items.fetchFirstPage()
})

// Simple edit hooks kept here; you can later move this to a dedicated modal component if needed
const editingId = ref<number | null>(null)
type EditableItem = ReturnType<typeof useItemsStore>['items'][number]
const editDraft = ref<Partial<EditableItem>>({})
const editErrors = ref<FieldErrors>({})
const editLoading = ref(false)

function startEdit(item: EditableItem) {
  editingId.value = item.id
  editDraft.value = { ...item }
}

function cancelEdit() {
  editingId.value = null
  editDraft.value = {}
  editErrors.value = {}
}

async function saveEdit() {
  if (editingId.value === null) return
  editLoading.value = true
  editErrors.value = {}
  try {
    const payload: Partial<EditableItem> = {
      name: editDraft.value.name?.trim(),
      sku: editDraft.value.sku?.trim(),
      description:
        editDraft.value.description !== undefined
          ? (editDraft.value.description ?? '').toString().trim() || null
          : undefined,
      price_cents:
        editDraft.value.price_cents !== undefined
          ? Number(editDraft.value.price_cents) || 0
          : undefined,
      is_active:
        editDraft.value.is_active !== undefined ? Boolean(editDraft.value.is_active) : undefined,
    }
    await items.updateItem(editingId.value, payload)
    ui.showSuccess('Item updated.')

    cancelEdit()
  } catch (e: unknown) {
    // If backend reports item not found (e.g. deleted in another tab), show a friendly message.
    const status =
      typeof e === 'object' && e && 'response' in e
        ? (e as { response?: { status?: number } }).response?.status
        : undefined
    if (status === 404) {
      ui.showError('Item no longer exists. Reloading list.')
      cancelEdit()
      void items.fetchFirstPage()
      return
    }
    const fe = apiFieldErrors(e)
    if (fe) editErrors.value = fe
    const msg = apiErrorMessage(e, 'Update failed.')
    ui.showError(msg)
  } finally {
    editLoading.value = false
  }
}

async function onLogout() {
  await auth.logout()
  await router.replace({ path: '/login', query: { next: '/items' } })
}

async function handleDelete(id: number) {
  const confirmed = window.confirm(`Delete item #${id}? This cannot be undone.`)
  if (!confirmed) return
  try {
    await items.deleteItem(id)
    ui.showSuccess(`Item #${id} deleted.`)
  } catch (e: unknown) {
    const msg = apiErrorMessage(e, 'Delete failed.')
    ui.showError(msg)
  }
}

async function handleCreate(payload: Omit<EditableItem, 'id' | 'created_at' | 'updated_at'>) {
  try {
    await items.createItem(payload)
    ui.showSuccess('Item created.')

  } catch (e: unknown) {
    const msg = apiErrorMessage(e, 'Create item failed.')
    ui.showError(msg)
  }
}
</script>

<template>
  <div class="min-h-screen bg-slate-950 text-slate-50 flex flex-col">
    <!-- Top bar: brand + user info -->
    <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between px-4 pt-4">
      <div class="flex items-center gap-3">
        <div class="h-3 w-3 rounded-full bg-sky-400 shadow shadow-sky-400/70" />
        <div>
          <div class="text-lg font-semibold">Items</div>
          <div class="text-xs text-slate-400">Cursor pagination + search + CRUD</div>
        </div>
      </div>

      <div class="flex items-center justify-between sm:justify-end gap-4">
        <div class="text-right text-xs text-slate-400">
          <div class="uppercase tracking-wide text-[10px] text-slate-500">Signed in as</div>
          <div class="font-medium text-slate-100">{{ auth.user?.email }}</div>
        </div>
        <button
          type="button"
          class="inline-flex items-center justify-center rounded-full border border-slate-600 px-3 py-1.5 text-xs font-medium text-slate-100 hover:bg-slate-800/80"
          @click="onLogout"
        >
          Logout
        </button>
      </div>
    </header>

    <!-- Main panel -->
    <section
      class="mt-4 mb-6 mx-4 rounded-2xl border border-slate-800 bg-slate-900/60 p-4 space-y-4"
    >
      <!-- Search bar -->
      <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <ItemsFilterBar
          :search="searchInput"
          :loading="items.loading"
          @update:search="(v) => (searchInput = v)"
          @refresh="items.fetchFirstPage()"
        />
        <div class="flex items-center gap-2 text-[11px] sm:text-xs text-slate-300 mt-1 sm:mt-0">
          <span class="uppercase tracking-wide text-slate-500">Status</span>
          <select
            :value="items.statusFilter"
            class="rounded-xl border border-slate-700 bg-slate-900/60 px-3 py-2 text-xs text-slate-50 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400"
            @change="
              items.setStatusFilter(
                ($event.target as HTMLSelectElement).value as 'all' | 'yes' | 'no',
              )
            "
          >
            <option value="all">All</option>
            <option value="yes">Yes</option>
            <option value="no">No</option>
          </select>
        </div>
      </div>

      <!-- Create form -->
      <ItemCreateForm :loading="items.loading" @create="handleCreate" />

      <!-- Error + initial loading states -->
      <p v-if="items.error" class="text-xs text-rose-300">
        {{ items.error }}
      </p>
      <p v-if="items.loading && !items.items.length" class="text-xs text-slate-400">
        Loading items...
      </p>

      <!-- Items table (reuses edit/delete events from page state) -->
      <ItemsTable
        :items="displayedItems"
        :loading="items.loading"
        :next-cursor="items.nextCursor"
        @load-more="items.fetchNextPage()"
        @delete="handleDelete"
        @edit="startEdit"
      />

      <!-- Simple inline edit panel -->
      <div
        v-if="editingId !== null"
        class="mt-4 rounded-2xl border border-slate-700 bg-slate-900/80 p-4 space-y-3"
      >
        <div class="flex items-center justify-between gap-2">
          <h2 class="text-sm font-semibold text-slate-100">Edit item #{{ editingId }}</h2>
          <button
            type="button"
            class="text-[11px] uppercase tracking-wide text-slate-400 hover:text-slate-200"
            @click="cancelEdit"
          >
            Cancel
          </button>
        </div>

        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-5">
          <label class="flex flex-col gap-1 text-xs text-slate-200">
            <span>Name</span>
            <input
              v-model="editDraft.name"
              class="rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-sm text-slate-50"
            />
            <span v-if="editErrors.name" class="text-[11px] text-rose-300">
              {{ editErrors.name[0] }}
            </span>
          </label>

          <label class="flex flex-col gap-1 text-xs text-slate-200">
            <span>SKU</span>
            <input
              v-model="editDraft.sku"
              class="rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-sm text-slate-50"
            />
            <span v-if="editErrors.sku" class="text-[11px] text-rose-300">
              {{ editErrors.sku[0] }}
            </span>
          </label>

          <label class="flex flex-col gap-1 text-xs text-slate-200">
            <span>Price (cents)</span>
            <input
              v-model.number="editDraft.price_cents"
              type="number"
              min="0"
              class="rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-sm text-slate-50"
            />
            <span v-if="editErrors.price_cents" class="text-[11px] text-rose-300">
              {{ editErrors.price_cents[0] }}
            </span>
          </label>

          <label class="flex flex-col gap-1 text-xs text-slate-200">
            <span>Description</span>
            <input
              v-model="editDraft.description"
              class="rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-sm text-slate-50"
            />
            <span v-if="editErrors.description" class="text-[11px] text-rose-300">
              {{ editErrors.description[0] }}
            </span>
          </label>

          <label class="inline-flex items-center gap-2 text-xs sm:text-sm text-slate-200">
            <input v-model="editDraft.is_active" type="checkbox" class="h-4 w-4 rounded border-slate-600" />
            <span>Active</span>
          </label>
        </div>

        <div class="flex justify-end">
          <button
            type="button"
            class="inline-flex items-center justify-center rounded-full bg-emerald-400 px-4 py-2 text-xs font-semibold text-slate-950 disabled:opacity-60 disabled:cursor-not-allowed"
            :disabled="editLoading"
            @click="saveEdit"
          >
            {{ editLoading ? 'Saving...' : 'Save changes' }}
          </button>
        </div>
      </div>
    </section>
  </div>
</template>
