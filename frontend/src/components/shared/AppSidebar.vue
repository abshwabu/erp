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
  Settings,
  Shield,
  ChevronLeft,
  Menu
} from 'lucide-vue-next'

const uiStore = useUIStore()
const authStore = useAuthStore()
const { hasPermission } = usePermission()

const navigationGroups = [
  {
    title: 'OPERATIONS',
    items: [
      { name: 'Dashboard', to: '/', icon: LayoutDashboard },
      { name: 'Point of Sale', to: '/pos', icon: ShoppingCart, permission: 'pos.sessions.open' },
      { name: 'Inventory', to: '/inventory', icon: Package, permission: 'inventory.products.view' },
      { name: 'Warehouse', to: '/warehouse', icon: Warehouse, permission: 'warehouse.receive' },
      { name: 'Procurement', to: '/procurement', icon: ShoppingBag, permission: 'procurement.purchase_orders.view' },
    ]
  },
  {
    title: 'PEOPLE & CUSTOMERS',
    items: [
      { name: 'HR & Employees', to: '/hr', icon: Users, permission: 'hr.employees.view' },
      { name: 'Payroll', to: '/payroll', icon: Banknote, permission: 'payroll.runs.view' },
      { name: 'CRM', to: '/crm', icon: UserCheck, permission: 'sales.orders.view' },
    ]
  },
  {
    title: 'FINANCE',
    items: [
      { name: 'Sales & Invoicing', to: '/sales', icon: FileText, permission: 'sales.invoices.create' },
      { name: 'Accounting', to: '/accounting', icon: Calculator, permission: 'accounting.journals.view' },
      { name: 'Reporting', to: '/reporting', icon: BarChart2, permission: 'accounting.reports.view' },
    ]
  },
  {
    title: 'SETTINGS',
    items: [
      { name: 'Settings', to: '/settings', icon: Settings, permission: 'core.settings.view' },
      { name: 'Users & Roles', to: '/roles', icon: Shield, permission: 'core.roles.view' },
    ]
  }
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
          <router-link
            v-for="item in group.items"
            :key="item.name"
            :to="item.to"
            class="flex items-center p-2 rounded-md hover:bg-slate-800 transition-colors group relative"
            active-class="bg-primary-600 text-white hover:bg-primary-700"
            v-tooltip="!uiStore.sidebarOpen ? item.name : ''"
          >
            <component :is="item.icon" :size="20" class="shrink-0" />
            <span v-if="uiStore.sidebarOpen" class="ml-3 truncate">{{ item.name }}</span>
            
            <!-- Tooltip for collapsed mode -->
            <div v-if="!uiStore.sidebarOpen" class="absolute left-14 bg-slate-900 text-white px-2 py-1 rounded text-xs invisible group-hover:visible whitespace-nowrap z-50">
              {{ item.name }}
            </div>
          </router-link>
        </div>
      </div>
    </nav>
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
