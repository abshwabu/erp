<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/api/client'
import { Bell, CheckCheck, Trash2, Info, CheckCircle, AlertTriangle, AlertCircle } from '@lucide/vue'

interface AppNotification {
  id: string
  type: string
  title: string
  message: string
  action_url: string | null
  read_at: string | null
  created_at: string
}

const notifications = ref<AppNotification[]>([])
const unreadCount = ref(0)
const loading = ref(true)
const filterUnread = ref(false)

const typeIcons: Record<string, any> = {
  info: Info,
  success: CheckCircle,
  warning: AlertTriangle,
  alert: AlertCircle,
}

const typeStyles: Record<string, { bg: string; icon: string }> = {
  info: { bg: 'bg-blue-50 border-blue-200', icon: 'text-blue-500' },
  success: { bg: 'bg-green-50 border-green-200', icon: 'text-green-500' },
  warning: { bg: 'bg-amber-50 border-amber-200', icon: 'text-amber-500' },
  alert: { bg: 'bg-red-50 border-red-200', icon: 'text-red-500' },
}

async function fetchNotifications() {
  loading.value = true
  try {
    const res = await api.get('/notifications', {
      params: { unread_only: filterUnread.value ? 1 : 0 },
    })
    notifications.value = res.data?.data ?? []
    unreadCount.value = res.data?.unread_count ?? 0
  } catch (e) {
    console.error('Failed to load notifications', e)
  } finally {
    loading.value = false
  }
}

async function markAsRead(id: string) {
  try {
    await api.post(`/notifications/${id}/read`)
    const notif = notifications.value.find(n => n.id === id)
    if (notif) notif.read_at = new Date().toISOString()
    if (unreadCount.value > 0) unreadCount.value--
  } catch (e) {
    console.error('Failed to mark notification as read', e)
  }
}

async function markAllAsRead() {
  try {
    await api.post('/notifications/mark-all-read')
    notifications.value.forEach(n => {
      n.read_at = new Date().toISOString()
    })
    unreadCount.value = 0
  } catch (e) {
    console.error('Failed to mark all as read', e)
  }
}

async function deleteNotification(id: string) {
  try {
    await api.delete(`/notifications/${id}`)
    notifications.value = notifications.value.filter(n => n.id !== id)
  } catch (e) {
    console.error('Failed to delete notification', e)
  }
}

function formatDate(iso: string) {
  if (!iso) return ''
  const d = new Date(iso)
  return d.toLocaleDateString() + ' ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

onMounted(fetchNotifications)
</script>

<template>
  <div class="p-6 space-y-6 max-w-4xl mx-auto">
    <div class="flex items-center justify-between">
      <div class="flex items-center space-x-3">
        <Bell class="w-7 h-7 text-gray-700" />
        <h1 class="text-2xl font-bold text-gray-900">Notifications</h1>
        <span
          v-if="unreadCount > 0"
          class="bg-red-500 text-white text-xs font-semibold px-2 py-0.5 rounded-full"
        >
          {{ unreadCount }} new
        </span>
      </div>
      <div class="flex items-center space-x-3">
        <button
          @click="filterUnread = !filterUnread; fetchNotifications()"
          :class="[filterUnread ? 'bg-primary-50 text-primary-700 border-primary-300' : 'bg-white text-gray-700 border-gray-300', 'px-3 py-1.5 text-sm border rounded-md font-medium hover:bg-gray-50']"
        >
          {{ filterUnread ? 'Showing Unread' : 'Filter Unread' }}
        </button>
        <button
          v-if="unreadCount > 0"
          @click="markAllAsRead"
          class="inline-flex items-center px-3 py-1.5 text-sm bg-white border border-gray-300 rounded-md font-medium text-gray-700 hover:bg-gray-50 space-x-1.5"
        >
          <CheckCheck class="w-4 h-4 text-green-600" />
          <span>Mark all read</span>
        </button>
      </div>
    </div>

    <div v-if="loading" class="text-center py-12 text-gray-500">Loading notifications…</div>

    <div v-else-if="notifications.length === 0" class="text-center py-16 bg-white rounded-lg border border-gray-200">
      <Bell class="w-12 h-12 text-gray-300 mx-auto mb-3" />
      <p class="text-gray-500 font-medium">No notifications to display</p>
      <p class="text-sm text-gray-400 mt-1">You're all caught up!</p>
    </div>

    <div v-else class="space-y-3">
      <div
        v-for="item in notifications"
        :key="item.id"
        :class="[
          'p-4 rounded-lg border transition-all flex items-start justify-between space-x-4',
          item.read_at ? 'bg-white border-gray-200' : (typeStyles[item.type]?.bg ?? 'bg-blue-50 border-blue-200 shadow-sm')
        ]"
      >
        <div class="flex items-start space-x-3.5 flex-1">
          <component
            :is="typeIcons[item.type] ?? Info"
            :class="['w-5 h-5 mt-0.5 shrink-0', typeStyles[item.type]?.icon ?? 'text-blue-500']"
          />
          <div class="space-y-1 flex-1">
            <div class="flex items-center space-x-2">
              <span class="text-sm font-semibold text-gray-900">{{ item.title }}</span>
              <span v-if="!item.read_at" class="inline-block w-2 h-2 rounded-full bg-primary-600"></span>
            </div>
            <p class="text-sm text-gray-600 leading-relaxed">{{ item.message }}</p>
            <p class="text-xs text-gray-400 pt-1">{{ formatDate(item.created_at) }}</p>
          </div>
        </div>

        <div class="flex items-center space-x-2 shrink-0">
          <button
            v-if="!item.read_at"
            @click="markAsRead(item.id)"
            class="text-xs text-primary-600 hover:text-primary-800 font-medium px-2 py-1 rounded hover:bg-primary-50"
            title="Mark as read"
          >
            Mark read
          </button>
          <button
            @click="deleteNotification(item.id)"
            class="text-gray-400 hover:text-red-600 p-1 rounded hover:bg-gray-100"
            title="Delete notification"
          >
            <Trash2 class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
