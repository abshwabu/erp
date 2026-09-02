<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { useOfflineSync } from '@/services/offlineSync'
import {
  WifiOff,
  RefreshCw,
  CheckCircle2,
  AlertTriangle,
  CloudUpload,
} from '@lucide/vue'

const { isOnline, isSyncing, pendingCount, syncAll, refreshPendingCount } = useOfflineSync()

const showSuccessNotice = ref(false)
let successTimer: any = null

onMounted(() => {
  refreshPendingCount()

  // Listen for sync completion event
  if (typeof window !== 'undefined') {
    window.addEventListener('erp:offline-synced', ((e: CustomEvent) => {
      if (e.detail?.successCount > 0) {
        showSuccessNotice.value = true
        clearTimeout(successTimer)
        successTimer = setTimeout(() => {
          showSuccessNotice.value = false
        }, 5000)
      }
    }) as EventListener)
  }
})

const handleManualSync = async () => {
  await syncAll()
}
</script>

<template>
  <div>
    <!-- 1. Offline Alert Banner -->
    <div
      v-if="!isOnline"
      class="bg-amber-500 text-slate-950 px-4 py-2 text-xs font-bold flex items-center justify-between shadow-sm z-40 transition-all border-b border-amber-600"
    >
      <div class="flex items-center gap-2 max-w-4xl mx-auto flex-1">
        <WifiOff class="w-4 h-4 text-slate-900 shrink-0 animate-pulse" />
        <span>
          <strong>Offline Mode Active:</strong> You are currently disconnected. All invoices, POS checkouts, and records are safely saved locally on your device and will synchronize automatically when connection resumes.
        </span>
      </div>
      <div v-if="pendingCount > 0" class="shrink-0 flex items-center gap-2 pl-2">
        <span class="px-2 py-0.5 rounded-md bg-amber-600/30 text-[11px] font-black uppercase">
          {{ pendingCount }} Queued
        </span>
      </div>
    </div>

    <!-- 2. Syncing In Progress Banner -->
    <div
      v-else-if="isSyncing"
      class="bg-blue-600 text-white px-4 py-2 text-xs font-bold flex items-center justify-center gap-2 shadow-sm z-40 transition-all"
    >
      <RefreshCw class="w-4 h-4 animate-spin shrink-0" />
      <span>
        Reconnected! Synchronizing {{ pendingCount }} offline transactions with cloud server...
      </span>
    </div>

    <!-- 3. Sync Success Notification Banner -->
    <div
      v-else-if="showSuccessNotice"
      class="bg-emerald-600 text-white px-4 py-1.5 text-xs font-bold flex items-center justify-between shadow-sm z-40 transition-all"
    >
      <div class="flex items-center gap-2 max-w-4xl mx-auto flex-1">
        <CheckCircle2 class="w-4 h-4 shrink-0" />
        <span>Online connection restored: All offline changes were synchronized successfully.</span>
      </div>
      <button
        type="button"
        @click="showSuccessNotice = false"
        class="text-emerald-100 hover:text-white text-xs underline cursor-pointer"
      >
        Dismiss
      </button>
    </div>

    <!-- 4. Online but pending items exist (e.g. failed attempt or pending trigger) -->
    <div
      v-else-if="isOnline && pendingCount > 0"
      class="bg-indigo-600 text-white px-4 py-1.5 text-xs font-bold flex items-center justify-between shadow-sm z-40 transition-all"
    >
      <div class="flex items-center gap-2 max-w-4xl mx-auto flex-1">
        <CloudUpload class="w-4 h-4 shrink-0" />
        <span>{{ pendingCount }} offline record(s) ready to synchronize.</span>
      </div>
      <button
        type="button"
        @click="handleManualSync"
        class="px-2.5 py-1 bg-white text-indigo-700 rounded-lg text-xs font-black hover:bg-indigo-50 transition-colors cursor-pointer shadow-xs"
      >
        Sync Now
      </button>
    </div>
  </div>
</template>
