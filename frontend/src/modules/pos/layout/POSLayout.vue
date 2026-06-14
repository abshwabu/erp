<script setup lang="ts">
import { ref } from 'vue'
import { RouterView } from 'vue-router'
import ProductGrid from '../components/ProductGrid.vue'
import POSCart from '../components/POSCart.vue'
import PaymentPanel from '../components/PaymentPanel.vue'

const showPayment = ref(false)
</script>

<template>
  <div class="h-screen w-screen bg-slate-50 text-slate-900 flex overflow-hidden">
    <!-- POS Layout: Fullscreen, no sidebar -->
    <div class="w-[60%] border-r border-slate-200">
      <ProductGrid />
    </div>
    <div class="w-[40%] bg-white border-l border-slate-200">
      <PaymentPanel v-if="showPayment" />
      <POSCart v-else @checkout="showPayment = true" />
    </div>
    <RouterView />
  </div>
</template>
