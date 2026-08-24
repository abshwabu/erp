<script setup lang="ts">
import { useRoute } from 'vue-router'
import { CheckCircle2, ShieldCheck, Zap, BarChart3, Building2 } from '@lucide/vue'

const route = useRoute()

const highlights = [
  {
    icon: Zap,
    title: 'Real-Time Multi-Tenant Operations',
    desc: 'Instant POS checkout, inventory sync, and multi-branch warehouse routing.',
  },
  {
    icon: BarChart3,
    title: 'Automated Accounting & Analytics',
    desc: 'Double-entry journals, automated depreciation, and live P&L generation.',
  },
  {
    icon: ShieldCheck,
    title: 'Enterprise-Grade Security',
    desc: 'Tenant-isolated databases, granular RBAC, and secure JWT authentication.',
  },
]
</script>

<template>
  <div class="min-h-screen flex bg-slate-950 text-slate-100 selection:bg-primary-500 selection:text-white">
    <!-- Left Hero Banner (Desktop) -->
    <div class="hidden lg:flex lg:w-1/2 relative flex-col justify-between p-12 overflow-hidden bg-gradient-to-br from-slate-950 via-slate-900 to-primary-950 border-r border-slate-800/60">
      <!-- Ambient Glow Orbs -->
      <div class="absolute -top-24 -left-24 w-96 h-96 bg-primary-600/20 rounded-full blur-3xl pointer-events-none"></div>
      <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl pointer-events-none"></div>

      <!-- Top Branding -->
      <div class="relative z-10">
        <router-link to="/" class="inline-flex items-center space-x-3 group">
          <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-primary-600 to-indigo-500 flex items-center justify-center shadow-lg shadow-primary-600/30 group-hover:scale-105 transition-transform">
            <Building2 class="w-6 h-6 text-white" />
          </div>
          <div>
            <span class="text-2xl font-black tracking-tight text-white block">ERP <span class="text-primary-400 font-light">CORE</span></span>
            <span class="text-xs text-slate-400 font-mono tracking-widest uppercase">Enterprise Suite</span>
          </div>
        </router-link>
      </div>

      <!-- Center Feature Highlights -->
      <div class="relative z-10 my-auto py-10 space-y-8 max-w-lg">
        <div class="space-y-3">
          <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full text-xs font-semibold bg-primary-500/10 text-primary-400 border border-primary-500/20">
            <span class="w-2 h-2 rounded-full bg-primary-400 animate-pulse"></span>
            <span>Version 2.0 • Production Ready</span>
          </span>
          <h1 class="text-4xl font-extrabold text-white tracking-tight leading-tight">
            Next-Generation ERP for Modern Commerce
          </h1>
          <p class="text-slate-400 text-sm leading-relaxed">
            Unify point of sale, inventory tracking, manufacturing, payroll, and financial reports in one seamless, high-performance platform.
          </p>
        </div>

        <div class="space-y-5">
          <div
            v-for="(item, idx) in highlights"
            :key="idx"
            class="flex items-start space-x-4 p-3.5 rounded-xl bg-white/[0.03] border border-white/[0.06] backdrop-blur-sm"
          >
            <div class="p-2.5 rounded-lg bg-primary-500/10 text-primary-400 border border-primary-500/20 shrink-0">
              <component :is="item.icon" class="w-5 h-5" />
            </div>
            <div class="space-y-0.5">
              <h3 class="text-sm font-semibold text-white">{{ item.title }}</h3>
              <p class="text-xs text-slate-400 leading-relaxed">{{ item.desc }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Bottom Trust Metrics -->
      <div class="relative z-10 pt-6 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400">
        <div class="flex items-center space-x-2">
          <CheckCircle2 class="w-4 h-4 text-emerald-400" />
          <span>99.99% Guaranteed Uptime</span>
        </div>
        <div>
          <span>Multi-tenant Redis & PostgreSQL</span>
        </div>
      </div>
    </div>

    <!-- Right Form Section -->
    <div class="w-full lg:w-1/2 flex flex-col justify-between p-6 sm:p-12 overflow-y-auto bg-slate-900/50">
      <!-- Mobile Logo Header -->
      <div class="lg:hidden flex items-center justify-between mb-8 pb-4 border-b border-slate-800">
        <router-link to="/" class="inline-flex items-center space-x-2.5">
          <div class="w-10 h-10 rounded-lg bg-primary-600 flex items-center justify-center">
            <Building2 class="w-5 h-5 text-white" />
          </div>
          <span class="text-xl font-bold text-white">ERP <span class="text-primary-400">CORE</span></span>
        </router-link>
      </div>

      <!-- Auth Form Card Container -->
      <div class="my-auto w-full max-w-md mx-auto">
        <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-2xl shadow-black/50 border border-slate-200">
          <router-view v-slot="{ Component }">
            <transition name="fade" mode="out-in">
              <component :is="Component" :key="route.fullPath" />
            </transition>
          </router-view>
        </div>
      </div>

      <!-- Footer Copyright -->
      <div class="pt-8 text-center text-xs text-slate-500">
        &copy; 2026 ERP System. Enterprise Architecture Edition.
      </div>
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}

.fade-enter-from {
  opacity: 0;
  transform: translateY(4px);
}

.fade-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
