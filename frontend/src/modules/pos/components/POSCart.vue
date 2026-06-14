<script setup lang="ts">
import { usePosStore } from '../stores/posStore'
import UiButton from '@/components/ui/UiButton.vue'
import { X, Trash2 } from '@lucide/vue'

const posStore = usePosStore()
const emit = defineEmits(['checkout'])
</script>

<template>
  <div class="h-full bg-white flex flex-col border-l border-slate-200">
    <div class="p-4 border-b border-slate-200 font-semibold text-slate-900">Current Order</div>
    
    <div class="flex-1 overflow-y-auto p-4 space-y-3">
      <div v-for="item in posStore.cart" :key="item.id" class="flex justify-between items-center p-3 bg-slate-50 rounded-lg">
        <div>
          <div class="font-medium text-slate-900">{{ item.name }}</div>
          <div class="text-xs text-slate-500">x{{ item.quantity }} @ ${{ item.price }}</div>
        </div>
        <div class="flex items-center gap-3">
          <div class="font-semibold text-slate-900">${{ (item.price * item.quantity).toFixed(2) }}</div>
          <button @click="posStore.removeFromCart(item.id)" class="text-slate-400 hover:text-red-500">
            <Trash2 class="h-4 w-4" />
          </button>
        </div>
      </div>
    </div>
    
    <div class="border-t border-slate-200 p-4 bg-slate-50">
      <div class="flex justify-between text-lg font-bold text-slate-900 mb-4">
        <span>Total</span>
        <span>${{ posStore.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0).toFixed(2) }}</span>
      </div>
      <UiButton @click="emit('checkout')" class="w-full" size="lg">Charge Order</UiButton>
    </div>
  </div>
</template>
