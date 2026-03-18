<script setup lang="ts">
import { reactive } from 'vue'
import type { Item } from '../../stores/items'

type CreatePayload = Omit<Item, 'id' | 'created_at' | 'updated_at'>

const props = defineProps<{
  loading: boolean
}>()

const emit = defineEmits<{
  (e: 'create', payload: CreatePayload): void
}>()

// Local draft for new item
const draft = reactive<CreatePayload>({
  name: '',
  sku: '',
  description: '',
  price_cents: 0,
  is_active: true,
})

// Simple validation: name & sku must be non-empty
const canCreate = () => draft.name.trim() && draft.sku.trim()

function submit() {
  if (!canCreate()) return
  emit('create', {
    name: draft.name.trim(),
    sku: draft.sku.trim(),
    description: (draft.description ?? '').trim() || null,
    price_cents: Number(draft.price_cents) || 0,
    is_active: !!draft.is_active,
  })
  draft.name = ''
  draft.sku = ''
  draft.description = ''
  draft.price_cents = 0
  draft.is_active = true
}
</script>

<template>
  <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-5 w-full">
      <input
        v-model="draft.name"
        placeholder="Name"
        class="rounded-xl border border-slate-700 bg-slate-900/60 px-3 py-2 text-sm text-slate-50"
      />
      <input
        v-model="draft.sku"
        placeholder="SKU (alpha-dash)"
        class="rounded-xl border border-slate-700 bg-slate-900/60 px-3 py-2 text-sm text-slate-50"
      />
      <input
        v-model="draft.description"
        placeholder="Description (optional)"
        class="rounded-xl border border-slate-700 bg-slate-900/60 px-3 py-2 text-sm text-slate-50"
      />
      <input
        v-model.number="draft.price_cents"
        type="number"
        min="0"
        placeholder="Price (cents)"
        class="rounded-xl border border-slate-700 bg-slate-900/60 px-3 py-2 text-sm text-slate-50"
      />
      <label class="inline-flex items-center gap-2 text-xs sm:text-sm text-slate-200">
        <input v-model="draft.is_active" type="checkbox" class="h-4 w-4 rounded border-slate-600" />
        <span>Active</span>
      </label>
    </div>

    <button
      type="button"
      class="inline-flex items-center justify-center rounded-xl bg-emerald-400 px-3.5 py-2.5 text-sm font-semibold text-slate-950 disabled:opacity-60 disabled:cursor-not-allowed"
      :disabled="!canCreate() || props.loading"
      @click="submit"
    >
      Create
    </button>
  </div>
</template>
