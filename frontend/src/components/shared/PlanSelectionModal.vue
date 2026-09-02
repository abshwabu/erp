<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { authApi } from '@/api/auth'
import { useToast } from '@/composables/useToast'
import {
  Crown,
  Zap,
  Layers,
  CheckCircle2,
  AlertOctagon,
  ShieldCheck,
  Clock,
  Sparkles,
  X,
} from '@lucide/vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiSpinner from '@/components/ui/UiSpinner.vue'

const props = withDefaults(
  defineProps<{
    modelValue?: boolean
    forced?: boolean
  }>(),
  {
    modelValue: false,
    forced: false,
  }
)

const emit = defineEmits<{
  (e: 'update:modelValue', val: boolean): void
  (e: 'plan-selected', plan: any): void
}>()

const authStore = useAuthStore()
const toast = useToast()

const billingCycle = ref<'monthly' | 'annually'>('monthly')
const isLoading = ref(false)
const isSubmitting = ref(false)
const selectedPlanId = ref<string | null>(null)
const plans = ref<any[]>([])

const isOpen = computed({
  get: () => props.forced || props.modelValue,
  set: (val: boolean) => {
    if (!props.forced) {
      emit('update:modelValue', val)
    }
  },
})

const fetchPlans = async () => {
  isLoading.value = true
  try {
    const res = await authApi.getBillingPlans()
    plans.value = res.data || []
    if (plans.value.length > 0) {
      // Default select the most popular or current plan
      selectedPlanId.value = plans.value.find((p) => p.slug === 'professional')?.id || plans.value[0].id
    }
  } catch (err: any) {
    toast.error('Failed to load subscription plans: ' + (err?.response?.data?.message || err?.message))
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchPlans()
})

