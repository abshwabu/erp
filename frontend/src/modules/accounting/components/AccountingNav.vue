<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  Calculator,
  FileText,
  Scale,
  TrendingUp,
  Landmark,
  ArrowDownLeft,
  ArrowUpRight,
  Banknote,
} from '@lucide/vue'

const route = useRoute()
const { t } = useI18n()

const tabs = computed(() => [
  { name: t('nav.incomeExpenses'), to: '/accounting/finance', icon: Banknote },
  { name: t('nav.chartOfAccounts'), to: '/accounting/chart-of-accounts', icon: Calculator },
  { name: t('nav.journals'), to: '/accounting/journals', icon: FileText },
  { name: t('nav.trialBalance'), to: '/accounting/trial-balance', icon: Scale },
  { name: t('nav.profitLoss'), to: '/accounting/profit-loss', icon: TrendingUp },
  { name: t('nav.balanceSheet'), to: '/accounting/balance-sheet', icon: Landmark },
  { name: t('nav.arAging'), to: '/accounting/ar-aging', icon: ArrowDownLeft },
  { name: t('nav.apAging'), to: '/accounting/ap-aging', icon: ArrowUpRight },
])

const isActive = (path: string) => route.path === path || route.path.startsWith(`${path}/`)
</script>

<template>
  <nav class="w-full overflow-x-auto pb-1 scrollbar-none">
    <div class="inline-flex min-w-full sm:min-w-0 items-center gap-1 rounded-2xl border border-slate-200 bg-white p-1.5 shadow-xs">
      <router-link
        v-for="tab in tabs"
        :key="tab.to"
        :to="tab.to"
        class="inline-flex items-center gap-2 whitespace-nowrap rounded-xl px-3.5 py-2 text-xs sm:text-sm font-medium transition-all"
        :class="
          isActive(tab.to)
            ? 'bg-primary-50 text-primary-700 shadow-xs font-semibold'
            : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
        "
      >
        <component :is="tab.icon" class="h-4 w-4 shrink-0" />
        <span>{{ tab.name }}</span>
      </router-link>
    </div>
  </nav>
</template>

<style scoped>
.scrollbar-none {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
.scrollbar-none::-webkit-scrollbar {
  display: none;
}
</style>
