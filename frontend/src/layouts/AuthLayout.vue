<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-primary-900 flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
      <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-white/10 backdrop-blur-sm border border-white/20 mb-6">
        <span class="text-3xl font-bold text-white">ERP</span>
      </div>
      <h2 class="text-3xl font-extrabold text-white tracking-tight">
        Enterprise Resource Planning
      </h2>
      <p class="mt-2 text-slate-400">
        Unified management for your business
      </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="bg-white/95 backdrop-blur-md py-8 px-4 shadow-2xl sm:rounded-xl sm:px-10 border border-white/20">
        <router-view v-slot="{ Component }">
          <transition name="fade" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </div>
      
      <div class="mt-6 text-center">
        <p class="text-xs text-slate-500">
          &copy; 2026 ERP System. All rights reserved.
        </p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