const handleSelectPlan = async (plan: any) => {
  isSubmitting.value = true
  try {
    const res = await authApi.selectPlan({
      plan_id: plan.id,
      billing_cycle: billingCycle.value,
    })

    toast.success(res.message || `Successfully activated ${plan.name} plan!`)
    emit('plan-selected', plan)

    // Refresh user & tenant state
    await authStore.checkAuth()

    if (!props.forced) {
      emit('update:modelValue', false)
    }
  } catch (err: any) {
    toast.error(err?.response?.data?.message || err?.message || 'Failed to activate plan.')
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <!-- Full Screen / Modal Overlay -->
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-md overflow-y-auto"
  >
    <div
      class="bg-white rounded-3xl border border-slate-200 shadow-2xl w-full max-w-5xl my-8 overflow-hidden flex flex-col relative"
      :class="props.forced ? 'ring-4 ring-amber-500/20' : ''"
    >
      <!-- Close button for voluntary upgrades -->
      <button
        v-if="!props.forced"
        @click="isOpen = false"
        type="button"
        class="absolute top-5 right-5 p-2 rounded-full text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors z-10"
      >
        <X class="w-5 h-5" />
      </button>

      <!-- Banner Header -->
      <div
        class="p-6 lg:p-8 text-center space-y-3"
        :class="props.forced ? 'bg-gradient-to-b from-amber-500/10 to-transparent' : 'bg-slate-50/50'"
      >
        <div v-if="props.forced" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-amber-100 text-amber-900 text-xs font-bold border border-amber-200 shadow-xs">
          <AlertOctagon class="w-4 h-4 text-amber-600" />
          <span>Your 2-Month Free Trial Has Ended</span>
        </div>

        <div v-else class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-indigo-50 text-indigo-700 text-xs font-bold border border-indigo-100">
          <Sparkles class="w-4 h-4 text-indigo-600" />
          <span>Choose Your Workspace Subscription</span>
        </div>

        <h2 class="text-2xl lg:text-3xl font-black text-slate-900 tracking-tight">
          {{ props.forced ? 'Select a Plan to Continue Operations' : 'Upgrade Your ERP Capabilities' }}
        </h2>

        <p class="text-sm text-slate-500 max-w-2xl mx-auto leading-relaxed">
          {{
            props.forced
              ? 'Your organization has enjoyed 60 days of enterprise cloud ERP. Choose a continuous subscription tier in Ethiopian Birr to retain seamless access to invoices, ledger, inventory, and team data.'
              : 'Select the optimal operational tier for your team. All pricing is billed in Ethiopian Birr (ETB) with instant activation.'
          }}
        </p>

        <!-- Billing Cycle Toggle -->
        <div class="pt-2 flex justify-center">
          <div class="inline-flex items-center p-1 bg-slate-200/70 rounded-2xl text-xs font-bold">
            <button
              type="button"
              @click="billingCycle = 'monthly'"
              class="px-4 py-1.5 rounded-xl transition-all"
              :class="billingCycle === 'monthly' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
            >
              Billed Monthly
            </button>
            <button
              type="button"
              @click="billingCycle = 'annually'"
              class="px-4 py-1.5 rounded-xl transition-all flex items-center gap-1.5"
              :class="billingCycle === 'annually' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
            >
              <span>Billed Annually</span>
              <span class="px-1.5 py-0.2 rounded-md bg-emerald-100 text-emerald-800 text-[10px] font-black uppercase">
                Save ~17%
              </span>
            </button>
          </div>
        </div>
      </div>

      <!-- Plan Cards Container -->
      <div class="p-6 lg:p-8 pt-0">
        <div v-if="isLoading" class="p-16 flex justify-center">
          <UiSpinner size="lg" />
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div
            v-for="plan in plans"
            :key="plan.id"
            class="rounded-3xl border p-6 flex flex-col justify-between space-y-6 relative transition-all duration-200 hover:shadow-lg"
            :class="[
              plan.slug === 'enterprise' ? 'border-purple-300 ring-2 ring-purple-600/30 bg-purple-50/10' :
              plan.slug === 'professional' ? 'border-blue-300 ring-2 ring-blue-600/30 bg-blue-50/10' :
              'border-slate-200 bg-white'
            ]"
          >
            <!-- Badge -->
            <div
              v-if="plan.badge"
              class="absolute top-0 right-0 font-bold text-[10px] px-3 py-1 rounded-bl-xl uppercase tracking-wider text-white"
              :class="[
                plan.slug === 'enterprise' ? 'bg-gradient-to-r from-purple-600 to-indigo-600' :
                plan.slug === 'professional' ? 'bg-gradient-to-r from-blue-600 to-cyan-600' :
                'bg-slate-700'
              ]"
            >
              {{ plan.badge }}
            </div>

            <div class="space-y-4">
              <div class="flex items-center gap-2">
                <Crown v-if="plan.slug === 'enterprise'" class="w-5 h-5 text-amber-500" />
                <Zap v-else-if="plan.slug === 'professional'" class="w-5 h-5 text-blue-500" />
                <Layers v-else class="w-5 h-5 text-slate-500" />
                <h3 class="text-xl font-black text-slate-900">{{ plan.name }}</h3>
              </div>

              <p class="text-xs text-slate-500 leading-relaxed min-h-[36px]">
                {{ plan.tagline || plan.description }}
              </p>

              <!-- Price -->
              <div class="p-4 bg-slate-50/80 rounded-2xl border border-slate-100 space-y-1">
                <div class="flex items-baseline gap-1.5">
                  <span class="text-3xl font-black text-slate-900">
                    {{
                      billingCycle === 'monthly'
                        ? (plan.price_monthly / 100).toLocaleString()
                        : (plan.price_annually / 100 / 12).toFixed(0)
                    }}
                  </span>
                  <span class="text-sm font-bold text-slate-700">Birr</span>
                  <span class="text-xs text-slate-500 font-medium">/ month</span>
                </div>
                <div class="text-[11px] text-slate-400 font-medium">
                  {{
                    billingCycle === 'monthly'
                      ? 'Billed monthly per organization'
                      : `${(plan.price_annually / 100).toLocaleString()} Birr billed annually`
                  }}
                </div>
              </div>

              <!-- Perks -->
              <div class="space-y-2 pt-2 border-t border-slate-100">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">
                  Included Perks & Features
                </span>
                <ul class="space-y-2 text-xs text-slate-700">
                  <li
                    v-for="(perk, i) in plan.perks.slice(0, 7)"
                    :key="i"
                    class="flex items-start gap-2 leading-snug"
                  >
                    <CheckCircle2 class="w-3.5 h-3.5 text-emerald-600 shrink-0 mt-0.5" />
                    <span :class="perk.startsWith('Everything in') ? 'font-bold text-indigo-700' : ''">{{ perk }}</span>
                  </li>
                </ul>
              </div>
            </div>

            <!-- Action Button -->
            <div class="pt-4 border-t border-slate-100">
              <UiButton
                class="w-full font-bold shadow-xs cursor-pointer"
                :variant="plan.slug === 'professional' ? 'primary' : plan.slug === 'enterprise' ? 'primary' : 'outline'"
                :loading="isSubmitting"
                @click="handleSelectPlan(plan)"
              >
                Choose {{ plan.name }}
              </UiButton>
            </div>
          </div>
        </div>
      </div>

      <!-- Trust Footer -->
      <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500">
        <div class="flex items-center gap-2">
          <ShieldCheck class="w-4 h-4 text-emerald-600" />
          <span>Encrypted PostgreSQL Multi-Tenant Database Guard</span>
        </div>
        <div class="flex items-center gap-2">
          <Clock class="w-4 h-4 text-blue-600" />
          <span>Instant Activation • Upgrade or Change Anytime</span>
        </div>
      </div>
    </div>
  </div>
</template>
