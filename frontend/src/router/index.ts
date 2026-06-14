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
          component: () => import('@/modules/pos/layout/POSLayout.vue'),
          meta: { title: 'Point of Sale', permission: 'pos.sessions.open' }
        },
        // Inventory Module
        {
          path: 'inventory/products',
          name: 'inventory-products',
          component: () => import('@/modules/inventory/pages/ProductsPage.vue'),
          meta: { title: 'Products', permission: 'inventory.products.view' }
        },
        {
          path: 'inventory/stock',
          name: 'inventory-stock',
          component: () => import('@/modules/inventory/pages/StockPage.vue'),
          meta: { title: 'Stock Levels', permission: 'inventory.products.view' }
        },
        {
          path: 'inventory/movements',
          name: 'inventory-movements',
          component: () => import('@/modules/inventory/pages/StockMovementsPage.vue'),
          meta: { title: 'Stock Movements', permission: 'inventory.products.view' }
        },
        {
          path: 'inventory/low-stock',
          name: 'inventory-low-stock',
          component: () => import('@/modules/inventory/pages/LowStockPage.vue'),
          meta: { title: 'Low Stock Alerts', permission: 'inventory.products.view' }
        },
        // HR Module
        {
          path: 'hr',
          redirect: 'hr/employees',
        },
        {
          path: 'hr/employees',
          name: 'hr-employees',
          component: () => import('@/modules/hr/pages/EmployeesPage.vue'),
          meta: { title: 'Employees', permission: 'hr.employees.view' }
        },
        {
          path: 'hr/employees/:id',
          name: 'hr-employee-profile',
          component: () => import('@/modules/hr/pages/EmployeeProfilePage.vue'),
          meta: { title: 'Employee Profile', permission: 'hr.employees.view' }
        },
        {
          path: 'hr/leave',
          name: 'hr-leave',
          component: () => import('@/modules/hr/pages/LeaveManagementPage.vue'),
          meta: { title: 'Leave Management', permission: 'hr.leave.view' }
        },
        {
          path: 'hr/attendance',
          name: 'hr-attendance',
          component: () => import('@/modules/hr/pages/AttendancePage.vue'),
          meta: { title: 'Attendance', permission: 'hr.attendance.view' }
        },
        {
          path: 'hr/org-chart',
          name: 'hr-org-chart',
          component: () => import('@/modules/hr/pages/OrgChartPage.vue'),
          meta: { title: 'Org Chart', permission: 'hr.employees.view' }
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

router.beforeEach(async (to) => {
  const authStore = useAuthStore()
  const uiStore = useUIStore()

  // Set Page Title
  if (to.meta.title) {
    uiStore.setPageTitle(to.meta.title as string)
  }

  // Load user info if missing but authenticated (e.g., on page refresh)
  if (authStore.isAuthenticated && !authStore.user && to.name !== 'login') {
    try {
      await authStore.checkAuth()
    } catch {
      return { name: 'login' }
    }
  }

  // Auth Guard
  if (to.meta.requiresAuth !== false && !authStore.isAuthenticated) {
    return { name: 'login' }
  }

  // Permission Guard
  if (to.meta.permission && !authStore.hasPermission(to.meta.permission as string)) {
    return { name: 'dashboard' } // Or a dedicated 403 page
  }

  // Redirect if logged in
  if (to.name === 'login' && authStore.isAuthenticated) {
    return { name: 'dashboard' }
  }
})

export default router
