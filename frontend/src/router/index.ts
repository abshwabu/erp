import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useUIStore } from '@/stores/ui'
import AppLayout from '@/layouts/AppLayout.vue'
import AuthLayout from '@/layouts/AuthLayout.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/auth',
      component: AuthLayout,
      children: [
        {
          path: '/login',
          name: 'login',
          component: () => import('@/views/auth/LoginView.vue'),
          meta: { title: 'Login', requiresAuth: false }
        },
        {
          path: '/register',
          name: 'register',
          component: () => import('@/views/auth/RegisterView.vue'),
          meta: { title: 'Register', requiresAuth: false }
        },
        {
          path: '/forgot-password',
          name: 'forgot-password',
          component: () => import('@/views/auth/ForgotPasswordView.vue'),
          meta: { title: 'Forgot Password', requiresAuth: false }
        },
        {
          path: '/reset-password',
          name: 'reset-password',
          component: () => import('@/views/auth/ResetPasswordView.vue'),
          meta: { title: 'Reset Password', requiresAuth: false }
        }
      ]
    },
    {
      path: '/',
      component: AppLayout,
      meta: { requiresAuth: true },
      children: [
        {
          path: '',
          name: 'dashboard',
          component: () => import('@/views/dashboard/DashboardView.vue'),
          meta: { title: 'Dashboard' }
        },
        {
          path: 'pos',
          name: 'pos',
          component: () => import('@/views/pos/POSView.vue'),
          meta: { title: 'Point of Sale', permission: 'pos.sessions.open' }
        },
        {
          path: 'inventory',
          component: () => import('@/views/inventory/InventoryLayout.vue'),
          meta: { title: 'Inventory', permission: 'inventory.products.view' },
          children: [
            {
              path: '',
              name: 'inventory-list',
              component: () => import('@/views/dashboard/DashboardView.vue'), // Placeholder
            }
          ]
        },
        // Add more module routes here...
      ]
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/views/dashboard/DashboardView.vue') // Placeholder for 404
    }
  ]
})

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()
  const uiStore = useUIStore()

  // Set Page Title
  if (to.meta.title) {
    uiStore.setPageTitle(to.meta.title as string)
  }

  // Auth Guard
  if (to.meta.requiresAuth !== false && !authStore.isAuthenticated) {
    next({ name: 'login' })
    return
  }

  // Permission Guard
  if (to.meta.permission && !authStore.hasPermission(to.meta.permission as string)) {
    next({ name: 'dashboard' }) // Or a dedicated 403 page
    return
  }

  // Redirect if logged in
  if (to.name === 'login' && authStore.isAuthenticated) {
    next({ name: 'dashboard' })
    return
  }

  next()
})

export default router
