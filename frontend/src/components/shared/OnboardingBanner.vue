<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useOnboardingStore, type OnboardingStep } from '@/stores/onboarding'
import {
  Rocket,
  CheckCircle2,
  Circle,
  ChevronDown,
  ChevronUp,
  X,
  ArrowRight,
  Check,
} from '@lucide/vue'

const router = useRouter()
const onboardingStore = useOnboardingStore()

function handleStepClick(step: OnboardingStep) {
  onboardingStore.completeStep(step.id)
  router.push(step.route)
}
</script>

<template>
  <div
    v-if="onboardingStore.isVisible"
    class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white rounded-3xl p-5 sm:p-6 shadow-md border border-slate-800 space-y-4"
  >
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
              class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-amber-400/20 text-amber-300 border border-amber-400/30"
            >
              {{ onboardingStore.completedCount }} of {{ onboardingStore.totalSteps }} Done
            </span>
          </div>
          <p class="text-xs text-slate-300">Essential milestones to customize, configure, and scale your business operations.</p>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <button
          type="button"
          @click="onboardingStore.markAllCompleted()"
          class="hidden sm:inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-white/10 hover:bg-white/20 text-xs font-bold text-white transition-colors cursor-pointer border border-white/10"
          title="Dismiss and mark all completed"
        >
          <Check class="w-3.5 h-3.5 text-emerald-400" /> Mark all done
        </button>
        <button
          type="button"
          @click="onboardingStore.isCollapsed = !onboardingStore.isCollapsed"
          class="p-1.5 rounded-xl hover:bg-white/10 text-slate-400 hover:text-white transition-colors cursor-pointer"
          :title="onboardingStore.isCollapsed ? 'Expand Checklist' : 'Collapse Checklist'"
        >
          <ChevronDown v-if="onboardingStore.isCollapsed" class="w-5 h-5" />
          <ChevronUp v-else class="w-5 h-5" />
        </button>
        <button
          type="button"
          @click="onboardingStore.dismiss()"
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
        <span class="font-mono font-bold text-amber-400">{{ onboardingStore.progressPercentage }}%</span>
      </div>
      <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden border border-slate-700">
        <div
          class="bg-gradient-to-r from-amber-400 via-indigo-400 to-emerald-400 h-2 rounded-full transition-all duration-500"
          :style="{ width: `${Math.max(onboardingStore.progressPercentage, 4)}%` }"
        ></div>
      </div>
    </div>

    <!-- Collapsible Steps Grid -->
    <div v-if="!onboardingStore.isCollapsed" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 pt-2">
      <div
        v-for="step in onboardingStore.steps"
        :key="step.id"
        class="p-3.5 rounded-2xl border transition-all flex flex-col justify-between space-y-3 cursor-pointer group"
        :class="onboardingStore.completedSteps.includes(step.id) ? 'bg-white/5 border-slate-700/60' : 'bg-white/10 border-indigo-500/30 hover:bg-white/15 hover:border-indigo-400/50'"
        @click="handleStepClick(step)"
      >
        <div class="flex items-start justify-between gap-2">
          <div class="p-2 rounded-xl bg-white/10 text-amber-300 group-hover:scale-105 transition-transform">
            <component :is="step.icon" class="w-4 h-4" />
          </div>
          <button
            type="button"
            @click.stop="onboardingStore.toggleStep(step.id)"
            class="text-slate-400 hover:text-emerald-400 transition-colors cursor-pointer"
          >
            <CheckCircle2 v-if="onboardingStore.completedSteps.includes(step.id)" class="w-4 h-4 text-emerald-400" />
            <Circle v-else class="w-4 h-4 text-slate-400" />
          </button>
        </div>

        <div>
          <h4
            class="text-xs font-bold text-white group-hover:text-amber-300 transition-colors"
            :class="onboardingStore.completedSteps.includes(step.id) ? 'line-through text-slate-400' : ''"
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
