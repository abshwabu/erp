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
  ChevronRight,
  CreditCard,
} from '@lucide/vue'
import { markRaw, ref } from 'vue'
import UiDropdown from '@/components/ui/UiDropdown.vue'
import NavClockWidget from './NavClockWidget.vue'
import OfflineStatusWidget from './OfflineStatusWidget.vue'
import PlanSelectionModal from './PlanSelectionModal.vue'
import OnboardingHelper from './OnboardingHelper.vue'

const uiStore = useUIStore()
const authStore = useAuthStore()
const notificationStore = useNotificationStore()
const router = useRouter()
const isPlanModalOpen = ref(false)

const handleLogout = async () => {
  await authStore.logout()
  router.push('/login')
}

const userMenuItems = [
  { label: 'Profile', icon: markRaw(User), to: '/profile' },
  { label: 'Settings', icon: markRaw(Settings), to: '/settings' },
  { label: 'Subscription & Plans', icon: markRaw(CreditCard), action: () => { isPlanModalOpen.value = true } },
  { label: 'Logout', icon: markRaw(LogOut), action: handleLogout, variant: 'danger' as const },
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
      <div class="hidden xl:flex relative">
        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <Search :size="18" class="text-slate-400" />
        </span>
        <input
          type="text"
          placeholder="Search..."
          class="block w-full pl-10 pr-3 py-2 border border-slate-200 rounded-md leading-5 bg-slate-50 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-1 focus:ring-primary-500 focus:border-primary-500 sm:text-sm transition-all"
        />
      </div>

      <!-- 2-Month Free Trial Countdown Badge -->
      <div
        v-if="authStore.isTrialActive"
        class="hidden sm:flex items-center gap-2 px-3 py-1.5 bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200/80 rounded-xl text-xs shadow-xs"
      >
        <span class="flex h-2 w-2 relative">
          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
          <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
        </span>
        <span class="font-bold text-amber-900">
          Free Trial: {{ authStore.trialDaysLeft }} days left
        </span>
        <button
          type="button"
          @click="isPlanModalOpen = true"
          class="ml-1 text-[11px] font-extrabold text-white bg-amber-600 hover:bg-amber-700 px-2 py-0.5 rounded-lg transition-colors cursor-pointer shadow-xs"
        >
          Choose Plan
        </button>
      </div>

      <!-- Offline / Online Synchronization Status Widget -->
      <OfflineStatusWidget />

      <!-- Quick Clock In / Out Widget for all users -->
      <NavClockWidget />

      <!-- Quick Start / Onboarding Helper for new users -->
      <OnboardingHelper />

      <!-- Notifications -->
      <UiDropdown align="right">
        <template #trigger>
          <button class="p-2 hover:bg-slate-100 rounded-full relative transition-colors" title="Notifications">
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
            <div 
              v-for="notif in notificationStore.notifications" 
              :key="notif.id"
              class="p-4 hover:bg-slate-50 transition-colors"
              :class="{ 'bg-primary-50/30': !notif.read }"
            >
              <p class="text-sm font-medium text-slate-900">{{ notif.title }}</p>
              <p class="text-xs text-slate-500 mt-1">{{ notif.message }}</p>
              <span class="text-[10px] text-slate-400 mt-2 block">{{ notif.time }}</span>
            </div>
          </div>
        </div>
      </UiDropdown>

      <!-- User Profile Menu -->
      <UiDropdown :items="userMenuItems" align="right">
        <template #trigger>
          <button class="flex items-center space-x-3 p-1 hover:bg-slate-100 rounded-lg transition-colors group">
            <div class="h-9 w-9 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 font-bold border-2 border-transparent group-hover:border-primary-200 transition-all">
              {{ authStore.userInitials || 'JD' }}
            </div>
            <div class="hidden lg:block text-left">
              <p class="text-sm font-medium text-slate-900 leading-none">{{ authStore.user?.name || 'John Doe' }}</p>
              <p class="text-xs text-slate-500 mt-1 leading-none">{{ authStore.user?.roles?.[0] || 'Administrator' }}</p>
            </div>
          </button>
        </template>
      </UiDropdown>
    </div>

    <!-- Voluntary Plan Selection Modal -->
    <PlanSelectionModal v-model="isPlanModalOpen" />
  </header>
</template>
