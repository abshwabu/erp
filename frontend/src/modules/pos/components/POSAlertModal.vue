<script setup lang="ts">
import { computed } from 'vue'
import { usePosStore } from '../stores/posStore'
import {
  AlertTriangle,
  AlertCircle,
  CheckCircle2,
  Info,
  X,
  PackageX,
} from '@lucide/vue'

const posStore = usePosStore()

const alert = computed(() => posStore.alertState)

const iconComponent = computed(() => {
  switch (alert.value.type) {
    case 'error':
      return AlertCircle
    case 'success':
      return CheckCircle2
    case 'info':
      return Info
    case 'warning':
    default:
      return alert.value.title?.toLowerCase().includes('stock') ? PackageX : AlertTriangle
  }
})

const badgeColors = computed(() => {
  switch (alert.value.type) {
    case 'error':
      return 'bg-rose-50 text-rose-600 border-rose-200 ring-rose-500/10'
    case 'success':
      return 'bg-emerald-50 text-emerald-600 border-emerald-200 ring-emerald-500/10'
    case 'info':
      return 'bg-blue-50 text-blue-600 border-blue-200 ring-blue-500/10'
    case 'warning':
    default:
      return 'bg-amber-50 text-amber-600 border-amber-200 ring-amber-500/10'
  }
})

const buttonColors = computed(() => {
  switch (alert.value.type) {
    case 'error':
      return 'bg-rose-600 hover:bg-rose-500 text-white shadow-rose-600/20'
    case 'success':
      return 'bg-emerald-600 hover:bg-emerald-500 text-white shadow-emerald-600/20'
    case 'info':
      return 'bg-blue-600 hover:bg-blue-500 text-white shadow-blue-600/20'
    case 'warning':
    default:
      return 'bg-amber-600 hover:bg-amber-500 text-white shadow-amber-600/20'
  }
})
</script>

<template>
  <div
    v-if="alert.open"
    class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs animate-fade-in"
    @click.self="posStore.cancelAlert()"
  >
    <div
      class="bg-white rounded-3xl shadow-2xl max-w-sm w-full p-6 text-center border border-slate-200/90 animate-scale-up relative space-y-4"
      role="alertdialog"
      aria-modal="true"
    >
      <!-- Close X button -->
      <button
        @click="posStore.cancelAlert()"
        class="absolute top-4 right-4 p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-xl transition-colors cursor-pointer"
        title="Close"
      >
        <X class="w-4 h-4" />
      </button>

      <!-- Icon Badge -->
      <div
        class="w-14 h-14 mx-auto rounded-2xl flex items-center justify-center border ring-8 transition-transform"
        :class="badgeColors"
      >
        <component :is="iconComponent" class="w-7 h-7" />
      </div>

      <!-- Content -->
      <div class="space-y-1.5">
        <h3 class="text-base sm:text-lg font-bold text-slate-900 tracking-tight leading-snug">
          {{ alert.title || 'Notice' }}
        </h3>
        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed px-1">
          {{ alert.message }}
        </p>
      </div>

      <!-- Action Buttons -->
      <div class="pt-2 flex gap-2.5">
        <button
          v-if="alert.cancelText"
          type="button"
          @click="posStore.cancelAlert()"
          class="flex-1 py-2.5 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs sm:text-sm rounded-xl transition-all cursor-pointer active:scale-98"
        >
          {{ alert.cancelText }}
        </button>

        <button
          type="button"
          @click="posStore.confirmAlert()"
          class="flex-1 py-2.5 px-4 font-bold text-xs sm:text-sm rounded-xl shadow-md transition-all cursor-pointer active:scale-98"
          :class="buttonColors"
        >
          {{ alert.confirmText || 'Understood' }}
        </button>
      </div>
    </div>
  </div>
</template>
