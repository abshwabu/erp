<script setup lang="ts">
import { useUIStore } from '@/stores/ui'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notification'
import { useRouter } from 'vue-router'
import { 
  Bell, 
  Search, 
  Menu,
  User,
  Settings,
  LogOut,
  ChevronRight
} from 'lucide-vue-next'
import UiDropdown from '@/components/ui/UiDropdown.vue'

const uiStore = useUIStore()
const authStore = useAuthStore()
const notificationStore = useNotificationStore()
const router = useRouter()

const handleLogout = () => {
  authStore.logout()
  router.push('/login')
}

const userMenuItems = [
  { label: 'Profile', icon: User, to: '/profile' },
  { label: 'Settings', icon: Settings, to: '/settings' },
  { label: 'Logout', icon: LogOut, action: handleLogout, variant: 'danger' as const },
]
</script>

<template>
  <header class="bg-white border-b border-slate-200 h-16 flex items-center px-4 lg:px-6 justify-between shrink-0 sticky top-0 z-30">
    <div class="flex items-center">
      <button @click="uiStore.toggleSidebar()" class="p-2 hover:bg-slate-100 rounded-md lg:hidden mr-2">
        <Menu :size="20" class="text-slate-600" />
      </button>
      
      <div class="hidden lg:flex items-center space-x-2 text-sm text-slate-500 mr-4">
        <span v-for="(crumb, index) in uiStore.breadcrumbs" :key="index" class="flex items-center">
          <ChevronRight v-if="index > 0" :size="14" class="mx-2" />
          <router-link v-if="crumb.to" :to="crumb.to" class="hover:text-primary-600 transition-colors">
            {{ crumb.label }}
          </router-link>
          <span v-else>{{ crumb.label }}</span>
        </span>
      </div>
      
      <h1 class="text-lg font-semibold text-slate-900 truncate">{{ uiStore.pageTitle }}</h1>
    </div>

    <div class="flex items-center space-x-2 lg:space-x-4">
      <!-- Search (Desktop) -->
      <div class="hidden md:flex relative">
        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <Search :size="18" class="text-slate-400" />
        </span>
        <input
          type="text"
          placeholder="Search..."
          class="block w-full pl-10 pr-3 py-2 border border-slate-200 rounded-md leading-5 bg-slate-50 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-1 focus:ring-primary-500 focus:border-primary-500 sm:text-sm transition-all"
        />
      </div>

      <!-- Notifications -->
      <UiDropdown align="right">
        <template #trigger>
          <button class="p-2 hover:bg-slate-100 rounded-full relative transition-colors">
            <Bell :size="20" class="text-slate-600" />
            <span 
              v-if="notificationStore.notifications.length > 0"
              class="absolute top-1.5 right-1.5 block h-2.5 w-2.5 rounded-full bg-red-500 ring-2 ring-white"
            ></span>
          </button>
        </template>
        <div class="w-80 max-h-96 overflow-y-auto">
          <div class="px-4 py-2 font-semibold text-sm border-b border-slate-100 flex justify-between items-center">
            Notifications
            <button class="text-xs text-primary-600 hover:underline">Mark all read</button>
          </div>
          <div v-if="notificationStore.notifications.length === 0" class="p-8 text-center text-sm text-slate-500">
            No new notifications
          </div>
          <div v-else class="divide-y divide-slate-100">
            <div v-for="n in notificationStore.notifications" :key="n.id" class="p-4 hover:bg-slate-50 cursor-pointer transition-colors">
              <p class="text-sm font-medium text-slate-900">{{ n.title }}</p>
              <p class="text-xs text-slate-500 mt-1">{{ n.message }}</p>
            </div>
          </div>
        </div>
      </UiDropdown>

      <!-- User Menu -->
      <UiDropdown :items="userMenuItems" align="right">
        <template #trigger>
          <button class="flex items-center space-x-3 p-1 hover:bg-slate-100 rounded-lg transition-colors group">
            <div class="h-9 w-9 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 font-bold border-2 border-transparent group-hover:border-primary-200 transition-all">
              {{ authStore.userInitials || 'JD' }}
            </div>
            <div class="hidden lg:block text-left">
              <p class="text-sm font-medium text-slate-900 leading-none">{{ authStore.user?.name || 'John Doe' }}</p>
              <p class="text-xs text-slate-500 mt-1 leading-none">{{ authStore.user?.role || 'Administrator' }}</p>
            </div>
          </button>
        </template>
      </UiDropdown>
    </div>
  </header>
</template>
