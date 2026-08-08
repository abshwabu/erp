<script setup lang="ts">
import { useRouter } from 'vue-router'

const router = useRouter()
</script>

<template>
  <div class="min-h-[60vh] flex flex-col items-center justify-center px-4 text-center">
    <p class="text-6xl font-bold text-slate-300 tracking-tight">404</p>
    <h1 class="mt-4 text-2xl font-semibold text-slate-900">Page not found</h1>
    <p class="mt-2 text-sm text-slate-500 max-w-md">
      The page you are looking for does not exist or has been moved.
    </p>
    <button
      type="button"
      class="mt-8 inline-flex items-center px-4 py-2 rounded-md bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 transition-colors"
      @click="router.push({ name: 'dashboard' })"
    >
      Back to Dashboard
    </button>
  </div>
</template>
