<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useStorage } from '@vueuse/core'
import {
  Rocket,
  CheckCircle2,
  Circle,
  Building2,
  Boxes,
  Package,
  ShoppingCart,
  Users,
  FileText,
  BarChart3,
  ChevronRight,
  ChevronDown,
  ChevronUp,
  X,
  Sparkles,
  HelpCircle,
  Store,
  Briefcase,
  Layers,
  ArrowRight,
} from '@lucide/vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiModal from '@/components/ui/UiModal.vue'

const router = useRouter()
const authStore = useAuthStore()

// State
const isOpen = ref(false)
const isBannerCollapsed = useStorage('erp_onboarding_collapsed', false)
const isBannerDismissed = useStorage('erp_onboarding_dismissed', false)
const completedSteps = useStorage<string[]>('erp_onboarding_completed_steps', [])
const activeGuideTab = ref<'retail' | 'corporate' | 'wholesale'>('retail')

interface OnboardingStep {
  id: string
  title: string
  description: string
  icon: any
  route: string
  badgeText: string
  actionLabel: string
}

const steps: OnboardingStep[] = [
  {
    id: 'company_profile',
    title: 'Set Up Company Profile & Branding',
    description: 'Configure legal company details, default currency, fiscal timezone, and invoice prefixes.',
    icon: Building2,
    route: '/settings',
    badgeText: 'Core Settings',
    actionLabel: 'Configure Company',
  },
  {
    id: 'enable_modules',
    title: 'Customize ERP Modules & Apps',
    description: 'Enable or disable the 15 enterprise modules with automated dependency resolution based on your workflow.',
    icon: Boxes,
    route: '/modules',
    badgeText: 'Module Center',
    actionLabel: 'Manage Modules',
  },
  {
    id: 'add_products',
    title: 'Add Inventory Products & Initial Stock',
    description: 'Create products with SKUs, barcodes, cost/selling prices, and record warehouse stock levels.',
    icon: Package,
    route: '/inventory/products',
    badgeText: 'Inventory',
    actionLabel: 'View Product Catalog',
  },
  {
    id: 'launch_pos',
    title: 'Launch POS Terminal & Cashier Shift',
    description: 'Open a cashier register session, scan product barcodes, and print or email digital receipts.',
    icon: ShoppingCart,
    route: '/pos',
    badgeText: 'POS Terminal',
    actionLabel: 'Open POS Register',
  },
  {
    id: 'team_roles',
    title: 'Invite Staff & Assign Roles',
    description: 'Onboard team members (Cashiers, Accountants, Store Keepers, HR Managers) with RBAC permissions.',
    icon: Users,
    route: '/roles',
    badgeText: 'Team & RBAC',
    actionLabel: 'Manage Users & Roles',
  },
  {
    id: 'create_invoice',
    title: 'Generate Your First Client Invoice',
    description: 'Create customer profiles and issue branded digital invoices with automated tax calculations.',
    icon: FileText,
    route: '/sales/invoices',
    badgeText: 'Sales & Billing',
    actionLabel: 'Create Invoice',
  },
  {
    id: 'financial_reports',
    title: 'Review Double-Entry Financial Reports',
    description: 'Inspect real-time Balance Sheets, Profit & Loss, Trial Balance, and Aging schedules.',
    icon: BarChart3,
    route: '/reporting',
    badgeText: 'Financial Reports',
    actionLabel: 'Open Reports',
  },
]

// Progress calculations
const totalSteps = steps.length
const completedCount = computed(() => completedSteps.value.length)
const progressPercentage = computed(() => Math.round((completedCount.value / totalSteps) * 100))
const isAllCompleted = computed(() => completedCount.value >= totalSteps)

function toggleStep(stepId: string) {
  if (completedSteps.value.includes(stepId)) {
    completedSteps.value = completedSteps.value.filter(id => id !== stepId)
  } else {
    completedSteps.value = [...completedSteps.value, stepId]
  }
}

function handleStepAction(step: OnboardingStep) {
  if (!completedSteps.value.includes(step.id)) {
    completedSteps.value = [...completedSteps.value, step.id]
  }
  isOpen.value = false
  router.push(step.route)
}

function openHelper() {
  isOpen.value = true
}

function resetProgress() {
  completedSteps.value = []
  isBannerDismissed.value = false
  isBannerCollapsed.value = false
}

defineExpose({
  openHelper,
})
</script>

