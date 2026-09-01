<script setup lang="ts">
import { useUIStore } from '@/stores/ui'
import { useAuthStore } from '@/stores/auth'
import { usePermission } from '@/composables/usePermission'
import { 
  LayoutDashboard, 
  ShoppingCart, 
  Package, 
  Warehouse, 
  ShoppingBag,
  Users,
  Banknote,
  UserCheck,
  FileText,
  Calculator,
  BarChart2,
  Calendar,
  Shield,
  ChevronLeft,
  ChevronDown,
  Menu,
  Box,
  ArrowRightLeft,
  AlertTriangle,
  LogOut,
  Settings,
  Store,
  Factory,
  FolderKanban,
  Landmark,
  Headphones,
  Globe,
  Plug,
  Briefcase,
  Target,
  TrendingUp,
  CheckSquare,
  Sparkles,
  Clock,
  Flag,
  MessageSquare,
  BookOpen,
  Crown,
} from '@lucide/vue'
import { ref, markRaw } from 'vue'
import { useRouter } from 'vue-router'
import UiIcon from '@/components/ui/UiIcon.vue'

const uiStore = useUIStore()
const authStore = useAuthStore()
const router = useRouter()
const { hasPermission } = usePermission()

const expandedItems = ref<Record<string, boolean>>({
  Inventory: true,
  'Human Resources': true,
  CRM: true,
  Projects: true,
  Support: true,
  Accounting: true,
  'Sales & Invoicing': true,
})

const toggleExpand = (name: string) => {
  expandedItems.value[name] = !expandedItems.value[name]
}

const handleLogout = async () => {
  await authStore.logout()
  router.push('/login')
}

