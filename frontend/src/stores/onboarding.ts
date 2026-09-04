import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useStorage } from '@vueuse/core'
import {
  Building2,
  Boxes,
  Package,
  ShoppingCart,
  Users,
  FileText,
  BarChart3,
} from '@lucide/vue'

export interface OnboardingStep {
  id: string
  title: string
  description: string
  icon: any
  route: string
  badgeText: string
  actionLabel: string
}

export const ONBOARDING_STEPS: OnboardingStep[] = [
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

export const useOnboardingStore = defineStore('onboarding', () => {
  const steps = ONBOARDING_STEPS
  const completedSteps = useStorage<string[]>('erp_onboarding_completed_steps', [])
  const isDismissed = useStorage('erp_onboarding_dismissed', false)
  const isCollapsed = useStorage('erp_onboarding_collapsed', false)
  const isModalOpen = ref(false)

  const totalSteps = steps.length
  const completedCount = computed(() => {
    return steps.filter(s => completedSteps.value.includes(s.id)).length
  })

  const progressPercentage = computed(() => {
    return Math.round((completedCount.value / totalSteps) * 100)
  })

  const isAllCompleted = computed(() => {
    return totalSteps > 0 && completedCount.value >= totalSteps
  })

  // Visible only when NOT all completed and NOT dismissed
  const isVisible = computed(() => {
    return !isDismissed.value && !isAllCompleted.value
  })

  function toggleStep(stepId: string) {
    if (completedSteps.value.includes(stepId)) {
      completedSteps.value = completedSteps.value.filter(id => id !== stepId)
    } else {
      completedSteps.value = [...new Set([...completedSteps.value, stepId])]
    }
  }

  function completeStep(stepId: string) {
    if (!completedSteps.value.includes(stepId)) {
      completedSteps.value = [...new Set([...completedSteps.value, stepId])]
    }
  }

  function markAllCompleted() {
    completedSteps.value = steps.map(s => s.id)
    isDismissed.value = true
  }

  function dismiss() {
    isDismissed.value = true
  }

  function resetProgress() {
    completedSteps.value = []
    isDismissed.value = false
    isCollapsed.value = false
  }

  // Auto-detect completed steps based on live data in workspace
  function syncWithWorkspaceData(data: {
    inventory?: { total_products?: number }
    operations?: { employee_count?: number; open_pos_sessions?: number }
    financial?: { monthly_revenue_cents?: number; today_orders_count?: number }
  }) {
    if (!data) return

    const newCompleted = new Set(completedSteps.value)

    if ((data.inventory?.total_products ?? 0) > 0) {
      newCompleted.add('add_products')
    }
    if ((data.operations?.employee_count ?? 0) > 0) {
      newCompleted.add('team_roles')
    }
    if ((data.operations?.open_pos_sessions ?? 0) > 0) {
      newCompleted.add('launch_pos')
    }
    if ((data.financial?.today_orders_count ?? 0) > 0 || (data.financial?.monthly_revenue_cents ?? 0) > 0) {
      newCompleted.add('create_invoice')
      newCompleted.add('financial_reports')
    }

    completedSteps.value = Array.from(newCompleted)
  }

  return {
    steps,
    completedSteps,
    isDismissed,
    isCollapsed,
    isModalOpen,
    totalSteps,
    completedCount,
    progressPercentage,
    isAllCompleted,
    isVisible,
    toggleStep,
    completeStep,
    markAllCompleted,
    dismiss,
    resetProgress,
    syncWithWorkspaceData,
  }
})
