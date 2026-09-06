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
  Workflow,
  Store,
  Factory,
  Briefcase,
  Headphones
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
// Real Currency State (ETB / USD)
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
const activeTab = ref<'analytics' | 'pos' | 'inventory' | 'invoicing' | 'hr'>('analytics')

// Real Products from TenantDemoDataSeeder
const realCatalog = [
  {
    id: 1,
    name: 'Epson 80mm High-Speed Thermal Receipt Printer',
    sku: 'POS-PRN-80',
    category: 'POS Hardware',
    priceEtb: 12500,
    priceUsd: 125,
    icon: '🖨️',
    desc: 'ESC/POS USB & LAN network auto-cutter printer'
  },
  {
    id: 2,
    name: 'Omni-Directional 2D POS Barcode Scanner',
    sku: 'POS-SCAN-2D',
    category: 'POS Hardware',
    priceEtb: 7500,
    priceUsd: 75,
    icon: '🔍',
    desc: 'Hands-free high-speed optical barcode reader'
  },
  {
    id: 3,
    name: 'Heavy-Duty RJ11 Electric Cash Drawer (5B/8C)',
    sku: 'POS-DRW-HD',
    category: 'POS Hardware',
    priceEtb: 5800,
    priceUsd: 58,
    icon: '💵',
    desc: 'Steel roller ball-bearing multi-coin till'
  },
  {
    id: 4,
    name: 'Specialty Grade 1 Yirgacheffe Arabica Coffee (1kg)',
    sku: 'RET-COF-YRG',
    category: 'Retail & Consumables',
    priceEtb: 850,
    priceUsd: 8.5,
    icon: '☕',
    desc: 'Single-origin washed roasted whole beans'
  },
  {
    id: 5,
    name: 'Universal USB-C Dual 4K Docking Station 100W PD',
    sku: 'ACC-DOCK-4K',
    category: 'Accessories',
    priceEtb: 9200,
    priceUsd: 92,
    icon: '🔌',
    desc: 'Thunderbolt compatible multiport enterprise hub'
  },
  {
    id: 6,
    name: 'Dell Latitude 5440 i7 Laptop (16GB/512GB SSD)',
    sku: 'LAP-DELL-5440',
    category: 'Computers & Laptops',
    priceEtb: 85000,
    priceUsd: 850,
    icon: '💻',
    desc: 'Enterprise business laptop with Windows 11 Pro'
  },
]

