<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
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
  ArrowRight,
} from '@lucide/vue'
import UiButton from '@/components/ui/UiButton.vue'

const router = useRouter()

// Persisted storage
const isCollapsed = useStorage('erp_onboarding_collapsed', false)
const isDismissed = useStorage('erp_onboarding_dismissed', false)
const completedSteps = useStorage<string[]>('erp_onboarding_completed_steps', [])

interface OnboardingStep {
  id: string
  title: string
  description: string
  icon: any
  route: string
  actionLabel: string
}

const steps: OnboardingStep[] = [
  {
    id: 'company_profile',
    title: 'Company & Branding',
    description: 'Set company currency, timezone & logo',
    icon: Building2,
    route: '/settings',
    actionLabel: 'Setup',
  },
  {
    id: 'enable_modules',
    title: 'ERP Modules',
    description: 'Toggle business apps & modules',
    icon: Boxes,
    route: '/modules',
    actionLabel: 'Manage',
  },
  {
    id: 'add_products',
    title: 'Products & Stock',
    description: 'Add catalog items & warehouse stock',
    icon: Package,
    route: '/inventory/products',
    actionLabel: 'Add Items',
  },
  {
    id: 'launch_pos',
    title: 'POS Terminal',
    description: 'Launch cashier shift & barcode checkout',
    icon: ShoppingCart,
    route: '/pos',
    actionLabel: 'Open POS',
  },
  {
    id: 'team_roles',
    title: 'Team & RBAC',
    description: 'Invite users & assign permissions',
    icon: Users,
    route: '/roles',
    actionLabel: 'Invite',
  },
  {
    id: 'create_invoice',
    title: 'Sales & Invoices',
    description: 'Generate client billing invoices',
    icon: FileText,
    route: '/sales/invoices',
    actionLabel: 'Invoice',
  },
  {
    id: 'financial_reports',
    title: 'Financial Reports',
    description: 'View real-time P&L & Balance Sheet',
    icon: BarChart3,
    route: '/reporting',
    actionLabel: 'Reports',
  },
]

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

function handleStepClick(step: OnboardingStep) {
  if (!completedSteps.value.includes(step.id)) {
    completedSteps.value = [...completedSteps.value, step.id]
  }
  router.push(step.route)
}

function reopenChecklist() {
  isDismissed.value = false
  isCollapsed.value = false
}

defineExpose({
  reopenChecklist,
})
</script>

<template>
  <div v-if="!isDismissed && !isAllCompleted" class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white rounded-3xl p-5 sm:p-6 shadow-md border border-slate-800 space-y-4">
    <!-- Top Header -->
    <div class="flex items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-2xl bg-indigo-500/20 border border-indigo-400/30 flex items-center justify-center shrink-0">
          <Rocket class="w-5 h-5 text-amber-400" />
        </div>
        <div>
          <div class="flex items-center gap-2">
            <h3 class="text-base font-black text-white">Getting Started with Your ERP Workspace</h3>
            <span
              class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider"
              :class="isAllCompleted ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-amber-400/20 text-amber-300 border border-amber-400/30'"
            >
              {{ isAllCompleted ? 'Ready for Business' : `${completedCount} of ${totalSteps} Done` }}
            </span>
          </div>
          <p class="text-xs text-slate-300">Essential milestones to customize, configure, and scale your business operations.</p>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <button
          type="button"
          @click="isCollapsed = !isCollapsed"
          class="p-1.5 rounded-xl hover:bg-white/10 text-slate-400 hover:text-white transition-colors cursor-pointer"
          :title="isCollapsed ? 'Expand Checklist' : 'Collapse Checklist'"
        >
          <ChevronDown v-if="isCollapsed" class="w-5 h-5" />
          <ChevronUp v-else class="w-5 h-5" />
        </button>
        <button
          type="button"
          @click="isDismissed = true"
          class="p-1.5 rounded-xl hover:bg-white/10 text-slate-400 hover:text-white transition-colors cursor-pointer"
          title="Dismiss Checklist"
        >
          <X class="w-5 h-5" />
        </button>
      </div>
    </div>

    <!-- Progress Bar -->
    <div class="space-y-1.5">
      <div class="flex items-center justify-between text-xs text-slate-300">
        <span>Setup Progress</span>
        <span class="font-mono font-bold text-amber-400">{{ progressPercentage }}%</span>
      </div>
      <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden border border-slate-700">
        <div
          class="bg-gradient-to-r from-amber-400 via-indigo-400 to-emerald-400 h-2 rounded-full transition-all duration-500"
          :style="{ width: `${Math.max(progressPercentage, 4)}%` }"
        ></div>
      </div>
    </div>

    <!-- Collapsible Steps Grid -->
    <div v-if="!isCollapsed" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 pt-2">
      <div
        v-for="step in steps"
        :key="step.id"
        class="p-3.5 rounded-2xl border transition-all flex flex-col justify-between space-y-3 cursor-pointer group"
        :class="completedSteps.includes(step.id) ? 'bg-white/5 border-slate-700/60' : 'bg-white/10 border-indigo-500/30 hover:bg-white/15 hover:border-indigo-400/50'"
        @click="handleStepClick(step)"
      >
        <div class="flex items-start justify-between gap-2">
          <div class="p-2 rounded-xl bg-white/10 text-amber-300 group-hover:scale-105 transition-transform">
            <component :is="step.icon" class="w-4 h-4" />
          </div>
          <button
            type="button"
            @click.stop="toggleStep(step.id)"
            class="text-slate-400 hover:text-emerald-400 transition-colors cursor-pointer"
          >
            <CheckCircle2 v-if="completedSteps.includes(step.id)" class="w-4 h-4 text-emerald-400" />
            <Circle v-else class="w-4 h-4 text-slate-400" />
          </button>
        </div>

        <div>
          <h4
            class="text-xs font-bold text-white group-hover:text-amber-300 transition-colors"
            :class="completedSteps.includes(step.id) ? 'line-through text-slate-400' : ''"
          >
            {{ step.title }}
          </h4>
          <p class="text-[11px] text-slate-300 line-clamp-2 mt-0.5">{{ step.description }}</p>
        </div>

        <div class="pt-1 flex items-center justify-between text-[11px] font-bold text-indigo-300 group-hover:text-white">
          <span>{{ step.actionLabel }}</span>
          <ArrowRight class="w-3.5 h-3.5 group-hover:translate-x-1 transition-transform" />
        </div>
      </div>
    </div>
  </div>
</template>
