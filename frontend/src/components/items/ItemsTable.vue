<script setup lang="ts">
import type { Item } from '../../stores/items'

const props = defineProps<{
  items: Item[]
  loading: boolean
  nextCursor: string | null
}>()

const emit = defineEmits<{
  (e: 'edit', item: Item): void
  (e: 'delete', id: number): void
  (e: 'load-more'): void
}>()
</script>

<template>
  <div class="rounded-xl border border-slate-700/70 overflow-hidden bg-slate-900/60">
    <div
      class="grid grid-cols-6 gap-2 px-3 py-2 text-xs font-semibold uppercase tracking-wide bg-slate-800/80 text-slate-300"
    >
      <div class="w-12">ID</div>
      <div>Name</div>
      <div>SKU</div>
      <div class="text-right">Price</div>
      <div class="text-center">Active</div>
      <div class="text-right">Actions</div>
    </div>

    <div
      v-for="it in props.items"
      :key="it.id"
      class="grid grid-cols-6 gap-2 px-3 py-2 border-t border-slate-800 text-sm items-center"
    >
      <div class="text-xs text-slate-400">#{{ it.id }}</div>
      <div class="truncate">{{ it.name }}</div>
      <div class="font-mono text-xs truncate">{{ it.sku }}</div>
      <div class="font-mono text-xs text-right">{{ it.price_cents }}</div>
      <div class="text-center text-xs">
        <span
          :class="[
            'inline-flex items-center rounded-full px-2 py-0.5',
            it.is_active ? 'bg-emerald-500/20 text-emerald-300' : 'bg-slate-700/60 text-slate-300',
          ]"
        >
          {{ it.is_active ? 'Yes' : 'No' }}
        </span>
      </div>
      <div class="flex justify-end gap-1">
        <button
          type="button"
          class="rounded-lg border border-slate-600 px-2 py-1 text-xs text-slate-100 hover:bg-slate-700/60"
          @click="emit('edit', it)"
        >
          Edit
        </button>
        <button
          type="button"
          class="rounded-lg bg-rose-500 px-2 py-1 text-xs text-slate-950 hover:bg-rose-400"
          @click="emit('delete', it.id)"
        >
          Delete
        </button>
      </div>
    </div>
  </div>

  <div class="flex justify-center pt-3">
    <button
      type="button"
      class="inline-flex items-center justify-center rounded-full border border-slate-600 px-4 py-1.5 text-xs font-medium text-slate-100 disabled:opacity-60 disabled:cursor-not-allowed"
      :disabled="props.loading || !props.nextCursor"
      @click="emit('load-more')"
    >
      {{ props.nextCursor ? (props.loading ? 'Loading...' : 'Load more') : 'No more' }}
    </button>
  </div>
</template>
