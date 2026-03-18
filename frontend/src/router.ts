import { createRouter, createWebHistory } from 'vue-router'
import LoginPage from './views/LoginPage.vue'
import RegisterPage from './views/RegisterPage.vue'
import ItemsPage from './views/ItemsPage.vue'
import { useAuthStore } from './stores/auth'
import { useItemsStore } from './stores/items'

export const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', redirect: '/items' },
    { path: '/login', component: LoginPage },
    { path: '/register', component: RegisterPage },
    { path: '/items', component: ItemsPage },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (!auth.initialized) {
    await auth.bootstrap()
  }

  if (to.path !== '/login' && to.path !== '/register' && !auth.user) {
    return { path: '/login', query: { next: to.fullPath } }
  }

  if ((to.path === '/login' || to.path === '/register') && auth.user) {
    return { path: '/items' }
  }

  // Start fetching items as early as possible (do not block navigation).
  if (to.path === '/items' && auth.user) {
    const items = useItemsStore()
    if (!items.loading && items.items.length === 0) {
      void items.fetchFirstPage()
    }
  }
})
