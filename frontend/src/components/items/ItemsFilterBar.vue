<script setup lang="ts">
const props = defineProps<{
  search: string
  loading: boolean
}>()

const emit = defineEmits<{
  (e: 'update:search', value: string): void
  (e: 'refresh'): void
}>()

// Emit on input change so parent can debounce / call API
function onSearchInput(e: Event) {
  const value = (e.target as HTMLInputElement).value
  emit('update:search', value)
}
</script>

<template>
  <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
    <input
      :value="props.search"
      placeholder="Search by SKU exact or name contains..."
      class="flex-1 rounded-xl border border-slate-700 bg-slate-900/60 px-3.5 py-2.5 text-sm text-slate-50 placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400"
      @input="onSearchInput"
    />
    <button
      type="button"
      class="inline-flex items-center justify-center rounded-xl bg-sky-400 px-3.5 py-2.5 text-sm font-semibold text-slate-950 disabled:opacity-60 disabled:cursor-not-allowed"
      :disabled="props.loading"
      @click="emit('refresh')"
    >
      Refresh
    </button>
  </div>
</template>
