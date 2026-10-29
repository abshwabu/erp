<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import api from '@/api/client'
import { useOnboardingStore } from '@/stores/onboarding'
import OnboardingBanner from '@/components/shared/OnboardingBanner.vue'
import LanguageSwitcher from '@/components/shared/LanguageSwitcher.vue'
import {
  TrendingUp,
  TrendingDown,
  DollarSign,
  Package,
  Users,
  AlertTriangle,
  ArrowUpRight,
  Plus,
  ShoppingCart,
  BookOpen,
  UserPlus,
  BarChart3,
  Clock,
  CheckCircle2,
  RefreshCw,
  Box,
  Layers,
  ArrowRight,
  ShieldCheck,
  Building,
  Globe,
  Briefcase,
  LifeBuoy,
  CreditCard,
} from '@lucide/vue'

import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler,
} from 'chart.js'
import { Line, Doughnut } from 'vue-chartjs'

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler
)

const router = useRouter()
const authStore = useAuthStore()
const onboardingStore = useOnboardingStore()
const { t, locale } = useI18n()

const loading = ref(true)

// Real Dynamic Dashboard State
const dashboardData = ref({
  financial: {
    monthly_revenue_cents: 0,
    prev_monthly_revenue_cents: 0,
    revenue_growth_pct: 0,
    today_revenue_cents: 0,
    today_orders_count: 0,
    monthly_trend: [] as { month: string; revenue: number }[],
  },
  inventory: {
    total_products: 0,
    total_valuation_cents: 0,
    optimal_count: 0,
    low_stock_count: 0,
    out_of_stock_count: 0,
    low_stock_items: [] as any[],
    recent_movements: [] as any[],
  },
  operations: {
    employee_count: 0,
    pending_leaves_count: 0,
    open_tickets_count: 0,
    active_projects_count: 0,
    open_pos_sessions: 0,
    active_storefronts: 0,
    recent_orders: [] as any[],
  },
})

const currentHour = new Date().getHours()
const greeting = computed(() => {
  if (currentHour < 12) return t('dashboard.greetingMorning')
  if (currentHour < 18) return t('dashboard.greetingAfternoon')
  return t('dashboard.greetingEvening')
})

const userName = computed(() => authStore.user?.name || 'Administrator')

function formatCents(cents: number) {
  const val = (cents || 0) / 100
  if (locale.value === 'am') {
    return (
      new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }).format(val) + ' ብር'
    )
  }
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(val)
}

function formatDate(dateStr: string) {
  if (!dateStr) return t('dashboard.justNow')
  return new Date(dateStr).toLocaleDateString(locale.value === 'am' ? 'am-ET' : 'en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

// Fetch Real Live Dashboard Data
async function loadDashboardData() {
  loading.value = true
  try {
    const res = await api.get('/core/dashboard')
    const data = res.data?.data || res.data
    if (data) {
      dashboardData.value = {
        financial: { ...dashboardData.value.financial, ...(data.financial || {}) },
        inventory: { ...dashboardData.value.inventory, ...(data.inventory || {}) },
        operations: { ...dashboardData.value.operations, ...(data.operations || {}) },
      }
      onboardingStore.syncWithWorkspaceData(data)
    }
  } catch (err) {
    console.error('Failed to load dashboard data:', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadDashboardData()
})

// Revenue Line Chart Data (From Real Monthly Trend)
const revenueChartData = computed(() => {
  const trend = dashboardData.value.financial.monthly_trend || []
  const labels = trend.length > 0 ? trend.map((t) => t.month) : ['Month 1', 'Month 2', 'Month 3', 'Month 4', 'Month 5', 'Month 6']
  const data = trend.length > 0 ? trend.map((t) => t.revenue) : [0, 0, 0, 0, 0, 0]

  return {
    labels,
    datasets: [
      {
        label: `${t('dashboard.grossSalesLabel')} (${locale.value === 'am' ? 'ብር' : '$'})`,
        data,
        borderColor: '#3b82f6',
        backgroundColor: 'rgba(59, 130, 246, 0.12)',
        fill: true,
        tension: 0.4,
        pointRadius: 4,
        pointHoverRadius: 6,
      },
    ],
  }
})

const lineChartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top' as const,
      labels: {
        usePointStyle: true,
        font: { family: 'Inter', size: 12 },
      },
    },
    tooltip: {
      mode: 'index' as const,
      intersect: false,
    },
  },
  scales: {
    x: { grid: { display: false } },
    y: {
      grid: { color: 'rgba(226, 232, 240, 0.6)' },
      ticks: {
        callback: (val: any) => (locale.value === 'am' ? `${val} ብር` : `$${val}`),
      },
      beginAtZero: true,
    },
  },
}))

