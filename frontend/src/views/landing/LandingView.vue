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
  Receipt,
  DollarSign,
  Activity,
  Boxes,
  Plus,
  Trash2,
  Store,
  ChevronDown,
  Menu,
  X
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
// Currency State (ETB / USD)
// -------------------------------------------------------------
const selectedCurrency = ref<'ETB' | 'USD'>('ETB')
const currencyRate = 100 // 100 ETB per 1 USD for display conversion parity

const formatVal = (centsOrUnits: number, isCents = false) => {
  const amount = isCents ? centsOrUnits / 100 : centsOrUnits
  if (selectedCurrency.value === 'ETB') {
    return new Intl.NumberFormat('en-ET', {
      style: 'currency',
      currency: 'ETB',
      maximumFractionDigits: 2,
    }).format(amount)
  }
  const usdAmount = amount / currencyRate
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    maximumFractionDigits: 2,
  }).format(usdAmount)
}

// -------------------------------------------------------------
// Interactive Live Sandbox Mockups (Populated with Real Seed Data)
// -------------------------------------------------------------
const activeTab = ref<'pos' | 'inventory' | 'sales' | 'invoicing' | 'payroll'>('pos')

// Real Products from Store Catalog
const realCatalog = [
  {
    id: 1,
    name: 'Thermal Receipt Printer (80mm High Speed)',
    sku: 'POS-PRN-80',
    category: 'Store Equipment',
    priceEtb: 12500,
    priceUsd: 125,
    icon: '🖨️',
    desc: 'Instant auto-cutting paper receipt printer for cashier counter'
  },
  {
    id: 2,
    name: 'Hands-Free Barcode Scanner (Fast 2D)',
    sku: 'POS-SCAN-2D',
    category: 'Store Equipment',
    priceEtb: 7500,
    priceUsd: 75,
    icon: '🔍',
    desc: 'Beeps barcodes instantly without touching or aiming'
  },
  {
    id: 3,
    name: 'Heavy-Duty Steel Cash Drawer',
    sku: 'POS-DRW-HD',
    category: 'Store Equipment',
    priceEtb: 5800,
    priceUsd: 58,
    icon: '💵',
    desc: 'Pops open automatically on every paid cash sale'
  },
  {
    id: 4,
    name: 'Grade 1 Yirgacheffe Specialty Coffee (1kg)',
    sku: 'RET-COF-YRG',
    category: 'Bestselling Retail',
    priceEtb: 850,
    priceUsd: 8.5,
    icon: '☕',
    desc: 'Freshly roasted whole beans in high customer demand'
  },
  {
    id: 5,
    name: 'Multi-Port Store Connection Hub (USB-C)',
    sku: 'ACC-DOCK-4K',
    category: 'Store Accessories',
    priceEtb: 9200,
    priceUsd: 92,
    icon: '🔌',
    desc: 'Connects receipt printer, scanner & screen to one tablet'
  },
  {
    id: 6,
    name: 'Business Sales Laptop (16GB RAM / Fast SSD)',
    sku: 'LAP-DELL-5440',
    category: 'Store Hardware',
    priceEtb: 85000,
    priceUsd: 850,
    icon: '💻',
    desc: 'Reliable all-day shop manager & billing workstation'
  },
]

// POS Sandbox Cart State
const posCart = ref([
  {
    id: 1,
    name: 'Thermal Receipt Printer (80mm High Speed)',
    sku: 'POS-PRN-80',
    priceEtb: 12500,
    priceUsd: 125,
    qty: 1
  },
  {
    id: 4,
    name: 'Grade 1 Yirgacheffe Specialty Coffee (1kg)',
    sku: 'RET-COF-YRG',
    priceEtb: 850,
    priceUsd: 8.5,
    qty: 3
  }
])

const addPosItem = (item: typeof realCatalog[0]) => {
  const existing = posCart.value.find(c => c.id === item.id)
  if (existing) {
    existing.qty++
  } else {
    posCart.value.push({
      id: item.id,
      name: item.name,
      sku: item.sku,
      priceEtb: item.priceEtb,
      priceUsd: item.priceUsd,
      qty: 1
    })
  }
}

const removePosItem = (id: number) => {
  posCart.value = posCart.value.filter(c => c.id !== id)
}

const posSubtotal = computed(() => {
  return posCart.value.reduce((acc, item) => {
    const unitPrice = selectedCurrency.value === 'ETB' ? item.priceEtb : item.priceUsd
    return acc + unitPrice * item.qty
  }, 0)
})

// Ethiopian standard 15% VAT rate
const posTax = computed(() => posSubtotal.value * 0.15)
const posTotal = computed(() => posSubtotal.value + posTax.value)

// -------------------------------------------------------------
// Interactive ROI & Time Saved Calculator (For Store Owners)
// -------------------------------------------------------------
const staffCount = ref(12)
const averageWageEtb = ref(200)
const averageWageUsd = ref(20)

const currentHourlyWage = computed(() => {
  return selectedCurrency.value === 'ETB' ? averageWageEtb.value : averageWageUsd.value
})

const wastedHoursPerWeek = computed(() => Math.round(staffCount.value * 5))
const monthlyMoneySaved = computed(() => {
  const weeklyHours = wastedHoursPerWeek.value
  const weeklySavings = weeklyHours * currentHourlyWage.value * 0.75
  return Math.round(weeklySavings * 4.33)
})
const yearlyMoneySaved = computed(() => monthlyMoneySaved.value * 12)
const hoursSavedPerYear = computed(() => Math.round(wastedHoursPerWeek.value * 52 * 0.75))

// -------------------------------------------------------------
// Real Business Pricing Plans
// -------------------------------------------------------------
const isAnnual = ref(true)

const pricingTiers = computed(() => [
  {
    name: 'Starter Shop',
    badge: 'Single Store',
    tagline: 'Perfect for retail shops, cafes, boutiques, and single-store owners starting out.',
    monthlyEtb: 1000,
    annualEtb: 833, // 10,000 ETB / year (Save 17%)
    monthlyUsd: 10,
    annualUsd: 8.33,
    storage: '5 GB secure storage',
    usersLimit: 'Up to 5 Cashiers & Staff',
    modulesCount: 'Core POS & Sales Tools',
    perks: [
      'Up to 5 Cashiers & Staff Accounts',
      'Lightning-Fast POS Register (Touch & Barcode)',
      'Works 100% Offline When Store Wi-Fi Cuts Out',
      'Official Receipt Printing with your Shop Name & TIN',
      'Instant Daily Cash & Sales Closing Summaries',
      'Single Shop Inventory & Low Stock Alerts',
      'Customer Directory & Customer Credit Tracking',
      'Friendly WhatsApp & Phone Support'
    ],
    highlight: false,
    cta: 'Start 14-Day Free Trial'
  },
  {
    name: 'Growth Business',
    badge: 'Most Popular for Stores',
    tagline: 'Ideal for busy multi-counter shops, mini-markets, and growing wholesale businesses.',
    monthlyEtb: 2500,
    annualEtb: 2083, // 25,000 ETB / year (Save 17%)
    monthlyUsd: 25,
    annualUsd: 20.83,
    storage: '50 GB secure storage',
    usersLimit: 'Up to 25 Cashiers & Staff',
    modulesCount: 'All Store & Finance Modules',
    perks: [
      'Everything in Starter Shop, plus:',
      'Up to 25 Cashiers, Sales Reps & Managers',
      'Multiple Cashiers Selling at the Same Time (Zero Conflicts)',
      'Multi-Warehouse & Branch Stock Transfers (e.g. Bole & Kazanchis)',
      'Instant Profit & Loss Reports (Know your real profit today)',
      '1-Click Staff Monthly Payroll with Ethiopian Tax & 7% Pension',
      'Track Supplier Bills, Purchases & Incoming Stock Deliveries',
      'Send Professional Tax Invoices (TIN & 15% VAT) to Corporate Clients',
      'Daily Automatic Data Backups to Private Cloud',
      'Dedicated Onboarding & Live Training for Staff'
    ],
    highlight: true,
    cta: 'Get Started with Growth'
  },
  {
    name: 'Enterprise Chain',
    badge: 'Multi-Branch & Wholesale',
    tagline: 'For retail supermarket chains, wholesale distributors, and multi-branch enterprises.',
    monthlyEtb: 5000,
    annualEtb: 4166, // 50,000 ETB / year (Save 17%)
    monthlyUsd: 50,
    annualUsd: 41.66,
    storage: '500 GB secure storage',
    usersLimit: 'Unlimited Cashiers & Branches',
    modulesCount: 'Everything Unlocked',
    perks: [
      'Everything in Growth Business, plus:',
      'Unlimited Store Branches, Cashiers & Warehouse Locations',
      'Centralized Real-Time Overview of All Branches on your Phone',
      'Bulk Stock Redistribution Between Warehouses & Shops',
      'Custom Staff Permissions (Lock discount limits, void rules)',
      'Manufacturing & Product Assembly (BOM Tracking)',
      'Online Storefront with Live Inventory Sync',
      'Bank-Grade 100% Private Database Security',
      '24/7 Priority Emergency Phone & On-Site Support'
    ],
    highlight: false,
    cta: 'Talk to Sales & Get Demo'
  }
])

