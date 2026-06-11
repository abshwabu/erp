<script setup lang="ts">
import { useUIStore } from '@/stores/ui'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import { 
  LayoutDashboard, 
  ShoppingCart, 
  Package, 
  Users, 
  LogOut,
  Menu,
  ChevronLeft
} from 'lucide-vue-next'

const uiStore = useUIStore()
const authStore = useAuthStore()
const router = useRouter()

const navigation = [
  { name: 'Dashboard', to: '/', icon: LayoutDashboard },
  { name: 'POS', to: '/pos', icon: ShoppingCart },
  { name: 'Inventory', to: '/inventory', icon: Package },
  { name: 'HR', to: '/hr', icon: Users },
]

const handleLogout = () => {
  authStore.logout()
  router.push('/login')
}
</script>

<template>
  <div class="flex h-screen bg-slate-50">
    <!-- Sidebar -->
    <aside
      class="bg-sidebar text-white transition-all duration-300 flex flex-col"
      :class="[uiStore.sidebarOpen ? 'w-64' : 'w-20']"
    >
      <div class="p-4 flex items-center justify-between border-b border-slate-800">
        <span v-if="uiStore.sidebarOpen" class="text-xl font-bold">ERP System</span>
        <button @click="uiStore.toggleSidebar()" class="p-1 hover:bg-slate-800 rounded">
          <Menu v-if="!uiStore.sidebarOpen" :size="20" />
          <ChevronLeft v-else :size="20" />
        </button>
      </div>

      <nav class="flex-1 p-4 space-y-2">
        <router-link
          v-for="item in navigation"
          :key="item.name"
          :to="item.to"
          class="flex items-center p-2 rounded-md hover:bg-slate-800 transition-colors"
          active-class="bg-primary-600 hover:bg-primary-700"
        >
          <component :is="item.icon" :size="20" />
          <span v-if="uiStore.sidebarOpen" class="ml-3">{{ item.name }}</span>
        </router-link>
      </nav>

      <div class="p-4 border-t border-slate-800">
        <button
          @click="handleLogout"
          class="flex items-center w-full p-2 text-red-400 hover:bg-slate-800 rounded-md transition-colors"
        >
          <LogOut :size="20" />
          <span v-if="uiStore.sidebarOpen" class="ml-3">Logout</span>
        </button>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col overflow-hidden">
      <!-- Header -->
      <header class="bg-white border-b border-slate-200 h-16 flex items-center px-6 justify-between shrink-0">
        <h1 class="text-xl font-semibold">{{ uiStore.pageTitle }}</h1>
        <div class="flex items-center space-x-4">
          <div class="text-right">
            <p class="text-sm font-medium text-slate-900">{{ authStore.user?.name || 'User' }}</p>
            <p class="text-xs text-slate-500">{{ authStore.user?.email }}</p>
          </div>
          <div class="h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 font-bold">
            {{ authStore.userInitials }}
          </div>
        </div>
      </header>

      <!-- Page Content -->
      <div class="flex-1 overflow-auto p-6">
        <router-view />
      </div>
    </main>
  </div>
</template>