// Modules with working routes
const navigationGroups = [
  {
    title: 'OPERATIONS',
    items: [
      { name: 'Dashboard', to: '/', icon: markRaw(LayoutDashboard) },
      { name: 'Point of Sale', to: '/pos', icon: markRaw(ShoppingCart), permission: 'pos.sessions.open' },
      { 
        name: 'Inventory', 
        icon: markRaw(Package), 
        permission: 'inventory.products.view',
        children: [
          { name: 'Products', to: '/inventory/products', icon: markRaw(Package) },
          { name: 'Stock Levels', to: '/inventory/stock', icon: markRaw(Box) },
          { name: 'Movements', to: '/inventory/movements', icon: markRaw(ArrowRightLeft) },
          { name: 'Low Stock', to: '/inventory/low-stock', icon: markRaw(AlertTriangle) },
        ]
      },
      { name: 'Warehouse', to: '/warehouse', icon: markRaw(Warehouse), permission: 'warehouse.receive' },
      { name: 'Shops', to: '/shops', icon: markRaw(Store), permission: 'shops.view' },
      { name: 'Procurement', to: '/procurement', icon: markRaw(ShoppingBag), permission: 'procurement.purchase_orders.view' },
      { name: 'Manufacturing', to: '/manufacturing', icon: markRaw(Factory), permission: 'manufacturing.work_orders.view' },
      { name: 'Documents', to: '/documents', icon: markRaw(FileText), permission: 'documents.view' },
      { name: 'E-Commerce', to: '/ecommerce', icon: markRaw(Globe), permission: 'ecommerce.channels.view' },
    ]
  },
  {
    title: 'PEOPLE & CUSTOMERS',
    items: [
      {
        name: 'Sales & Invoicing',
        to: '/sales/invoices',
        icon: markRaw(FileText),
        permission: 'sales.invoices.create',
      },
      { 
        name: 'Human Resources', 
        icon: markRaw(Users), 
        permission: 'hr.employees.view',
        children: [
          { name: 'Employees', to: '/hr/employees', icon: markRaw(Users) },
          { name: 'Departments', to: '/hr/departments', icon: markRaw(Warehouse) },
          { name: 'Attendance', to: '/hr/attendance', icon: markRaw(UserCheck) },
          { name: 'Leave Management', to: '/hr/leave', icon: markRaw(Calendar) },
          { name: 'Org Chart', to: '/hr/org-chart', icon: markRaw(BarChart2) },
          { name: 'Job Opportunities', to: '/hr/jobs', icon: markRaw(Briefcase) },
        ]
      },
      { 
        name: 'CRM', 
        icon: markRaw(Target), 
        permission: 'crm.contacts.view',
        children: [
          { name: 'Overview', to: '/crm', icon: markRaw(LayoutDashboard) },
          { name: 'Leads & Prospects', to: '/crm/leads', icon: markRaw(Users) },
          { name: 'Deals & Pipeline', to: '/crm/deals', icon: markRaw(TrendingUp) },
          { name: 'Contacts & Accounts', to: '/crm/contacts', icon: markRaw(UserCheck) },
          { name: 'Activities & Tasks', to: '/crm/activities', icon: markRaw(CheckSquare) },
          { name: 'Lead Forms & Sources', to: '/crm/lead-forms', icon: markRaw(Sparkles) },
        ]
      },
      { name: 'Payroll', to: '/payroll', icon: markRaw(Banknote), permission: 'payroll.runs.view' },
      { 
        name: 'Projects', 
        icon: markRaw(FolderKanban), 
        permission: 'projects.view',
        children: [
          { name: 'Portfolio Overview', to: '/projects', icon: markRaw(LayoutDashboard) },
          { name: 'Tasks & Kanban', to: '/projects/tasks', icon: markRaw(CheckSquare) },
          { name: 'Timesheets & Logs', to: '/projects/time-logs', icon: markRaw(Clock) },
          { name: 'Milestones', to: '/projects/milestones', icon: markRaw(Flag) },
        ]
      },
      { 
        name: 'Support', 
        icon: markRaw(Headphones), 
        permission: 'support.tickets.view',
        children: [
          { name: 'Helpdesk Overview', to: '/support', icon: markRaw(LayoutDashboard) },
          { name: 'Ticket Queue', to: '/support/tickets', icon: markRaw(MessageSquare) },
          { name: 'Knowledge Base', to: '/support/knowledge-base', icon: markRaw(BookOpen) },
        ]
      },
    ]
  },
  {
    title: 'FINANCE',
    items: [
      {
        name: 'Accounting',
        icon: markRaw(Calculator),
        permission: 'accounting.journals.view',
        children: [
          { name: 'Chart of Accounts', to: '/accounting/chart-of-accounts', icon: markRaw(Calculator) },
          { name: 'Journals', to: '/accounting/journals', icon: markRaw(FileText) },
          { name: 'Trial Balance', to: '/accounting/trial-balance', icon: markRaw(BarChart2) },
          { name: 'Profit & Loss', to: '/accounting/profit-loss', icon: markRaw(BarChart2) },
          { name: 'Balance Sheet', to: '/accounting/balance-sheet', icon: markRaw(BarChart2) },
          { name: 'AR Aging', to: '/accounting/ar-aging', icon: markRaw(AlertTriangle) },
          { name: 'AP Aging', to: '/accounting/ap-aging', icon: markRaw(AlertTriangle) },
        ]
      },
      { name: 'Reporting', to: '/reporting', icon: markRaw(BarChart2), permission: 'accounting.reports.view' },
      { name: 'Assets', to: '/assets', icon: markRaw(Landmark), permission: 'assets.view' },
    ]
  },
  {
    title: 'SETTINGS',
    items: [
      { name: 'Settings', to: '/settings', icon: markRaw(Settings), permission: 'core.settings.view' },
      { name: 'Users & Roles', to: '/roles', icon: markRaw(Shield), permission: 'core.roles.view' },
      { name: 'Integrations', to: '/integrations', icon: markRaw(Plug), permission: 'integrations.view' },
      { name: 'Super Admin', to: '/super-admin', icon: markRaw(Crown) },
    ]
  },
]

const filteredGroups = navigationGroups.map(group => ({
  ...group,
  items: group.items.filter(item => !item.permission || hasPermission(item.permission))
})).filter(group => group.items.length > 0)
</script>

