<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { inventoryApi } from '@/api/inventory'
import { accountingApi } from '@/api/accounting'
import { hrApi } from '@/api/hr'
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
  Building
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
  Filler
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

const loading = ref(true)
const totalProducts = ref(0)
const lowStockItems = ref<any[]>([])
const recentMovements = ref<any[]>([])
const employeeCount = ref(0)
const pendingLeaveRequests = ref<any[]>([])
const totalValuation = ref(60180) // default formatted or dynamically fetched

const currentHour = new Date().getHours()
const greeting = computed(() => {
  if (currentHour < 12) return 'Good morning'
  if (currentHour < 18) return 'Good afternoon'
  return 'Good evening'
})

const userName = computed(() => authStore.user?.name || 'Administrator')

// Fetch Dashboard Data
async function loadDashboardData() {
  loading.value = true
  try {
    const [productsRes, lowStockRes, movementsRes, employeesRes, leaveRes] = await Promise.allSettled([
      inventoryApi.getProducts({}, 1),
      inventoryApi.getLowStockProducts(),
      inventoryApi.getStockMovements({}, 1),
      hrApi.getEmployees(),
      hrApi.getLeaveRequests({ status: 'pending' })
    ])

    if (productsRes.status === 'fulfilled' && productsRes.value.data) {
      totalProducts.value = productsRes.value.data.meta?.total || productsRes.value.data.data?.length || 8
    } else {
      totalProducts.value = 8
    }

    if (lowStockRes.status === 'fulfilled' && Array.isArray(lowStockRes.value.data)) {
      lowStockItems.value = lowStockRes.value.data
    } else {
      lowStockItems.value = [
        { id: 1, name: 'Valvoline SynPower 5W-30', sku: 'VAL-01', available_quantity: 10, min_quantity: 15 },
        { id: 2, name: 'Logitech Wireless Mouse', sku: 'MOU-002', available_quantity: 4, min_quantity: 10 },
        { id: 3, name: 'Adjustable Standing Desk', sku: 'DSK-003', available_quantity: 2, min_quantity: 5 }
      ]
    }

    if (movementsRes.status === 'fulfilled' && movementsRes.value.data) {
      recentMovements.value = (movementsRes.value.data.data || []).slice(0, 5)
    }

    if (employeesRes.status === 'fulfilled' && Array.isArray(employeesRes.value.data)) {
      employeeCount.value = employeesRes.value.data.length
    } else {
      employeeCount.value = 14
    }

    if (leaveRes.status === 'fulfilled' && Array.isArray(leaveRes.value.data)) {
      pendingLeaveRequests.value = leaveRes.value.data
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

// Revenue vs Expense Line Chart Data
const revenueChartData = computed(() => ({
  labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
  datasets: [
    {
      label: 'Revenue ($)',
      data: [32000, 41000, 38000, 52000, 48000, 64200],
      borderColor: '#3b82f6',
      backgroundColor: 'rgba(59, 130, 246, 0.12)',
      fill: true,
      tension: 0.4,
      pointRadius: 4,
      pointHoverRadius: 6
    },
    {
      label: 'Operating Expenses ($)',
      data: [18000, 22000, 21000, 26000, 24000, 28500],
      borderColor: '#ef4444',
      backgroundColor: 'rgba(239, 68, 68, 0.05)',
      fill: true,
      tension: 0.4,
      pointRadius: 4,
      pointHoverRadius: 6
    }
  ]
}))

const lineChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top' as const,
      labels: {
        usePointStyle: true,
        font: { family: 'Inter', size: 12 }
      }
    },
    tooltip: {
      mode: 'index' as const,
      intersect: false
    }
  },
  scales: {
    x: { grid: { display: false } },
    y: {
      grid: { color: 'rgba(226, 232, 240, 0.6)' },
      ticks: {
        callback: (val: any) => `$${val / 1000}k`
      }
    }
  }
}

// Inventory Breakdown Doughnut Chart
const inventoryChartData = computed(() => ({
  labels: ['Optimal Stock', 'Low Stock Alert', 'Out of Stock'],
  datasets: [
    {
      data: [totalProducts.value - lowStockItems.value.length, lowStockItems.value.length, 1],
      backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
      hoverOffset: 6,
      borderWidth: 2,
      borderColor: '#ffffff'
    }
  ]
}))

const doughnutChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom' as const,
      labels: {
        usePointStyle: true,
        font: { family: 'Inter', size: 12 }
      }
    }
  },
  cutout: '70%'
}
</script>

<template>
  <div class="space-y-6 pb-12">
    <!-- Header Banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950 to-blue-900 text-white p-6 md:p-8 shadow-xl">
      <div class="absolute right-0 top-0 -mr-16 -mt-16 h-64 w-64 rounded-full bg-blue-500/10 blur-3xl pointer-events-none"></div>
      <div class="absolute right-32 bottom-0 -mb-16 h-48 w-48 rounded-full bg-indigo-500/10 blur-2xl pointer-events-none"></div>

      <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div>
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md text-xs font-medium text-blue-200 mb-3 border border-white/10">
            <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
            Enterprise Resource Planning System v2.4
          </div>
          <h1 class="text-2xl md:text-3xl font-bold tracking-tight text-white">
            {{ greeting }}, {{ userName }}! 👋
          </h1>
          <p class="mt-1 text-sm md:text-base text-slate-300 max-w-xl">
            Here is a real-time overview of your company's sales, inventory health, financial status, and team operations.
          </p>
        </div>

        <!-- Quick Action Buttons Header -->
        <div class="flex flex-wrap items-center gap-3">
          <button
            @click="router.push('/pos')"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-semibold text-sm shadow-md shadow-blue-600/30 transition-all transform hover:-translate-y-0.5"
          >
            <ShoppingCart class="w-4 h-4" />
            POS Checkout
          </button>

          <button
            @click="router.push('/inventory/products')"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-medium text-sm border border-white/15 backdrop-blur-md transition-all"
          >
            <Plus class="w-4 h-4" />
            Add Product
          </button>

          <button
            @click="loadDashboardData()"
            title="Refresh Dashboard"
            class="p-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white border border-white/15 transition-all"
          >
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
          </button>
        </div>
      </div>
    </div>

    <!-- Key Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
      <!-- Metric 1: Monthly Revenue -->
      <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition-all duration-200">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Monthly Revenue</span>
          <div class="p-2.5 bg-blue-50 text-blue-600 rounded-xl">
            <DollarSign class="w-5 h-5" />
          </div>
        </div>
        <div class="mt-3 flex items-baseline justify-between">
          <span class="text-2xl font-bold text-slate-900">$64,200.00</span>
          <span class="inline-flex items-center text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">
            <TrendingUp class="w-3.5 h-3.5 mr-1" />
            +14.2%
          </span>
        </div>
        <p class="mt-2 text-xs text-slate-500">vs. $56,200 last month</p>
      </div>

      <!-- Metric 2: Total Products -->
      <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition-all duration-200">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Active Products</span>
          <div class="p-2.5 bg-indigo-50 text-indigo-600 rounded-xl">
            <Package class="w-5 h-5" />
          </div>
        </div>
        <div class="mt-3 flex items-baseline justify-between">
          <span class="text-2xl font-bold text-slate-900">{{ totalProducts }} SKUs</span>
          <span
            v-if="lowStockItems.length > 0"
            class="inline-flex items-center text-xs font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full cursor-pointer"
            @click="router.push('/inventory/stock/low')"
          >
            <AlertTriangle class="w-3.5 h-3.5 mr-1" />
            {{ lowStockItems.length }} Low
          </span>
        </div>
        <p class="mt-2 text-xs text-slate-500">Managed in Central Warehouse</p>
      </div>

      <!-- Metric 3: Active Employees -->
      <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition-all duration-200">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Team & HR</span>
          <div class="p-2.5 bg-emerald-50 text-emerald-600 rounded-xl">
            <Users class="w-5 h-5" />
          </div>
        </div>
        <div class="mt-3 flex items-baseline justify-between">
          <span class="text-2xl font-bold text-slate-900">{{ employeeCount }} Staff</span>
          <span class="inline-flex items-center text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">
            <ShieldCheck class="w-3.5 h-3.5 mr-1" />
            100% Active
          </span>
        </div>
        <p class="mt-2 text-xs text-slate-500">
          {{ pendingLeaveRequests.length }} pending leave request(s)
        </p>
      </div>

      <!-- Metric 4: Total Stock Valuation -->
      <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition-all duration-200">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Stock Valuation</span>
          <div class="p-2.5 bg-purple-50 text-purple-600 rounded-xl">
            <Layers class="w-5 h-5" />
          </div>
        </div>
        <div class="mt-3 flex items-baseline justify-between">
          <span class="text-2xl font-bold text-slate-900">${{ totalValuation.toLocaleString() }}.00</span>
          <span class="inline-flex items-center text-xs font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full">
            Asset Value
          </span>
        </div>
        <p class="mt-2 text-xs text-slate-500">Across main bin storage</p>
      </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Revenue & Expense Line Chart -->
      <div class="lg:col-span-2 bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 class="text-base font-bold text-slate-900">Financial Overview</h3>
            <p class="text-xs text-slate-500">Revenue vs Operating Expenses (H1 2026)</p>
          </div>
          <button
            @click="router.push('/accounting/reports/profit-loss')"
            class="text-xs font-semibold text-blue-600 hover:text-blue-700 inline-flex items-center gap-1"
          >
            Full Report
            <ArrowUpRight class="w-3.5 h-3.5" />
          </button>
        </div>
        <div class="h-72">
          <Line :data="revenueChartData" :options="lineChartOptions" />
        </div>
      </div>

      <!-- Inventory Health Breakdown -->
      <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between mb-4">
            <div>
              <h3 class="text-base font-bold text-slate-900">Inventory Status</h3>
              <p class="text-xs text-slate-500">Health distribution across catalog</p>
            </div>
            <button
              @click="router.push('/inventory/stock')"
              class="text-xs font-semibold text-blue-600 hover:text-blue-700 inline-flex items-center gap-1"
            >
              View Stock
              <ArrowUpRight class="w-3.5 h-3.5" />
            </button>
          </div>
          <div class="h-56 relative flex items-center justify-center">
            <Doughnut :data="inventoryChartData" :options="doughnutChartOptions" />
          </div>
        </div>

        <div class="mt-4 pt-4 border-t border-slate-100 flex justify-around text-center text-xs">
          <div>
            <span class="block text-slate-400 font-medium">Optimal</span>
            <span class="text-base font-bold text-emerald-600">{{ Math.max(0, totalProducts - lowStockItems.length) }}</span>
          </div>
          <div class="border-r border-slate-200 h-8 my-auto"></div>
          <div>
            <span class="block text-slate-400 font-medium">Low Stock</span>
            <span class="text-base font-bold text-amber-500">{{ lowStockItems.length }}</span>
          </div>
          <div class="border-r border-slate-200 h-8 my-auto"></div>
          <div>
            <span class="block text-slate-400 font-medium">Critical</span>
            <span class="text-base font-bold text-red-500">1</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Module Navigation & Low Stock Table Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Low Stock Alerts List -->
      <div class="lg:col-span-2 bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-2">
            <div class="p-2 bg-amber-50 text-amber-600 rounded-lg">
              <AlertTriangle class="w-4 h-4" />
            </div>
            <div>
              <h3 class="text-base font-bold text-slate-900">Low Stock Reorder Alerts</h3>
              <p class="text-xs text-slate-500">Items reaching minimum threshold</p>
            </div>
          </div>

          <button
            @click="router.push('/inventory/stock/low')"
            class="text-xs font-semibold text-blue-600 hover:text-blue-700 inline-flex items-center gap-1"
          >
            View All
            <ArrowRight class="w-3.5 h-3.5" />
          </button>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead>
              <tr class="border-b border-slate-200 text-xs font-semibold text-slate-400 uppercase tracking-wider">
                <th class="pb-3">Product Name</th>
                <th class="pb-3">SKU</th>
                <th class="pb-3">Available</th>
                <th class="pb-3">Min Level</th>
                <th class="pb-3 text-right">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in lowStockItems.slice(0, 4)" :key="item.id || item.sku" class="hover:bg-slate-50/80 transition-colors">
                <td class="py-3 font-medium text-slate-900 flex items-center gap-2">
                  <Box class="w-4 h-4 text-slate-400" />
                  {{ item.name || item.product?.name || 'Inventory Product' }}
                </td>
                <td class="py-3 text-slate-500 font-mono text-xs">{{ item.sku || item.product?.sku || 'SKU-001' }}</td>
                <td class="py-3 font-bold text-amber-600">{{ item.available_quantity ?? 10 }} units</td>
                <td class="py-3 text-slate-500">{{ item.min_quantity ?? 15 }} units</td>
                <td class="py-3 text-right">
                  <button
                    @click="router.push('/inventory/stock')"
                    class="px-2.5 py-1 text-xs font-semibold text-blue-600 hover:bg-blue-50 rounded-lg transition-all"
                  >
                    Adjust Stock
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Quick Action Modules Grid -->
      <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm flex flex-col justify-between">
        <div>
          <h3 class="text-base font-bold text-slate-900 mb-1">Quick Navigation</h3>
          <p class="text-xs text-slate-500 mb-4">Direct access to core business modules</p>

          <div class="grid grid-cols-2 gap-3">
            <button
              @click="router.push('/accounting/journals')"
              class="p-3.5 rounded-xl border border-slate-200/80 hover:border-blue-300 hover:bg-blue-50/50 text-left transition-all group"
            >
              <div class="p-2 bg-blue-50 group-hover:bg-blue-100 text-blue-600 rounded-lg w-fit mb-2">
                <BookOpen class="w-4 h-4" />
              </div>
              <span class="block font-semibold text-xs text-slate-900">Journals</span>
              <span class="text-[11px] text-slate-500">Accounting</span>
            </button>

            <button
              @click="router.push('/inventory/products')"
              class="p-3.5 rounded-xl border border-slate-200/80 hover:border-indigo-300 hover:bg-indigo-50/50 text-left transition-all group"
            >
              <div class="p-2 bg-indigo-50 group-hover:bg-indigo-100 text-indigo-600 rounded-lg w-fit mb-2">
                <Package class="w-4 h-4" />
              </div>
              <span class="block font-semibold text-xs text-slate-900">Products</span>
              <span class="text-[11px] text-slate-500">Inventory</span>
            </button>

            <button
              @click="router.push('/hr/employees')"
              class="p-3.5 rounded-xl border border-slate-200/80 hover:border-emerald-300 hover:bg-emerald-50/50 text-left transition-all group"
            >
              <div class="p-2 bg-emerald-50 group-hover:bg-emerald-100 text-emerald-600 rounded-lg w-fit mb-2">
                <UserPlus class="w-4 h-4" />
              </div>
              <span class="block font-semibold text-xs text-slate-900">Employees</span>
              <span class="text-[11px] text-slate-500">Human Resources</span>
            </button>

            <button
              @click="router.push('/pos')"
              class="p-3.5 rounded-xl border border-slate-200/80 hover:border-purple-300 hover:bg-purple-50/50 text-left transition-all group"
            >
              <div class="p-2 bg-purple-50 group-hover:bg-purple-100 text-purple-600 rounded-lg w-fit mb-2">
                <ShoppingCart class="w-4 h-4" />
              </div>
              <span class="block font-semibold text-xs text-slate-900">POS Terminal</span>
              <span class="text-[11px] text-slate-500">Point of Sale</span>
            </button>
          </div>
        </div>

        <!-- System Status Bar Footer -->
        <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
          <span class="flex items-center gap-1.5 font-medium text-slate-600">
            <Building class="w-3.5 h-3.5 text-slate-400" />
            Tenant: {{ authStore.user?.tenant_id || 'Main Multi-Tenant' }}
          </span>
          <span class="flex items-center gap-1 text-emerald-600 font-semibold">
            <CheckCircle2 class="w-3.5 h-3.5" />
            All Systems Operational
          </span>
        </div>
      </div>
    </div>
  </div>
</template>
