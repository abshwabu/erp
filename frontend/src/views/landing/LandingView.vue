<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AppLogo from '@/components/shared/AppLogo.vue'
import {
  LayoutDashboard,
  ShoppingCart,
  Package,
  Warehouse,
  Banknote,
  Users,
  CheckCircle2,
  ArrowRight,
  ShieldCheck,
  Zap,
  TrendingUp,
  BarChart3,
  Sparkles,
  Calculator,
  Globe,
  Clock,
  Layers,
  FileText,
  Lock,
  Server,
  Check,
  ChevronDown,
  Menu,
  X,
  Receipt,
  DollarSign,
  Star,
  Activity,
  Boxes,
  Plus,
  Trash2,
  RefreshCw,
  Sliders,
  Cpu,
  ArrowUpRight,
  Database,
  Building2,
  Workflow
} from '@lucide/vue'

const router = useRouter()
const authStore = useAuthStore()

// Navigation & Scroll State
const isScrolled = ref(false)
const mobileMenuOpen = ref(false)

const handleScroll = () => {
  isScrolled.value = window.scrollY > 20
}

onMounted(() => {
  window.addEventListener('scroll', handleScroll)
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
})

const navigateToAuth = (type: 'login' | 'register') => {
  router.push(`/${type}`)
}

const navigateToDashboard = () => {
  router.push('/dashboard')
}

// -------------------------------------------------------------
// Interactive Live Sandbox Mockups
// -------------------------------------------------------------
const activeTab = ref<'analytics' | 'pos' | 'inventory' | 'invoicing' | 'hr'>('analytics')

// POS Sandbox Interactive State
const posCart = ref([
  { id: 1, name: 'Premium Espresso Roast (1kg)', price: 34.50, qty: 2 },
  { id: 2, name: 'Stainless Milk Pitcher 600ml', price: 18.00, qty: 1 },
])
const posCatalog = [
  { id: 1, name: 'Premium Espresso Roast (1kg)', price: 34.50, category: 'Beverage', icon: '☕' },
  { id: 2, name: 'Stainless Milk Pitcher 600ml', price: 18.00, category: 'Hardware', icon: '🥛' },
  { id: 3, name: 'Organic Oat Milk 1L', price: 4.20, category: 'Beverage', icon: '🌾' },
  { id: 4, name: 'Portafilter Handle 58mm', price: 42.00, category: 'Hardware', icon: '⚙️' },
]

const addPosItem = (item: typeof posCatalog[0]) => {
  const existing = posCart.value.find(c => c.id === item.id)
  if (existing) {
    existing.qty++
  } else {
    posCart.value.push({ id: item.id, name: item.name, price: item.price, qty: 1 })
  }
}

const removePosItem = (id: number) => {
  posCart.value = posCart.value.filter(c => c.id !== id)
}

const posSubtotal = computed(() => {
  return posCart.value.reduce((acc, item) => acc + item.price * item.qty, 0)
})
const posTax = computed(() => posSubtotal.value * 0.15)
const posTotal = computed(() => posSubtotal.value + posTax.value)

// -------------------------------------------------------------
// Interactive ROI & Productivity Calculator
// -------------------------------------------------------------
const teamSize = ref(24)
const hourlyWage = ref(35)
const manualHoursPerWeek = computed(() => Math.round(teamSize.value * 6.5))
const monthlyCostSaved = computed(() => {
  const weeklyHours = manualHoursPerWeek.value
  const weeklyDollars = weeklyHours * hourlyWage.value * 0.72 // 72% automation factor
  return Math.round(weeklyDollars * 4.33)
})
const yearlyCostSaved = computed(() => monthlyCostSaved.value * 12)
const hoursSavedPerYear = computed(() => Math.round(manualHoursPerWeek.value * 52 * 0.72))

// -------------------------------------------------------------
// Pricing Plans State
// -------------------------------------------------------------
const isAnnual = ref(true)

const pricingTiers = [
  {
    name: 'Starter Cloud',
    badge: 'Small Businesses',
    monthlyPrice: 39,
    annualPrice: 29,
    description: 'Essential POS, inventory tracking, and invoicing for growing single-location businesses.',
    features: [
      'Up to 3 Cashier / User Seats',
      'Point of Sale with Offline Sync',
      'Basic Inventory & Stock Management',
      'Sales Invoicing & Receipt Printing',
      'Daily Revenue & Tax Summaries',
      'Standard Email & Ticket Support'
    ],
    highlight: false,
    cta: 'Start 14-Day Free Trial'
  },
  {
    name: 'Professional Enterprise',
    badge: 'Most Popular',
    monthlyPrice: 99,
    annualPrice: 79,
    description: 'Full-featured ERP suite for multi-branch retailers, manufacturers, and growing teams.',
    features: [
      'Up to 15 Team Seats + Unlimited POS',
      'Double-Entry Accounting & Ledger Engine',
      'Multi-Warehouse Logistics & Transfers',
      'Automated HR & Payroll Calculation',
      'CRM Pipeline & Lead Generation Forms',
      'Project Task Boards & Timesheets',
      'Priority 24/7 SLA Support & API Access'
    ],
    highlight: true,
    cta: 'Get Started with Pro'
  },
  {
    name: 'Enterprise Scale',
    badge: 'Large Organizations',
    monthlyPrice: 249,
    annualPrice: 199,
    description: 'Uncapped power, dedicated isolated database tenancy, custom integrations, and SLA.',
    features: [
      'Unlimited Seats & POS Terminals',
      'Multi-Tenant Tenant Isolation & White-Label',
      'Advanced Manufacturing & Work Orders',
      'Custom ERP Workflows & Webhook Triggers',
      'Omni-Channel E-Commerce Storefronts',
      'Dedicated Account Manager & Data Migration',
      '99.99% Guaranteed SLA Uptime'
    ],
    highlight: false,
    cta: 'Contact Enterprise Team'
  }
]

// -------------------------------------------------------------
// FAQ Accordion State
// -------------------------------------------------------------
const openFaq = ref<number | null>(0)
const toggleFaq = (index: number) => {
  openFaq.value = openFaq.value === index ? null : index
}

const faqs = [
  {
    q: 'Can the Point of Sale (POS) run concurrently across multiple cashier registers?',
    a: 'Yes! Bina ERP is built with a real-time POS session management engine and native BroadcastChannel synchronization. Cashiers can open separate concurrent sessions across various tills, process transactions simultaneously, and hold/resume customer orders with zero race conditions.'
  },
  {
    q: 'Does Bina ERP support compliant printable receipts with Company TIN & tax breakdowns?',
    a: 'Absolutely. Every printable receipt, sales invoice, and procurement PO includes your registered company name, address, tax identification number (TIN), itemized tax rates (VAT/GST), and thermal printer formatting (58mm, 80mm, or standard A4 PDF).'
  },
  {
    q: 'How does Multi-Tenancy work and is our company data isolated?',
    a: 'Bina ERP offers strict database isolation and workspace-level tenant security. Role-based access controls (RBAC) ensure that cashiers, warehouse managers, accountants, and HR officers only access their authorized departments.'
  },
  {
    q: 'Can we integrate external hardware like thermal receipt printers and barcode scanners?',
    a: 'Yes! Our web POS client natively interfaces with standard USB, Bluetooth, and ESC/POS thermal printers as well as standard 1D/2D optical barcode scanners with instant autofocus detection.'
  },
  {
    q: 'How seamless is migrating our legacy data from Excel or other ERP systems?',
    a: 'Bina ERP provides one-click CSV and Excel import wizards for your chart of accounts, existing product catalogs, customer contacts, employee profiles, and inventory opening balances.'
  }
]
</script>

