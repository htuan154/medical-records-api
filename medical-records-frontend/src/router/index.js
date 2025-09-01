// src/router/index.js
import { createRouter, createWebHistory } from 'vue-router'
import store from '@/store'

const HomeView = () => import('@/views/HomeView.vue')
const LoginView = () => import('@/views/LoginView.vue')
const PatientsListView = () => import('@/views/PatientsListView.vue')

const router = createRouter({
  history: createWebHistory(process.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: { guestOnly: true }
    },
    {
      path: '/',
      name: 'home',
      component: HomeView
      // Home là public; nếu muốn yêu cầu đăng nhập, thêm requiresAuth: true
    },
    {
      path: '/patients',
      name: 'patients',
      component: PatientsListView,
      meta: { requiresAuth: true, roles: ['admin', 'staff', 'doctor'] }
    },
    { path: '/:pathMatch(.*)*', redirect: '/' }
  ],
  scrollBehavior () {
    return { top: 0 }
  }
})

let boot = false
router.beforeEach(async (to, from, next) => {
  const isAuthed = store.getters.isAuthenticated

  if (!boot) {
    boot = true
    if (!store.state.user && isAuthed) {
      await store.dispatch('fetchMe').catch(() => {})
    }
  }

  if (to.meta.guestOnly && isAuthed) return next({ name: 'home' })
  if (to.meta.requiresAuth && !isAuthed) {
    return next({ name: 'login', query: { redirect: to.fullPath } })
  }

  if (to.meta.roles?.length) {
    const roles = new Set(store.getters.roles || [])
    const ok = to.meta.roles.some(r => roles.has(r))
    if (!ok) return next({ name: 'home' })
  }

  next()
})

export default router
