<script setup lang="ts">
import { ref, computed } from 'vue'
import { usePosStore } from '../stores/posStore'
import { ShoppingCart, Plus, Minus, Trash2, ArrowLeft, RotateCcw, CreditCard, User, Search, Loader2 } from '@lucide/vue'

const posStore = usePosStore()
const emit = defineEmits(['checkout', 'switch-to-catalog'])
const showCustomerForm = ref(false)

async function handleTinSearch() {
  await posStore.lookupCustomerByTin()
}

const subtotal = computed(() => {
  return posStore.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0)
})

const taxAmount = computed(() => {
  return subtotal.value * 0.05 // 5% estimated tax
})

const grandTotal = computed(() => {
  return subtotal.value + taxAmount.value
})

const totalItems = computed(() => {
  return posStore.cart.reduce((sum, item) => sum + item.quantity, 0)
})
</script>

<template>
  <div class="h-full bg-white flex flex-col justify-between overflow-hidden">
    <!-- Cart Header -->
    <div class="p-4 border-b border-slate-200/80 flex items-center justify-between shrink-0 bg-slate-50/50">
      <div class="flex items-center gap-2">
        <button
          @click="emit('switch-to-catalog')"
          class="md:hidden p-1.5 text-slate-500 hover:text-slate-900 rounded-lg hover:bg-slate-200/60 transition-colors"
          title="Back to Catalog"
        >
          <ArrowLeft class="w-4 h-4" />
        </button>

        <div class="p-2 bg-blue-50 text-blue-600 rounded-xl">
          <ShoppingCart class="w-4 h-4" />
        </div>
        <div>
          <h2 class="font-bold text-sm text-slate-900 leading-tight">Current Order</h2>
          <p class="text-[11px] text-slate-500">{{ totalItems }} item(s) selected</p>
        </div>
      </div>

      <button
        v-if="posStore.cart.length > 0"
        @click="posStore.clearCart()"
        class="text-xs font-medium text-slate-400 hover:text-red-600 inline-flex items-center gap-1 transition-colors px-2 py-1 rounded-lg hover:bg-red-50"
      >
        <RotateCcw class="w-3.5 h-3.5" />
        Clear
      </button>
    </div>

    <!-- Customer Bar / Inline Input (Optional) -->
    <div class="px-3.5 py-2 border-b border-slate-200/80 bg-slate-50/60 shrink-0">
      <div v-if="!showCustomerForm && (posStore.customer.tin || posStore.customer.name)" class="flex items-center justify-between text-xs">
        <div class="flex items-center gap-1.5 min-w-0">
          <User class="w-3.5 h-3.5 text-blue-600 shrink-0" />
          <span class="font-semibold text-slate-800 truncate">
            {{ posStore.customer.name || 'Customer' }}
          </span>
          <span v-if="posStore.customer.tin" class="text-blue-700 font-mono text-[10px] bg-blue-100/70 px-1.5 py-0.5 rounded font-bold shrink-0">
            TIN: {{ posStore.customer.tin }}
          </span>
        </div>
        <div class="flex items-center gap-2 shrink-0 ml-2">
          <button
            type="button"
            @click="showCustomerForm = true"
            class="text-[11px] text-blue-600 hover:text-blue-700 font-semibold"
          >
            Edit
          </button>
          <button
            type="button"
            @click="posStore.clearCustomer"
            class="text-[11px] text-slate-400 hover:text-red-500"
          >
            Clear
          </button>
        </div>
      </div>

      <div v-else-if="!showCustomerForm" class="flex items-center justify-between">
        <button
          type="button"
          @click="showCustomerForm = true"
          class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500 hover:text-blue-600 transition-colors"
        >
          <User class="w-3.5 h-3.5 text-slate-400" />
          <span>+ Add Customer / TIN <span class="text-[10px] text-slate-400 font-normal">(Optional)</span></span>
        </button>
      </div>

      <!-- Expanded Customer Quick Entry Form -->
      <div v-else class="space-y-2 py-0.5">
        <div class="flex items-center justify-between text-[11px] font-semibold text-slate-700">
          <span>Customer & TIN (Optional)</span>
          <button
            type="button"
            @click="showCustomerForm = false"
            class="text-blue-600 hover:text-blue-700 font-bold text-xs"
          >
            Done
          </button>
        </div>

        <div class="flex gap-1.5">
          <input
            v-model="posStore.customer.tin"
            @keydown.enter.prevent="handleTinSearch"
            type="text"
            maxlength="30"
            placeholder="TIN (e.g. 0012345678)"
            class="flex-1 px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-mono text-slate-900 focus:outline-none focus:ring-1 focus:ring-blue-500 placeholder:text-slate-400"
          />
          <button
            type="button"
            @click="handleTinSearch"
            :disabled="posStore.isLookingUpTin || !posStore.customer.tin?.trim()"
            class="px-2.5 py-1.5 bg-blue-600 hover:bg-blue-500 disabled:bg-slate-200 disabled:text-slate-400 text-white text-xs font-semibold rounded-lg transition-colors flex items-center gap-1 shrink-0"
            title="Lookup customer name from eTrade or database records"
          >
            <Loader2 v-if="posStore.isLookingUpTin" class="w-3 h-3 animate-spin" />
            <Search v-else class="w-3 h-3" />
            <span>Lookup</span>
          </button>
        </div>

        <input
          v-model="posStore.customer.name"
          type="text"
          maxlength="255"
          placeholder="Customer / Business name (optional)"
          class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-blue-500 placeholder:text-slate-400"
        />

        <p v-if="posStore.tinLookupMessage" class="text-[10px] text-slate-500 leading-tight">
          {{ posStore.tinLookupMessage }}
        </p>
      </div>
    </div>

    <!-- Items List -->
    <div class="flex-1 overflow-y-auto p-4 space-y-3">
      <template v-if="posStore.cart.length > 0">
        <div
          v-for="item in posStore.cart"
          :key="item.cartKey || item.id"
          class="p-3 bg-slate-50 border border-slate-200/80 rounded-xl flex items-center justify-between gap-3 hover:border-slate-300 transition-all"
        >
          <div class="flex-1 min-w-0">
            <div class="font-semibold text-xs text-slate-900 truncate mb-0.5">{{ item.name }}</div>
            <div class="flex items-center gap-1.5 text-[11px] text-slate-500 font-mono">
              <span>${{ Number(item.price || 0).toFixed(2) }} / unit</span>
              <span v-if="item.sku" class="text-slate-400 truncate max-w-[100px]">· {{ item.sku }}</span>
            </div>
          </div>

          <!-- Quantity Controls -->
          <div class="flex items-center gap-1 bg-white border border-slate-200 rounded-lg p-0.5 shadow-sm">
            <button
              type="button"
              @click.stop.prevent="posStore.updateQuantity(item.cartKey || item.id, -1)"
              class="p-1.5 rounded-md text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition-colors cursor-pointer active:scale-95 select-none"
              title="Decrease quantity"
            >
              <Minus class="w-3.5 h-3.5" />
            </button>
            <span class="w-7 text-center text-xs font-bold text-slate-900 font-mono select-none">{{ item.quantity }}</span>
            <button
              type="button"
              @click.stop.prevent="posStore.updateQuantity(item.cartKey || item.id, 1)"
              class="p-1.5 rounded-md text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition-colors cursor-pointer active:scale-95 select-none"
              title="Increase quantity"
            >
              <Plus class="w-3.5 h-3.5" />
            </button>
          </div>

          <!-- Item Price Total & Trash -->
          <div class="flex items-center gap-2 text-right shrink-0">
            <span class="font-bold text-xs text-slate-900 min-w-[50px] text-right">
              ${{ (item.price * item.quantity).toFixed(2) }}
            </span>
            <button
              @click="posStore.removeFromCart(item.cartKey || item.id)"
              class="text-slate-300 hover:text-red-500 transition-colors p-1"
            >
              <Trash2 class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>
      </template>

      <!-- Empty Cart State -->
      <div v-else class="h-full flex flex-col items-center justify-center text-slate-400 p-6 text-center">
        <div class="p-4 bg-slate-100 rounded-full mb-3 text-slate-300">
          <ShoppingCart class="w-10 h-10 stroke-[1.5]" />
        </div>
        <p class="text-sm font-semibold text-slate-700">Order Cart is Empty</p>
        <p class="text-xs text-slate-400 max-w-[200px] mt-1">Tap products from the catalog to add items to this checkout order.</p>
      </div>
    </div>

    <!-- Order Summary & Checkout Footer -->
    <div class="border-t border-slate-200/80 p-4 bg-slate-50/80 shrink-0 space-y-3">
      <div class="space-y-1.5 text-xs text-slate-600">
        <div class="flex justify-between">
          <span>Subtotal</span>
          <span class="font-mono text-slate-900">${{ subtotal.toFixed(2) }}</span>
        </div>
        <div class="flex justify-between">
          <span>Estimated Tax (5%)</span>
          <span class="font-mono text-slate-900">${{ taxAmount.toFixed(2) }}</span>
        </div>
        <div class="flex justify-between text-base font-bold text-slate-900 pt-2 border-t border-slate-200">
          <span>Total</span>
          <span class="font-mono text-blue-600">${{ grandTotal.toFixed(2) }}</span>
        </div>
      </div>

      <button
        @click="emit('checkout')"
        :disabled="posStore.cart.length === 0"
        class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-500 disabled:bg-slate-300 disabled:cursor-not-allowed text-white font-bold text-sm rounded-xl shadow-md shadow-blue-600/20 transition-all flex items-center justify-center gap-2 active:scale-98"
      >
        <CreditCard class="w-4 h-4" />
        Charge Order (${{ grandTotal.toFixed(2) }})
      </button>
    </div>
  </div>
</template>