<template>
  <div class="min-h-screen bg-[#070a12] text-slate-100 font-sans selection:bg-cyan-500/30 selection:text-cyan-200 relative overflow-x-hidden">
    
    <!-- Dynamic Ambient Background Glows -->
    <div class="fixed inset-0 pointer-events-none z-0 overflow-hidden">
      <div class="absolute top-[-10%] left-[20%] w-[650px] h-[650px] bg-cyan-600/10 rounded-full blur-[140px]"></div>
      <div class="absolute top-[30%] right-[5%] w-[550px] h-[550px] bg-indigo-600/10 rounded-full blur-[140px]"></div>
      <div class="absolute top-[65%] left-[10%] w-[700px] h-[700px] bg-emerald-600/5 rounded-full blur-[160px]"></div>
      <!-- Subtle Grid Pattern -->
      <div class="absolute inset-0 bg-[linear-gradient(to_right,#ffffff05_1px,transparent_1px),linear-gradient(to_bottom,#ffffff05_1px,transparent_1px)] bg-[size:4rem_4rem] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_0%,#000_70%,transparent_100%)]"></div>
    </div>

    <!-- ========================================================= -->
    <!-- HEADER / NAVIGATION BAR -->
    <!-- ========================================================= -->
    <header 
      class="fixed top-0 inset-x-0 z-50 transition-all duration-300 backdrop-blur-xl"
      :class="[isScrolled ? 'bg-[#070a12]/85 border-b border-slate-800/80 shadow-2xl shadow-black/40 py-3.5' : 'bg-transparent py-5']"
    >
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between">
        
        <!-- Logo -->
        <router-link to="/" class="flex items-center gap-3 group">
          <AppLogo light size="sm" />
          <span class="hidden sm:inline-flex items-center gap-1.5 px-2 py-0.5 text-[11px] font-semibold tracking-wide bg-cyan-950/60 border border-cyan-800/50 text-cyan-400 rounded-full">
            <span class="w-1.5 h-1.5 rounded-full bg-cyan-400 animate-pulse"></span>
            v2.5 Cloud
          </span>
        </router-link>

        <!-- Desktop Navigation Links -->
        <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-300">
          <a href="#features" class="hover:text-cyan-400 transition-colors">Features</a>
          <a href="#live-sandbox" class="hover:text-cyan-400 transition-colors">Live Preview</a>
          <a href="#roi-calculator" class="hover:text-cyan-400 transition-colors">ROI Calculator</a>
          <a href="#pricing" class="hover:text-cyan-400 transition-colors">Pricing</a>
          <a href="#faq" class="hover:text-cyan-400 transition-colors">FAQ</a>
        </nav>

        <!-- Header Actions -->
        <div class="hidden sm:flex items-center gap-3">
          <template v-if="authStore.isAuthenticated">
            <button 
              @click="navigateToDashboard"
              class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-white shadow-lg shadow-cyan-500/20 transition-all hover:scale-[1.02] active:scale-[0.98]"
            >
              <LayoutDashboard :size="16" />
              <span>Go to Dashboard</span>
              <ArrowRight :size="15" />
            </button>
          </template>
          <template v-else>
            <button 
              @click="navigateToAuth('login')"
              class="px-4 py-2 text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-800/60 rounded-lg transition-colors"
            >
              Sign In
            </button>
            <button 
              @click="navigateToAuth('register')"
              class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-white shadow-lg shadow-cyan-500/20 transition-all hover:scale-[1.02] active:scale-[0.98]"
            >
              <span>Get Started Free</span>
              <ArrowRight :size="15" />
            </button>
          </template>
        </div>

        <!-- Mobile Menu Toggle -->
        <button 
          @click="mobileMenuOpen = !mobileMenuOpen"
          class="md:hidden p-2 text-slate-400 hover:text-white hover:bg-slate-800/60 rounded-lg"
          aria-label="Toggle Menu"
        >
          <Menu v-if="!mobileMenuOpen" :size="22" />
          <X v-else :size="22" />
        </button>
      </div>

      <!-- Mobile Dropdown Navigation -->
      <div v-if="mobileMenuOpen" class="md:hidden border-b border-slate-800 bg-[#090e1a]/95 px-6 py-5 space-y-4 shadow-2xl backdrop-blur-2xl">
        <nav class="flex flex-col space-y-3 text-base font-medium text-slate-300">
          <a @click="mobileMenuOpen = false" href="#features" class="hover:text-cyan-400 py-1">Features</a>
          <a @click="mobileMenuOpen = false" href="#live-sandbox" class="hover:text-cyan-400 py-1">Live Preview</a>
          <a @click="mobileMenuOpen = false" href="#roi-calculator" class="hover:text-cyan-400 py-1">ROI Calculator</a>
          <a @click="mobileMenuOpen = false" href="#pricing" class="hover:text-cyan-400 py-1">Pricing</a>
          <a @click="mobileMenuOpen = false" href="#faq" class="hover:text-cyan-400 py-1">FAQ</a>
        </nav>
        <div class="pt-4 border-t border-slate-800/80 flex flex-col gap-2.5">
          <template v-if="authStore.isAuthenticated">
            <button 
              @click="navigateToDashboard"
              class="w-full justify-center inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-lg bg-cyan-500 text-white shadow-lg shadow-cyan-500/20"
            >
              <LayoutDashboard :size="16" />
              <span>Go to Dashboard</span>
            </button>
          </template>
          <template v-else>
            <button 
              @click="navigateToAuth('login')"
              class="w-full py-2.5 text-center text-sm font-medium text-slate-300 bg-slate-800/60 rounded-lg"
            >
              Sign In
            </button>
            <button 
              @click="navigateToAuth('register')"
              class="w-full py-2.5 text-center text-sm font-semibold bg-gradient-to-r from-cyan-500 to-blue-600 text-white rounded-lg shadow-lg shadow-cyan-500/20"
            >
              Get Started Free
            </button>
          </template>
        </div>
      </div>
    </header>

    <!-- ========================================================= -->
    <!-- HERO SECTION -->
    <!-- ========================================================= -->
    <section class="relative pt-32 pb-20 md:pt-40 md:pb-32 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto text-center z-10">
      
      <!-- Top Announcement Pill -->
      <div class="inline-flex items-center gap-2.5 px-3.5 py-1.5 rounded-full bg-slate-900/80 border border-slate-700/60 text-xs sm:text-sm font-medium text-slate-300 shadow-xl mb-8 backdrop-blur-md">
        <span class="flex h-2 w-2 rounded-full bg-cyan-400 animate-ping"></span>
        <span class="text-cyan-400 font-semibold">Bina ERP 2.5</span>
        <span class="text-slate-600">|</span>
        <span class="text-slate-300">Multi-Session POS, Real-Time Accounting & Omnichannel</span>
        <ArrowRight :size="14" class="text-slate-400" />
      </div>

      <!-- Main Headline -->
      <h1 class="text-4xl sm:text-6xl lg:text-7xl font-extrabold tracking-tight text-white max-w-5xl mx-auto leading-[1.12]">
        The Modern Operating System for <br class="hidden sm:block"/>
        <span class="bg-clip-text text-transparent bg-gradient-to-r from-cyan-400 via-blue-400 to-indigo-400">
          Agile Enterprises & Retail Chains
        </span>
      </h1>

      <!-- Subtitle -->
      <p class="mt-6 text-lg sm:text-xl text-slate-400 max-w-3xl mx-auto leading-relaxed font-normal">
        Unify your entire enterprise across high-speed multi-cashier POS, compliant double-entry accounting, smart warehouse inventory, automated payroll, and customer pipelines in one seamless cloud workspace.
      </p>

      <!-- Hero Action CTAs -->
      <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4 max-w-md mx-auto sm:max-w-none">
        <button 
          @click="authStore.isAuthenticated ? navigateToDashboard() : navigateToAuth('register')"
          class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-8 py-4 text-base font-bold rounded-xl bg-gradient-to-r from-cyan-500 via-blue-600 to-indigo-600 hover:from-cyan-400 hover:to-indigo-500 text-white shadow-xl shadow-cyan-500/25 transition-all duration-200 hover:scale-[1.03] active:scale-[0.98]"
        >
          <span>{{ authStore.isAuthenticated ? 'Open ERP Workspace' : 'Start 14-Day Free Trial' }}</span>
          <ArrowRight :size="18" />
        </button>
        <a 
          href="#live-sandbox"
          class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-4 text-base font-semibold rounded-xl bg-slate-900/90 hover:bg-slate-800 border border-slate-700/80 text-slate-200 transition-all duration-200 hover:border-slate-600"
        >
          <Sparkles :size="18" class="text-cyan-400" />
          <span>Explore Interactive Sandbox</span>
        </a>
      </div>

      <!-- Trust Metrics & Compliance Bar -->
      <div class="mt-16 pt-10 border-t border-slate-800/60 grid grid-cols-2 md:grid-cols-4 gap-6 max-w-4xl mx-auto text-left">
        <div class="flex items-center gap-3.5 p-3 rounded-xl bg-slate-900/40 border border-slate-800/40">
          <div class="p-2.5 rounded-lg bg-cyan-500/10 text-cyan-400 shrink-0">
            <Zap :size="22" />
          </div>
          <div>
            <div class="text-lg font-bold text-white">&lt; 85ms</div>
            <div class="text-xs text-slate-400">POS Execution Latency</div>
          </div>
        </div>

        <div class="flex items-center gap-3.5 p-3 rounded-xl bg-slate-900/40 border border-slate-800/40">
          <div class="p-2.5 rounded-lg bg-emerald-500/10 text-emerald-400 shrink-0">
            <ShieldCheck :size="22" />
          </div>
          <div>
            <div class="text-lg font-bold text-white">99.99%</div>
            <div class="text-xs text-slate-400">Cloud High Availability</div>
          </div>
        </div>

        <div class="flex items-center gap-3.5 p-3 rounded-xl bg-slate-900/40 border border-slate-800/40">
          <div class="p-2.5 rounded-lg bg-blue-500/10 text-blue-400 shrink-0">
            <Database :size="22" />
          </div>
          <div>
            <div class="text-lg font-bold text-white">100% Isolated</div>
            <div class="text-xs text-slate-400">Tenant Data Partitioning</div>
          </div>
        </div>

        <div class="flex items-center gap-3.5 p-3 rounded-xl bg-slate-900/40 border border-slate-800/40">
          <div class="p-2.5 rounded-lg bg-indigo-500/10 text-indigo-400 shrink-0">
            <Receipt :size="22" />
          </div>
          <div>
            <div class="text-lg font-bold text-white">TIN & VAT</div>
            <div class="text-xs text-slate-400">Compliant Invoicing</div>
          </div>
        </div>
      </div>
    </section>

    <!-- ========================================================= -->
    <!-- INTERACTIVE LIVE PRODUCT SANDBOX -->
    <!-- ========================================================= -->
    <section id="live-sandbox" class="py-20 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto relative z-10">
      <div class="text-center max-w-3xl mx-auto mb-12">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-cyan-950/60 border border-cyan-800/50 text-xs font-semibold text-cyan-400 uppercase tracking-wider mb-3">
          <Sparkles :size="13" />
          Live Interactive Sandbox
        </div>
        <h2 class="text-3xl sm:text-5xl font-bold text-white tracking-tight">
          Experience the Power in Real-Time
        </h2>
        <p class="mt-4 text-slate-400 text-base sm:text-lg">
          Switch tabs below to test simulated modules directly in your browser without logging in.
        </p>
      </div>

      <!-- Sandbox Tabs Navigation -->
      <div class="flex items-center justify-start sm:justify-center gap-2 overflow-x-auto pb-4 mb-8 custom-scrollbar">
        <button 
          @click="activeTab = 'analytics'"
          class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all shrink-0"
          :class="[activeTab === 'analytics' ? 'bg-cyan-500 text-white shadow-lg shadow-cyan-500/25' : 'bg-slate-900/80 text-slate-400 hover:text-white hover:bg-slate-800 border border-slate-800']"
        >
          <BarChart3 :size="16" />
          <span>Executive Analytics</span>
        </button>

        <button 
          @click="activeTab = 'pos'"
          class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all shrink-0"
          :class="[activeTab === 'pos' ? 'bg-cyan-500 text-white shadow-lg shadow-cyan-500/25' : 'bg-slate-900/80 text-slate-400 hover:text-white hover:bg-slate-800 border border-slate-800']"
        >
          <ShoppingCart :size="16" />
          <span>Point of Sale (POS)</span>
        </button>

        <button 
          @click="activeTab = 'inventory'"
          class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all shrink-0"
          :class="[activeTab === 'inventory' ? 'bg-cyan-500 text-white shadow-lg shadow-cyan-500/25' : 'bg-slate-900/80 text-slate-400 hover:text-white hover:bg-slate-800 border border-slate-800']"
        >
          <Package :size="16" />
          <span>Warehouse & Stock</span>
        </button>

        <button 
          @click="activeTab = 'invoicing'"
          class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all shrink-0"
          :class="[activeTab === 'invoicing' ? 'bg-cyan-500 text-white shadow-lg shadow-cyan-500/25' : 'bg-slate-900/80 text-slate-400 hover:text-white hover:bg-slate-800 border border-slate-800']"
        >
          <Receipt :size="16" />
          <span>Tax Invoicing</span>
        </button>

        <button 
          @click="activeTab = 'hr'"
          class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all shrink-0"
          :class="[activeTab === 'hr' ? 'bg-cyan-500 text-white shadow-lg shadow-cyan-500/25' : 'bg-slate-900/80 text-slate-400 hover:text-white hover:bg-slate-800 border border-slate-800']"
        >
          <Users :size="16" />
          <span>HR & Payroll</span>
        </button>
      </div>

      <!-- Sandbox Window Container -->
      <div class="rounded-2xl border border-slate-800/90 bg-[#090d18] shadow-2xl shadow-cyan-950/20 overflow-hidden">
        <!-- Mockup Top Bar -->
        <div class="px-4 py-3 bg-[#0c1220] border-b border-slate-800/80 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded-full bg-rose-500/80"></span>
            <span class="w-3 h-3 rounded-full bg-amber-500/80"></span>
            <span class="w-3 h-3 rounded-full bg-emerald-500/80"></span>
            <span class="ml-3 text-xs font-mono text-slate-400 hidden sm:inline-block">https://app.binaerp.cloud/{{ activeTab }}</span>
          </div>
          <div class="flex items-center gap-3">
            <span class="text-xs px-2.5 py-1 rounded bg-emerald-500/10 text-emerald-400 font-mono font-medium border border-emerald-500/20 flex items-center gap-1.5">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
              Live Sync: Active
            </span>
          </div>
        </div>

        <!-- Tab 1: Executive Analytics Preview -->
        <div v-if="activeTab === 'analytics'" class="p-6 sm:p-8 space-y-6">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-5 rounded-xl bg-slate-900/60 border border-slate-800">
              <div class="text-xs font-semibold text-slate-400 uppercase">Gross Revenue (MTD)</div>
              <div class="mt-2 text-2xl font-bold text-white">$148,920.00</div>
              <div class="mt-1 flex items-center gap-1.5 text-xs text-emerald-400 font-medium">
                <TrendingUp :size="14" /> +18.4% vs last month
              </div>
            </div>
            <div class="p-5 rounded-xl bg-slate-900/60 border border-slate-800">
              <div class="text-xs font-semibold text-slate-400 uppercase">Net Profit Margin</div>
              <div class="mt-2 text-2xl font-bold text-white">32.6%</div>
              <div class="mt-1 flex items-center gap-1.5 text-xs text-emerald-400 font-medium">
                <TrendingUp :size="14" /> +4.2% optimized COGS
              </div>
            </div>
            <div class="p-5 rounded-xl bg-slate-900/60 border border-slate-800">
              <div class="text-xs font-semibold text-slate-400 uppercase">Active POS Registers</div>
              <div class="mt-2 text-2xl font-bold text-white">12 Terminals</div>
              <div class="mt-1 flex items-center gap-1.5 text-xs text-cyan-400 font-medium">
                <Activity :size="14" /> 4 locations online
              </div>
            </div>
            <div class="p-5 rounded-xl bg-slate-900/60 border border-slate-800">
              <div class="text-xs font-semibold text-slate-400 uppercase">Total Inventory Value</div>
              <div class="mt-2 text-2xl font-bold text-white">$492,150.00</div>
              <div class="mt-1 flex items-center gap-1.5 text-xs text-indigo-400 font-medium">
                <Boxes :size="14" /> 3,420 SKU records
              </div>
            </div>
          </div>

          <!-- Chart & Breakdown Mockup -->
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 p-6 rounded-xl bg-slate-900/60 border border-slate-800">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-white">Real-Time Revenue & Cash Flow Trends</h3>
                <span class="text-xs font-mono text-cyan-400 bg-cyan-950/50 px-2 py-1 rounded">Daily Interval</span>
              </div>
              <div class="h-52 flex items-end gap-3 pt-6 pb-2 px-2 border-b border-slate-800">
                <div class="flex-1 bg-gradient-to-t from-cyan-600/30 to-cyan-500 rounded-t h-[40%] relative group">
                  <div class="absolute -top-7 left-1/2 -translate-x-1/2 bg-slate-800 text-[10px] px-1.5 py-0.5 rounded text-white opacity-0 group-hover:opacity-100 transition-opacity">$12k</div>
                </div>
                <div class="flex-1 bg-gradient-to-t from-cyan-600/30 to-cyan-500 rounded-t h-[55%] relative group">
                  <div class="absolute -top-7 left-1/2 -translate-x-1/2 bg-slate-800 text-[10px] px-1.5 py-0.5 rounded text-white opacity-0 group-hover:opacity-100 transition-opacity">$16k</div>
                </div>
                <div class="flex-1 bg-gradient-to-t from-cyan-600/30 to-cyan-500 rounded-t h-[70%] relative group">
                  <div class="absolute -top-7 left-1/2 -translate-x-1/2 bg-slate-800 text-[10px] px-1.5 py-0.5 rounded text-white opacity-0 group-hover:opacity-100 transition-opacity">$21k</div>
                </div>
                <div class="flex-1 bg-gradient-to-t from-cyan-600/30 to-cyan-500 rounded-t h-[60%] relative group">
                  <div class="absolute -top-7 left-1/2 -translate-x-1/2 bg-slate-800 text-[10px] px-1.5 py-0.5 rounded text-white opacity-0 group-hover:opacity-100 transition-opacity">$18k</div>
                </div>
                <div class="flex-1 bg-gradient-to-t from-cyan-600/30 to-cyan-500 rounded-t h-[85%] relative group">
                  <div class="absolute -top-7 left-1/2 -translate-x-1/2 bg-slate-800 text-[10px] px-1.5 py-0.5 rounded text-white opacity-0 group-hover:opacity-100 transition-opacity">$26k</div>
                </div>
                <div class="flex-1 bg-gradient-to-t from-cyan-600/30 to-cyan-500 rounded-t h-[95%] relative group">
                  <div class="absolute -top-7 left-1/2 -translate-x-1/2 bg-slate-800 text-[10px] px-1.5 py-0.5 rounded text-white opacity-0 group-hover:opacity-100 transition-opacity">$31k</div>
                </div>
              </div>
              <div class="flex justify-between text-xs text-slate-500 mt-2 font-mono">
                <span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat (Peak)</span>
              </div>
            </div>

            <div class="p-6 rounded-xl bg-slate-900/60 border border-slate-800 flex flex-col justify-between">
              <div>
                <h3 class="text-base font-semibold text-white mb-4">Channel Revenue Split</h3>
                <div class="space-y-3.5">
                  <div>
                    <div class="flex justify-between text-xs text-slate-300 mb-1">
                      <span>Retail POS Registers</span>
                      <span class="font-bold text-white">58% ($86.3k)</span>
                    </div>
                    <div class="w-full bg-slate-800 rounded-full h-2">
                      <div class="bg-cyan-400 h-2 rounded-full" style="width: 58%"></div>
                    </div>
                  </div>
                  <div>
                    <div class="flex justify-between text-xs text-slate-300 mb-1">
                      <span>Online Store & B2B Orders</span>
                      <span class="font-bold text-white">28% ($41.7k)</span>
                    </div>
                    <div class="w-full bg-slate-800 rounded-full h-2">
                      <div class="bg-blue-400 h-2 rounded-full" style="width: 28%"></div>
                    </div>
                  </div>
                  <div>
                    <div class="flex justify-between text-xs text-slate-300 mb-1">
                      <span>Direct Contract Invoicing</span>
                      <span class="font-bold text-white">14% ($20.9k)</span>
                    </div>
                    <div class="w-full bg-slate-800 rounded-full h-2">
                      <div class="bg-indigo-400 h-2 rounded-full" style="width: 14%"></div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="pt-4 mt-4 border-t border-slate-800 text-xs text-slate-400 flex items-center justify-between">
                <span>Auto-Reconciled Ledgers</span>
                <span class="text-emerald-400 font-semibold">100% Balanced</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Tab 2: Interactive POS Terminal Sandbox -->
        <div v-if="activeTab === 'pos'" class="p-6 sm:p-8">
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left: Catalog Items -->
            <div class="lg:col-span-7 space-y-4">
              <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400 uppercase">Interactive Product Catalog (Click to Add)</span>
                <span class="text-xs text-cyan-400">Terminal 01 - Cashier Dave</span>
              </div>
              <div class="grid grid-cols-2 sm:grid-cols-2 gap-3">
                <button
                  v-for="item in posCatalog"
                  :key="item.id"
                  @click="addPosItem(item)"
                  class="p-4 rounded-xl bg-slate-900/80 border border-slate-800 hover:border-cyan-500/60 transition-all text-left group hover:scale-[1.02]"
                >
                  <div class="text-2xl mb-2">{{ item.icon }}</div>
                  <div class="font-semibold text-sm text-white group-hover:text-cyan-300 transition-colors">{{ item.name }}</div>
                  <div class="text-xs text-slate-400 mt-1">{{ item.category }}</div>
                  <div class="mt-3 flex items-center justify-between">
                    <span class="text-cyan-400 font-bold font-mono text-sm">${{ item.price.toFixed(2) }}</span>
                    <span class="p-1 rounded bg-slate-800 group-hover:bg-cyan-500 group-hover:text-black transition-colors">
                      <Plus :size="14" />
                    </span>
                  </div>
                </button>
              </div>
            </div>

            <!-- Right: Simulated POS Cart / Receipt -->
            <div class="lg:col-span-5 p-5 rounded-xl bg-[#0b101c] border border-slate-800 flex flex-col justify-between">
              <div>
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                  <div class="text-sm font-bold text-white flex items-center gap-2">
                    <Receipt :size="16" class="text-cyan-400" />
                    Active Cart
                  </div>
                  <span class="text-xs font-mono text-emerald-400 bg-emerald-950/40 px-2 py-0.5 rounded">TIN: 0048291039</span>
                </div>

                <!-- Cart Items List -->
                <div class="divide-y divide-slate-800/60 my-3 max-h-48 overflow-y-auto custom-scrollbar">
                  <div v-if="posCart.length === 0" class="py-6 text-center text-xs text-slate-500">
                    Cart is empty. Click any product on the left to add.
                  </div>
                  <div v-for="item in posCart" :key="item.id" class="py-2.5 flex items-center justify-between">
                    <div class="text-xs">
                      <div class="font-medium text-slate-200">{{ item.name }}</div>
                      <div class="text-slate-500 font-mono">{{ item.qty }} x ${{ item.price.toFixed(2) }}</div>
                    </div>
                    <div class="flex items-center gap-3">
                      <span class="font-mono text-xs font-bold text-white">${{ (item.qty * item.price).toFixed(2) }}</span>
                      <button @click="removePosItem(item.id)" class="text-slate-500 hover:text-rose-400 transition-colors">
                        <Trash2 :size="13" />
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Cart Summary & Action -->
              <div class="pt-3 border-t border-slate-800 space-y-2">
                <div class="flex justify-between text-xs text-slate-400 font-mono">
                  <span>Subtotal</span>
                  <span>${{ posSubtotal.toFixed(2) }}</span>
                </div>
                <div class="flex justify-between text-xs text-slate-400 font-mono">
                  <span>VAT (15%)</span>
                  <span>${{ posTax.toFixed(2) }}</span>
                </div>
                <div class="flex justify-between text-sm font-bold text-white font-mono pt-1 border-t border-slate-800/60">
                  <span>Total Due</span>
                  <span class="text-cyan-400">${{ posTotal.toFixed(2) }}</span>
                </div>
                <button 
                  @click="navigateToAuth('register')"
                  class="w-full mt-3 py-2.5 rounded-lg bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-white font-semibold text-xs shadow-lg shadow-emerald-500/20 transition-all flex items-center justify-center gap-2"
                >
                  <Receipt :size="14" />
                  <span>Print Tax Invoice & Finish</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Tab 3: Warehouse & Stock Logistics -->
        <div v-if="activeTab === 'inventory'" class="p-6 sm:p-8 space-y-4">
          <div class="flex items-center justify-between pb-3 border-b border-slate-800">
            <div>
              <h3 class="text-sm font-bold text-white">Central Hub & Multi-Warehouse Distribution</h3>
              <p class="text-xs text-slate-400">Automated inter-branch transfers, batch tracking, and re-order thresholds.</p>
            </div>
            <span class="text-xs font-mono text-emerald-400 bg-emerald-950/40 px-2 py-1 rounded">3 Warehouses Synced</span>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 space-y-2">
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-white">Main Depot (East)</span>
                <span class="text-[10px] text-emerald-400 bg-emerald-950/60 px-1.5 py-0.5 rounded">98% Capacity</span>
              </div>
              <div class="text-xl font-mono font-bold text-cyan-400">14,280 Units</div>
              <div class="text-xs text-slate-400">Last transfer: 12 min ago</div>
            </div>

            <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 space-y-2">
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-white">Downtown Retail Store</span>
                <span class="text-[10px] text-amber-400 bg-amber-950/60 px-1.5 py-0.5 rounded">Low Stock Alert</span>
              </div>
              <div class="text-xl font-mono font-bold text-amber-400">420 Units</div>
              <div class="text-xs text-slate-400">Auto-replenish PO #1084 active</div>
            </div>

            <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 space-y-2">
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-white">West Logistics Terminal</span>
                <span class="text-[10px] text-cyan-400 bg-cyan-950/60 px-1.5 py-0.5 rounded">Operational</span>
              </div>
              <div class="text-xl font-mono font-bold text-cyan-400">6,840 Units</div>
              <div class="text-xs text-slate-400">Cross-dock receiving ready</div>
            </div>
          </div>
        </div>

        <!-- Tab 4: Tax Compliant Invoicing -->
        <div v-if="activeTab === 'invoicing'" class="p-6 sm:p-8 space-y-4">
          <div class="p-6 rounded-xl bg-[#0b101c] border border-slate-800 max-w-2xl mx-auto space-y-4">
            <div class="flex justify-between items-start border-b border-slate-800 pb-4">
              <div>
                <div class="text-lg font-bold text-white">TAX INVOICE</div>
                <div class="text-xs text-slate-400">Invoice Ref: <span class="font-mono text-cyan-400">INV-2026-00984</span></div>
              </div>
              <div class="text-right text-xs">
                <div class="font-bold text-white">Bina Enterprise Solutions Inc.</div>
                <div class="text-slate-400 font-mono">TIN: 009-842-114-001</div>
              </div>
            </div>

            <div class="text-xs grid grid-cols-2 gap-4 text-slate-300">
              <div>
                <span class="text-slate-500 uppercase font-semibold">Billed To:</span>
                <div class="font-medium text-white mt-1">Acme Global Logistics Ltd.</div>
                <div class="text-slate-400">TIN: 094-118-293</div>
              </div>
              <div class="text-right">
                <span class="text-slate-500 uppercase font-semibold">Payment Status:</span>
                <div class="text-emerald-400 font-bold mt-1">PAID (Bank Transfer)</div>
              </div>
            </div>

            <div class="border-t border-slate-800 pt-3">
              <table class="w-full text-xs text-left">
                <thead>
                  <tr class="text-slate-500 border-b border-slate-800/60 pb-1">
                    <th>Description</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Rate</th>
                    <th class="text-right">Total</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/40 text-slate-300">
                  <tr>
                    <td class="py-2">Cloud ERP Annual Enterprise Subscription</td>
                    <td class="text-right">1</td>
                    <td class="text-right font-mono">$2,388.00</td>
                    <td class="text-right font-mono text-white">$2,388.00</td>
                  </tr>
                  <tr>
                    <td class="py-2">Multi-Session POS Hardware Configuration</td>
                    <td class="text-right">3</td>
                    <td class="text-right font-mono">$150.00</td>
                    <td class="text-right font-mono text-white">$450.00</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="border-t border-slate-800 pt-3 flex justify-end">
              <div class="w-48 text-xs space-y-1 font-mono">
                <div class="flex justify-between text-slate-400"><span>Subtotal:</span><span>$2,838.00</span></div>
                <div class="flex justify-between text-slate-400"><span>Tax (15%):</span><span>$425.70</span></div>
                <div class="flex justify-between font-bold text-white text-sm pt-1 border-t border-slate-800">
                  <span>Grand Total:</span><span class="text-cyan-400">$3,263.70</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Tab 5: HR & Automated Payroll -->
        <div v-if="activeTab === 'hr'" class="p-6 sm:p-8 space-y-4">
          <div class="flex items-center justify-between pb-3 border-b border-slate-800">
            <div>
              <h3 class="text-sm font-bold text-white">Automated Payroll Calculation & Timesheets</h3>
              <p class="text-xs text-slate-400">Biometric check-in reconciliation, tax deductions, and one-click payslip generation.</p>
            </div>
            <span class="text-xs font-mono text-cyan-400 bg-cyan-950/40 px-2 py-1 rounded">September 2026 Run</span>
          </div>

          <div class="divide-y divide-slate-800/60">
            <div class="py-3 flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-cyan-500/20 text-cyan-400 flex items-center justify-center font-bold text-xs">
                  SL
                </div>
                <div>
                  <div class="text-sm font-semibold text-white">Sarah Lawson</div>
                  <div class="text-xs text-slate-400">Senior Operations Lead • Full Time</div>
                </div>
              </div>
              <div class="text-right text-xs">
                <div class="font-mono font-bold text-emerald-400">$5,400.00 Net</div>
                <div class="text-slate-500 font-mono">Tax: $810.00 | Pension: $378.00</div>
              </div>
            </div>

            <div class="py-3 flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-indigo-500/20 text-indigo-400 flex items-center justify-center font-bold text-xs">
                  MC
                </div>
                <div>
                  <div class="text-sm font-semibold text-white">Marcus Chen</div>
                  <div class="text-xs text-slate-400">Inventory & Supply Officer • Hourly</div>
                </div>
              </div>
              <div class="text-right text-xs">
                <div class="font-mono font-bold text-emerald-400">$3,840.00 Net</div>
                <div class="text-slate-500 font-mono">160 Hrs Logged + 8 OT</div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </section>

    <!-- ========================================================= -->
    <!-- BENTO GRID ARCHITECTURE & FEATURES -->
    <!-- ========================================================= -->
    <section id="features" class="py-24 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto relative z-10">
      <div class="text-center max-w-3xl mx-auto mb-16">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-950/60 border border-indigo-800/50 text-xs font-semibold text-indigo-400 uppercase tracking-wider mb-3">
          <Workflow :size="13" />
          Full-Stack Enterprise Architecture
        </div>
        <h2 class="text-3xl sm:text-5xl font-bold text-white tracking-tight">
          Engineered for Speed, Scale & Compliance
        </h2>
        <p class="mt-4 text-slate-400 text-base sm:text-lg">
          Replace disjointed SaaS subscriptions with one fully integrated platform built on robust transactional integrity.
        </p>
      </div>

      <!-- Bento Grid Layout -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Bento Card 1: POS (Large 2-col) -->
        <div class="md:col-span-2 p-8 rounded-2xl bg-gradient-to-br from-slate-900/80 to-[#0c1222] border border-slate-800/90 relative overflow-hidden group hover:border-cyan-500/40 transition-all">
          <div class="absolute -right-10 -top-10 w-48 h-48 bg-cyan-500/10 rounded-full blur-3xl pointer-events-none group-hover:bg-cyan-500/20 transition-all"></div>
          <div class="p-3 w-fit rounded-xl bg-cyan-500/10 text-cyan-400 mb-6">
            <ShoppingCart :size="26" />
          </div>
          <h3 class="text-2xl font-bold text-white">Multi-Cashier High-Speed Point of Sale</h3>
          <p class="mt-2 text-slate-400 text-sm sm:text-base leading-relaxed">
            Run concurrent POS sessions across multiple counters simultaneously. Powered by localized caching and instant thermal receipt printing with complete TIN and tax itemization.
          </p>
          <div class="mt-6 flex flex-wrap gap-2 text-xs font-medium">
            <span class="px-2.5 py-1 rounded-md bg-slate-800/90 text-cyan-300 border border-slate-700/50">Multi-Register Sync</span>
            <span class="px-2.5 py-1 rounded-md bg-slate-800/90 text-cyan-300 border border-slate-700/50">Barcode Autofocus</span>
            <span class="px-2.5 py-1 rounded-md bg-slate-800/90 text-cyan-300 border border-slate-700/50">Cash Drawer & Thermal Print</span>
          </div>
        </div>

        <!-- Bento Card 2: Accounting -->
        <div class="p-8 rounded-2xl bg-gradient-to-br from-slate-900/80 to-[#0c1222] border border-slate-800/90 relative overflow-hidden group hover:border-blue-500/40 transition-all">
          <div class="p-3 w-fit rounded-xl bg-blue-500/10 text-blue-400 mb-6">
            <Banknote :size="26" />
          </div>
          <h3 class="text-xl font-bold text-white">Double-Entry Accounting</h3>
          <p class="mt-2 text-slate-400 text-sm leading-relaxed">
            Automatic journal entries triggered by POS sales, procurement, and payroll. Generate instant P&L and Balance Sheets.
          </p>
          <div class="mt-6 flex flex-wrap gap-2 text-xs font-medium">
            <span class="px-2.5 py-1 rounded-md bg-slate-800/90 text-blue-300 border border-slate-700/50">Real-Time P&L</span>
            <span class="px-2.5 py-1 rounded-md bg-slate-800/90 text-blue-300 border border-slate-700/50">AR/AP Aging</span>
          </div>
        </div>

        <!-- Bento Card 3: Warehousing & Inventory -->
        <div class="p-8 rounded-2xl bg-gradient-to-br from-slate-900/80 to-[#0c1222] border border-slate-800/90 relative overflow-hidden group hover:border-emerald-500/40 transition-all">
          <div class="p-3 w-fit rounded-xl bg-emerald-500/10 text-emerald-400 mb-6">
            <Warehouse :size="26" />
          </div>
          <h3 class="text-xl font-bold text-white">Multi-Location Warehousing</h3>
          <p class="mt-2 text-slate-400 text-sm leading-relaxed">
            Track stock levels across branches, manage automated PO re-orders, and monitor inventory movements with zero shrinkage.
          </p>
          <div class="mt-6 flex flex-wrap gap-2 text-xs font-medium">
            <span class="px-2.5 py-1 rounded-md bg-slate-800/90 text-emerald-300 border border-slate-700/50">Inter-Branch Transfer</span>
            <span class="px-2.5 py-1 rounded-md bg-slate-800/90 text-emerald-300 border border-slate-700/50">Low-Stock Triggers</span>
          </div>
        </div>

        <!-- Bento Card 4: HR & Payroll (Large 2-col) -->
        <div class="md:col-span-2 p-8 rounded-2xl bg-gradient-to-br from-slate-900/80 to-[#0c1222] border border-slate-800/90 relative overflow-hidden group hover:border-indigo-500/40 transition-all">
          <div class="absolute -right-10 -top-10 w-48 h-48 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none group-hover:bg-indigo-500/20 transition-all"></div>
          <div class="p-3 w-fit rounded-xl bg-indigo-500/10 text-indigo-400 mb-6">
            <Users :size="26" />
          </div>
          <h3 class="text-2xl font-bold text-white">HR, Biometric Attendance & Payroll Engine</h3>
          <p class="mt-2 text-slate-400 text-sm sm:text-base leading-relaxed">
            Manage your complete workforce lifecycle. From applicant tracking and departmental org charts to automated salary computations, overtime bonuses, and tax withholding slips.
          </p>
          <div class="mt-6 flex flex-wrap gap-2 text-xs font-medium">
            <span class="px-2.5 py-1 rounded-md bg-slate-800/90 text-indigo-300 border border-slate-700/50">One-Click Pay Runs</span>
            <span class="px-2.5 py-1 rounded-md bg-slate-800/90 text-indigo-300 border border-slate-700/50">Leave Workflows</span>
            <span class="px-2.5 py-1 rounded-md bg-slate-800/90 text-indigo-300 border border-slate-700/50">Visual Org Chart</span>
          </div>
        </div>

      </div>
    </section>

    <!-- ========================================================= -->
    <!-- INTERACTIVE ROI & VALUE CALCULATOR -->
    <!-- ========================================================= -->
    <section id="roi-calculator" class="py-20 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto relative z-10">
      <div class="p-8 sm:p-12 rounded-3xl bg-gradient-to-br from-[#0c1224] via-[#090e1c] to-[#0a0f1d] border border-slate-800 shadow-2xl relative overflow-hidden">
        
        <div class="max-w-3xl mb-10">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-950/60 border border-emerald-800/50 text-xs font-semibold text-emerald-400 uppercase tracking-wider mb-3">
            <Calculator :size="13" />
            Productivity & ROI Calculator
          </div>
          <h2 class="text-3xl sm:text-4xl font-bold text-white">
            Calculate How Much Bina ERP Saves Your Team
          </h2>
          <p class="mt-2 text-slate-400 text-sm sm:text-base">
            Adjust the sliders below to estimate the measurable annual labor and operational savings unlocked by automating POS reconciliation, inventory tracking, and payroll.
          </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
          <!-- Left Sliders -->
          <div class="lg:col-span-7 space-y-6">
            <!-- Team Size Slider -->
            <div class="p-5 rounded-xl bg-slate-900/60 border border-slate-800 space-y-3">
              <div class="flex justify-between items-center">
                <label class="text-sm font-semibold text-white">Total Employees & Staff</label>
                <span class="text-cyan-400 font-bold font-mono text-base">{{ teamSize }} Team Members</span>
              </div>
              <input 
                type="range" 
                v-model.number="teamSize" 
                min="3" 
                max="150" 
                step="1"
                class="w-full h-2 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-cyan-400"
              />
              <div class="flex justify-between text-[11px] text-slate-500 font-mono">
                <span>3 (Small Team)</span>
                <span>75 (Midsize)</span>
                <span>150+ (Enterprise)</span>
              </div>
            </div>

            <!-- Average Hourly Rate Slider -->
            <div class="p-5 rounded-xl bg-slate-900/60 border border-slate-800 space-y-3">
              <div class="flex justify-between items-center">
                <label class="text-sm font-semibold text-white">Average Hourly Wage ($ / hr)</label>
                <span class="text-emerald-400 font-bold font-mono text-base">${{ hourlyWage }} / hour</span>
              </div>
              <input 
                type="range" 
                v-model.number="hourlyWage" 
                min="12" 
                max="80" 
                step="1"
                class="w-full h-2 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-emerald-400"
              />
              <div class="flex justify-between text-[11px] text-slate-500 font-mono">
                <span>$12/hr</span>
                <span>$45/hr</span>
                <span>$80/hr</span>
              </div>
            </div>
          </div>

          <!-- Right Computed Results Card -->
          <div class="lg:col-span-5 p-6 rounded-2xl bg-gradient-to-br from-cyan-950/40 via-slate-900/90 to-blue-950/40 border border-cyan-800/40 space-y-6">
            <div>
              <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Estimated Annual Savings</div>
              <div class="text-4xl sm:text-5xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 via-teal-300 to-cyan-400 font-mono mt-2">
                ${{ yearlyCostSaved.toLocaleString() }}
              </div>
              <div class="text-xs text-slate-400 mt-1">Approx. <span class="text-white font-semibold">${{ monthlyCostSaved.toLocaleString() }}</span> saved every month</div>
            </div>

            <div class="pt-4 border-t border-slate-800 space-y-2">
              <div class="flex justify-between text-xs text-slate-300">
                <span>Manual Admin Hours Eliminated:</span>
                <span class="font-bold text-white font-mono">{{ hoursSavedPerYear.toLocaleString() }} hrs / yr</span>
              </div>
              <div class="flex justify-between text-xs text-slate-300">
                <span>POS Reconciliation Error Reduction:</span>
                <span class="font-bold text-emerald-400">99.4%</span>
              </div>
            </div>

            <button 
              @click="authStore.isAuthenticated ? navigateToDashboard() : navigateToAuth('register')"
              class="w-full py-3.5 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-white font-bold text-sm shadow-xl shadow-cyan-500/20 transition-all hover:scale-[1.02]"
            >
              Start Realizing These Savings Today
            </button>
          </div>
        </div>

      </div>
    </section>

    <!-- ========================================================= -->
    <!-- PRICING TIERS -->
    <!-- ========================================================= -->
    <section id="pricing" class="py-24 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto relative z-10">
      <div class="text-center max-w-3xl mx-auto mb-12">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-cyan-950/60 border border-cyan-800/50 text-xs font-semibold text-cyan-400 uppercase tracking-wider mb-3">
          <DollarSign :size="13" />
          Transparent Cloud Plans
        </div>
        <h2 class="text-3xl sm:text-5xl font-bold text-white tracking-tight">
          Scale Freely Without Hidden Fees
        </h2>
        <p class="mt-4 text-slate-400 text-base sm:text-lg">
          Predictable pricing with zero setup charges. Switch plans or cancel anytime with full data export.
        </p>

        <!-- Annual / Monthly Toggle -->
        <div class="mt-8 flex items-center justify-center gap-3">
          <span class="text-sm font-medium" :class="[!isAnnual ? 'text-white' : 'text-slate-400']">Monthly Billing</span>
          <button 
            @click="isAnnual = !isAnnual"
            class="w-12 h-6 rounded-full bg-slate-800 p-1 transition-colors relative"
            :class="[isAnnual ? 'bg-cyan-500' : 'bg-slate-700']"
          >
            <div 
              class="w-4 h-4 rounded-full bg-white transition-transform"
              :class="[isAnnual ? 'translate-x-6' : 'translate-x-0']"
            ></div>
          </button>
          <span class="text-sm font-medium flex items-center gap-1.5" :class="[isAnnual ? 'text-white' : 'text-slate-400']">
            Annual Billing
            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
              Save 20%
            </span>
          </span>
        </div>
      </div>

      <!-- Pricing Cards Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-stretch">
        <div 
          v-for="tier in pricingTiers" 
          :key="tier.name"
          class="p-8 rounded-2xl flex flex-col justify-between transition-all duration-300 relative"
          :class="[
            tier.highlight 
              ? 'bg-gradient-to-b from-[#0e172a] to-[#090d18] border-2 border-cyan-500 shadow-2xl shadow-cyan-950/40 scale-100 lg:-translate-y-2' 
              : 'bg-slate-900/60 border border-slate-800 hover:border-slate-700'
          ]"
        >
          <!-- Popular Badge -->
          <div v-if="tier.highlight" class="absolute -top-3.5 left-1/2 -translate-x-1/2 px-3 py-0.5 rounded-full bg-gradient-to-r from-cyan-500 to-blue-600 text-white text-xs font-bold shadow-lg">
            {{ tier.badge }}
          </div>

          <div>
            <div class="flex justify-between items-center">
              <h3 class="text-xl font-bold text-white">{{ tier.name }}</h3>
              <span v-if="!tier.highlight" class="text-xs font-semibold px-2 py-0.5 rounded bg-slate-800 text-slate-400">{{ tier.badge }}</span>
            </div>
            
            <p class="text-xs text-slate-400 mt-2 min-h-[36px]">{{ tier.description }}</p>

            <!-- Price -->
            <div class="mt-6 flex items-baseline gap-1">
              <span class="text-4xl font-extrabold text-white font-mono">
                ${{ isAnnual ? tier.annualPrice : tier.monthlyPrice }}
              </span>
              <span class="text-xs text-slate-400">/ month billed {{ isAnnual ? 'annually' : 'monthly' }}</span>
            </div>

            <!-- Features List -->
            <div class="mt-8 space-y-3 border-t border-slate-800/80 pt-6">
              <div v-for="feat in tier.features" :key="feat" class="flex items-start gap-2.5 text-xs text-slate-300">
                <CheckCircle2 :size="15" class="text-cyan-400 shrink-0 mt-0.5" />
                <span>{{ feat }}</span>
              </div>
            </div>
          </div>

          <!-- Tier CTA Button -->
          <div class="mt-8 pt-4">
            <button 
              @click="authStore.isAuthenticated ? navigateToDashboard() : navigateToAuth('register')"
              class="w-full py-3 rounded-xl font-semibold text-sm transition-all"
              :class="[
                tier.highlight 
                  ? 'bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-white shadow-lg shadow-cyan-500/25 hover:scale-[1.02]' 
                  : 'bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700'
              ]"
            >
              {{ tier.cta }}
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- ========================================================= -->
    <!-- FAQ ACCORDION SECTION -->
    <!-- ========================================================= -->
    <section id="faq" class="py-20 px-4 sm:px-6 lg:px-8 max-w-4xl mx-auto relative z-10">
      <div class="text-center mb-12">
        <h2 class="text-3xl sm:text-4xl font-bold text-white tracking-tight">
          Frequently Asked Questions
        </h2>
        <p class="mt-3 text-slate-400 text-sm sm:text-base">
          Everything you need to know about deployment, POS hardware sync, and data isolation.
        </p>
      </div>

      <div class="space-y-3">
        <div 
          v-for="(faq, idx) in faqs" 
          :key="idx"
          class="rounded-xl border border-slate-800 bg-slate-900/60 overflow-hidden transition-colors"
        >
          <button 
            @click="toggleFaq(idx)"
            class="w-full p-5 text-left flex items-center justify-between gap-4 font-semibold text-sm sm:text-base text-white hover:text-cyan-300 transition-colors"
          >
            <span>{{ faq.q }}</span>
            <ChevronDown 
              :size="18" 
              class="text-slate-400 transition-transform duration-200 shrink-0"
              :class="[openFaq === idx ? 'rotate-180 text-cyan-400' : '']"
            />
          </button>
          <div 
            v-if="openFaq === idx"
            class="px-5 pb-5 text-xs sm:text-sm text-slate-400 leading-relaxed border-t border-slate-800/60 pt-3"
          >
            {{ faq.a }}
          </div>
        </div>
      </div>
    </section>

    <!-- ========================================================= -->
    <!-- CLOSING CTA BANNER -->
    <!-- ========================================================= -->
    <section class="py-20 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto relative z-10">
      <div class="p-10 sm:p-16 rounded-3xl bg-gradient-to-r from-cyan-950/60 via-blue-950/40 to-indigo-950/60 border border-cyan-500/30 text-center relative overflow-hidden shadow-2xl">
        <div class="max-w-3xl mx-auto relative z-10">
          <h2 class="text-3xl sm:text-5xl font-extrabold text-white tracking-tight leading-tight">
            Ready to Accelerate Your Enterprise Operations?
          </h2>
          <p class="mt-4 text-slate-300 text-base sm:text-lg">
            Join forward-thinking retailers, distributors, and organizations running on Bina ERP. Get fully set up in under 5 minutes.
          </p>
          <div class="mt-8 flex flex-col sm:flex-row justify-center gap-4">
            <button 
              @click="authStore.isAuthenticated ? navigateToDashboard() : navigateToAuth('register')"
              class="px-8 py-4 text-base font-bold rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-white shadow-xl shadow-cyan-500/30 transition-all hover:scale-[1.03]"
            >
              {{ authStore.isAuthenticated ? 'Open ERP Workspace' : 'Get Started Free (14 Days)' }}
            </button>
            <button 
              @click="navigateToAuth('login')"
              class="px-8 py-4 text-base font-semibold rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-200 border border-slate-700/80 transition-all"
            >
              Sign In to Existing Workspace
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- ========================================================= -->
    <!-- FOOTER -->
    <!-- ========================================================= -->
    <footer class="border-t border-slate-800/80 bg-[#05070e] text-slate-400 text-xs py-12 px-4 sm:px-6 lg:px-8 relative z-10">
      <div class="max-w-7xl mx-auto grid grid-cols-2 md:grid-cols-5 gap-8">
        
        <!-- Col 1: Brand -->
        <div class="col-span-2 space-y-4">
          <AppLogo light size="sm" />
          <p class="text-slate-400 max-w-sm text-xs leading-relaxed">
            The next-generation cloud enterprise management suite. Real-time POS, automated accounting, multi-warehouse inventory, and workforce management.
          </p>
          <div class="flex items-center gap-2 text-emerald-400 font-mono text-[11px]">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            All Cloud Services Operational (99.99% Uptime)
          </div>
        </div>

        <!-- Col 2: Platform -->
        <div>
          <div class="font-semibold text-white mb-3 text-xs uppercase tracking-wider">Platform</div>
          <ul class="space-y-2">
            <li><a href="#live-sandbox" class="hover:text-cyan-400 transition-colors">Point of Sale</a></li>
            <li><a href="#live-sandbox" class="hover:text-cyan-400 transition-colors">Accounting & Ledgers</a></li>
            <li><a href="#live-sandbox" class="hover:text-cyan-400 transition-colors">Warehouse Logistics</a></li>
            <li><a href="#live-sandbox" class="hover:text-cyan-400 transition-colors">HR & Payroll</a></li>
            <li><a href="#live-sandbox" class="hover:text-cyan-400 transition-colors">CRM & Pipelines</a></li>
          </ul>
        </div>

        <!-- Col 3: Resources -->
        <div>
          <div class="font-semibold text-white mb-3 text-xs uppercase tracking-wider">Resources</div>
          <ul class="space-y-2">
            <li><a href="#roi-calculator" class="hover:text-cyan-400 transition-colors">ROI Calculator</a></li>
            <li><a href="#pricing" class="hover:text-cyan-400 transition-colors">Pricing Plans</a></li>
            <li><a href="#faq" class="hover:text-cyan-400 transition-colors">Documentation & FAQ</a></li>
            <li><router-link to="/login" class="hover:text-cyan-400 transition-colors">Tenant Login</router-link></li>
          </ul>
        </div>

        <!-- Col 4: Legal & Security -->
        <div>
          <div class="font-semibold text-white mb-3 text-xs uppercase tracking-wider">Security</div>
          <ul class="space-y-2">
            <li><span class="text-slate-400">SOC-2 Type II Ready</span></li>
            <li><span class="text-slate-400">Strict Tenant Data Isolation</span></li>
            <li><span class="text-slate-400">256-Bit SSL Encryption</span></li>
            <li><span class="text-slate-400">Privacy & Terms</span></li>
          </ul>
        </div>

      </div>

      <div class="max-w-7xl mx-auto pt-8 mt-8 border-t border-slate-800/60 flex flex-col sm:flex-row items-center justify-between text-slate-500 gap-4">
        <div>© 2026 Bina ERP Platform Inc. All rights reserved.</div>
        <div class="flex gap-6">
          <a href="#" class="hover:text-slate-400">Privacy Policy</a>
          <a href="#" class="hover:text-slate-400">Terms of Service</a>
          <a href="#" class="hover:text-slate-400">Security Whitepaper</a>
        </div>
      </div>
    </footer>

  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: rgba(15, 23, 42, 0.4);
  border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(51, 65, 85, 0.6);
  border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(100, 116, 139, 0.8);
}
</style>
