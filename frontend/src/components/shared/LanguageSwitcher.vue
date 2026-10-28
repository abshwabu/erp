<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Globe, Check, ChevronDown } from '@lucide/vue'
import { onClickOutside } from '@vueuse/core'
import { SUPPORTED_LOCALES, setAppLocale, type SupportedLocale } from '@/i18n'

interface Props {
  variant?: 'light' | 'dark' | 'outline'
  compact?: boolean
  showLabel?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'light',
  compact: false,
  showLabel: true
})

const { locale } = useI18n()
const isOpen = ref(false)
const dropdownRef = ref<HTMLElement | null>(null)

onClickOutside(dropdownRef, () => {
  isOpen.value = false
})

function selectLocale(code: SupportedLocale) {
  locale.value = code
  setAppLocale(code)
  isOpen.value = false
}

const currentLocaleObj = () => {
  return SUPPORTED_LOCALES.find(l => l.code === locale.value) || SUPPORTED_LOCALES[0]
}
</script>

<template>
  <div class="relative inline-block text-left" ref="dropdownRef">
    <button
      type="button"
      @click="isOpen = !isOpen"
      :class="[
        'inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium rounded-lg transition-all cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-blue-500',
        variant === 'dark'
          ? 'bg-slate-800 text-slate-200 border border-slate-700 hover:bg-slate-700/80 hover:text-white'
          : variant === 'outline'
          ? 'bg-transparent text-slate-700 border border-slate-300 hover:bg-slate-100 hover:text-slate-900'
          : 'bg-slate-100 text-slate-700 hover:bg-slate-200/80 hover:text-slate-900 border border-slate-200/80 shadow-xs'
      ]"
      :title="'Language / ቋንቋ: ' + currentLocaleObj().name"
      aria-haspopup="true"
      :aria-expanded="isOpen"
    >
      <Globe class="w-4 h-4 shrink-0 text-blue-500" />
      <span v-if="!compact" class="font-semibold">{{ currentLocaleObj().name }}</span>
      <span v-else class="font-bold uppercase tracking-wider text-[11px]">{{ currentLocaleObj().flag }}</span>
      <ChevronDown class="w-3.5 h-3.5 opacity-60 transition-transform duration-150" :class="{ 'rotate-180': isOpen }" />
    </button>

    <!-- Dropdown Menu -->
    <transition
      enter-active-class="transition duration-100 ease-out"
      enter-from-class="transform scale-95 opacity-0"
      enter-to-class="transform scale-100 opacity-100"
      leave-active-class="transition duration-75 ease-in"
      leave-from-class="transform scale-100 opacity-100"
      leave-to-class="transform scale-95 opacity-0"
    >
      <div
        v-if="isOpen"
        class="absolute right-0 mt-1.5 w-40 rounded-xl bg-white shadow-xl ring-1 ring-black/5 divide-y divide-slate-100 focus:outline-none z-50 py-1 border border-slate-200"
        role="menu"
      >
        <div class="px-3 py-1.5 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
          Language / ቋንቋ
        </div>
        <div class="py-1">
          <button
            v-for="item in SUPPORTED_LOCALES"
            :key="item.code"
            type="button"
            @click="selectLocale(item.code)"
            :class="[
              'w-full flex items-center justify-between px-3 py-2 text-xs text-left transition-colors cursor-pointer',
              locale === item.code
                ? 'bg-blue-50/80 text-blue-700 font-bold'
                : 'text-slate-700 hover:bg-slate-50 hover:text-slate-900'
            ]"
            role="menuitem"
          >
            <div class="flex items-center gap-2">
              <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                {{ item.flag }}
              </span>
              <span>{{ item.name }}</span>
            </div>
            <Check v-if="locale === item.code" class="w-3.5 h-3.5 text-blue-600 shrink-0" />
          </button>
        </div>
      </div>
    </transition>
  </div>
</template>
