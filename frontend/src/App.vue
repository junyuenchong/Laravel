<script setup lang="ts">
import { storeToRefs } from 'pinia'
import { useUiStore } from './stores/ui'

const ui = useUiStore()
const { notification } = storeToRefs(ui)
</script>

<template>
  <div class="min-h-screen bg-slate-950 text-slate-50">
    <div
      v-if="notification"
      class="fixed top-3 inset-x-0 flex justify-center z-50 px-4 pointer-events-none"
    >
      <div
        class="pointer-events-auto max-w-md w-full rounded-full border px-4 py-2 text-xs sm:text-sm shadow-lg"
        :class="{
          'bg-emerald-500/15 border-emerald-400 text-emerald-100':
            notification.type === 'success',
          'bg-rose-500/15 border-rose-400 text-rose-100': notification.type === 'error',
          'bg-sky-500/15 border-sky-400 text-sky-100': notification.type === 'info',
        }"
      >
        <div class="flex items-center justify-between gap-3">
          <span>{{ notification.message }}</span>
          <button
            type="button"
            class="text-[11px] uppercase tracking-wide opacity-70 hover:opacity-100"
            @click="ui.clear()"
          >
            Close
          </button>
        </div>
      </div>
    </div>

    <router-view />
  </div>
</template>
