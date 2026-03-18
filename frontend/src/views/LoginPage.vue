<script setup lang="ts">
import { ref } from 'vue'
import { useRouter, useRoute, RouterLink } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

// Pre-fill demo credentials for faster manual testing
const email = ref('test@example.com')
const password = ref('password')

// Handle form submit: call Pinia login, then redirect to next or /items
async function onSubmit() {
  const ok = await auth.login(email.value, password.value)
  if (!ok) return
  const next = typeof route.query.next === 'string' ? route.query.next : '/items'
  await router.replace(next)
}
</script>

<template>
  <!-- Fullscreen centered layout, works well on mobile and desktop -->
  <div class="min-h-screen flex items-center justify-center bg-slate-950 px-4">
    <div
      class="w-full max-w-md rounded-2xl border border-slate-700/60 bg-slate-900/90 shadow-xl shadow-slate-900/40 backdrop-blur-xl p-6 sm:p-7"
    >
      <header class="flex items-start justify-between gap-3 mb-4">
        <div>
          <h1 class="text-xl sm:text-2xl font-semibold text-slate-50">Welcome back</h1>
          <p class="text-xs sm:text-sm text-slate-400 mt-1">
            Sign in to manage your items (JWT cookie + CSRF protected).
          </p>
        </div>
        <RouterLink
          to="/register"
          class="text-xs sm:text-sm text-sky-300 hover:text-sky-200 underline-offset-2 hover:underline"
        >
          Create account
        </RouterLink>
      </header>

      <!-- Simple vertical form, easy to test -->
      <form class="space-y-3" @submit.prevent="onSubmit">
        <label class="flex flex-col gap-1 text-xs sm:text-sm text-slate-200">
          <span>Email</span>
          <input
            v-model="email"
            type="email"
            autocomplete="username"
            required
            class="w-full rounded-full border border-slate-600/70 bg-slate-950/70 px-3.5 py-2.5 text-sm text-slate-50 placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400"
          />
          <span v-if="auth.fieldErrors.email" class="text-[11px] text-rose-300">
            {{ auth.fieldErrors.email[0] }}
          </span>
        </label>

        <label class="flex flex-col gap-1 text-xs sm:text-sm text-slate-200">
          <span>Password</span>
          <input
            v-model="password"
            type="password"
            autocomplete="current-password"
            required
            class="w-full rounded-full border border-slate-600/70 bg-slate-950/70 px-3.5 py-2.5 text-sm text-slate-50 placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400"
          />
          <span v-if="auth.fieldErrors.password" class="text-[11px] text-rose-300">
            {{ auth.fieldErrors.password[0] }}
          </span>
        </label>

        <button
          type="submit"
          :disabled="auth.loading"
          class="mt-1 w-full inline-flex items-center justify-center rounded-full bg-gradient-to-r from-sky-400 to-emerald-400 text-slate-950 text-sm font-semibold py-2.5 disabled:opacity-60 disabled:cursor-not-allowed"
        >
          {{ auth.loading ? 'Signing in...' : 'Sign in' }}
        </button>

        <p v-if="auth.error" class="text-xs text-rose-300 mt-1">
          {{ auth.error }}
        </p>
      </form>
    </div>
  </div>
</template>
