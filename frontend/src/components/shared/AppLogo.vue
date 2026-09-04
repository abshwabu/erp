<script setup lang="ts">
interface Props {
  size?: 'sm' | 'md' | 'lg' | 'xl'
  showText?: boolean
  light?: boolean
  variant?: 'full' | 'icon' | 'badge'
}

withDefaults(defineProps<Props>(), {
  size: 'md',
  showText: true,
  light: false,
  variant: 'full',
})
</script>

<template>
  <div class="inline-flex items-center gap-2.5 select-none group">
    <!-- Bina ERP Geometric Architectural "B" Logomark -->
    <div
      class="relative shrink-0 flex items-center justify-center transition-transform group-hover:scale-105 duration-200"
      :class="{
        'w-7 h-7': size === 'sm',
        'w-9 h-9': size === 'md',
        'w-11 h-11': size === 'lg',
        'w-14 h-14': size === 'xl',
      }"
    >
      <svg
        viewBox="0 0 48 48"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        class="w-full h-full drop-shadow-sm"
      >
        <defs>
          <!-- Radiant Primary Linear Gradient -->
          <linearGradient id="binaGrad1" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#6366F1" />
            <stop offset="50%" stop-color="#4F46E5" />
            <stop offset="100%" stop-color="#3730A3" />
          </linearGradient>
          <!-- Accent Cyan Gradient for upper architectural wing -->
          <linearGradient id="binaGrad2" x1="0%" y1="100%" x2="100%" y2="0%">
            <stop offset="0%" stop-color="#06B6D4" />
            <stop offset="100%" stop-color="#38BDF8" />
          </linearGradient>
          <!-- Warm Amber / Gold highlight dot -->
          <linearGradient id="binaGrad3" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#F59E0B" />
            <stop offset="100%" stop-color="#EF4444" />
          </linearGradient>
        </defs>

        <!-- Outer Rounded Squircle Base Shield -->
        <rect width="48" height="48" rx="12" fill="url(#binaGrad1)" />

        <!-- Isometric Architectural "B" Pillar Geometry -->
        <!-- Vertical Backbone Beam -->
        <path
          d="M12 12H19C21.7614 12 24 14.2386 24 17C24 19.7614 21.7614 22 19 22H12V12Z"
          fill="white"
          fill-opacity="0.95"
        />
        <path
          d="M12 22H21C23.7614 22 26 24.2386 26 27C26 29.7614 23.7614 32 21 32H12V22Z"
          fill="white"
          fill-opacity="0.95"
        />
        <rect x="12" y="12" width="5" height="24" rx="2" fill="white" />

        <!-- Floating Architectural Dynamic Nodes (Construction & Connectivity) -->
        <path
          d="M26 14L34 18V24L26 20V14Z"
          fill="url(#binaGrad2)"
        />
        <path
          d="M28 24L36 28V34L28 30V24Z"
          fill="url(#binaGrad2)"
          fill-opacity="0.9"
        />
        <!-- Center connection energy dot -->
        <circle cx="34" cy="18" r="2.5" fill="#FBBF24" />
      </svg>
    </div>

    <!-- Wordmark / Brand Typography -->
    <div v-if="showText && variant !== 'icon'" class="flex flex-col leading-none">
      <div class="flex items-center gap-1">
        <span
          class="font-black tracking-tight"
          :class="[
            light ? 'text-white' : 'text-slate-900',
            {
              'text-base': size === 'sm',
              'text-lg': size === 'md',
              'text-xl': size === 'lg',
              'text-2xl': size === 'xl',
            }
          ]"
        >
          BINA
        </span>
        <span
          class="font-extrabold px-1.5 py-0.5 rounded-md text-[10px] tracking-wider uppercase"
          :class="light ? 'bg-indigo-500/30 text-indigo-200 border border-indigo-400/30' : 'bg-indigo-600 text-white shadow-xs'"
        >
          ERP
        </span>
      </div>
      <span
        class="text-[9px] font-mono uppercase tracking-widest mt-0.5"
        :class="light ? 'text-slate-400' : 'text-slate-500'"
      >
        Enterprise Cloud
      </span>
    </div>
  </div>
</template>
