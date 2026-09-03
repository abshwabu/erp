<script setup lang="ts">
import { ref, computed } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { modulesApi, type ModuleItem } from '@/api/modules'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import {
  Boxes,
  Layers,
  CheckCircle2,
  AlertTriangle,
  Lock,
  ArrowRight,
  Sparkles,
  Search,
  Zap,
  ShieldCheck,
  Package,
  BookOpen,
  Users,
  FileText,
  Store,
  UserCheck,
  Truck,
  Banknote,
  FolderKanban,
  Factory,
  ShoppingBag,
  LifeBuoy,
  Coins,
  Webhook,
  RefreshCw,
} from '@lucide/vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiSpinner from '@/components/ui/UiSpinner.vue'
import PlanSelectionModal from '@/components/shared/PlanSelectionModal.vue'

const authStore = useAuthStore()
const toast = useToast()
const queryClient = useQueryClient()

const activeCategory = ref('all')
const searchQuery = ref('')
const isPlanModalOpen = ref(false)

// Confirmation modal state for turning off modules with active dependents
const confirmModalOpen = ref(false)
const pendingDisableModule = ref<ModuleItem | null>(null)
const pendingDisableDependents = ref<string[]>([])

// Icon mapping
const iconMap: Record<string, any> = {
  ShieldCheck,
  Package,
  BookOpen,
  Users,
  FileText,
  Store,
  UserCheck,
  Truck,
  Banknote,
  FolderKanban,
  Factory,
  ShoppingBag,
  LifeBuoy,
  Coins,
  Webhook,
}

// Fetch all modules
const { data: responseData, isLoading } = useQuery({
  queryKey: ['core', 'modules'],
  queryFn: () => modulesApi.getModules(),
})

const modules = computed<ModuleItem[]>(() => responseData.value?.data?.modules || [])
const planInfo = computed(() => responseData.value?.data?.plan)
const enabledCount = computed(() => modules.value.filter((m) => m.is_enabled).length)

// Categories
const categories = computed(() => {
  const cats = ['all', 'Revenue', 'Operations', 'Finance', 'People', 'Administration', 'Technology']
  return cats
})

const filteredModules = computed(() => {
  let list = modules.value

  if (activeCategory.value !== 'all') {
    list = list.filter((m) => m.category.toLowerCase() === activeCategory.value.toLowerCase())
  }

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(
      (m) =>
        m.name.toLowerCase().includes(q) ||
        m.description.toLowerCase().includes(q) ||
        m.key.toLowerCase().includes(q) ||
        m.category.toLowerCase().includes(q)
    )
  }

  return list
})

// Toggle mutation
const toggleMutation = useMutation({
  mutationFn: ({ module, enabled }: { module: string; enabled: boolean }) =>
    modulesApi.toggleModule(module, enabled),
  onSuccess: (res) => {
    queryClient.invalidateQueries({ queryKey: ['core', 'modules'] })
    authStore.setEnabledModules(res.data.enabled_modules)
    toast.success(res.message)
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || 'Failed to update module state')
  },
})

const handleToggle = (module: ModuleItem) => {
  if (module.is_core) {
    toast.info('Core Settings & RBAC is fundamental and cannot be turned off.')
    return
  }

  if (!module.allowed_by_plan) {
    isPlanModalOpen.value = true
    return
  }

  const targetState = !module.is_enabled

  if (targetState) {
    // Turning ON
    toggleMutation.mutate({ module: module.key, enabled: true })
  } else {
    // Turning OFF: Check if other currently enabled modules depend on this module
    const activeDependents = module.dependents.filter((depKey) => {
      const depMod = modules.value.find((m) => m.key === depKey)
      return depMod && depMod.is_enabled
    })

    if (activeDependents.length > 0) {
      pendingDisableModule.value = module
      pendingDisableDependents.value = activeDependents
      confirmModalOpen.value = true
    } else {
      toggleMutation.mutate({ module: module.key, enabled: false })
    }
  }
}

const confirmDisable = () => {
  if (pendingDisableModule.value) {
    toggleMutation.mutate({
      module: pendingDisableModule.value.key,
      enabled: false,
    })
    confirmModalOpen.value = false
    pendingDisableModule.value = null
  }
}

const getModuleName = (key: string): string => {
  const mod = modules.value.find((m) => m.key === key)
  return mod ? mod.name : key
}

const isModuleActive = (key: string): boolean => {
  const mod = modules.value.find((m) => m.key === key)
  return mod ? mod.is_enabled : false
}
</script>