// Inventory Breakdown Doughnut Chart (Real Optimal vs Low vs Out of Stock)
const inventoryChartData = computed(() => {
  const { optimal_count, low_stock_count, out_of_stock_count, total_products } = dashboardData.value.inventory

  if (total_products === 0) {
    return {
      labels: [t('dashboard.noCatalogItems')],
      datasets: [
        {
          data: [1],
          backgroundColor: ['#e2e8f0'],
          borderWidth: 0,
        },
      ],
    }
  }

  return {
    labels: [t('dashboard.healthyStock'), t('dashboard.lowStockAlert'), t('dashboard.outOfStock')],
    datasets: [
      {
        data: [optimal_count, low_stock_count, out_of_stock_count],
        backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
        hoverOffset: 6,
        borderWidth: 2,
        borderColor: '#ffffff',
      },
    ],
  }
})

const doughnutChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom' as const,
      labels: {
        usePointStyle: true,
        font: { family: 'Inter', size: 12 },
      },
    },
  },
  cutout: '70%',
}
</script>

<template>
  <div class="space-y-6 pb-12 font-sans max-w-7xl mx-auto px-4 sm:px-6">
    <!-- Header Banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950 to-blue-900 text-white p-5 sm:p-8 shadow-xl">
      <div class="absolute right-0 top-0 -mr-16 -mt-16 h-64 w-64 rounded-full bg-blue-500/10 blur-3xl pointer-events-none"></div>
      <div class="absolute right-32 bottom-0 -mb-16 h-48 w-48 rounded-full bg-indigo-500/10 blur-2xl pointer-events-none"></div>

      <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div>
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md text-xs font-medium text-blue-200 mb-3 border border-white/10">
            <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
            {{ $t('dashboard.bannerBadge') }}
          </div>
          <h1 class="text-2xl md:text-3xl font-bold tracking-tight text-white">
            {{ greeting }}, {{ userName }}! 👋
          </h1>
          <p class="mt-1 text-xs sm:text-sm text-slate-300 max-w-xl leading-relaxed">
            {{ $t('dashboard.bannerSubtitle') }}
          </p>
        </div>

        <!-- Quick Action Buttons Header -->
        <div class="flex flex-wrap items-center gap-2.5 sm:gap-3">
          <button
            @click="router.push('/pos')"
            class="inline-flex items-center gap-2 px-3.5 sm:px-4 py-2 sm:py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs sm:text-sm shadow-md shadow-blue-600/30 transition-all transform hover:-translate-y-0.5 active:translate-y-0"
          >
            <ShoppingCart class="w-4 h-4" />
            <span>{{ $t('dashboard.posRegister') }}</span>
          </button>

          <button
            @click="router.push('/inventory/products')"
            class="inline-flex items-center gap-2 px-3.5 sm:px-4 py-2 sm:py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-medium text-xs sm:text-sm border border-white/15 backdrop-blur-md transition-all"
          >
            <Plus class="w-4 h-4" />
            <span>{{ $t('dashboard.addProduct') }}</span>
          </button>

          <button
            @click="loadDashboardData()"
            :title="$t('dashboard.refreshData')"
            class="p-2 sm:p-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white border border-white/15 transition-all"
          >
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
          </button>

          <LanguageSwitcher variant="pill" :compact="true" />
        </div>
      </div>
    </div>

    <!-- Interactive Onboarding & Quick Start Checklist for New Users -->
    <OnboardingBanner />

    <!-- Key Real Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
      <!-- Metric 1: Monthly Sales Revenue -->
      <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition-all">
        <div class="flex items-center justify-between">
          <span class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ $t('dashboard.monthlyRevenue') }}</span>
          <div class="p-2.5 bg-blue-50 text-blue-600 rounded-xl">
            <DollarSign class="w-5 h-5" />
          </div>
        </div>
        <div class="mt-3 flex items-baseline justify-between">
          <span class="text-xl sm:text-2xl font-black text-slate-900">
            {{ formatCents(dashboardData.financial.monthly_revenue_cents) }}
          </span>
          <span
            v-if="dashboardData.financial.revenue_growth_pct !== 0"
            :class="[
              'inline-flex items-center text-xs font-bold px-2 py-0.5 rounded-full',
              dashboardData.financial.revenue_growth_pct >= 0 ? 'text-emerald-600 bg-emerald-50' : 'text-red-600 bg-red-50'
            ]"
          >
            <component :is="dashboardData.financial.revenue_growth_pct >= 0 ? TrendingUp : TrendingDown" class="w-3.5 h-3.5 mr-1" />
            {{ dashboardData.financial.revenue_growth_pct > 0 ? '+' : '' }}{{ dashboardData.financial.revenue_growth_pct }}%
          </span>
        </div>
        <p class="mt-2 text-xs text-slate-500">
          {{ $t('dashboard.priorMonth', { amount: formatCents(dashboardData.financial.prev_monthly_revenue_cents) }) }}
        </p>
      </div>

      <!-- Metric 2: Today's Orders -->
      <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition-all">
        <div class="flex items-center justify-between">
          <span class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ $t('dashboard.todaySales') }}</span>
          <div class="p-2.5 bg-emerald-50 text-emerald-600 rounded-xl">
            <ShoppingCart class="w-5 h-5" />
          </div>
        </div>
        <div class="mt-3 flex items-baseline justify-between">
          <span class="text-xl sm:text-2xl font-black text-slate-900">
            {{ formatCents(dashboardData.financial.today_revenue_cents) }}
          </span>
          <span class="text-xs font-semibold text-slate-500">
            {{ $t('dashboard.ordersCount', { count: dashboardData.financial.today_orders_count }) }}
          </span>
        </div>
        <p class="mt-2 text-xs text-slate-500">{{ $t('dashboard.salesChannels') }}</p>
      </div>

      <!-- Metric 3: Active Catalog & Inventory -->
      <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition-all">
        <div class="flex items-center justify-between">
          <span class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ $t('dashboard.catalogInventory') }}</span>
          <div class="p-2.5 bg-indigo-50 text-indigo-600 rounded-xl">
            <Package class="w-5 h-5" />
          </div>
        </div>
        <div class="mt-3 flex items-baseline justify-between">
          <span class="text-xl sm:text-2xl font-black text-slate-900">
            {{ $t('dashboard.productsCount', { count: dashboardData.inventory.total_products }) }}
          </span>
          <span
            v-if="dashboardData.inventory.low_stock_count > 0"
            class="inline-flex items-center text-xs font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full cursor-pointer hover:bg-amber-100 transition-colors"
            @click="router.push('/inventory/stock')"
          >
            <AlertTriangle class="w-3.5 h-3.5 mr-1" />
            {{ $t('dashboard.lowStockCount', { count: dashboardData.inventory.low_stock_count }) }}
          </span>
        </div>
        <p class="mt-2 text-xs text-slate-500">
          {{ $t('dashboard.valuation', { amount: formatCents(dashboardData.inventory.total_valuation_cents) }) }}
        </p>
      </div>

      <!-- Metric 4: Workforce & Storefronts -->
      <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition-all">
        <div class="flex items-center justify-between">
          <span class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ $t('dashboard.activeWorkforce') }}</span>
          <div class="p-2.5 bg-purple-50 text-purple-600 rounded-xl">
            <Users class="w-5 h-5" />
          </div>
        </div>
        <div class="mt-3 flex items-baseline justify-between">
          <span class="text-xl sm:text-2xl font-black text-slate-900">
            {{ $t('dashboard.staffCount', { count: dashboardData.operations.employee_count }) }}
          </span>
          <span
            v-if="dashboardData.operations.pending_leaves_count > 0"
            class="inline-flex items-center text-xs font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full cursor-pointer"
            @click="router.push('/hr/leaves')"
          >
            {{ $t('dashboard.leaveReqCount', { count: dashboardData.operations.pending_leaves_count }) }}
          </span>
        </div>
        <p class="mt-2 text-xs text-slate-500">
          {{ dashboardData.operations.active_storefronts === 1 ? $t('dashboard.liveWebStore', { count: dashboardData.operations.active_storefronts }) : $t('dashboard.liveWebStores', { count: dashboardData.operations.active_storefronts }) }}
        </p>
      </div>
    </div>

    <!-- Charts & Analytics Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Revenue Trend Line Chart -->
      <div class="lg:col-span-2 bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-sm flex flex-col justify-between">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
          <div>
            <h3 class="text-base sm:text-lg font-bold text-slate-900">{{ $t('dashboard.salesRevenueTrend') }}</h3>
            <p class="text-xs text-slate-500">{{ $t('dashboard.salesTrendSubtitle') }}</p>
          </div>
          <button
            @click="router.push('/accounting')"
            class="text-xs font-semibold text-primary-600 hover:text-primary-700 flex items-center space-x-1"
          >
            <span>{{ $t('dashboard.ledgerDetails') }}</span>
            <ArrowRight class="w-3.5 h-3.5" />
          </button>
        </div>

        <div class="h-64 sm:h-72 w-full">
          <Line :data="revenueChartData" :options="lineChartOptions" />
        </div>
      </div>

      <!-- Inventory Breakdown Doughnut Chart -->
      <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-sm flex flex-col justify-between">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 class="text-base sm:text-lg font-bold text-slate-900">{{ $t('dashboard.inventoryHealth') }}</h3>
            <p class="text-xs text-slate-500">{{ $t('dashboard.inventoryHealthSubtitle') }}</p>
          </div>
          <button
            @click="router.push('/inventory/products')"
            class="text-xs font-semibold text-primary-600 hover:text-primary-700"
          >
            {{ $t('dashboard.manage') }}
          </button>
        </div>

        <div class="h-56 sm:h-64 relative flex items-center justify-center">
          <Doughnut :data="inventoryChartData" :options="doughnutChartOptions" />
        </div>

        <div class="grid grid-cols-3 gap-2 pt-4 border-t border-slate-100 text-center text-xs">
          <div>
            <span class="text-slate-400 block text-[10px] uppercase font-bold">{{ $t('dashboard.optimalStock') }}</span>
            <span class="font-bold text-emerald-600">{{ dashboardData.inventory.optimal_count }}</span>
          </div>
          <div>
            <span class="text-slate-400 block text-[10px] uppercase font-bold">{{ $t('dashboard.lowStock') }}</span>
            <span class="font-bold text-amber-600">{{ dashboardData.inventory.low_stock_count }}</span>
          </div>
          <div>
            <span class="text-slate-400 block text-[10px] uppercase font-bold">{{ $t('dashboard.outOfStock') }}</span>
            <span class="font-bold text-red-600">{{ dashboardData.inventory.out_of_stock_count }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Operational Split Grid (Low Stock Alerts & Recent Activity) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- 1. Low Stock & Critical Inventory Alerts -->
      <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-2">
            <AlertTriangle class="w-5 h-5 text-amber-500" />
            <h3 class="text-base font-bold text-slate-900">{{ $t('dashboard.stockReorderAlerts') }}</h3>
          </div>
          <button
            @click="router.push('/inventory/stock')"
            class="text-xs font-semibold text-primary-600 hover:text-primary-700"
          >
            {{ $t('dashboard.viewAllStock') }}
          </button>
        </div>

        <div v-if="dashboardData.inventory.low_stock_items.length === 0" class="text-center py-10 bg-slate-50 rounded-xl border border-slate-100 space-y-2">
          <ShieldCheck class="w-10 h-10 text-emerald-500 mx-auto" />
          <p class="text-xs sm:text-sm font-semibold text-slate-700">{{ $t('dashboard.allStockOptimal') }}</p>
          <p class="text-[11px] text-slate-400">{{ $t('dashboard.noItemsBelowMin') }}</p>
        </div>

        <div v-else class="space-y-2.5">
          <div
            v-for="item in dashboardData.inventory.low_stock_items"
            :key="item.id"
            class="flex items-center justify-between p-3 rounded-xl border border-slate-100 bg-slate-50/70 hover:bg-slate-100 transition-colors"
          >
            <div class="space-y-0.5 truncate pr-2">
              <h4 class="font-bold text-xs text-slate-900 truncate">{{ item.name }}</h4>
              <p class="text-[11px] font-mono text-slate-400">{{ $t('dashboard.sku', { sku: item.sku }) }}</p>
            </div>
            <div class="flex items-center space-x-3 shrink-0">
              <div class="text-right">
                <span :class="['text-xs font-bold', item.available_quantity === 0 ? 'text-red-600' : 'text-amber-600']">
                  {{ $t('dashboard.inStock', { count: item.available_quantity }) }}
                </span>
                <p class="text-[10px] text-slate-400">{{ $t('dashboard.minQuantity', { count: item.min_quantity }) }}</p>
              </div>
              <button
                @click="router.push('/procurement/purchase-orders')"
                class="px-2.5 py-1 text-xs font-semibold rounded bg-slate-900 text-white hover:bg-primary-600 transition-colors"
              >
                {{ $t('dashboard.reorder') }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- 2. Recent Sales & Orders Stream -->
      <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-2">
            <Clock class="w-5 h-5 text-indigo-500" />
            <h3 class="text-base font-bold text-slate-900">{{ $t('dashboard.recentTransactions') }}</h3>
          </div>
          <button
            @click="router.push('/sales/invoices')"
            class="text-xs font-semibold text-primary-600 hover:text-primary-700"
          >
            {{ $t('dashboard.salesLedger') }}
          </button>
        </div>

        <div v-if="dashboardData.operations.recent_orders.length === 0" class="text-center py-10 bg-slate-50 rounded-xl border border-slate-100 space-y-2">
          <ShoppingCart class="w-10 h-10 text-slate-300 mx-auto" />
          <p class="text-xs sm:text-sm font-semibold text-slate-700">{{ $t('dashboard.noTransactionsYet') }}</p>
          <p class="text-[11px] text-slate-400">{{ $t('dashboard.realtimeStreamNotice') }}</p>
        </div>

        <div v-else class="space-y-2.5">
          <div
            v-for="(ord, idx) in dashboardData.operations.recent_orders"
            :key="idx"
            class="flex items-center justify-between p-3 rounded-xl border border-slate-100 bg-slate-50/70"
          >
            <div class="space-y-0.5 truncate pr-2">
              <div class="flex items-center space-x-2">
                <span class="font-mono font-bold text-xs text-slate-900">{{ ord.number }}</span>
                <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-200 font-semibold text-slate-700">
                  {{ ord.channel }}
                </span>
              </div>
              <p class="text-[11px] text-slate-500 truncate">{{ ord.customer }}</p>
            </div>
            <div class="text-right shrink-0">
              <span class="font-black text-xs sm:text-sm text-slate-900">{{ formatCents(ord.total_cents) }}</span>
              <p class="text-[10px] text-slate-400">{{ formatDate(ord.created_at) }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
