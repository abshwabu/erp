<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useUIStore } from '@/stores/ui'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notification'
import AppSidebar from '@/components/shared/AppSidebar.vue'
import AppHeader from '@/components/shared/AppHeader.vue'
import OfflineBanner from '@/components/shared/OfflineBanner.vue'
import PlanSelectionModal from '@/components/shared/PlanSelectionModal.vue'
import UiToast from '@/components/ui/UiToast.vue'

const uiStore = useUIStore()
const authStore = useAuthStore()
const notificationStore = useNotificationStore()
const route = useRoute()

const isPosRoute = computed(() => {
  const path = route.path || ''
  const name = String(route.name || '')
  return name === 'pos' ||
         path.includes('pos') ||
         route.matched?.some(r => r.name === 'pos' || String(r.path).includes('pos')) ||
         !!route.meta?.fullHeight
})
</script>

<template>
  <div class="h-screen h-[100dvh] bg-slate-50 flex overflow-hidden">
    <AppSidebar />

    <div class="flex-1 flex flex-col min-w-0 h-full overflow-hidden">
      <!-- Global Offline / Sync Network Banner -->
      <OfflineBanner />

      <AppHeader />

      <!-- Main container (auto-adapts for POS without duplicate router-views) -->
      <main
        class="flex-1 min-h-0 flex flex-col"
        :class="isPosRoute
          ? 'overflow-hidden p-1 sm:p-2 lg:p-2.5'
          : 'overflow-auto p-4 lg:p-6 custom-scrollbar'"
      >
        <div
          class="flex-1 min-h-0 flex flex-col w-full"
          :class="isPosRoute ? 'h-full' : 'max-w-7xl mx-auto'"
        >
          <router-view />
        </div>
      </main>

      <!-- Paywall / Plan Picker for Expired Trials -->
      <PlanSelectionModal :forced="authStore.needsPlanSelection" />

      <!-- Global Toasts -->
      <div
        aria-live="assertive"
        class="fixed inset-0 flex items-end px-4 py-6 pointer-events-none sm:p-6 sm:items-start z-50"
      >
        <div class="w-full flex flex-col items-center space-y-4 sm:items-end">
          <transition-group
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
          >
            <UiToast
              v-for="toast in notificationStore.toasts"
              :key="toast.id"
              :id="toast.id"
              :message="toast.message"
              :type="toast.type"
              @close="notificationStore.removeToast"
            />
          </transition-group>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>
