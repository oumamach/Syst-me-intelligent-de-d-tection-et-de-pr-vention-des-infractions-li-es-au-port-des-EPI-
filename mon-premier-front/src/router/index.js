import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import LoginView from '@/views/LoginView.vue'
import DashboardView from '@/views/DashboardView.vue'
import AnomaliesView from '@/views/AnomaliesView.vue'
import AnomalieDetailView from '@/views/AnomalieDetailView.vue'
import OperateursView from '@/views/OperateursView.vue'
import CamerasView from '@/views/CamerasView.vue'
import ManagerView from '@/views/ManagerView.vue'



const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: '/', redirect: '/login' },
    { path: '/login', component: LoginView },
    { path: '/dashboard', component: DashboardView, meta: { requiresAuth: true } },
    { path: '/anomalies', component: AnomaliesView, meta: { requiresAuth: true } },
    { path: '/anomalies/:id', component: AnomalieDetailView, meta: { requiresAuth: true } },
    { path: '/operateurs', component: OperateursView, meta: { requiresAuth: true } },
    { path: '/operateurs', component: OperateursView, meta: { requiresAuth: true } },
    { path: '/cameras', component: CamerasView, meta: { requiresAuth: true } },
    { path: '/manager', component: ManagerView, meta: { requiresAuth: true } },

  ],
})

router.beforeEach((to) => {
  const authStore = useAuthStore()
  if (to.meta.requiresAuth && !authStore.isAuthenticated()) {
    return '/login'
  }
})

export default router