<template>
  <div class="space-y-6 max-w-7xl font-sans pb-12">
    <!-- Header Banner -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white p-6 sm:p-8 rounded-3xl shadow-md border border-slate-800 relative overflow-hidden">
      <div class="space-y-2 z-10">
        <div class="flex items-center gap-2">
          <Boxes class="w-6 h-6 text-indigo-400" />
          <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Modules & Capability Center</h1>
          <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-400/30">
            {{ enabledCount }} of {{ modules.length }} Active
          </span>
        </div>
        <p class="text-xs sm:text-sm text-slate-300 max-w-2xl leading-relaxed">
          Customize your workspace engines. Activating a specialized module automatically enables its required prerequisites to ensure continuous accounting and ledger integrity.
        </p>
      </div>

      <div class="flex items-center gap-3 z-10">
        <div class="p-3 bg-white/10 backdrop-blur-md rounded-2xl border border-white/10 text-right">
          <span class="text-[10px] text-slate-300 uppercase tracking-wider block font-semibold">Active Plan</span>
          <span class="text-sm font-black text-amber-400 flex items-center gap-1">
            <Sparkles class="w-3.5 h-3.5" />
            {{ planInfo?.name || 'Enterprise' }} Tier
          </span>
        </div>

        <UiButton
          variant="primary"
          class="bg-indigo-600 hover:bg-indigo-500 text-white font-black shadow-xs cursor-pointer"
          @click="isPlanModalOpen = true"
        >
          Change Plan
        </UiButton>
      </div>

      <!-- Background Glow -->
      <div class="absolute -top-24 -right-24 w-72 h-72 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
      <!-- Category Tabs -->
      <div class="flex items-center gap-1.5 overflow-x-auto pb-1 lg:pb-0 custom-scrollbar">
        <button
          v-for="cat in categories"
          :key="cat"
          type="button"
          @click="activeCategory = cat"
          class="px-3.5 py-1.5 rounded-xl text-xs font-bold capitalize transition-all cursor-pointer whitespace-nowrap"
          :class="[
            activeCategory === cat
              ? 'bg-slate-900 text-white shadow-xs'
              : 'bg-slate-50 text-slate-600 hover:bg-slate-100'
          ]"
        >
          {{ cat === 'all' ? `All Modules (${modules.length})` : cat }}
        </button>
      </div>

      <!-- Search -->
      <UiInput
        v-model="searchQuery"
        placeholder="Filter by module, keyword..."
        size="sm"
        class="w-full lg:w-72"
      >
        <template #prefix>
          <Search class="w-3.5 h-3.5 text-slate-400" />
        </template>
      </UiInput>
    </div>

    <!-- Loading Spinner -->
    <div v-if="isLoading" class="p-20 flex justify-center">
      <UiSpinner size="lg" />
    </div>

    <!-- Modules Cards Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="mod in filteredModules"
        :key="mod.key"
        class="bg-white rounded-3xl border p-6 flex flex-col justify-between space-y-5 shadow-xs relative transition-all duration-200 hover:shadow-md"
        :class="[
          mod.is_enabled
            ? 'border-indigo-200 ring-2 ring-indigo-500/10 bg-gradient-to-b from-white to-indigo-50/20'
            : 'border-slate-200 bg-slate-50/40 opacity-90'
        ]"
      >
        <!-- Top Row: Icon, Title & Toggle Switch -->
        <div class="space-y-3">
          <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-3">
              <div
                class="w-11 h-11 rounded-2xl flex items-center justify-center font-bold text-lg shrink-0 shadow-xs"
                :class="[
                  mod.is_enabled
                    ? 'bg-gradient-to-tr from-indigo-600 to-blue-500 text-white'
                    : 'bg-slate-100 text-slate-400'
                ]"
              >
                <component :is="iconMap[mod.icon] || Boxes" class="w-5 h-5" />
              </div>

              <div>
                <div class="flex items-center gap-1.5">
                  <h3 class="font-black text-slate-900 text-base leading-snug">
                    {{ mod.name }}
                  </h3>
                  <span
                    v-if="mod.is_core"
                    class="px-1.5 py-0.2 rounded text-[10px] font-black uppercase bg-slate-100 text-slate-600 border border-slate-200"
                  >
                    Core
                  </span>
                </div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                  {{ mod.category }}
                </span>
              </div>
            </div>

            <!-- Custom Switch / Toggle Button -->
            <div class="shrink-0 pt-0.5">
              <!-- Core system locked -->
              <div
                v-if="mod.is_core"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-slate-100 text-slate-500 text-xs font-bold border border-slate-200 cursor-not-allowed"
                title="Core system is always active"
              >
                <Lock class="w-3 h-3" />
                <span>Locked</span>
              </div>

              <!-- Locked by Plan Upgrade -->
              <button
                v-else-if="!mod.allowed_by_plan"
                type="button"
                @click="isPlanModalOpen = true"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-50 text-amber-800 text-xs font-bold border border-amber-200 hover:bg-amber-100 transition-colors cursor-pointer"
                title="Upgrade plan to unlock this module"
              >
                <Lock class="w-3 h-3 text-amber-600" />
                <span>Upgrade</span>
              </button>

              <!-- Interactive Switch -->
              <button
                v-else
                type="button"
                :disabled="toggleMutation.isPending.value"
                @click="handleToggle(mod)"
                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                :class="mod.is_enabled ? 'bg-indigo-600' : 'bg-slate-300'"
                :title="mod.is_enabled ? 'Click to disable module' : 'Click to enable module'"
              >
                <span
                  class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out"
                  :class="mod.is_enabled ? 'translate-x-5' : 'translate-x-0'"
                />
              </button>
            </div>
          </div>

          <!-- Description -->
          <p class="text-xs text-slate-600 leading-relaxed min-h-[38px]">
            {{ mod.description }}
          </p>
        </div>

        <!-- Dependencies & Downstream Relationships -->
        <div class="pt-3 border-t border-slate-100 space-y-2 text-[11px]">
          <!-- Prerequisites (Requires) -->
          <div v-if="mod.dependencies.length > 0" class="flex items-start gap-2">
            <span class="text-slate-400 font-bold shrink-0 mt-0.5">Requires:</span>
            <div class="flex flex-wrap gap-1">
              <span
                v-for="dep in mod.dependencies"
                :key="dep"
                class="px-2 py-0.5 rounded-md font-bold capitalize flex items-center gap-1"
                :class="[
                  isModuleActive(dep)
                    ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                    : 'bg-slate-100 text-slate-500 border border-slate-200'
                ]"
                :title="isModuleActive(dep) ? 'Prerequisite active' : 'Will be automatically enabled'"
              >
                <CheckCircle2 v-if="isModuleActive(dep)" class="w-2.5 h-2.5 text-emerald-600" />
                <Zap v-else class="w-2.5 h-2.5 text-amber-500" />
                {{ getModuleName(dep) }}
              </span>
            </div>
          </div>

          <!-- Downstream (Powers) -->
          <div v-if="mod.dependents.length > 0" class="flex items-start gap-2">
            <span class="text-slate-400 font-bold shrink-0 mt-0.5">Powers:</span>
            <div class="flex flex-wrap gap-1">
              <span
                v-for="down in mod.dependents.slice(0, 3)"
                :key="down"
                class="px-1.5 py-0.2 rounded text-[10px] font-semibold capitalize bg-slate-100 text-slate-600"
              >
                {{ getModuleName(down) }}
              </span>
              <span v-if="mod.dependents.length > 3" class="text-[10px] text-slate-400 font-bold">
                +{{ mod.dependents.length - 3 }} more
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Confirmation Modal When Disabling a Module with Active Dependents -->
    <UiModal
      v-model="confirmModalOpen"
      title="Deactivate Dependent Modules"
      size="md"
    >
      <div v-if="pendingDisableModule" class="space-y-4">
        <div class="p-4 bg-amber-50 border border-amber-200 rounded-2xl flex items-start gap-3">
          <AlertTriangle class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
          <div class="space-y-1 text-xs text-amber-900">
            <h4 class="font-bold text-sm">
              Deactivating {{ pendingDisableModule.name }}
            </h4>
            <p class="leading-relaxed">
              The following active modules depend on <strong>{{ pendingDisableModule.name }}</strong> and will also be automatically deactivated:
            </p>
            <div class="flex flex-wrap gap-1.5 pt-2">
              <span
                v-for="dep in pendingDisableDependents"
                :key="dep"
                class="px-2 py-0.5 rounded-lg bg-amber-200/80 text-amber-950 font-bold text-[11px]"
              >
                {{ getModuleName(dep) }}
              </span>
            </div>
          </div>
        </div>

        <p class="text-xs text-slate-500">
          You can reactivate {{ pendingDisableModule.name }} and its dependent engines at any time from this dashboard.
        </p>

        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
          <UiButton
            variant="outline"
            size="sm"
            type="button"
            @click="confirmModalOpen = false"
          >
            Cancel
          </UiButton>
          <UiButton
            variant="danger"
            size="sm"
            :loading="toggleMutation.isPending.value"
            @click="confirmDisable"
          >
            Confirm & Deactivate
          </UiButton>
        </div>
      </div>
    </UiModal>

    <!-- Plan Upgrade Modal -->
    <PlanSelectionModal v-model="isPlanModalOpen" />
  </div>
</template>
