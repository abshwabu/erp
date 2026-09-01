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
      path: '/store/:slug',
      name: 'public-storefront',
      component: () => import('@/views/store/PublicStorefrontView.vue'),
      meta: { title: 'Online Store', requiresAuth: false }
    },
    {
      path: '/careers/:slug',
      name: 'public-career-job',
      component: () => import('@/views/careers/PublicJobApplicationPage.vue'),
      meta: { title: 'Career Opportunity', requiresAuth: false }
    },
    {
      path: '/leads/wizard/:slug',
      name: 'public-lead-wizard',
      component: () => import('@/views/crm/PublicLeadWizardPage.vue'),
      meta: { title: 'Lead Intake Wizard', requiresAuth: false }
    },
    {
      path: '/leads/form/:slug',
      name: 'public-lead-form',
      component: () => import('@/views/crm/PublicLeadClassicPage.vue'),
      meta: { title: 'Lead Inquiry Form', requiresAuth: false }
    },
    {
      path: '/embed/lead-form/:slug',
      name: 'embed-lead-form',
      component: () => import('@/views/crm/PublicLeadClassicPage.vue'),
      meta: { title: 'Embed Lead Form', requiresAuth: false }
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
        {
          path: 'warehouse',
          name: 'warehouse',
          component: () => import('@/modules/warehouse/pages/WarehousePage.vue'),
          meta: { title: 'Warehouse', permission: 'warehouse.receive' }
        },
        {
          path: 'shops',
          name: 'shops',
          component: () => import('@/modules/shops/pages/ShopsPage.vue'),
          meta: { title: 'Shops', permission: 'shops.view' }
        },
        {
          path: 'shops/:id',
          name: 'shop-detail',
          component: () => import('@/modules/shops/pages/ShopDetailPage.vue'),
          meta: { title: 'Shop', permission: 'shops.view' }
        },
        {
          path: 'procurement',
          name: 'procurement',
          component: () => import('@/modules/procurement/pages/PurchaseOrdersPage.vue'),
          meta: { title: 'Procurement', permission: 'procurement.purchase_orders.view' }
        },
        {
          path: 'manufacturing',
          name: 'manufacturing',
          component: () => import('@/modules/manufacturing/pages/ManufacturingPage.vue'),
          meta: { title: 'Manufacturing', permission: 'manufacturing.work_orders.view' }
        },
        {
          path: 'documents',
          name: 'documents',
          component: () => import('@/modules/documents/pages/DocumentsPage.vue'),
          meta: { title: 'Documents', permission: 'documents.view' }
        },
        {
          path: 'ecommerce',
          name: 'ecommerce',
          component: () => import('@/modules/ecommerce/pages/EcommercePage.vue'),
          meta: { title: 'E-Commerce', permission: 'ecommerce.channels.view' }
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
          path: 'hr/departments',
          name: 'hr-departments',
          component: () => import('@/modules/hr/pages/DepartmentsPage.vue'),
          meta: { title: 'Departments', permission: 'hr.employees.view' }
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
        {
          path: 'hr/jobs',
          name: 'hr-jobs',
          component: () => import('@/modules/hr/pages/JobOpportunitiesPage.vue'),
          meta: { title: 'Job Opportunities & Recruitment', permission: 'hr.employees.view' }
        },
        // Sales Module
        {
          path: 'sales',
          redirect: 'sales/invoices',
        },
        {
          path: 'sales/invoices',
          name: 'sales-invoices',
          component: () => import('@/modules/sales/pages/InvoicesPage.vue'),
          meta: { title: 'Sales & Invoicing', permission: 'sales.invoices.create' }
        },
        {
          path: 'payroll',
          name: 'payroll',
          component: () => import('@/modules/payroll/pages/PayrollPage.vue'),
          meta: { title: 'Payroll', permission: 'payroll.runs.view' }
        },
        // CRM Module
        {
          path: 'crm',
          name: 'crm',
          component: () => import('@/modules/crm/pages/CrmDashboardPage.vue'),
          meta: { title: 'CRM Overview', permission: 'crm.contacts.view' }
        },
        {
          path: 'crm/leads',
          name: 'crm-leads',
          component: () => import('@/modules/crm/pages/CrmLeadsPage.vue'),
          meta: { title: 'Leads & Prospects', permission: 'crm.contacts.view' }
        },
        {
          path: 'crm/deals',
          name: 'crm-deals',
          component: () => import('@/modules/crm/pages/CrmDealsPage.vue'),
          meta: { title: 'Deals & Pipeline', permission: 'crm.contacts.view' }
        },
        {
          path: 'crm/contacts',
          name: 'crm-contacts',
          component: () => import('@/modules/crm/pages/CrmContactsPage.vue'),
          meta: { title: 'Contacts & Accounts', permission: 'crm.contacts.view' }
        },
        {
          path: 'crm/activities',
          name: 'crm-activities',
          component: () => import('@/modules/crm/pages/CrmActivitiesPage.vue'),
          meta: { title: 'Activities & Tasks', permission: 'crm.contacts.view' }
        },
        {
          path: 'crm/lead-forms',
          name: 'crm-lead-forms',
          component: () => import('@/modules/crm/pages/CrmLeadFormsPage.vue'),
          meta: { title: 'Lead Forms & Channels', permission: 'crm.contacts.view' }
        },
        // Projects Module
        {
          path: 'projects',
          name: 'projects',
          component: () => import('@/modules/projects/pages/ProjectsOverviewPage.vue'),
          meta: { title: 'Projects Portfolio', permission: 'projects.view' }
        },
        {
          path: 'projects/tasks',
          name: 'projects-tasks',
          component: () => import('@/modules/projects/pages/ProjectTasksPage.vue'),
          meta: { title: 'Task Board & Kanban', permission: 'projects.view' }
        },
        {
          path: 'projects/time-logs',
          name: 'projects-time-logs',
          component: () => import('@/modules/projects/pages/ProjectTimeLogsPage.vue'),
          meta: { title: 'Timesheets & Logs', permission: 'projects.view' }
        },
        {
          path: 'projects/milestones',
          name: 'projects-milestones',
          component: () => import('@/modules/projects/pages/ProjectMilestonesPage.vue'),
          meta: { title: 'Project Milestones', permission: 'projects.view' }
        },
        // Support & Helpdesk Module
        {
          path: 'support',
          name: 'support',
          component: () => import('@/modules/support/pages/SupportDashboardPage.vue'),
          meta: { title: 'Helpdesk Overview', permission: 'support.tickets.view' }
        },
        {
          path: 'support/tickets',
          name: 'support-tickets',
          component: () => import('@/modules/support/pages/SupportTicketsPage.vue'),
          meta: { title: 'Support Tickets Queue', permission: 'support.tickets.view' }
        },
        {
          path: 'support/knowledge-base',
          name: 'support-knowledge-base',
          component: () => import('@/modules/support/pages/SupportKnowledgeBasePage.vue'),
          meta: { title: 'Knowledge Base & FAQs', permission: 'support.tickets.view' }
        },
        {
          path: 'reporting',
          name: 'reporting',
          component: () => import('@/modules/reporting/pages/ReportingPage.vue'),
          meta: { title: 'Reporting', permission: 'accounting.reports.view' }
        },
        {
          path: 'assets',
          name: 'assets',
          component: () => import('@/modules/assets/pages/AssetsPage.vue'),
          meta: { title: 'Assets', permission: 'assets.view' }
        },
        {
          path: 'settings',
          name: 'settings',
          component: () => import('@/modules/core/pages/SettingsPage.vue'),
          meta: { title: 'Settings', permission: 'core.settings.view' }
        },
        {
          path: 'integrations',
          name: 'integrations',
          component: () => import('@/modules/integrations/pages/IntegrationsPage.vue'),
          meta: { title: 'Integrations', permission: 'integrations.view' }
        },
        {
          path: 'notifications',
          name: 'notifications',
          component: () => import('@/modules/notifications/pages/NotificationsPage.vue'),
          meta: { title: 'Notifications' }
        },
        // Accounting Module
        {
          path: 'accounting',
          redirect: 'accounting/chart-of-accounts',
        },
        {
          path: 'accounting/chart-of-accounts',
          name: 'accounting-chart-of-accounts',
          component: () => import('@/modules/accounting/pages/ChartOfAccountsPage.vue'),
          meta: { title: 'Chart of Accounts', permission: 'accounting.journals.view' }
        },
        {
          path: 'accounting/journals',
          name: 'accounting-journals',
          component: () => import('@/modules/accounting/pages/JournalsPage.vue'),
          meta: { title: 'Journals', permission: 'accounting.journals.view' }
        },
        {
          path: 'accounting/trial-balance',
          name: 'accounting-trial-balance',
          component: () => import('@/modules/accounting/pages/TrialBalancePage.vue'),
          meta: { title: 'Trial Balance', permission: 'accounting.reports.view' }
        },
        {
          path: 'accounting/profit-loss',
          name: 'accounting-profit-loss',
          component: () => import('@/modules/accounting/pages/ProfitLossPage.vue'),
          meta: { title: 'Profit & Loss', permission: 'accounting.reports.view' }
        },
        {
          path: 'accounting/balance-sheet',
          name: 'accounting-balance-sheet',
          component: () => import('@/modules/accounting/pages/BalanceSheetPage.vue'),
          meta: { title: 'Balance Sheet', permission: 'accounting.reports.view' }
        },
        {
          path: 'accounting/ar-aging',
          name: 'accounting-ar-aging',
          component: () => import('@/modules/accounting/pages/ARAgingPage.vue'),
          meta: { title: 'AR Aging', permission: 'accounting.reports.view' }
        },
        {
          path: 'accounting/ap-aging',
          name: 'accounting-ap-aging',
          component: () => import('@/modules/accounting/pages/APAgingPage.vue'),
          meta: { title: 'AP Aging', permission: 'accounting.reports.view' }
        },
        {
          path: 'roles',
          name: 'roles',
          component: () => import('@/modules/core/pages/RolesPage.vue'),
          meta: { title: 'Users & Roles', permission: 'core.roles.view' }
        },
        {
          path: 'users',
          name: 'users',
          component: () => import('@/modules/core/pages/RolesPage.vue'),
          meta: { title: 'Team Members & Users', permission: 'core.roles.view' }
        },
        {
          path: 'super-admin',
          name: 'super-admin',
          component: () => import('@/modules/core/pages/SuperAdminTenantsPage.vue'),
          meta: { title: 'Super Admin - Tenant Management' }
        },
        {
          path: 'tenants',
          redirect: 'super-admin',
        },
      ]
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/views/NotFoundView.vue'),
      meta: { title: 'Not Found', requiresAuth: false }
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

  // Load user info if missing or permissions empty but authenticated (e.g., on page refresh or role change)
  if (authStore.isAuthenticated && (!authStore.user || authStore.permissions.length === 0) && to.name !== 'login') {
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