// -------------------------------------------------------------
// Real Business FAQ Accordion
// -------------------------------------------------------------
const openFaq = ref<number | null>(0)
const toggleFaq = (index: number) => {
  openFaq.value = openFaq.value === index ? null : index
}

const faqs = [
  {
    q: 'Can my sales staff use our existing tablets, computers, or phones?',
    a: 'Yes, absolutely! Bina ERP runs smoothly on any device with a web browser. You can use touch-screen POS monitors, iPads, Android tablets, regular Windows laptops, or even smartphones. No expensive proprietary computers required.'
  },
  {
    q: 'What happens if our shop Wi-Fi or mobile internet cuts out during busy hours?',
    a: 'Your checkout register keeps right on working. Cashiers can continue scanning barcodes, ringing up items, taking cash, and printing receipts even completely offline. As soon as your internet reconnects, all transactions sync up automatically.'
  },
  {
    q: 'Can multiple cashiers ring up sales at the same time in different counters or branches?',
    a: 'Yes. Each cashier logs into their own register session with their personal float and shift tracking. Whether you have 2 checkout lanes in your Bole store and 3 counters in Kazanchis, everyone sells at the same time without any confusion, duplicate sales, or system slowdown.'
  },
  {
    q: 'Does it print official receipts with our registered Company Name, TIN, and 15% VAT?',
    a: 'Yes, 100% tax compliant. Every receipt and invoice automatically prints your company legal name, address, Tax Identification Number (TIN), itemized prices, 15% VAT breakdown, and custom thank-you footer. It works directly with standard 58mm and 80mm thermal receipt printers.'
  },
  {
    q: 'How does it help stop stock theft, shrinkage, and missing inventory?',
    a: 'Every time an item is scanned or sold, stock levels drop instantly. You receive low-stock alerts before items run out, and cashiers cannot delete or alter completed sales without manager approval. At the end of every shift, you get a clear breakdown matching actual cash collected to items sold.'
  },
  {
    q: 'How hard is it for sales persons and cashiers to learn?',
    a: 'Most sales staff learn the entire checkout screen in less than 15 minutes. It is built specifically for retail and sales speed: large touch buttons, fast product search, instant barcode scanning, and simple 2-tap payment completion.'
  },
  {
    q: 'How does the automated staff payroll work with Ethiopian tax and pension?',
    a: 'At the end of the month, Bina ERP calculates each employee’s take-home pay with Ethiopian income tax brackets, 7% employee pension, and 11% employer pension already factored in. You can print clean monthly payslips with a single click.'
  }
]
</script>