<template>
  <aside
    class="bg-sidebar text-white transition-all duration-300 flex flex-col fixed inset-y-0 left-0 z-40 lg:static"
    :class="[uiStore.sidebarOpen ? 'w-64 translate-x-0' : 'w-20 lg:w-20 -translate-x-full lg:translate-x-0']"
  >
    <div class="h-16 flex items-center justify-between px-4 border-b border-slate-800 shrink-0">
      <span v-if="uiStore.sidebarOpen" class="text-xl font-bold truncate">ERP System</span>
      <button @click="uiStore.toggleSidebar()" class="p-1 hover:bg-slate-800 rounded transition-colors ml-auto">
        <Menu v-if="!uiStore.sidebarOpen" :size="20" />
        <ChevronLeft v-else :size="20" />
      </button>
    </div>

    <nav class="flex-1 overflow-y-auto overflow-x-hidden p-4 space-y-8 custom-scrollbar">
      <div v-for="group in filteredGroups" :key="group.title">
        <h4 v-if="uiStore.sidebarOpen" class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-4 px-2">
          {{ group.title }}
        </h4>
        <div class="space-y-1">
          <template v-for="item in group.items" :key="item.name">
            <!-- Item with children -->
            <div v-if="item.children" class="space-y-1">
              <button
                @click="uiStore.sidebarOpen && toggleExpand(item.name)"
                class="w-full flex items-center p-2 rounded-md hover:bg-slate-800 transition-colors group relative"
                :class="[!uiStore.sidebarOpen ? 'justify-center' : 'justify-between']"
              >
                <div class="flex items-center">
                  <UiIcon v-if="item.icon" :icon="item.icon" :size="20" class="shrink-0" />
                  <span v-if="uiStore.sidebarOpen" class="ml-3 truncate">{{ item.name }}</span>
                </div>
                <UiIcon
                  v-if="uiStore.sidebarOpen" 
                  :icon="ChevronDown"
                  :size="16" 
                  class="transition-transform duration-200"
                  :class="[expandedItems[item.name] ? 'rotate-180' : '']"
                />
                
                <!-- Tooltip for collapsed mode -->
                <div v-if="!uiStore.sidebarOpen" class="absolute left-14 bg-slate-900 text-white px-2 py-1 rounded text-xs invisible group-hover:visible whitespace-nowrap z-50">
                  {{ item.name }}
                </div>
              </button>
              
              <!-- Sub-items -->
              <div v-if="uiStore.sidebarOpen && expandedItems[item.name]" class="ml-4 pl-4 border-l border-slate-800 space-y-1">
                <router-link
                  v-for="subItem in item.children"
                  :key="subItem.name"
                  :to="subItem.to"
                  class="flex items-center p-2 rounded-md hover:bg-slate-800 transition-colors text-sm text-slate-400 hover:text-white"
                  exact-active-class="bg-primary-600 text-white font-medium hover:bg-primary-700"
                >
                  <UiIcon v-if="subItem.icon" :icon="subItem.icon" :size="16" class="shrink-0" />
                  <span class="ml-3 truncate">{{ subItem.name }}</span>
                </router-link>
              </div>
            </div>

            <!-- Single Item -->
            <router-link
              v-else
              :to="item.to"
              class="flex items-center p-2 rounded-md hover:bg-slate-800 transition-colors group relative"
              exact-active-class="bg-primary-600 text-white hover:bg-primary-700"
            >
              <UiIcon v-if="item.icon" :icon="item.icon" :size="20" class="shrink-0" />
              <span v-if="uiStore.sidebarOpen" class="ml-3 truncate">{{ item.name }}</span>
              
              <!-- Tooltip for collapsed mode -->
              <div v-if="!uiStore.sidebarOpen" class="absolute left-14 bg-slate-900 text-white px-2 py-1 rounded text-xs invisible group-hover:visible whitespace-nowrap z-50">
                {{ item.name }}
              </div>
            </router-link>
          </template>
        </div>
      </div>
    </nav>

    <!-- Logout at bottom -->
    <div class="p-4 border-t border-slate-800">
      <button
        @click="handleLogout"
        class="w-full flex items-center p-2 rounded-md hover:bg-red-900/20 text-slate-400 hover:text-red-400 transition-colors group relative"
        :class="[!uiStore.sidebarOpen ? 'justify-center' : '']"
      >
        <UiIcon :icon="LogOut" :size="20" class="shrink-0" />
        <span v-if="uiStore.sidebarOpen" class="ml-3 truncate font-medium">Logout</span>
        
        <!-- Tooltip for collapsed mode -->
        <div v-if="!uiStore.sidebarOpen" class="absolute left-14 bg-red-900 text-white px-2 py-1 rounded text-xs invisible group-hover:visible whitespace-nowrap z-50">
          Logout
        </div>
      </button>
    </div>
  </aside>

  <!-- Mobile Overlay -->
  <div 
    v-if="uiStore.sidebarOpen" 
    @click="uiStore.toggleSidebar()" 
    class="fixed inset-0 bg-slate-900/50 z-30 lg:hidden"
  ></div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #334155;
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #475569;
}
</style>