// POS Sandbox Cart State
const posCart = ref([
  {
    id: 1,
    name: 'Epson 80mm High-Speed Thermal Receipt Printer',
    sku: 'POS-PRN-80',
    priceEtb: 12500,
    priceUsd: 125,
    qty: 1
  },
  {
    id: 4,
    name: 'Specialty Grade 1 Yirgacheffe Arabica Coffee (1kg)',
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
// Interactive ROI & Productivity Calculator (Real-World Parameters)
// -------------------------------------------------------------
const teamSize = ref(25)
const hourlyWageEtb = ref(250)
const hourlyWageUsd = ref(25)

const currentWage = computed(() => {
  return selectedCurrency.value === 'ETB' ? hourlyWageEtb.value : hourlyWageUsd.value
})

const manualHoursPerWeek = computed(() => Math.round(teamSize.value * 5.5))
const monthlyCostSaved = computed(() => {
  const weeklyHours = manualHoursPerWeek.value
  const weeklyMoney = weeklyHours * currentWage.value * 0.68 // 68% manual friction eliminated
  return Math.round(weeklyMoney * 4.33)
})
const yearlyCostSaved = computed(() => monthlyCostSaved.value * 12)
const hoursSavedPerYear = computed(() => Math.round(manualHoursPerWeek.value * 52 * 0.68))

// -------------------------------------------------------------
// Real Pricing Plans (Direct from Database PlanSeeder)
// -------------------------------------------------------------
const isAnnual = ref(true)

const pricingTiers = computed(() => [
  {
    name: 'Basic',
    badge: 'Starter',
    tagline: 'Essential tools for single-location shops and early-stage small businesses.',
    monthlyEtb: 1000,
    annualEtb: 833, // 10,000 ETB / 12 mo (Save ~17%)
    monthlyUsd: 10,
    annualUsd: 8.33,
    storage: '5 GB',
    usersLimit: 'Up to 5 Team Member Seats',
    modulesCount: '5 Core Modules',
    perks: [
      'Up to 5 Team Member Seats',
      'Up to 100 Invoices & Quotations / month',
      'Point of Sale (POS) Cashier Terminal with Receipt Printing',
      'Basic Single-Warehouse Stock & Product Catalog',
      'Core CRM Contacts & Embedded Lead Capture Forms',
      'Standard Revenue & Tax Calculation Summary',
      'Basic Data Export (CSV / Excel)',
      'Standard Email Support (48-hour response)'
    ],
    highlight: false,
    cta: 'Start with Basic'
  },
  {
    name: 'Professional',
    badge: 'Most Popular',
    tagline: 'Full-spectrum ERP for growing companies requiring accounting, HR & procurement.',
    monthlyEtb: 2500,
    annualEtb: 2083, // 25,000 ETB / 12 mo (Save ~17%)
    monthlyUsd: 25,
    annualUsd: 20.83,
    storage: '50 GB',
    usersLimit: 'Up to 25 Team Member Seats',
    modulesCount: '12 Advanced Modules',
    perks: [
      'Everything in Basic, plus:',
      'Up to 25 Team Member Seats',
      'Unlimited Sales Invoices, Estimates & Credit Notes',
      'Full Double-Entry Accounting (General Ledger, Journals, Aging, P&L)',
      'Procurement Management (Purchase Orders, Bills & Suppliers)',
      'Complete HR Suite (Employees, Attendance & Leave Approvals)',
      'Payroll Run Engine & Automated Payslips',
      'Multi-Warehouse Inventory & Internal Transfer Orders',
      'Project Task Boards, Milestones & Billable Time Tracking',
      'Customer Support Helpdesk & Knowledge Base Articles',
      'Fixed Asset Management & Depreciation Schedules',
      'Custom Domain Mapping (e.g. portal.yourcompany.com)',
      'Priority Business Support (12-hour response)'
    ],
    highlight: true,
    cta: 'Launch Professional'
  },
  {
    name: 'Enterprise',
    badge: 'All-Inclusive',
    tagline: 'Unrestricted platform operations with manufacturing, webhooks, ecommerce & dedicated SLA.',
    monthlyEtb: 5000,
    annualEtb: 4166, // 50,000 ETB / 12 mo (Save ~17%)
    monthlyUsd: 50,
    annualUsd: 41.66,
    storage: '500 GB',
    usersLimit: 'Unlimited User Seats',
    modulesCount: 'All 15+ Modules Unlocked',
    perks: [
      'Everything in Professional, plus:',
      'Unlimited Team Members, User Seats & Workspaces',
      'Manufacturing & Production (BOMs & Work Orders)',
      'Multi-Storefront Ecommerce & Digital Product Catalog Engine',
      'Real-Time Webhook Engine & 12-Gateway Connectors (Slack, Stripe, Zapier)',
      'Dedicated PostgreSQL Schema Isolation & Security Guard',
      'Cross-Tenant Super Admin Workspace Impersonation',
      'Hourly Automated Database Backups & Snapshots',
      'Full Audit Log History & Activity Telemetry',
      '24/7 Dedicated Account Rep with 1-Hour SLA'
    ],
    highlight: false,
    cta: 'Get Enterprise Suite'
  }
])

// -------------------------------------------------------------
// FAQ Accordion State (Real System Architecture Details)
// -------------------------------------------------------------
const openFaq = ref<number | null>(0)
const toggleFaq = (index: number) => {
  openFaq.value = openFaq.value === index ? null : index
}

const faqs = [
  {
    q: 'Can multiple cashiers run POS sessions concurrently in different branches or checkout lanes?',
    a: 'Yes. Bina ERP features an advanced multi-session POS architecture. Each cashier can open independent POS sessions with their own float balance across physical branches (such as Bole Flagship Store and Kazanchis Branch). Real-time browser synchronization via BroadcastChannel ensures cashiers never collide or lock records.'
  },
  {
    q: 'Does the system print tax-compliant receipts with Company TIN and 15% VAT breakdowns?',
    a: 'Yes. Every receipt, proforma estimate, sales invoice, and procurement order automatically incorporates your registered legal company name, Tax Identification Number (TIN), itemized 15% VAT calculations, and custom footer notices for standard thermal printers (58mm/80mm ESC/POS) and A4 PDF printing.'
  },
  {
    q: 'How is tenant data security and database isolation guaranteed?',
    a: 'Bina ERP is engineered with PostgreSQL schema-level tenant isolation. Every registered enterprise receives its own dedicated PostgreSQL schema, preventing cross-tenant data leaks and allowing independent migrations, custom fields, and automated hourly database snapshot backups.'
  },
  {
    q: 'Can we connect external POS hardware like thermal printers, cash drawers, and barcode scanners?',
    a: 'Absolutely. The web POS client integrates natively with standard ESC/POS USB, Ethernet, and Bluetooth thermal printers, RJ11 kick-out cash drawers, and optical 1D/2D barcode scanners with real-time autofocus scanning.'
  },
  {
    q: 'How does the automated double-entry accounting engine work?',
    a: 'Whenever a POS sale is finalized, an invoice is paid, or a procurement order is received, Bina ERP automatically creates balanced double-entry journal entries against your Chart of Accounts (Cash, Accounts Receivable, Inventory Assets, Sales Revenue, and VAT Payable). Trial Balances, P&L statements, and Balance Sheets update instantly.'
  },
  {
    q: 'How are Ethiopian tax rules, employee pension (7%), and payroll withholding handled?',
    a: 'The HR & Payroll module includes predefined Ethiopian labor tax brackets, standard 7% employee pension deductions, 11% employer pension contributions, and overtime rate multipliers. You can generate monthly payroll runs and print itemized payslips with a single click.'
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
        
        <!-- Logo & Platform Version -->
        <router-link to="/" class="flex items-center gap-3 group">
          <AppLogo light size="sm" />
          <span class="hidden sm:inline-flex items-center gap-1.5 px-2 py-0.5 text-[11px] font-semibold tracking-wide bg-cyan-950/60 border border-cyan-800/50 text-cyan-400 rounded-full">
            <span class="w-1.5 h-1.5 rounded-full bg-cyan-400 animate-pulse"></span>
            Cloud v2.5
          </span>
        </router-link>

        <!-- Desktop Navigation Links -->
        <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-300">
          <a href="#modules" class="hover:text-cyan-400 transition-colors">Core Modules</a>
          <a href="#live-sandbox" class="hover:text-cyan-400 transition-colors">Live Preview</a>
          <a href="#roi-calculator" class="hover:text-cyan-400 transition-colors">ROI Calculator</a>
          <a href="#pricing" class="hover:text-cyan-400 transition-colors">Pricing & Plans</a>
          <a href="#faq" class="hover:text-cyan-400 transition-colors">FAQ</a>
        </nav>

        <!-- Header Actions: Currency Selector & Auth Controls -->
        <div class="hidden sm:flex items-center gap-3">
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
              <span>Start Free Trial</span>
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
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
          <span class="text-xs text-slate-400">Display Currency:</span>
          <div class="flex items-center rounded-lg bg-slate-900 border border-slate-800 p-0.5 text-xs font-semibold">
            <button 
              @click="selectedCurrency = 'ETB'"
              class="px-3 py-1 rounded-md transition-all"
              :class="[selectedCurrency === 'ETB' ? 'bg-cyan-500 text-white' : 'text-slate-400']"
            >
              ETB (Br)
            </button>
            <button 
              @click="selectedCurrency = 'USD'"
              class="px-3 py-1 rounded-md transition-all"
              :class="[selectedCurrency === 'USD' ? 'bg-cyan-500 text-white' : 'text-slate-400']"
            >
              USD ($)
            </button>
          </div>
        </div>
        <nav class="flex flex-col space-y-3 text-base font-medium text-slate-300">
          <a @click="mobileMenuOpen = false" href="#modules" class="hover:text-cyan-400 py-1">Core Modules</a>
          <a @click="mobileMenuOpen = false" href="#live-sandbox" class="hover:text-cyan-400 py-1">Live Preview</a>
          <a @click="mobileMenuOpen = false" href="#roi-calculator" class="hover:text-cyan-400 py-1">ROI Calculator</a>
          <a @click="mobileMenuOpen = false" href="#pricing" class="hover:text-cyan-400 py-1">Pricing & Plans</a>
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
              Start Free Trial
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
        <span class="text-cyan-400 font-semibold">Bina ERP Cloud Platform</span>
        <span class="text-slate-600">|</span>
        <span class="text-slate-300">Multi-Session POS, Real-Time Accounting & Omnichannel</span>
        <ArrowRight :size="14" class="text-slate-400" />
      </div>

      <!-- Main Headline -->
      <h1 class="text-4xl sm:text-6xl lg:text-7xl font-extrabold tracking-tight text-white max-w-5xl mx-auto leading-[1.12]">
        The Operating System for <br class="hidden sm:block"/>
        <span class="bg-clip-text text-transparent bg-gradient-to-r from-cyan-400 via-blue-400 to-indigo-400">
          Modern Enterprises & Multi-Store Chains
        </span>
      </h1>

      <!-- Subtitle -->
      <p class="mt-6 text-lg sm:text-xl text-slate-400 max-w-3xl mx-auto leading-relaxed font-normal">
        Unify your operations across high-speed multi-cashier POS, compliant double-entry general ledgers, multi-warehouse logistics, automated HR & payroll, and digital commerce in one lightning-fast workspace.
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
          <span>Explore Live Sandbox</span>
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
            <Layers :size="22" />
          </div>
          <div>
            <div class="text-lg font-bold text-white">15+ Modules</div>
            <div class="text-xs text-slate-400">Complete ERP Capabilities</div>
          </div>
        </div>

        <div class="flex items-center gap-3.5 p-3 rounded-xl bg-slate-900/40 border border-slate-800/40">
          <div class="p-2.5 rounded-lg bg-blue-500/10 text-blue-400 shrink-0">
            <Database :size="22" />
          </div>
          <div>
            <div class="text-lg font-bold text-white">PostgreSQL</div>
            <div class="text-xs text-slate-400">Schema-Isolated Tenancy</div>
          </div>
        </div>

        <div class="flex items-center gap-3.5 p-3 rounded-xl bg-slate-900/40 border border-slate-800/40">
          <div class="p-2.5 rounded-lg bg-indigo-500/10 text-indigo-400 shrink-0">
            <Receipt :size="22" />
          </div>
          <div>
            <div class="text-lg font-bold text-white">TIN & 15% VAT</div>
            <div class="text-xs text-slate-400">Compliant Tax Receipts</div>
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
          Test Simulated Workspaces in Real-Time
        </h2>
        <p class="mt-4 text-slate-400 text-base sm:text-lg">
          Click the module tabs below to experience our live product catalog, POS cashier terminal, warehouse distribution, and tax invoicing directly in your browser.
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
          <span>Warehouse & Logistics</span>
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
            <span class="ml-3 text-xs font-mono text-slate-400 hidden sm:inline-block">https://app.binaerp.com/{{ activeTab }}</span>
          </div>
          <div class="flex items-center gap-3">
            <span class="text-xs px-2.5 py-1 rounded bg-emerald-500/10 text-emerald-400 font-mono font-medium border border-emerald-500/20 flex items-center gap-1.5">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
              Tenant Schema: tenantc4813
            </span>
          </div>
        </div>

        <!-- Tab 1: Executive Analytics Preview -->
        <div v-if="activeTab === 'analytics'" class="p-6 sm:p-8 space-y-6">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-5 rounded-xl bg-slate-900/60 border border-slate-800">
              <div class="text-xs font-semibold text-slate-400 uppercase">Gross Revenue (MTD)</div>
              <div class="mt-2 text-2xl font-bold text-white">
                {{ selectedCurrency === 'ETB' ? 'ETB 1,489,200.00' : '$148,920.00' }}
              </div>
              <div class="mt-1 flex items-center gap-1.5 text-xs text-emerald-400 font-medium">
                <TrendingUp :size="14" /> +18.4% vs last month
              </div>
            </div>
            <div class="p-5 rounded-xl bg-slate-900/60 border border-slate-800">
              <div class="text-xs font-semibold text-slate-400 uppercase">Operating Profit Margin</div>
              <div class="mt-2 text-2xl font-bold text-white">32.6%</div>
              <div class="mt-1 flex items-center gap-1.5 text-xs text-emerald-400 font-medium">
                <TrendingUp :size="14" /> Balanced double-entry ledgers
              </div>
            </div>
            <div class="p-5 rounded-xl bg-slate-900/60 border border-slate-800">
              <div class="text-xs font-semibold text-slate-400 uppercase">Active Retail Tills</div>
              <div class="mt-2 text-2xl font-bold text-white">2 Branches Online</div>
              <div class="mt-1 flex items-center gap-1.5 text-xs text-cyan-400 font-medium">
                <Activity :size="14" /> Bole & Kazanchis active
              </div>
            </div>
            <div class="p-5 rounded-xl bg-slate-900/60 border border-slate-800">
              <div class="text-xs font-semibold text-slate-400 uppercase">Total Inventory Valuation</div>
              <div class="mt-2 text-2xl font-bold text-white">
                {{ selectedCurrency === 'ETB' ? 'ETB 4,921,500.00' : '$492,150.00' }}
              </div>
              <div class="mt-1 flex items-center gap-1.5 text-xs text-indigo-400 font-medium">
                <Boxes :size="14" /> 10 Seeded Catalog SKUs
              </div>
            </div>
          </div>

          <!-- Chart & Breakdown Mockup -->
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 p-6 rounded-xl bg-slate-900/60 border border-slate-800">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-white">Consolidated Branch Sales & Cash Receipts</h3>
                <span class="text-xs font-mono text-cyan-400 bg-cyan-950/50 px-2 py-1 rounded">Daily Peak Interval</span>
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
              <div class="flex justify-between text-xs text-slate-500 mt-2 font-mono">
                <span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat (Peak Volume)</span>
              </div>
            </div>

            <div class="p-6 rounded-xl bg-slate-900/60 border border-slate-800 flex flex-col justify-between">
              <div>
                <h3 class="text-base font-semibold text-white mb-4">Channel Revenue Breakdown</h3>
                <div class="space-y-3.5">
                  <div>
                    <div class="flex justify-between text-xs text-slate-300 mb-1">
                      <span>Bole Mega Mall Flagship POS</span>
                      <span class="font-bold text-white">52%</span>
                    </div>
                    <div class="w-full bg-slate-800 rounded-full h-2">
                      <div class="bg-cyan-400 h-2 rounded-full" style="width: 52%"></div>
                    </div>
                  </div>
                  <div>
                    <div class="flex justify-between text-xs text-slate-300 mb-1">
                      <span>Enterprise Invoicing (Corporate B2B)</span>
                      <span class="font-bold text-white">34%</span>
                    </div>
                    <div class="w-full bg-slate-800 rounded-full h-2">
                      <div class="bg-blue-400 h-2 rounded-full" style="width: 34%"></div>
                    </div>
                  </div>
                  <div>
                    <div class="flex justify-between text-xs text-slate-300 mb-1">
                      <span>Kazanchis Express Outlet</span>
                      <span class="font-bold text-white">14%</span>
                    </div>
                    <div class="w-full bg-slate-800 rounded-full h-2">
                      <div class="bg-indigo-400 h-2 rounded-full" style="width: 14%"></div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="pt-4 mt-4 border-t border-slate-800 text-xs text-slate-400 flex items-center justify-between">
                <span>Automated General Ledger</span>
                <span class="text-emerald-400 font-semibold">100% Balanced</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Tab 2: Interactive POS Terminal Sandbox (Real Products) -->
        <div v-if="activeTab === 'pos'" class="p-6 sm:p-8">
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left: Catalog Items -->
            <div class="lg:col-span-7 space-y-4">
              <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400 uppercase">Verified Products Catalog (Click item to add to till)</span>
                <span class="text-xs text-cyan-400 font-mono">Lane 01 - Bole Mega Mall</span>
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
                    <span class="text-[10px] font-mono text-slate-400 px-1.5 py-0.5 rounded bg-slate-800">{{ item.sku }}</span>
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

            <!-- Right: Simulated POS Cart / Printable Bill -->
            <div class="lg:col-span-5 p-5 rounded-xl bg-[#0b101c] border border-slate-800 flex flex-col justify-between">
              <div>
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                  <div>
                    <div class="text-sm font-bold text-white flex items-center gap-2">
                      <Receipt :size="16" class="text-cyan-400" />
                      Current Register Session
                    </div>
                    <div class="text-[11px] text-slate-400">Cashier: cashier@example.com</div>
                  </div>
                  <span class="text-xs font-mono text-emerald-400 bg-emerald-950/40 px-2 py-0.5 rounded border border-emerald-800/40">TIN: 0000001234</span>
                </div>

                <!-- Cart Items List -->
                <div class="divide-y divide-slate-800/60 my-3 max-h-52 overflow-y-auto custom-scrollbar">
                  <div v-if="posCart.length === 0" class="py-8 text-center text-xs text-slate-500">
                    Cart is empty. Click any product on the left catalog to add.
                  </div>
                  <div v-for="item in posCart" :key="item.id" class="py-2.5 flex items-center justify-between">
                    <div class="text-xs pr-2">
                      <div class="font-medium text-slate-200">{{ item.name }}</div>
                      <div class="text-slate-500 font-mono">
                        {{ item.qty }} × {{ selectedCurrency === 'ETB' ? `ETB ${item.priceEtb.toLocaleString()}` : `$${item.priceUsd.toFixed(2)}` }}
                      </div>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                      <span class="font-mono text-xs font-bold text-white">
                        {{ selectedCurrency === 'ETB' ? `ETB ${(item.qty * item.priceEtb).toLocaleString()}` : `$${(item.qty * item.priceUsd).toFixed(2)}` }}
                      </span>
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
                  <span>Subtotal:</span>
                  <span>{{ selectedCurrency === 'ETB' ? `ETB ${posSubtotal.toLocaleString()}` : `$${posSubtotal.toFixed(2)}` }}</span>
                </div>
                <div class="flex justify-between text-xs text-slate-400 font-mono">
                  <span>VAT (15% Ethiopian Tax):</span>
                  <span>{{ selectedCurrency === 'ETB' ? `ETB ${posTax.toLocaleString()}` : `$${posTax.toFixed(2)}` }}</span>
                </div>
                <div class="flex justify-between text-sm font-bold text-white font-mono pt-1 border-t border-slate-800/60">
                  <span>Total Payable:</span>
                  <span class="text-cyan-400">{{ selectedCurrency === 'ETB' ? `ETB ${posTotal.toLocaleString()}` : `$${posTotal.toFixed(2)}` }}</span>
                </div>
                <button 
                  @click="navigateToAuth('register')"
                  class="w-full mt-3 py-2.5 rounded-lg bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-white font-semibold text-xs shadow-lg shadow-emerald-500/20 transition-all flex items-center justify-center gap-2"
                >
                  <Receipt :size="14" />
                  <span>Print Tax Invoice & Record Sale</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Tab 3: Real Warehouses & Stock Logistics -->
        <div v-if="activeTab === 'inventory'" class="p-6 sm:p-8 space-y-5">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-slate-800 gap-2">
            <div>
              <h3 class="text-sm font-bold text-white">Central Logistics & Inter-Branch Stock Transfers</h3>
              <p class="text-xs text-slate-400">Manage real warehouses across Addis Ababa with automatic reorder triggers and transfer orders.</p>
            </div>
            <span class="text-xs font-mono text-emerald-400 bg-emerald-950/40 px-2.5 py-1 rounded border border-emerald-800/40 self-start sm:self-auto">
              2 Warehouses + 2 Shops Synced
            </span>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="p-5 rounded-xl bg-slate-900/60 border border-slate-800 space-y-2">
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-white">Central Logistics Warehouse</span>
                <span class="text-[10px] text-cyan-400 bg-cyan-950/60 px-1.5 py-0.5 rounded font-mono">WH-ADDIS-01</span>
              </div>
              <div class="text-xs text-slate-400">Ring Road, Bole, Addis Ababa</div>
              <div class="text-2xl font-mono font-bold text-cyan-400 mt-2">14,280 Units</div>
              <div class="text-xs text-slate-400 pt-1 border-t border-slate-800/60 flex items-center justify-between">
                <span>Type: Primary Own Hub</span>
                <span class="text-emerald-400 font-medium">98% Capacity</span>
              </div>
            </div>

            <div class="p-5 rounded-xl bg-slate-900/60 border border-slate-800 space-y-2">
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-white">Bole Retail Store Warehouse</span>
                <span class="text-[10px] text-cyan-400 bg-cyan-950/60 px-1.5 py-0.5 rounded font-mono">WH-BOLE-02</span>
              </div>
              <div class="text-xs text-slate-400">Cameroon St, Bole Medhanealem</div>
              <div class="text-2xl font-mono font-bold text-amber-400 mt-2">420 Units</div>
              <div class="text-xs text-slate-400 pt-1 border-t border-slate-800/60 flex items-center justify-between">
                <span>Serving: Bole Mall Store</span>
                <span class="text-amber-400 font-medium">Replenish Active</span>
              </div>
            </div>

            <div class="p-5 rounded-xl bg-slate-900/60 border border-slate-800 space-y-2">
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-white">Kazanchis Shared Storage</span>
                <span class="text-[10px] text-cyan-400 bg-cyan-950/60 px-1.5 py-0.5 rounded font-mono">LOC-MAIN-01</span>
              </div>
              <div class="text-xs text-slate-400">Kazanchis Tito St, Addis Ababa</div>
              <div class="text-2xl font-mono font-bold text-emerald-400 mt-2">1,840 Units</div>
              <div class="text-xs text-slate-400 pt-1 border-t border-slate-800/60 flex items-center justify-between">
                <span>Cross-Docking Storage</span>
                <span class="text-emerald-400 font-medium">Optimal</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Tab 4: Real Tax Invoicing (Ethiopian Airlines & CBE Data) -->
        <div v-if="activeTab === 'invoicing'" class="p-6 sm:p-8 space-y-4">
          <div class="p-6 sm:p-8 rounded-xl bg-[#0b101c] border border-slate-800 max-w-2xl mx-auto space-y-5 shadow-xl">
            <div class="flex justify-between items-start border-b border-slate-800 pb-4">
              <div>
                <div class="text-xl font-bold text-white tracking-wide">TAX INVOICE</div>
                <div class="text-xs text-slate-400 mt-0.5">Invoice No: <span class="font-mono text-cyan-400 font-semibold">INV-2026-0001</span></div>
                <div class="text-[11px] text-slate-500">Date: August 23, 2026</div>
              </div>
              <div class="text-right text-xs">
                <div class="font-bold text-white">Demo Enterprise Global</div>
                <div class="text-slate-400">Bole Medhanealem, Addis Ababa</div>
                <div class="text-cyan-400 font-mono font-semibold">TIN: 0000001234</div>
              </div>
            </div>

            <div class="text-xs grid grid-cols-2 gap-4 text-slate-300">
              <div>
                <span class="text-slate-500 uppercase font-semibold text-[11px]">Billed Customer:</span>
                <div class="font-semibold text-white mt-1">Ethiopian Airlines Logistics Enterprise</div>
                <div class="text-slate-400">Bole International Cargo Terminal</div>
                <div class="text-slate-400 font-mono">TIN: 0048291039</div>
              </div>
              <div class="text-right">
                <span class="text-slate-500 uppercase font-semibold text-[11px]">Payment Status:</span>
                <div class="text-emerald-400 font-bold mt-1">PAID IN FULL</div>
                <div class="text-slate-400 text-[11px]">Commercial Bank of Ethiopia</div>
              </div>
            </div>

            <div class="border-t border-slate-800 pt-3">
              <table class="w-full text-xs text-left">
                <thead>
                  <tr class="text-slate-500 border-b border-slate-800/60 pb-2">
                    <th class="py-1">Description</th>
                    <th class="text-right py-1">Qty</th>
                    <th class="text-right py-1">Unit Price</th>
                    <th class="text-right py-1">Amount</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/40 text-slate-300 font-mono">
                  <tr>
                    <td class="py-2.5 font-sans">Dell Latitude 5440 i7 Laptop (16GB/512GB SSD)</td>
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
                <div class="flex justify-between text-slate-400"><span>VAT (15%):</span><span>ETB 25,500.00</span></div>
                <div class="flex justify-between font-bold text-white text-sm pt-1.5 border-t border-slate-800">
                  <span>Total Paid:</span><span class="text-cyan-400">ETB 195,500.00</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Tab 5: Real HR & Automated Payroll (Seeded Positions & Salaries) -->
        <div v-if="activeTab === 'hr'" class="p-6 sm:p-8 space-y-5">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-slate-800 gap-2">
            <div>
              <h3 class="text-sm font-bold text-white">Automated Monthly Payroll Engine & Statutory Deductions</h3>
              <p class="text-xs text-slate-400">Compliant with Ethiopian tax withholding, 7% employee pension, and 11% employer pension.</p>
            </div>
            <span class="text-xs font-mono text-cyan-400 bg-cyan-950/40 px-2.5 py-1 rounded border border-cyan-800/40 self-start sm:self-auto">
              Run: September 2026
            </span>
          </div>

          <div class="divide-y divide-slate-800/60">
            <!-- Sarah Jenkins -->
            <div class="py-3.5 flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-cyan-500/20 text-cyan-400 flex items-center justify-center font-bold text-xs">
                  SJ
                </div>
                <div>
                  <div class="text-sm font-semibold text-white">Sarah Jenkins (EMP-0001)</div>
                  <div class="text-xs text-slate-400">Chief Executive Officer • Full Time</div>
                </div>
              </div>
              <div class="text-right text-xs">
                <div class="font-mono font-bold text-emerald-400">
                  {{ selectedCurrency === 'ETB' ? 'ETB 145,000.00 Net' : '$1,450.00 Net' }}
                </div>
                <div class="text-slate-500 font-mono">Tax: ETB 28,400 | Pension (7%): ETB 10,150</div>
              </div>
            </div>

            <!-- Marcus Chen -->
            <div class="py-3.5 flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-indigo-500/20 text-indigo-400 flex items-center justify-center font-bold text-xs">
                  MC
                </div>
                <div>
                  <div class="text-sm font-semibold text-white">Marcus Chen (EMP-0002)</div>
                  <div class="text-xs text-slate-400">Lead ERP Systems Architect • Full Time</div>
                </div>
              </div>
              <div class="text-right text-xs">
                <div class="font-mono font-bold text-emerald-400">
                  {{ selectedCurrency === 'ETB' ? 'ETB 98,000.00 Net' : '$980.00 Net' }}
                </div>
                <div class="text-slate-500 font-mono">Tax: ETB 19,200 | Pension (7%): ETB 6,860</div>
              </div>
            </div>

            <!-- Dawit Bekele -->
            <div class="py-3.5 flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-blue-500/20 text-blue-400 flex items-center justify-center font-bold text-xs">
                  DB
                </div>
                <div>
                  <div class="text-sm font-semibold text-white">Dawit Bekele (EMP-0003)</div>
                  <div class="text-xs text-slate-400">Senior Financial Controller • Full Time</div>
                </div>
              </div>
              <div class="text-right text-xs">
                <div class="font-mono font-bold text-emerald-400">
                  {{ selectedCurrency === 'ETB' ? 'ETB 75,000.00 Net' : '$750.00 Net' }}
                </div>
                <div class="text-slate-500 font-mono">Tax: ETB 14,700 | Pension (7%): ETB 5,250</div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </section>

    <!-- ========================================================= -->
    <!-- BENTO GRID ARCHITECTURE & MODULES SHOWCASE -->
    <!-- ========================================================= -->
    <section id="modules" class="py-24 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto relative z-10">
      <div class="text-center max-w-3xl mx-auto mb-16">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-950/60 border border-indigo-800/50 text-xs font-semibold text-indigo-400 uppercase tracking-wider mb-3">
          <Workflow :size="13" />
          Enterprise Capability Center
        </div>
        <h2 class="text-3xl sm:text-5xl font-bold text-white tracking-tight">
          Everything You Need in One Unified Stack
        </h2>
        <p class="mt-4 text-slate-400 text-base sm:text-lg">
          Replace disjointed legacy software with an integrated platform built on robust transactional integrity.
        </p>
      </div>

      <!-- Bento Grid Layout -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Bento Card 1: Multi-Cashier POS (2-col) -->
        <div class="md:col-span-2 p-8 rounded-2xl bg-gradient-to-br from-slate-900/80 to-[#0c1222] border border-slate-800/90 relative overflow-hidden group hover:border-cyan-500/40 transition-all">
          <div class="absolute -right-10 -top-10 w-48 h-48 bg-cyan-500/10 rounded-full blur-3xl pointer-events-none group-hover:bg-cyan-500/20 transition-all"></div>
          <div class="p-3 w-fit rounded-xl bg-cyan-500/10 text-cyan-400 mb-6">
            <ShoppingCart :size="26" />
          </div>
          <h3 class="text-2xl font-bold text-white">Multi-Session Point of Sale with Real-Time Tab Sync</h3>
          <p class="mt-2 text-slate-400 text-sm sm:text-base leading-relaxed">
            Run concurrent cashier registers across multiple shops and counters simultaneously. Equipped with offline resilience, barcode autofocus, cash drawer kicks, and instant ESC/POS thermal printing with registered Company Name and TIN.
          </p>
          <div class="mt-6 flex flex-wrap gap-2 text-xs font-medium">
            <span class="px-2.5 py-1 rounded-md bg-slate-800/90 text-cyan-300 border border-slate-700/50">Multi-Session BroadcastChannel Sync</span>
            <span class="px-2.5 py-1 rounded-md bg-slate-800/90 text-cyan-300 border border-slate-700/50">TIN & 15% VAT Receipts</span>
            <span class="px-2.5 py-1 rounded-md bg-slate-800/90 text-cyan-300 border border-slate-700/50">ESC/POS Thermal Printing</span>
          </div>
        </div>

        <!-- Bento Card 2: Accounting -->
        <div class="p-8 rounded-2xl bg-gradient-to-br from-slate-900/80 to-[#0c1222] border border-slate-800/90 relative overflow-hidden group hover:border-blue-500/40 transition-all">
          <div class="p-3 w-fit rounded-xl bg-blue-500/10 text-blue-400 mb-6">
            <Banknote :size="26" />
          </div>
          <h3 class="text-xl font-bold text-white">Double-Entry Accounting</h3>
          <p class="mt-2 text-slate-400 text-sm leading-relaxed">
            Automated journal entries on every POS sale and invoice. Generate instant P&L, Balance Sheet, and Trial Balance reports.
          </p>
          <div class="mt-6 flex flex-wrap gap-2 text-xs font-medium">
            <span class="px-2.5 py-1 rounded-md bg-slate-800/90 text-blue-300 border border-slate-700/50">Chart of Accounts</span>
            <span class="px-2.5 py-1 rounded-md bg-slate-800/90 text-blue-300 border border-slate-700/50">AR/AP Aging Reports</span>
          </div>
        </div>

        <!-- Bento Card 3: Warehousing & Multi-Branch -->
        <div class="p-8 rounded-2xl bg-gradient-to-br from-slate-900/80 to-[#0c1222] border border-slate-800/90 relative overflow-hidden group hover:border-emerald-500/40 transition-all">
          <div class="p-3 w-fit rounded-xl bg-emerald-500/10 text-emerald-400 mb-6">
            <Warehouse :size="26" />
          </div>
          <h3 class="text-xl font-bold text-white">Multi-Location Warehousing</h3>
          <p class="mt-2 text-slate-400 text-sm leading-relaxed">
            Inter-branch stock transfer orders between central depots and retail shop shelves with reorder alert thresholds.
          </p>
          <div class="mt-6 flex flex-wrap gap-2 text-xs font-medium">
            <span class="px-2.5 py-1 rounded-md bg-slate-800/90 text-emerald-300 border border-slate-700/50">Inter-Branch Transfers</span>
            <span class="px-2.5 py-1 rounded-md bg-slate-800/90 text-emerald-300 border border-slate-700/50">Batch & Serial Tracking</span>
          </div>
        </div>

        <!-- Bento Card 4: HR, Attendance & Payroll (2-col) -->
        <div class="md:col-span-2 p-8 rounded-2xl bg-gradient-to-br from-slate-900/80 to-[#0c1222] border border-slate-800/90 relative overflow-hidden group hover:border-indigo-500/40 transition-all">
          <div class="absolute -right-10 -top-10 w-48 h-48 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none group-hover:bg-indigo-500/20 transition-all"></div>
          <div class="p-3 w-fit rounded-xl bg-indigo-500/10 text-indigo-400 mb-6">
            <Users :size="26" />
          </div>
          <h3 class="text-2xl font-bold text-white">Human Resources, Attendance & Statutory Payroll</h3>
          <p class="mt-2 text-slate-400 text-sm sm:text-base leading-relaxed">
            Complete employee lifecycle management. From job applications and visual org charts to attendance logging, leave approvals, and one-click salary pay runs with statutory 7% pension and income tax deductions.
          </p>
          <div class="mt-6 flex flex-wrap gap-2 text-xs font-medium">
            <span class="px-2.5 py-1 rounded-md bg-slate-800/90 text-indigo-300 border border-slate-700/50">Automated Monthly Pay Runs</span>
            <span class="px-2.5 py-1 rounded-md bg-slate-800/90 text-indigo-300 border border-slate-700/50">7% & 11% Pension Rules</span>
            <span class="px-2.5 py-1 rounded-md bg-slate-800/90 text-indigo-300 border border-slate-700/50">Visual Department Org Chart</span>
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
            Calculate Measurable Savings with Bina ERP
          </h2>
          <p class="mt-2 text-slate-400 text-sm sm:text-base">
            Adjust team size and average compensation below to calculate your estimated annual operational and administrative savings.
          </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
          <!-- Left Sliders -->
          <div class="lg:col-span-7 space-y-6">
            <!-- Team Size Slider -->
            <div class="p-5 rounded-xl bg-slate-900/60 border border-slate-800 space-y-3">
              <div class="flex justify-between items-center">
                <label class="text-sm font-semibold text-white">Total Active Team Members</label>
                <span class="text-cyan-400 font-bold font-mono text-base">{{ teamSize }} Staff Members</span>
              </div>
              <input 
                type="range" 
                v-model.number="teamSize" 
                min="5" 
                max="150" 
                step="1"
                class="w-full h-2 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-cyan-400"
              />
              <div class="flex justify-between text-[11px] text-slate-500 font-mono">
                <span>5 (Small Business)</span>
                <span>25 (Professional Tier)</span>
                <span>150+ (Enterprise)</span>
              </div>
            </div>

            <!-- Average Hourly Rate Slider -->
            <div class="p-5 rounded-xl bg-slate-900/60 border border-slate-800 space-y-3">
              <div class="flex justify-between items-center">
                <label class="text-sm font-semibold text-white">
                  Average Hourly Compensation ({{ selectedCurrency }})
                </label>
                <span class="text-emerald-400 font-bold font-mono text-base">
                  {{ selectedCurrency === 'ETB' ? `ETB ${hourlyWageEtb} / hr` : `$${hourlyWageUsd} / hr` }}
                </span>
              </div>
              <input 
                v-if="selectedCurrency === 'ETB'"
                type="range" 
                v-model.number="hourlyWageEtb" 
                min="80" 
                max="1200" 
                step="20"
                class="w-full h-2 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-emerald-400"
              />
              <input 
                v-else
                type="range" 
                v-model.number="hourlyWageUsd" 
                min="10" 
                max="100" 
                step="5"
                class="w-full h-2 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-emerald-400"
              />
              <div class="flex justify-between text-[11px] text-slate-500 font-mono">
                <span>{{ selectedCurrency === 'ETB' ? 'ETB 80/hr' : '$10/hr' }}</span>
                <span>{{ selectedCurrency === 'ETB' ? 'ETB 500/hr' : '$50/hr' }}</span>
                <span>{{ selectedCurrency === 'ETB' ? 'ETB 1,200/hr' : '$100/hr' }}</span>
              </div>
            </div>
          </div>

          <!-- Right Computed Results Card -->
          <div class="lg:col-span-5 p-6 rounded-2xl bg-gradient-to-br from-cyan-950/40 via-slate-900/90 to-blue-950/40 border border-cyan-800/40 space-y-6">
            <div>
              <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Estimated Annual Savings</div>
              <div class="text-3xl sm:text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 via-teal-300 to-cyan-400 font-mono mt-2">
                {{ selectedCurrency === 'ETB' ? `ETB ${yearlyCostSaved.toLocaleString()}` : `$${yearlyCostSaved.toLocaleString()}` }}
              </div>
              <div class="text-xs text-slate-400 mt-1">
                Approx. <span class="text-white font-semibold">{{ selectedCurrency === 'ETB' ? `ETB ${monthlyCostSaved.toLocaleString()}` : `$${monthlyCostSaved.toLocaleString()}` }}</span> saved every month
              </div>
            </div>

            <div class="pt-4 border-t border-slate-800 space-y-2">
              <div class="flex justify-between text-xs text-slate-300">
                <span>Admin Hours Recovered / Year:</span>
                <span class="font-bold text-white font-mono">{{ hoursSavedPerYear.toLocaleString() }} hours</span>
              </div>
              <div class="flex justify-between text-xs text-slate-300">
                <span>POS & Ledger Error Elimination:</span>
                <span class="font-bold text-emerald-400">99.4% Accuracy</span>
              </div>
            </div>

            <button 
              @click="authStore.isAuthenticated ? navigateToDashboard() : navigateToAuth('register')"
              class="w-full py-3.5 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-white font-bold text-sm shadow-xl shadow-cyan-500/20 transition-all hover:scale-[1.02]"
            >
              Start Free Trial & Unlock Savings
            </button>
          </div>
        </div>

      </div>
    </section>

    <!-- ========================================================= -->
    <!-- REAL PRICING PLANS (FROM DATABASE SEEDER) -->
    <!-- ========================================================= -->
    <section id="pricing" class="py-24 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto relative z-10">
      <div class="text-center max-w-3xl mx-auto mb-12">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-cyan-950/60 border border-cyan-800/50 text-xs font-semibold text-cyan-400 uppercase tracking-wider mb-3">
          <DollarSign :size="13" />
          Official Cloud Pricing Tiers
        </div>
        <h2 class="text-3xl sm:text-5xl font-bold text-white tracking-tight">
          Transparent, Predictable Cloud Plans
        </h2>
        <p class="mt-4 text-slate-400 text-base sm:text-lg">
          Zero hidden setup costs. Switch plans or export your entire PostgreSQL database anytime.
        </p>

        <!-- Annual / Monthly Toggle -->
        <div class="mt-8 flex items-center justify-center gap-3">
          <span class="text-sm font-medium" :class="[!isAnnual ? 'text-white' : 'text-slate-400']">Monthly</span>
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
              2 Months Free (~17% Off)
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
            
            <p class="text-xs text-slate-400 mt-2 min-h-[36px]">{{ tier.tagline }}</p>

            <!-- Price -->
            <div class="mt-6 flex items-baseline gap-1">
              <span class="text-3xl sm:text-4xl font-extrabold text-white font-mono">
                {{ selectedCurrency === 'ETB' 
                  ? (isAnnual ? `ETB ${tier.annualEtb.toLocaleString()}` : `ETB ${tier.monthlyEtb.toLocaleString()}`)
                  : (isAnnual ? `$${tier.annualUsd}` : `$${tier.monthlyUsd}`)
                }}
              </span>
              <span class="text-xs text-slate-400">/ mo</span>
            </div>
            <div class="text-[11px] text-slate-500 mt-1">
              {{ isAnnual 
                ? (selectedCurrency === 'ETB' ? `Billed ETB ${(tier.annualEtb * 12).toLocaleString()} annually` : `Billed $${(tier.annualUsd * 12).toFixed(0)} annually`)
                : 'Billed on a monthly cycle'
              }}
            </div>

            <!-- Features List (Real perks from database) -->
            <div class="mt-8 space-y-3 border-t border-slate-800/80 pt-6">
              <div v-for="feat in tier.perks" :key="feat" class="flex items-start gap-2.5 text-xs text-slate-300">
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
          Details on multi-session POS, Ethiopian tax compliance, database isolation, and migration.
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
            Ready to Empower Your Enterprise Operations?
          </h2>
          <p class="mt-4 text-slate-300 text-base sm:text-lg">
            Join enterprises running high-speed multi-cashier POS, compliant accounting, and multi-warehouse distribution on Bina ERP.
          </p>
          <div class="mt-8 flex flex-col sm:flex-row justify-center gap-4">
            <button 
              @click="authStore.isAuthenticated ? navigateToDashboard() : navigateToAuth('register')"
              class="px-8 py-4 text-base font-bold rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-white shadow-xl shadow-cyan-500/30 transition-all hover:scale-[1.03]"
            >
              {{ authStore.isAuthenticated ? 'Open ERP Workspace' : 'Start 14-Day Free Trial' }}
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
            Enterprise cloud management platform. High-speed multi-cashier POS, double-entry accounting, multi-warehouse logistics, and automated statutory payroll.
          </p>
          <div class="flex items-center gap-2 text-emerald-400 font-mono text-[11px]">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            PostgreSQL Multi-Tenant Schema Engine Active
          </div>
        </div>

        <!-- Col 2: Platform -->
        <div>
          <div class="font-semibold text-white mb-3 text-xs uppercase tracking-wider">Capabilities</div>
          <ul class="space-y-2">
            <li><a href="#live-sandbox" class="hover:text-cyan-400 transition-colors">Point of Sale (POS)</a></li>
            <li><a href="#live-sandbox" class="hover:text-cyan-400 transition-colors">Double-Entry Ledgers</a></li>
            <li><a href="#live-sandbox" class="hover:text-cyan-400 transition-colors">Multi-Warehouse Inventory</a></li>
            <li><a href="#live-sandbox" class="hover:text-cyan-400 transition-colors">HR & Statutory Payroll</a></li>
            <li><a href="#live-sandbox" class="hover:text-cyan-400 transition-colors">Tax Invoicing & TIN</a></li>
          </ul>
        </div>

        <!-- Col 3: Resources -->
        <div>
          <div class="font-semibold text-white mb-3 text-xs uppercase tracking-wider">Resources</div>
          <ul class="space-y-2">
            <li><a href="#roi-calculator" class="hover:text-cyan-400 transition-colors">ROI Calculator</a></li>
            <li><a href="#pricing" class="hover:text-cyan-400 transition-colors">Pricing & Plans</a></li>
            <li><a href="#faq" class="hover:text-cyan-400 transition-colors">FAQ & Technical Specs</a></li>
            <li><router-link to="/login" class="hover:text-cyan-400 transition-colors">Tenant Login</router-link></li>
          </ul>
        </div>

        <!-- Col 4: Legal & Security -->
        <div>
          <div class="font-semibold text-white mb-3 text-xs uppercase tracking-wider">Security & Specs</div>
          <ul class="space-y-2">
            <li><span class="text-slate-400">PostgreSQL Schema Isolation</span></li>
            <li><span class="text-slate-400">15% VAT & TIN Compliance</span></li>
            <li><span class="text-slate-400">Multi-Cashier BroadcastChannel</span></li>
            <li><span class="text-slate-400">256-Bit SSL Encryption</span></li>
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