<template>
  <div>
    <!-- 1. Top Bar Trigger Button (disappears when all tasks are completed) -->
    <button
      v-if="!isAllCompleted"
      type="button"
      @click="openHelper"
      class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer shadow-xs border bg-gradient-to-r from-indigo-50 to-blue-50 text-indigo-900 border-indigo-200 hover:border-indigo-300"
      title="Quick Start & Onboarding Helper"
    >
      <Rocket class="w-3.5 h-3.5 text-indigo-600 shrink-0" />
      <span class="hidden sm:inline">Getting Started</span>
      <span class="px-1.5 py-0.2 rounded-md text-[10px] font-black bg-indigo-600 text-white">
        {{ completedCount }}/{{ totalSteps }}
      </span>
    </button>

    <!-- 2. Interactive Onboarding & Quick Start Modal -->
    <UiModal v-model="isOpen" title="ERP Workspace Quick Start & Onboarding Helper" size="lg">
      <div class="space-y-6">
        <!-- Header Banner & Progress Bar -->
        <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white p-6 rounded-2xl shadow-md space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-indigo-500/20 border border-indigo-400/30 flex items-center justify-center shrink-0">
                <Sparkles class="w-5 h-5 text-amber-400" />
              </div>
              <div>
                <h3 class="text-base font-black text-white">Welcome to Your ERP Platform</h3>
                <p class="text-xs text-slate-300">Complete these essential setup steps to unlock your full operational potential.</p>
              </div>
            </div>
            <div class="text-right">
              <span class="text-2xl font-black text-amber-400 font-mono">{{ progressPercentage }}%</span>
              <span class="text-[11px] text-slate-300 block font-medium">{{ completedCount }} of {{ totalSteps }} completed</span>
            </div>
          </div>

          <!-- Progress Bar Indicator -->
          <div class="w-full bg-slate-800 rounded-full h-2.5 overflow-hidden p-0.5 border border-slate-700">
            <div
              class="bg-gradient-to-r from-amber-400 via-indigo-400 to-emerald-400 h-1.5 rounded-full transition-all duration-500"
              :style="{ width: `${Math.max(progressPercentage, 4)}%` }"
            ></div>
          </div>
        </div>

        <!-- Role-based Industry Setup Recommendations -->
        <div class="border border-slate-200 rounded-2xl p-4 bg-slate-50/70 space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
              <Layers class="w-3.5 h-3.5 text-indigo-600" />
              Recommended Workflows by Business Type
            </span>
          </div>

          <div class="flex gap-2 border-b border-slate-200 pb-2">
            <button
              type="button"
              @click="activeGuideTab = 'retail'"
              class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5"
              :class="activeGuideTab === 'retail' ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'"
            >
              <Store class="w-3.5 h-3.5" /> Retail & Shops
            </button>
            <button
              type="button"
              @click="activeGuideTab = 'corporate'"
              class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5"
              :class="activeGuideTab === 'corporate' ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'"
            >
              <Briefcase class="w-3.5 h-3.5" /> B2B & Services
            </button>
            <button
              type="button"
              @click="activeGuideTab = 'wholesale'"
              class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5"
              :class="activeGuideTab === 'wholesale' ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'"
            >
              <Package class="w-3.5 h-3.5" /> Wholesale & Logistics
            </button>
          </div>

          <div v-if="activeGuideTab === 'retail'" class="text-xs text-slate-600 leading-relaxed space-y-1">
            <p><strong>Primary Workflow:</strong> Add retail products with barcodes &rarr; Assign shop keepers &rarr; Launch POS cashier shift &rarr; Print/email thermal receipts &rarr; Reconcile daily Z-report.</p>
          </div>
          <div v-else-if="activeGuideTab === 'corporate'" class="text-xs text-slate-600 leading-relaxed space-y-1">
            <p><strong>Primary Workflow:</strong> Track CRM leads & deals &rarr; Issue customer quotations & invoices &rarr; Log project billable hours &rarr; View automatic general ledger journal entries.</p>
          </div>
          <div v-else-if="activeGuideTab === 'wholesale'" class="text-xs text-slate-600 leading-relaxed space-y-1">
            <p><strong>Primary Workflow:</strong> Set up multi-location warehouses &rarr; Issue supplier purchase orders &rarr; Receive lot/serial tracked shipments &rarr; Run Bill of Materials (BOM) work orders.</p>
          </div>
        </div>

        <!-- 7-Step Interactive Checklist -->
        <div class="space-y-3">
          <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Setup Milestones</h4>

          <div class="divide-y divide-slate-100 border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-xs">
            <div
              v-for="(step, idx) in steps"
              :key="step.id"
              class="p-4 transition-colors flex items-start justify-between gap-4"
              :class="completedSteps.includes(step.id) ? 'bg-slate-50/60' : 'hover:bg-slate-50'"
            >
              <div class="flex items-start gap-3.5">
                <!-- Checkbox -->
                <button
                  type="button"
                  @click="toggleStep(step.id)"
                  class="mt-0.5 text-slate-300 hover:text-emerald-600 transition-colors cursor-pointer shrink-0"
                  :title="completedSteps.includes(step.id) ? 'Mark as incomplete' : 'Mark as complete'"
                >
                  <CheckCircle2 v-if="completedSteps.includes(step.id)" class="w-5 h-5 text-emerald-600" />
                  <Circle v-else class="w-5 h-5 text-slate-300" />
                </button>

                <div class="space-y-1">
                  <div class="flex items-center gap-2">
                    <span class="text-xs font-bold font-mono text-slate-400">0{{ idx + 1 }}</span>
                    <span
                      class="text-sm font-bold text-slate-900"
                      :class="completedSteps.includes(step.id) ? 'line-through text-slate-400' : ''"
                    >
                      {{ step.title }}
                    </span>
                    <span class="px-2 py-0.2 rounded-md text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                      {{ step.badgeText }}
                    </span>
                  </div>
                  <p class="text-xs text-slate-500">{{ step.description }}</p>
                </div>
              </div>

              <!-- Action CTA -->
              <UiButton
                size="sm"
                :variant="completedSteps.includes(step.id) ? 'ghost' : 'outline'"
                class="shrink-0 text-xs font-bold"
                @click="handleStepAction(step)"
              >
                {{ step.actionLabel }} <ChevronRight class="w-3.5 h-3.5 ml-1" />
              </UiButton>
            </div>
          </div>
        </div>

        <!-- Footer Actions -->
        <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-xs">
          <button
            type="button"
            @click="resetProgress"
            class="text-slate-400 hover:text-slate-600 font-medium cursor-pointer"
          >
            Reset checklist progress
          </button>
          <UiButton variant="primary" @click="isOpen = false">
            Done / Close Helper
          </UiButton>
        </div>
      </div>
    </UiModal>
  </div>
</template>