<template>
  <div class="min-h-screen bg-[#070a12] text-slate-100 font-sans selection:bg-cyan-500/30 selection:text-cyan-200 relative overflow-x-hidden">
    
    <!-- Subtle Ambient Background (Low Saturation - Robi's Design System) -->
    <div class="fixed inset-0 pointer-events-none z-0 overflow-hidden">
      <div class="absolute top-[-8%] left-[25%] w-[600px] h-[600px] bg-cyan-700/10 rounded-full blur-[140px]"></div>
      <div class="absolute top-[35%] right-[8%] w-[500px] h-[500px] bg-blue-700/10 rounded-full blur-[140px]"></div>
      <div class="absolute top-[70%] left-[15%] w-[600px] h-[600px] bg-emerald-700/5 rounded-full blur-[160px]"></div>
      <div class="absolute inset-0 bg-[linear-gradient(to_right,#ffffff05_1px,transparent_1px),linear-gradient(to_bottom,#ffffff05_1px,transparent_1px)] bg-[size:4rem_4rem] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_0%,#000_70%,transparent_100%)]"></div>
    </div>

    <!-- ========================================================= -->
    <!-- SIMPLE, CLEAN TOP BAR (NAVIGATION HEADER) -->
    <!-- ========================================================= -->
    <header 
      class="fixed top-0 inset-x-0 z-50 transition-all duration-200 backdrop-blur-md"
      :class="[isScrolled ? 'bg-[#070a12]/90 border-b border-slate-800 shadow-lg py-3' : 'bg-[#070a12]/50 border-b border-slate-800/40 py-4']"
    >
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between">
        
        <!-- Brand Logo -->
        <router-link to="/" class="flex items-center">
          <AppLogo light size="sm" />
        </router-link>

        <!-- Clean Navigation Links -->
        <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-300">
          <a href="#features" class="hover:text-white transition-colors">Features</a>
          <a href="#demo" class="hover:text-white transition-colors">Demo</a>
          <a href="#pricing" class="hover:text-white transition-colors">Pricing</a>
          <a href="#faq" class="hover:text-white transition-colors">FAQ</a>
        </nav>

        <!-- Simple Right Actions -->
        <div class="hidden sm:flex items-center gap-4">
          <template v-if="authStore.isAuthenticated">
            <button 
              @click="navigateToDashboard"
              class="px-4 py-2 text-sm font-medium rounded-lg bg-cyan-500 hover:bg-cyan-400 text-white transition-all shadow-sm"
            >
              Dashboard
            </button>
          </template>
          <template v-else>
            <button 
              @click="navigateToAuth('login')"
              class="text-sm font-medium text-slate-300 hover:text-white transition-colors"
            >
              Sign In
            </button>
            <button 
              @click="navigateToAuth('register')"
              class="px-4 py-2 text-sm font-semibold rounded-lg bg-cyan-500 hover:bg-cyan-400 text-white transition-all shadow-sm hover:scale-[1.02] active:scale-[0.98]"
            >
              Get Started
            </button>
          </template>
        </div>

        <!-- Mobile Menu Toggle -->
        <button 
          @click="mobileMenuOpen = !mobileMenuOpen"
          class="md:hidden p-2 text-slate-400 hover:text-white rounded-lg"
          aria-label="Toggle Menu"
        >
          <Menu v-if="!mobileMenuOpen" :size="20" />
          <X v-else :size="20" />
        </button>
      </div>

      <!-- Mobile Dropdown -->
      <div v-if="mobileMenuOpen" class="md:hidden border-b border-slate-800 bg-[#090e1a] px-6 py-4 space-y-3">
        <nav class="flex flex-col space-y-2 text-sm font-medium text-slate-300">
          <a @click="mobileMenuOpen = false" href="#features" class="py-1 hover:text-white">Features</a>
          <a @click="mobileMenuOpen = false" href="#demo" class="py-1 hover:text-white">Demo</a>
          <a @click="mobileMenuOpen = false" href="#pricing" class="py-1 hover:text-white">Pricing</a>
          <a @click="mobileMenuOpen = false" href="#faq" class="py-1 hover:text-white">FAQ</a>
        </nav>
        <div class="pt-3 border-t border-slate-800 flex flex-col gap-2">
          <template v-if="authStore.isAuthenticated">
            <button 
              @click="navigateToDashboard"
              class="w-full py-2.5 text-center text-sm font-medium bg-cyan-500 text-white rounded-lg"
            >
              Dashboard
            </button>
          </template>
          <template v-else>
            <button 
              @click="navigateToAuth('login')"
              class="w-full py-2 text-center text-sm font-medium text-slate-300 hover:text-white"
            >
              Sign In
            </button>
            <button 
              @click="navigateToAuth('register')"
              class="w-full py-2.5 text-center text-sm font-semibold bg-cyan-500 text-white rounded-lg"
            >
              Get Started
            </button>
          </template>
        </div>
      </div>
    </header>

    <!-- ========================================================= -->
    <!-- HERO SECTION -->
    <!-- ========================================================= -->
    <section class="relative pt-28 pb-16 md:pt-36 md:pb-24 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto text-center z-10">
      
      <!-- Clean, Simple Value Pill -->
      <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-slate-900/80 border border-slate-800 text-xs font-medium text-slate-300 mb-6">
        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
        <span>Store Management for Retail & Wholesale</span>
      </div>

      <!-- Main Headline: Focus on Sales, Profits & Peace of Mind -->
      <h1 class="text-4xl sm:text-6xl lg:text-7xl font-extrabold tracking-tight text-white max-w-5xl mx-auto leading-[1.12]">
        Sell Faster, Stop Stock Loss, & <br class="hidden sm:block"/>
        <span class="bg-clip-text text-transparent bg-gradient-to-r from-cyan-400 via-teal-300 to-blue-400">
          Know Your Daily Profits Instantly
        </span>
      </h1>

      <!-- Subtitle in everyday business terms -->
      <p class="mt-6 text-lg sm:text-xl text-slate-300 max-w-3xl mx-auto leading-relaxed font-normal">
        The easy, all-in-one store management system. Ring up sales in 2 seconds, print official TIN & 15% VAT receipts, keep stock accurate across all branches, and manage staff payroll with zero headaches.
      </p>

      <!-- Hero Call-to-Actions -->
      <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4 max-w-md mx-auto sm:max-w-none">
        <button 
          @click="authStore.isAuthenticated ? navigateToDashboard() : navigateToAuth('register')"
          class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-8 py-3.5 text-base font-bold rounded-xl bg-gradient-to-r from-cyan-500 via-teal-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-white shadow-xl shadow-cyan-500/25 transition-all duration-200 hover:scale-[1.02] active:scale-[0.98]"
        >
          <span>{{ authStore.isAuthenticated ? 'Open My Store' : 'Start 14-Day Free Trial' }}</span>
          <ArrowRight :size="18" />
        </button>
        <a 
          href="#demo"
          class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 text-base font-semibold rounded-xl bg-slate-900/90 hover:bg-slate-800 border border-slate-700/80 text-slate-200 transition-all duration-200 hover:border-slate-600"
        >
          <Sparkles :size="18" class="text-cyan-400" />
          <span>Try the Checkout Screen Below</span>
        </a>
      </div>

      <!-- Trust Badges For Retailers -->
      <div class="mt-14 pt-10 border-t border-slate-800/80 grid grid-cols-2 md:grid-cols-4 gap-5 max-w-4xl mx-auto text-left">
        <div class="flex items-center gap-3.5 p-3.5 rounded-xl bg-slate-900/50 border border-slate-800/60">
          <div class="p-2.5 rounded-lg bg-emerald-500/10 text-emerald-400 shrink-0">
            <Zap :size="22" />
          </div>
          <div>
            <div class="text-base font-bold text-white">2-Second Checkout</div>
            <div class="text-xs text-slate-400">Zero customer queues</div>
          </div>
        </div>

        <div class="flex items-center gap-3.5 p-3.5 rounded-xl bg-slate-900/50 border border-slate-800/60">
          <div class="p-2.5 rounded-lg bg-cyan-500/10 text-cyan-400 shrink-0">
            <Store :size="22" />
          </div>
          <div>
            <div class="text-base font-bold text-white">Works 100% Offline</div>
            <div class="text-xs text-slate-400">Sells even when Wi-Fi drops</div>
          </div>
        </div>

        <div class="flex items-center gap-3.5 p-3.5 rounded-xl bg-slate-900/50 border border-slate-800/60">
          <div class="p-2.5 rounded-lg bg-blue-500/10 text-blue-400 shrink-0">
            <Receipt :size="22" />
          </div>
          <div>
            <div class="text-base font-bold text-white">TIN & 15% VAT Ready</div>
            <div class="text-xs text-slate-400">Official thermal tax receipts</div>
          </div>
        </div>

        <div class="flex items-center gap-3.5 p-3.5 rounded-xl bg-slate-900/50 border border-slate-800/60">
          <div class="p-2.5 rounded-lg bg-indigo-500/10 text-indigo-400 shrink-0">
            <ShieldCheck :size="22" />
          </div>
          <div>
            <div class="text-base font-bold text-white">Bank-Grade Safety</div>
            <div class="text-xs text-slate-400">Automatic hourly cloud backups</div>
          </div>
        </div>
      </div>
    </section>

    <!-- ========================================================= -->
    <!-- INTERACTIVE STORE POS DEMO (CLICKABLE BY SALES PERSONS) -->
    <!-- ========================================================= -->
    <section id="demo" class="py-20 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto relative z-10">
      <div class="text-center max-w-3xl mx-auto mb-10">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-cyan-950/60 border border-cyan-800/50 text-xs font-semibold text-cyan-400 uppercase tracking-wider mb-3">
          <Sparkles :size="13" />
          Interactive Store Simulation
        </div>
        <h2 class="text-3xl sm:text-5xl font-bold text-white tracking-tight">
          Experience How Easy It Is to Sell & Manage
        </h2>
        <p class="mt-4 text-slate-300 text-base sm:text-lg">
          Try the cashier checkout terminal below. Click any product to add it to the till, see the instant 15% Ethiopian VAT calculation, and test how sales are finalized in seconds.
        </p>
      </div>

      <!-- Feature Tabs for End Users -->
      <div class="flex items-center justify-start sm:justify-center gap-2 overflow-x-auto pb-4 mb-8 custom-scrollbar">
        <button 
          @click="activeTab = 'pos'"
          class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all shrink-0"
          :class="[activeTab === 'pos' ? 'bg-cyan-500 text-white shadow-lg shadow-cyan-500/25' : 'bg-slate-900/80 text-slate-400 hover:text-white hover:bg-slate-800 border border-slate-800']"
        >
          <ShoppingCart :size="16" />
          <span>1. Cashier POS Register</span>
        </button>

        <button 
          @click="activeTab = 'inventory'"
          class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all shrink-0"
          :class="[activeTab === 'inventory' ? 'bg-cyan-500 text-white shadow-lg shadow-cyan-500/25' : 'bg-slate-900/80 text-slate-400 hover:text-white hover:bg-slate-800 border border-slate-800']"
        >
          <Package :size="16" />
          <span>2. Stock & Shop Inventory</span>
        </button>

        <button 
          @click="activeTab = 'sales'"
          class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all shrink-0"
          :class="[activeTab === 'sales' ? 'bg-cyan-500 text-white shadow-lg shadow-cyan-500/25' : 'bg-slate-900/80 text-slate-400 hover:text-white hover:bg-slate-800 border border-slate-800']"
        >
          <BarChart3 :size="16" />
          <span>3. Daily Sales & Profits</span>
        </button>

        <button 
          @click="activeTab = 'invoicing'"
          class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all shrink-0"
          :class="[activeTab === 'invoicing' ? 'bg-cyan-500 text-white shadow-lg shadow-cyan-500/25' : 'bg-slate-900/80 text-slate-400 hover:text-white hover:bg-slate-800 border border-slate-800']"
        >
          <Receipt :size="16" />
          <span>4. Official Tax Invoices</span>
        </button>

        <button 
          @click="activeTab = 'payroll'"
          class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all shrink-0"
          :class="[activeTab === 'payroll' ? 'bg-cyan-500 text-white shadow-lg shadow-cyan-500/25' : 'bg-slate-900/80 text-slate-400 hover:text-white hover:bg-slate-800 border border-slate-800']"
        >
          <Users :size="16" />
          <span>5. Staff & Payroll</span>
        </button>
      </div>

      <!-- Sandbox Window Container -->
      <div class="rounded-2xl border border-slate-800 bg-[#090d18] shadow-2xl overflow-hidden">
        <!-- Mockup Store Header With Contextual Currency Toggle -->
        <div class="px-5 py-3 bg-[#0c1220] border-b border-slate-800 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded-full bg-rose-500/80"></span>
            <span class="w-3 h-3 rounded-full bg-amber-500/80"></span>
            <span class="w-3 h-3 rounded-full bg-emerald-500/80"></span>
            <span class="ml-3 text-xs text-slate-400 font-medium hidden sm:inline-block">
              Store: <span class="text-white font-semibold">Bole Mega Mall Flagship Store</span>
            </span>
          </div>
          <div class="flex items-center gap-3">
            <!-- Contextual Currency Switcher -->
            <div class="flex items-center rounded-lg bg-slate-900 border border-slate-800 p-0.5 text-xs font-semibold">
              <button 
                @click="selectedCurrency = 'ETB'"
                class="px-2.5 py-0.5 rounded-md transition-all text-xs"
                :class="[selectedCurrency === 'ETB' ? 'bg-cyan-500 text-white' : 'text-slate-400 hover:text-white']"
              >
                ETB (Br)
              </button>
              <button 
                @click="selectedCurrency = 'USD'"
                class="px-2.5 py-0.5 rounded-md transition-all text-xs"
                :class="[selectedCurrency === 'USD' ? 'bg-cyan-500 text-white' : 'text-slate-400 hover:text-white']"
              >
                USD ($)
              </button>
            </div>
            <span class="text-xs px-2.5 py-1 rounded bg-emerald-500/10 text-emerald-400 font-medium border border-emerald-500/20 hidden md:flex items-center gap-1.5">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
              Live Store Simulation
            </span>
          </div>
        </div>

        <!-- TAB 1: POS Cashier Screen -->
        <div v-if="activeTab === 'pos'" class="p-6 sm:p-8">
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left: Catalog Items to click -->
            <div class="lg:col-span-7 space-y-4">
              <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-300 uppercase tracking-wider">
                  Store Products (Click any item to ring it up)
                </span>
                <span class="text-xs text-cyan-400 font-medium">Counter #1 • Active Shift</span>
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <button
                  v-for="item in realCatalog"
                  :key="item.id"
                  @click="addPosItem(item)"
                  class="p-4 rounded-xl bg-slate-900/80 border border-slate-800 hover:border-cyan-500/60 transition-all text-left group hover:scale-[1.01]"
                >
                  <div class="flex items-center justify-between">
                    <span class="text-2xl">{{ item.icon }}</span>
                    <span class="text-[10px] font-mono text-slate-400 px-2 py-0.5 rounded bg-slate-800">{{ item.category }}</span>
                  </div>
                  <div class="font-semibold text-sm text-white group-hover:text-cyan-300 transition-colors mt-2">{{ item.name }}</div>
                  <div class="text-xs text-slate-400 mt-0.5 line-clamp-1">{{ item.desc }}</div>
                  <div class="mt-3 flex items-center justify-between">
                    <span class="text-cyan-400 font-bold font-mono text-sm">
                      {{ selectedCurrency === 'ETB' ? `ETB ${item.priceEtb.toLocaleString()}` : `$${item.priceUsd.toFixed(2)}` }}
                    </span>
                    <span class="p-1 rounded bg-slate-800 group-hover:bg-cyan-500 group-hover:text-black transition-colors">
                      <Plus :size="14" />
                    </span>
                  </div>
                </button>
              </div>
            </div>

            <!-- Right: Current Customer Receipt / Cash Register -->
            <div class="lg:col-span-5 p-5 rounded-xl bg-[#0b101c] border border-slate-800 flex flex-col justify-between">
              <div>
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                  <div>
                    <div class="text-sm font-bold text-white flex items-center gap-2">
                      <Receipt :size="16" class="text-cyan-400" />
                      Current Customer Bill
                    </div>
                    <div class="text-[11px] text-slate-400">Cashier: Bethlehem T. (Shift A)</div>
                  </div>
                  <span class="text-xs font-mono text-emerald-400 bg-emerald-950/40 px-2 py-0.5 rounded border border-emerald-800/40">TIN: 0000001234</span>
                </div>

                <!-- Items in cart -->
                <div class="divide-y divide-slate-800/60 my-3 max-h-52 overflow-y-auto custom-scrollbar">
                  <div v-if="posCart.length === 0" class="py-8 text-center text-xs text-slate-400">
                    Cart is empty. Click any product on the left to ring it up.
                  </div>
                  <div v-for="item in posCart" :key="item.id" class="py-2.5 flex items-center justify-between">
                    <div class="text-xs pr-2">
                      <div class="font-medium text-slate-200">{{ item.name }}</div>
                      <div class="text-slate-400 font-mono">
                        {{ item.qty }} × {{ selectedCurrency === 'ETB' ? `ETB ${item.priceEtb.toLocaleString()}` : `$${item.priceUsd.toFixed(2)}` }}
                      </div>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                      <span class="font-mono text-xs font-bold text-white">
                        {{ selectedCurrency === 'ETB' ? `ETB ${(item.qty * item.priceEtb).toLocaleString()}` : `$${(item.qty * item.priceUsd).toFixed(2)}` }}
                      </span>
                      <button @click="removePosItem(item.id)" class="text-slate-400 hover:text-rose-400 transition-colors p-1" title="Remove item">
                        <Trash2 :size="14" />
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Bill Totals -->
              <div class="pt-3 border-t border-slate-800 space-y-2">
                <div class="flex justify-between text-xs text-slate-400 font-mono">
                  <span>Subtotal:</span>
                  <span>{{ selectedCurrency === 'ETB' ? `ETB ${posSubtotal.toLocaleString()}` : `$${posSubtotal.toFixed(2)}` }}</span>
                </div>
                <div class="flex justify-between text-xs text-slate-400 font-mono">
                  <span>Official VAT (15%):</span>
                  <span>{{ selectedCurrency === 'ETB' ? `ETB ${posTax.toLocaleString()}` : `$${posTax.toFixed(2)}` }}</span>
                </div>
                <div class="flex justify-between text-sm font-bold text-white font-mono pt-1.5 border-t border-slate-800/60">
                  <span>Total Amount Due:</span>
                  <span class="text-cyan-400 text-base">{{ selectedCurrency === 'ETB' ? `ETB ${posTotal.toLocaleString()}` : `$${posTotal.toFixed(2)}` }}</span>
                </div>
                <button 
                  @click="navigateToAuth('register')"
                  class="w-full mt-3 py-3 rounded-lg bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-white font-bold text-xs shadow-lg shadow-emerald-500/20 transition-all flex items-center justify-center gap-2"
                >
                  <Receipt :size="15" />
                  <span>Accept Payment & Print Receipt</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB 2: Inventory & Stock Tracking -->
        <div v-if="activeTab === 'inventory'" class="p-6 sm:p-8 space-y-5">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-slate-800 gap-2">
            <div>
              <h3 class="text-base font-bold text-white">Stock Quantities Across Your Shops & Warehouses</h3>
              <p class="text-xs text-slate-300">Know exactly what is on your shelves in real time. Transfer stock between stores with zero lost items.</p>
            </div>
            <span class="text-xs font-medium text-emerald-400 bg-emerald-950/40 px-3 py-1 rounded-full border border-emerald-800/40 self-start sm:self-auto">
              All Branches Synced Live
            </span>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Central Warehouse -->
            <div class="p-5 rounded-xl bg-slate-900/60 border border-slate-800 space-y-2">
              <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-white">Central Storage Depot</span>
                <span class="text-[10px] text-cyan-400 bg-cyan-950/60 px-2 py-0.5 rounded font-mono">Main Hub</span>
              </div>
              <div class="text-xs text-slate-400">Ring Road, Bole, Addis Ababa</div>
              <div class="text-2xl font-mono font-bold text-cyan-400 mt-2">14,280 Units</div>
              <div class="text-xs text-slate-300 pt-2 border-t border-slate-800/60 flex items-center justify-between">
                <span>Stock Status:</span>
                <span class="text-emerald-400 font-medium">Fully Stocked</span>
              </div>
            </div>

            <!-- Bole Store -->
            <div class="p-5 rounded-xl bg-slate-900/60 border border-slate-800 space-y-2">
              <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-white">Bole Mall Flagship Shop</span>
                <span class="text-[10px] text-amber-400 bg-amber-950/60 px-2 py-0.5 rounded font-mono">Retail Store</span>
              </div>
              <div class="text-xs text-slate-400">Cameroon St, Bole Medhanealem</div>
              <div class="text-2xl font-mono font-bold text-amber-400 mt-2">420 Units</div>
              <div class="text-xs text-slate-300 pt-2 border-t border-slate-800/60 flex items-center justify-between">
                <span>Stock Status:</span>
                <span class="text-amber-400 font-medium">Low Stock Alert</span>
              </div>
            </div>

            <!-- Kazanchis Store -->
            <div class="p-5 rounded-xl bg-slate-900/60 border border-slate-800 space-y-2">
              <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-white">Kazanchis Express Shop</span>
                <span class="text-[10px] text-emerald-400 bg-emerald-950/60 px-2 py-0.5 rounded font-mono">Branch 2</span>
              </div>
              <div class="text-xs text-slate-400">Tito St, Kazanchis, Addis Ababa</div>
              <div class="text-2xl font-mono font-bold text-emerald-400 mt-2">1,840 Units</div>
              <div class="text-xs text-slate-300 pt-2 border-t border-slate-800/60 flex items-center justify-between">
                <span>Stock Status:</span>
                <span class="text-emerald-400 font-medium">Healthy Inventory</span>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB 3: Daily Sales & Profits -->
        <div v-if="activeTab === 'sales'" class="p-6 sm:p-8 space-y-6">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-5 rounded-xl bg-slate-900/60 border border-slate-800">
              <div class="text-xs font-semibold text-slate-400 uppercase">This Month's Sales</div>
              <div class="mt-2 text-2xl font-bold text-white">
                {{ selectedCurrency === 'ETB' ? 'ETB 1,489,200.00' : '$14,892.00' }}
              </div>
              <div class="mt-1 flex items-center gap-1.5 text-xs text-emerald-400 font-medium">
                <TrendingUp :size="14" /> +18.4% compared to last month
              </div>
            </div>
            <div class="p-5 rounded-xl bg-slate-900/60 border border-slate-800">
              <div class="text-xs font-semibold text-slate-400 uppercase">Real Net Profit</div>
              <div class="mt-2 text-2xl font-bold text-emerald-400">
                {{ selectedCurrency === 'ETB' ? 'ETB 485,500.00' : '$4,855.00' }}
              </div>
              <div class="mt-1 flex items-center gap-1.5 text-xs text-slate-300 font-medium">
                32.6% Clean profit after all costs
              </div>
            </div>
            <div class="p-5 rounded-xl bg-slate-900/60 border border-slate-800">
              <div class="text-xs font-semibold text-slate-400 uppercase">Active Store Registers</div>
              <div class="mt-2 text-2xl font-bold text-white">2 Branches Open</div>
              <div class="mt-1 flex items-center gap-1.5 text-xs text-cyan-400 font-medium">
                <Activity :size="14" /> Bole & Kazanchis selling now
              </div>
            </div>
            <div class="p-5 rounded-xl bg-slate-900/60 border border-slate-800">
              <div class="text-xs font-semibold text-slate-400 uppercase">Total Stock Value on Hand</div>
              <div class="mt-2 text-2xl font-bold text-white">
                {{ selectedCurrency === 'ETB' ? 'ETB 4,921,500.00' : '$49,215.00' }}
              </div>
              <div class="mt-1 flex items-center gap-1.5 text-xs text-slate-300 font-medium">
                <Boxes :size="14" /> Verified physical inventory
              </div>
            </div>
          </div>

          <!-- Weekly Sales Chart & Top Selling Stores -->
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 p-6 rounded-xl bg-slate-900/60 border border-slate-800">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-white">Daily Cash & Sales Volume This Week</h3>
                <span class="text-xs text-cyan-400 bg-cyan-950/50 px-2.5 py-1 rounded font-medium">Peak Day: Saturday</span>
              </div>
              <div class="h-52 flex items-end gap-3 pt-6 pb-2 px-2 border-b border-slate-800">
                <div class="flex-1 bg-gradient-to-t from-cyan-600/30 to-cyan-500 rounded-t h-[40%] relative group">
                  <div class="absolute -top-7 left-1/2 -translate-x-1/2 bg-slate-800 text-[10px] px-1.5 py-0.5 rounded text-white opacity-0 group-hover:opacity-100 transition-opacity">Br 120k</div>
                </div>
                <div class="flex-1 bg-gradient-to-t from-cyan-600/30 to-cyan-500 rounded-t h-[55%] relative group">
                  <div class="absolute -top-7 left-1/2 -translate-x-1/2 bg-slate-800 text-[10px] px-1.5 py-0.5 rounded text-white opacity-0 group-hover:opacity-100 transition-opacity">Br 165k</div>
                </div>
                <div class="flex-1 bg-gradient-to-t from-cyan-600/30 to-cyan-500 rounded-t h-[70%] relative group">
                  <div class="absolute -top-7 left-1/2 -translate-x-1/2 bg-slate-800 text-[10px] px-1.5 py-0.5 rounded text-white opacity-0 group-hover:opacity-100 transition-opacity">Br 210k</div>
                </div>
                <div class="flex-1 bg-gradient-to-t from-cyan-600/30 to-cyan-500 rounded-t h-[60%] relative group">
                  <div class="absolute -top-7 left-1/2 -translate-x-1/2 bg-slate-800 text-[10px] px-1.5 py-0.5 rounded text-white opacity-0 group-hover:opacity-100 transition-opacity">Br 180k</div>
                </div>
                <div class="flex-1 bg-gradient-to-t from-cyan-600/30 to-cyan-500 rounded-t h-[85%] relative group">
                  <div class="absolute -top-7 left-1/2 -translate-x-1/2 bg-slate-800 text-[10px] px-1.5 py-0.5 rounded text-white opacity-0 group-hover:opacity-100 transition-opacity">Br 265k</div>
                </div>
                <div class="flex-1 bg-gradient-to-t from-cyan-600/30 to-cyan-500 rounded-t h-[95%] relative group">
                  <div class="absolute -top-7 left-1/2 -translate-x-1/2 bg-slate-800 text-[10px] px-1.5 py-0.5 rounded text-white opacity-0 group-hover:opacity-100 transition-opacity">Br 320k</div>
                </div>
              </div>
              <div class="flex justify-between text-xs text-slate-400 mt-2 font-medium">
                <span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span class="text-cyan-400 font-bold">Sat (Peak)</span>
              </div>
            </div>

            <div class="p-6 rounded-xl bg-slate-900/60 border border-slate-800 flex flex-col justify-between">
              <div>
                <h3 class="text-base font-semibold text-white mb-4">Where Sales Came From</h3>
                <div class="space-y-4">
                  <div>
                    <div class="flex justify-between text-xs text-slate-300 mb-1">
                      <span>Bole Flagship Store</span>
                      <span class="font-bold text-white">52%</span>
                    </div>
                    <div class="w-full bg-slate-800 rounded-full h-2">
                      <div class="bg-cyan-400 h-2 rounded-full" style="width: 52%"></div>
                    </div>
                  </div>
                  <div>
                    <div class="flex justify-between text-xs text-slate-300 mb-1">
                      <span>Direct Corporate Invoices</span>
                      <span class="font-bold text-white">34%</span>
                    </div>
                    <div class="w-full bg-slate-800 rounded-full h-2">
                      <div class="bg-blue-400 h-2 rounded-full" style="width: 34%"></div>
                    </div>
                  </div>
                  <div>
                    <div class="flex justify-between text-xs text-slate-300 mb-1">
                      <span>Kazanchis Express Shop</span>
                      <span class="font-bold text-white">14%</span>
                    </div>
                    <div class="w-full bg-slate-800 rounded-full h-2">
                      <div class="bg-teal-400 h-2 rounded-full" style="width: 14%"></div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="pt-4 mt-4 border-t border-slate-800 text-xs text-slate-300 flex items-center justify-between">
                <span>Daily Reconciliation</span>
                <span class="text-emerald-400 font-semibold">100% Cash Matched</span>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB 4: Professional Tax Invoices -->
        <div v-if="activeTab === 'invoicing'" class="p-6 sm:p-8 space-y-4">
          <div class="p-6 sm:p-8 rounded-xl bg-[#0b101c] border border-slate-800 max-w-2xl mx-auto space-y-5 shadow-xl">
            <div class="flex justify-between items-start border-b border-slate-800 pb-4">
              <div>
                <div class="text-xl font-bold text-white tracking-wide">OFFICIAL TAX INVOICE</div>
                <div class="text-xs text-slate-400 mt-0.5">Invoice No: <span class="font-mono text-cyan-400 font-semibold">INV-2026-0001</span></div>
                <div class="text-[11px] text-slate-400">Date: August 23, 2026</div>
              </div>
              <div class="text-right text-xs">
                <div class="font-bold text-white">Demo Retail & Distribution PLC</div>
                <div class="text-slate-400">Bole Medhanealem, Addis Ababa</div>
                <div class="text-cyan-400 font-mono font-semibold">TIN: 0000001234</div>
              </div>
            </div>

            <div class="text-xs grid grid-cols-2 gap-4 text-slate-300">
              <div>
                <span class="text-slate-400 uppercase font-semibold text-[11px]">Customer Name:</span>
                <div class="font-semibold text-white mt-1">Ethiopian Airlines Logistics Enterprise</div>
                <div class="text-slate-400">Bole International Cargo Terminal</div>
                <div class="text-slate-400 font-mono">TIN: 0048291039</div>
              </div>
              <div class="text-right">
                <span class="text-slate-400 uppercase font-semibold text-[11px]">Payment Status:</span>
                <div class="text-emerald-400 font-bold mt-1">PAID IN FULL</div>
                <div class="text-slate-400 text-[11px]">Commercial Bank of Ethiopia</div>
              </div>
            </div>

            <div class="border-t border-slate-800 pt-3">
              <table class="w-full text-xs text-left">
                <thead>
                  <tr class="text-slate-400 border-b border-slate-800/60 pb-2">
                    <th class="py-1">Product Description</th>
                    <th class="text-right py-1">Qty</th>
                    <th class="text-right py-1">Unit Price</th>
                    <th class="text-right py-1">Total</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/40 text-slate-300 font-mono">
                  <tr>
                    <td class="py-2.5 font-sans">Business Sales Laptop (16GB RAM / Fast SSD)</td>
                    <td class="text-right">2</td>
                    <td class="text-right">ETB 85,000.00</td>
                    <td class="text-right text-white">ETB 170,000.00</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="border-t border-slate-800 pt-3 flex justify-end">
              <div class="w-56 text-xs space-y-1.5 font-mono">
                <div class="flex justify-between text-slate-400"><span>Subtotal:</span><span>ETB 170,000.00</span></div>
                <div class="flex justify-between text-slate-400"><span>VAT (15% Tax):</span><span>ETB 25,500.00</span></div>
                <div class="flex justify-between font-bold text-white text-sm pt-1.5 border-t border-slate-800">
                  <span>Total Paid:</span><span class="text-cyan-400">ETB 195,500.00</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB 5: Staff & Payroll -->
        <div v-if="activeTab === 'payroll'" class="p-6 sm:p-8 space-y-5">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-slate-800 gap-2">
            <div>
              <h3 class="text-base font-bold text-white">1-Click Staff Payroll with Automatic Pension Deductions</h3>
              <p class="text-xs text-slate-300">Automatically calculates Ethiopian income tax brackets, 7% employee pension, and 11% employer contributions.</p>
            </div>
            <span class="text-xs font-mono text-cyan-400 bg-cyan-950/40 px-3 py-1 rounded-full border border-cyan-800/40 self-start sm:self-auto">
              Payroll Period: Current Month
            </span>
          </div>

          <div class="divide-y divide-slate-800/60">
            <!-- Staff 1 -->
            <div class="py-3.5 flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-cyan-500/20 text-cyan-400 flex items-center justify-center font-bold text-xs">
                  SJ
                </div>
                <div>
                  <div class="text-sm font-semibold text-white">Sarah Jenkins</div>
                  <div class="text-xs text-slate-400">General Manager • Bole Store</div>
                </div>
              </div>
              <div class="text-right text-xs">
                <div class="font-mono font-bold text-emerald-400">
                  {{ selectedCurrency === 'ETB' ? 'ETB 145,000.00 Take-Home' : '$1,450.00' }}
                </div>
                <div class="text-slate-400 font-mono">Tax: ETB 28,400 | Pension (7%): ETB 10,150</div>
              </div>
            </div>

            <!-- Staff 2 -->
            <div class="py-3.5 flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-indigo-500/20 text-indigo-400 flex items-center justify-center font-bold text-xs">
                  MC
                </div>
                <div>
                  <div class="text-sm font-semibold text-white">Marcus Chen</div>
                  <div class="text-xs text-slate-400">Head of Purchasing & Warehousing</div>
                </div>
              </div>
              <div class="text-right text-xs">
                <div class="font-mono font-bold text-emerald-400">
                  {{ selectedCurrency === 'ETB' ? 'ETB 98,000.00 Take-Home' : '$980.00' }}
                </div>
                <div class="text-slate-400 font-mono">Tax: ETB 19,200 | Pension (7%): ETB 6,860</div>
              </div>
            </div>

            <!-- Staff 3 -->
            <div class="py-3.5 flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-blue-500/20 text-blue-400 flex items-center justify-center font-bold text-xs">
                  DB
                </div>
                <div>
                  <div class="text-sm font-semibold text-white">Dawit Bekele</div>
                  <div class="text-xs text-slate-400">Senior Cashier & Sales Representative</div>
                </div>
              </div>
              <div class="text-right text-xs">
                <div class="font-mono font-bold text-emerald-400">
                  {{ selectedCurrency === 'ETB' ? 'ETB 75,000.00 Take-Home' : '$750.00' }}
                </div>
                <div class="text-slate-400 font-mono">Tax: ETB 14,700 | Pension (7%): ETB 5,250</div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </section>

    <!-- ========================================================= -->
    <!-- FEATURES SECTION (HOW BINA ERP HELPS YOUR BUSINESS) -->
    <!-- ========================================================= -->
    <section id="features" class="py-24 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto relative z-10">
      <div class="text-center max-w-3xl mx-auto mb-16">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-950/60 border border-indigo-800/50 text-xs font-semibold text-indigo-400 uppercase tracking-wider mb-3">
          <Store :size="13" />
          Built for Everyday Business Owners
        </div>
        <h2 class="text-3xl sm:text-5xl font-bold text-white tracking-tight">
          Everything You Need to Run & Grow Your Shop
        </h2>
        <p class="mt-4 text-slate-300 text-base sm:text-lg">
          No complicated computer science degrees required. Easy, clear, and designed to save you hours every single day.
        </p>
      </div>

      <!-- Bento Grid Layout of Real Benefits -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Card 1: Multi-Cashier POS (2-col) -->
        <div class="md:col-span-2 p-8 rounded-2xl bg-gradient-to-br from-slate-900/80 to-[#0c1222] border border-slate-800 relative overflow-hidden group hover:border-cyan-500/40 transition-all">
          <div class="absolute -right-10 -top-10 w-48 h-48 bg-cyan-500/10 rounded-full blur-3xl pointer-events-none group-hover:bg-cyan-500/20 transition-all"></div>
          <div class="p-3 w-fit rounded-xl bg-cyan-500/10 text-cyan-400 mb-6">
            <ShoppingCart :size="26" />
          </div>
          <h3 class="text-2xl font-bold text-white">Super-Fast Checkout with Multiple Cashiers</h3>
          <p class="mt-3 text-slate-300 text-sm sm:text-base leading-relaxed">
            Never keep customers waiting in line. Multiple cashiers can ring up sales simultaneously on separate registers or counters with zero conflicts. Plug in your receipt printer, scanner, and cash drawer and start selling immediately.
          </p>
          <div class="mt-6 flex flex-wrap gap-2 text-xs font-medium">
            <span class="px-3 py-1 rounded-md bg-slate-800/90 text-cyan-300 border border-slate-700/50">Multiple Cashiers at Once</span>
            <span class="px-3 py-1 rounded-md bg-slate-800/90 text-cyan-300 border border-slate-700/50">Works Completely Offline</span>
            <span class="px-3 py-1 rounded-md bg-slate-800/90 text-cyan-300 border border-slate-700/50">TIN & 15% VAT Receipts</span>
          </div>
        </div>

        <!-- Card 2: Daily Profit Reports -->
        <div class="p-8 rounded-2xl bg-gradient-to-br from-slate-900/80 to-[#0c1222] border border-slate-800 relative overflow-hidden group hover:border-blue-500/40 transition-all">
          <div class="p-3 w-fit rounded-xl bg-blue-500/10 text-blue-400 mb-6">
            <Banknote :size="26" />
          </div>
          <h3 class="text-xl font-bold text-white">Know Today's Real Profit</h3>
          <p class="mt-3 text-slate-300 text-sm leading-relaxed">
            Stop guessing if you made money today. See today's sales, product costs, and clear net profit calculated automatically to the penny.
          </p>
          <div class="mt-6 flex flex-wrap gap-2 text-xs font-medium">
            <span class="px-3 py-1 rounded-md bg-slate-800/90 text-blue-300 border border-slate-700/50">Instant Profit & Loss</span>
            <span class="px-3 py-1 rounded-md bg-slate-800/90 text-blue-300 border border-slate-700/50">Daily Cash Drawer Match</span>
          </div>
        </div>

        <!-- Card 3: Multi-Branch Warehousing -->
        <div class="p-8 rounded-2xl bg-gradient-to-br from-slate-900/80 to-[#0c1222] border border-slate-800 relative overflow-hidden group hover:border-emerald-500/40 transition-all">
          <div class="p-3 w-fit rounded-xl bg-emerald-500/10 text-emerald-400 mb-6">
            <Warehouse :size="26" />
          </div>
          <h3 class="text-xl font-bold text-white">Stop Stock Theft & Shortages</h3>
          <p class="mt-3 text-slate-300 text-sm leading-relaxed">
            Track stock in your warehouse and on your shop shelves. Get instant phone alerts when bestsellers are running low before you run out.
          </p>
          <div class="mt-6 flex flex-wrap gap-2 text-xs font-medium">
            <span class="px-3 py-1 rounded-md bg-slate-800/90 text-emerald-300 border border-slate-700/50">Inter-Branch Transfers</span>
            <span class="px-3 py-1 rounded-md bg-slate-800/90 text-emerald-300 border border-slate-700/50">Automatic Low-Stock Alerts</span>
          </div>
        </div>

        <!-- Card 4: Staff Attendance & Payroll (2-col) -->
        <div class="md:col-span-2 p-8 rounded-2xl bg-gradient-to-br from-slate-900/80 to-[#0c1222] border border-slate-800 relative overflow-hidden group hover:border-indigo-500/40 transition-all">
          <div class="absolute -right-10 -top-10 w-48 h-48 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none group-hover:bg-indigo-500/20 transition-all"></div>
          <div class="p-3 w-fit rounded-xl bg-indigo-500/10 text-indigo-400 mb-6">
            <Users :size="26" />
          </div>
          <h3 class="text-2xl font-bold text-white">Staff Management & 1-Click Monthly Salary</h3>
          <p class="mt-3 text-slate-300 text-sm sm:text-base leading-relaxed">
            Keep track of staff attendance, working shifts, and leaves. When month-end arrives, calculate everyone's salary with Ethiopian tax and standard 7% pension deductions with one button click, and print professional payslips.
          </p>
          <div class="mt-6 flex flex-wrap gap-2 text-xs font-medium">
            <span class="px-3 py-1 rounded-md bg-slate-800/90 text-indigo-300 border border-slate-700/50">Automatic Salary Calculations</span>
            <span class="px-3 py-1 rounded-md bg-slate-800/90 text-indigo-300 border border-slate-700/50">Ethiopian 7% Pension Rules</span>
            <span class="px-3 py-1 rounded-md bg-slate-800/90 text-indigo-300 border border-slate-700/50">Cashier Role Permissions</span>
          </div>
        </div>

      </div>
    </section>

    <!-- ========================================================= -->
    <!-- INTERACTIVE PROFIT & TIME SAVINGS CALCULATOR -->
    <!-- ========================================================= -->
    <section id="calculator" class="py-20 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto relative z-10">
      <div class="p-8 sm:p-12 rounded-3xl bg-gradient-to-br from-[#0c1224] via-[#090e1c] to-[#0a0f1d] border border-slate-800 shadow-2xl relative overflow-hidden">
        
        <div class="max-w-3xl mb-10">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-950/60 border border-emerald-800/50 text-xs font-semibold text-emerald-400 uppercase tracking-wider mb-3">
            <Calculator :size="13" />
            Shop Owner Savings Calculator
          </div>
          <h2 class="text-3xl sm:text-4xl font-bold text-white">
            Calculate How Much Time & Money You Save
          </h2>
          <p class="mt-2 text-slate-300 text-sm sm:text-base">
            Adjust the sliders below to see how eliminating paper notebooks, manual stock counting, and billing mistakes saves your business money every month.
          </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
          <!-- Left Sliders -->
          <div class="lg:col-span-7 space-y-6">
            <!-- Staff Slider -->
            <div class="p-5 rounded-xl bg-slate-900/60 border border-slate-800 space-y-3">
              <div class="flex justify-between items-center">
                <label class="text-sm font-semibold text-white">Number of Cashiers & Store Staff</label>
                <span class="text-cyan-400 font-bold font-mono text-base">{{ staffCount }} Staff Members</span>
              </div>
              <input 
                type="range" 
                v-model.number="staffCount" 
                min="2" 
                max="100" 
                step="1"
                class="w-full h-2 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-cyan-400"
              />
              <div class="flex justify-between text-[11px] text-slate-400 font-mono">
                <span>2 (Small Store)</span>
                <span>20 (Medium Supermarket)</span>
                <span>100+ (Multi-Branch Chain)</span>
              </div>
            </div>

            <!-- Average Hourly Rate Slider -->
            <div class="p-5 rounded-xl bg-slate-900/60 border border-slate-800 space-y-3">
              <div class="flex justify-between items-center">
                <label class="text-sm font-semibold text-white">
                  Average Hourly Staff Pay ({{ selectedCurrency }})
                </label>
                <span class="text-emerald-400 font-bold font-mono text-base">
                  {{ selectedCurrency === 'ETB' ? `ETB ${averageWageEtb} / hr` : `$${averageWageUsd} / hr` }}
                </span>
              </div>
              <input 
                v-if="selectedCurrency === 'ETB'"
                type="range" 
                v-model.number="averageWageEtb" 
                min="50" 
                max="1000" 
                step="25"
                class="w-full h-2 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-emerald-400"
              />
              <input 
                v-else
                type="range" 
                v-model.number="averageWageUsd" 
                min="5" 
                max="100" 
                step="5"
                class="w-full h-2 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-emerald-400"
              />
              <div class="flex justify-between text-[11px] text-slate-400 font-mono">
                <span>{{ selectedCurrency === 'ETB' ? 'ETB 50/hr' : '$5/hr' }}</span>
                <span>{{ selectedCurrency === 'ETB' ? 'ETB 500/hr' : '$50/hr' }}</span>
                <span>{{ selectedCurrency === 'ETB' ? 'ETB 1,000/hr' : '$100/hr' }}</span>
              </div>
            </div>
          </div>

          <!-- Right Results Card -->
          <div class="lg:col-span-5 p-6 rounded-2xl bg-gradient-to-br from-cyan-950/40 via-slate-900/90 to-blue-950/40 border border-cyan-800/40 space-y-6">
            <div>
              <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Estimated Annual Savings</div>
              <div class="text-3xl sm:text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 via-teal-300 to-cyan-400 font-mono mt-2">
                {{ selectedCurrency === 'ETB' ? `ETB ${yearlyMoneySaved.toLocaleString()}` : `$${yearlyMoneySaved.toLocaleString()}` }}
              </div>
              <div class="text-xs text-slate-300 mt-1">
                You save approx. <span class="text-white font-bold">{{ selectedCurrency === 'ETB' ? `ETB ${monthlyMoneySaved.toLocaleString()}` : `$${monthlyMoneySaved.toLocaleString()}` }}</span> every single month
              </div>
            </div>

            <div class="pt-4 border-t border-slate-800 space-y-2">
              <div class="flex justify-between text-xs text-slate-300">
                <span>Staff Hours Saved from Manual Paperwork:</span>
                <span class="font-bold text-white font-mono">{{ hoursSavedPerYear.toLocaleString() }} hours / year</span>
              </div>
              <div class="flex justify-between text-xs text-slate-300">
                <span>Billing & Stock Accuracy:</span>
                <span class="font-bold text-emerald-400">99.8% Error-Free</span>
              </div>
            </div>

            <button 
              @click="authStore.isAuthenticated ? navigateToDashboard() : navigateToAuth('register')"
              class="w-full py-3.5 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-white font-bold text-sm shadow-xl shadow-cyan-500/20 transition-all hover:scale-[1.02]"
            >
              Start Free Trial & Start Saving Today
            </button>
          </div>
        </div>

      </div>
    </section>

    <!-- ========================================================= -->
    <!-- TRANSPARENT PRICING PLANS -->
    <!-- ========================================================= -->
    <section id="pricing" class="py-24 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto relative z-10">
      <div class="text-center max-w-3xl mx-auto mb-12">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-cyan-950/60 border border-cyan-800/50 text-xs font-semibold text-cyan-400 uppercase tracking-wider mb-3">
          <DollarSign :size="13" />
          Simple, Honest Pricing
        </div>
        <h2 class="text-3xl sm:text-5xl font-bold text-white tracking-tight">
          Choose the Perfect Plan for Your Business
        </h2>
        <p class="mt-4 text-slate-300 text-base sm:text-lg">
          No surprise setup fees. No long contracts. Upgrade, change, or export your full store data at any time.
        </p>

        <!-- Controls: Billing Toggle & Currency Switcher -->
        <div class="mt-8 flex flex-wrap items-center justify-center gap-4 sm:gap-6">
          <!-- Currency Toggle -->
          <div class="flex items-center rounded-lg bg-slate-900 border border-slate-800 p-0.5 text-xs font-semibold">
            <button 
              @click="selectedCurrency = 'ETB'"
              class="px-2.5 py-1 rounded-md transition-all"
              :class="[selectedCurrency === 'ETB' ? 'bg-cyan-500 text-white shadow-sm' : 'text-slate-400 hover:text-white']"
            >
              ETB (Br)
            </button>
            <button 
              @click="selectedCurrency = 'USD'"
              class="px-2.5 py-1 rounded-md transition-all"
              :class="[selectedCurrency === 'USD' ? 'bg-cyan-500 text-white shadow-sm' : 'text-slate-400 hover:text-white']"
            >
              USD ($)
            </button>
          </div>

          <div class="h-4 w-px bg-slate-800 hidden sm:block"></div>

          <!-- Billing Period -->
          <div class="flex items-center gap-3">
            <span class="text-sm font-medium" :class="[!isAnnual ? 'text-white' : 'text-slate-400']">Monthly Billing</span>
            <button 
              @click="isAnnual = !isAnnual"
              class="w-12 h-6 rounded-full bg-slate-800 p-1 transition-colors relative"
              :class="[isAnnual ? 'bg-cyan-500' : 'bg-slate-700']"
              aria-label="Toggle annual billing"
            >
              <div 
                class="w-4 h-4 rounded-full bg-white transition-transform"
                :class="[isAnnual ? 'translate-x-6' : 'translate-x-0']"
              ></div>
            </button>
            <span class="text-sm font-medium flex items-center gap-1.5" :class="[isAnnual ? 'text-white' : 'text-slate-400']">
              Annual Billing
              <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                2 Months Free (~17% Savings)
              </span>
            </span>
          </div>
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
            
            <p class="text-xs text-slate-300 mt-2 min-h-[36px]">{{ tier.tagline }}</p>

            <!-- Price -->
            <div class="mt-6 flex items-baseline gap-1">
              <span class="text-3xl sm:text-4xl font-extrabold text-white font-mono">
                {{ selectedCurrency === 'ETB' 
                  ? (isAnnual ? `ETB ${tier.annualEtb.toLocaleString()}` : `ETB ${tier.monthlyEtb.toLocaleString()}`)
                  : (isAnnual ? `$${tier.annualUsd}` : `$${tier.monthlyUsd}`)
                }}
              </span>
              <span class="text-xs text-slate-400">/ month</span>
            </div>
            <div class="text-[11px] text-slate-400 mt-1">
              {{ isAnnual 
                ? (selectedCurrency === 'ETB' ? `Billed ETB ${(tier.annualEtb * 12).toLocaleString()} once a year` : `Billed $${(tier.annualUsd * 12).toFixed(0)} once a year`)
                : 'Billed monthly, cancel anytime'
              }}
            </div>

            <!-- Features List -->
            <div class="mt-8 space-y-3 border-t border-slate-800/80 pt-6">
              <div v-for="feat in tier.perks" :key="feat" class="flex items-start gap-2.5 text-xs text-slate-200">
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
    <!-- FAQ SECTION (WRITTEN FOR STORE MANAGERS & OWNERS) -->
    <!-- ========================================================= -->
    <section id="faq" class="py-20 px-4 sm:px-6 lg:px-8 max-w-4xl mx-auto relative z-10">
      <div class="text-center mb-12">
        <h2 class="text-3xl sm:text-4xl font-bold text-white tracking-tight">
          Frequently Asked Questions
        </h2>
        <p class="mt-3 text-slate-300 text-sm sm:text-base">
          Clear answers about equipment, receipts, internet reliability, and setup.
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
            class="px-5 pb-5 text-xs sm:text-sm text-slate-300 leading-relaxed border-t border-slate-800/60 pt-3"
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
            Ready to Take Full Control of Your Store?
          </h2>
          <p class="mt-4 text-slate-300 text-base sm:text-lg">
            Start your 14-day free trial in under 2 minutes. No credit card required. Experience faster checkouts, accurate stock, and clear daily profits today.
          </p>
          <div class="mt-8 flex flex-col sm:flex-row justify-center gap-4">
            <button 
              @click="authStore.isAuthenticated ? navigateToDashboard() : navigateToAuth('register')"
              class="px-8 py-4 text-base font-bold rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-white shadow-xl shadow-cyan-500/30 transition-all hover:scale-[1.03]"
            >
              {{ authStore.isAuthenticated ? 'Open My Store Workspace' : 'Start 14-Day Free Trial' }}
            </button>
            <button 
              @click="navigateToAuth('login')"
              class="px-8 py-4 text-base font-semibold rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-200 border border-slate-700/80 transition-all"
            >
              Sign In to Existing Account
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
            The complete, easy-to-use business management platform. High-speed cashier checkout, stock & warehouse tracking, official Ethiopian tax invoices, and automated payroll.
          </p>
          <div class="flex items-center gap-2 text-emerald-400 font-medium text-[11px]">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            100% Private, Bank-Grade Data Security
          </div>
        </div>

        <!-- Col 2: Store Features -->
        <div>
          <div class="font-semibold text-white mb-3 text-xs uppercase tracking-wider">Store Features</div>
          <ul class="space-y-2">
            <li><a href="#demo" class="hover:text-cyan-400 transition-colors">Cashier POS Checkout</a></li>
            <li><a href="#demo" class="hover:text-cyan-400 transition-colors">Stock & Warehousing</a></li>
            <li><a href="#demo" class="hover:text-cyan-400 transition-colors">Daily Sales & Profits</a></li>
            <li><a href="#demo" class="hover:text-cyan-400 transition-colors">TIN & 15% VAT Invoicing</a></li>
            <li><a href="#demo" class="hover:text-cyan-400 transition-colors">Staff & Salary Payroll</a></li>
          </ul>
        </div>

        <!-- Col 3: Resources -->
        <div>
          <div class="font-semibold text-white mb-3 text-xs uppercase tracking-wider">Help & Plans</div>
          <ul class="space-y-2">
            <li><a href="#calculator" class="hover:text-cyan-400 transition-colors">Savings Calculator</a></li>
            <li><a href="#pricing" class="hover:text-cyan-400 transition-colors">Pricing & Plans</a></li>
            <li><a href="#faq" class="hover:text-cyan-400 transition-colors">Frequently Asked Questions</a></li>
            <li><router-link to="/login" class="hover:text-cyan-400 transition-colors">Store Owner Sign In</router-link></li>
          </ul>
        </div>

        <!-- Col 4: Trust & Compliance -->
        <div>
          <div class="font-semibold text-white mb-3 text-xs uppercase tracking-wider">Trust & Security</div>
          <ul class="space-y-2">
            <li><span class="text-slate-400">Ethiopian Tax & TIN Compliant</span></li>
            <li><span class="text-slate-400">15% VAT Ready Receipts</span></li>
            <li><span class="text-slate-400">Offline Register Protection</span></li>
            <li><span class="text-slate-400">Automatic Hourly Cloud Backups</span></li>
          </ul>
        </div>

      </div>

      <div class="max-w-7xl mx-auto pt-8 mt-8 border-t border-slate-800/60 flex flex-col sm:flex-row items-center justify-between text-slate-500 gap-4">
        <div>© 2026 Bina ERP Platform. All rights reserved.</div>
        <div class="flex gap-6">
          <a href="#" class="hover:text-slate-400">Privacy Policy</a>
          <a href="#" class="hover:text-slate-400">Terms of Service</a>
          <a href="#" class="hover:text-slate-400">Customer Support</a>
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
