<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useOfflineSync } from '@/services/offlineSync'
import { offlineStorage, type OutboxItem } from '@/services/offlineStorage'
import {
  Wifi,
  WifiOff,
  RefreshCw,
  CloudUpload,
  Database,
  Trash2,
  X,
} from '@lucide/vue'

const { isOnline, isSyncing, pendingCount, syncAll, refreshPendingCount } = useOfflineSync()

const isModalOpen = ref(false)
const pendingItems = ref<OutboxItem[]>([])
const isLoadingItems = ref(false)

const loadItems = async () => {
  isLoadingItems.value = true
  try {
    pendingItems.value = await offlineStorage.getPendingOutbox()
  } finally {
    isLoadingItems.value = false
  }
}

const openModal = async () => {
  await loadItems()
  isModalOpen.value = true
}

const handleSync = async () => {
  await syncAll()
  await loadItems()
  await refreshPendingCount()
}

const handleRemoveItem = async (id: string) => {
  await offlineStorage.removeOutboxItem(id)
  await loadItems()
  await refreshPendingCount()
}

onMounted(() => {
  refreshPendingCount()
})
</script>

<template>
  <div>
    <!-- Compact Header Trigger -->
    <button
      type="button"
      @click="openModal"
      class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl text-xs font-bold border transition-all cursor-pointer"
      :class="[
        !isOnline
          ? 'bg-amber-100/90 text-amber-900 border-amber-300 hover:bg-amber-200'
          : pendingCount > 0
          ? 'bg-indigo-50 text-indigo-700 border-indigo-200 hover:bg-indigo-100'
          : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'
      ]"
      :title="!isOnline ? 'Offline Mode Active' : `${pendingCount} items pending sync`"
    >
      <WifiOff v-if="!isOnline" class="w-3.5 h-3.5 text-amber-700 animate-pulse" />
      <RefreshCw v-else-if="isSyncing" class="w-3.5 h-3.5 text-blue-600 animate-spin" />
      <Wifi v-else class="w-3.5 h-3.5 text-emerald-600" />

      <span class="hidden sm:inline">
        {{ !isOnline ? 'Offline' : isSyncing ? 'Syncing...' : 'Online' }}
      </span>

      <span
        v-if="pendingCount > 0"
        class="ml-0.5 px-1.5 py-0.2 rounded-full text-[10px] font-black"
        :class="!isOnline ? 'bg-amber-600 text-white' : 'bg-indigo-600 text-white'"
      >
        {{ pendingCount }}
      </span>
    </button>

    <!-- Slide-over / Modal for Outbox Queue -->
    <div
      v-if="isModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
    >
      <div class="bg-white rounded-2xl border border-slate-200 shadow-xl w-full max-w-lg overflow-hidden flex flex-col max-h-[85vh]">
        <!-- Modal Header -->
        <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
          <div class="flex items-center gap-2">
            <Database class="w-5 h-5 text-indigo-600" />
            <div>
              <h3 class="text-sm font-bold text-slate-900">Offline Storage & Outbox</h3>
              <p class="text-xs text-slate-500">
                Connection Status:
                <strong :class="isOnline ? 'text-emerald-600' : 'text-amber-600'">
                  {{ isOnline ? 'Online' : 'Offline' }}
                </strong>
              </p>
            </div>
          </div>
          <button
            type="button"
            @click="isModalOpen = false"
            class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-200/50"
          >
            <X class="w-4 h-4" />
          </button>
        </div>

        <!-- Outbox Content -->
        <div class="p-4 overflow-y-auto flex-1 space-y-3">
          <div v-if="pendingItems.length === 0" class="text-center py-8 text-slate-400 space-y-2">
            <CloudUpload class="w-8 h-8 mx-auto text-slate-300" />
            <p class="text-xs font-semibold">Outbox is empty</p>
            <p class="text-[11px] text-slate-400">All offline actions are in sync with the cloud.</p>
          </div>

          <div
            v-for="item in pendingItems"
            :key="item.id"
            class="p-3 rounded-xl border border-slate-200 bg-slate-50/70 flex items-center justify-between gap-3 text-xs"
          >
            <div class="space-y-1 min-w-0">
              <div class="flex items-center gap-2">
                <span class="px-1.5 py-0.5 rounded bg-slate-200 text-slate-700 font-mono text-[10px] font-bold">
                  {{ item.method }}
                </span>
                <span class="font-bold text-slate-900 truncate">
                  {{ item.title || item.url }}
                </span>
              </div>
              <p class="text-[11px] text-slate-400">
                Queued {{ new Date(item.createdAt).toLocaleTimeString() }}
                <span v-if="item.status === 'failed'" class="text-rose-600 font-semibold ml-1">
                  • Failed: {{ item.error }}
                </span>
              </p>
            </div>

            <button
              type="button"
              @click="handleRemoveItem(item.id)"
              class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer shrink-0"
              title="Discard item"
            >
              <Trash2 class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="p-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
          <span class="text-xs text-slate-500">
            {{ pendingItems.length }} transaction(s) pending
          </span>

          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="isModalOpen = false"
              class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-100 cursor-pointer"
            >
              Close
            </button>
            <button
              type="button"
              :disabled="!isOnline || isSyncing || pendingItems.length === 0"
              @click="handleSync"
              class="px-3 py-1.5 rounded-lg bg-indigo-600 text-xs font-bold text-white hover:bg-indigo-700 disabled:opacity-50 transition-colors flex items-center gap-1.5 cursor-pointer"
            >
              <RefreshCw class="w-3 h-3" :class="isSyncing ? 'animate-spin' : ''" />
              <span>{{ isSyncing ? 'Syncing...' : 'Sync Outbox Now' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
