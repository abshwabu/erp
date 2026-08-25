<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { resolveImageUrl } from '@/utils/format'
import { X, Package, Check, ShoppingCart } from '@lucide/vue'
import UiButton from '@/components/ui/UiButton.vue'

const props = defineProps<{
  product: any
  isOpen: boolean
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'select', variant: any, quantity: number): void
}>()

const selectedVariantId = ref<string | null>(null)
const quantity = ref(1)

const variants = computed(() => {
  if (!props.product?.variants || !Array.isArray(props.product.variants)) return []
  return props.product.variants
})

const selectedVariant = computed(() => {
  return variants.value.find((v: any) => String(v.id) === String(selectedVariantId.value)) || null
})

// Auto select first in-stock variant or first variant
const initSelection = () => {
  quantity.value = 1
  const firstInStock = variants.value.find((v: any) => (v.stock ?? v.available_quantity ?? 0) > 0)
  if (firstInStock) {
    selectedVariantId.value = String(firstInStock.id)
  } else if (variants.value.length > 0) {
    selectedVariantId.value = String(variants.value[0].id)
  } else {
    selectedVariantId.value = null
  }
}

watch(() => props.isOpen, (open) => {
  if (open) {
    initSelection()
  }
}, { immediate: true })

function handleConfirm() {
  if (!selectedVariant.value) return
  const availableStock = selectedVariant.value.stock ?? selectedVariant.value.available_quantity ?? 0
  if (availableStock <= 0) {
    alert(`${selectedVariant.value.name} is currently out of stock at this store.`)
    return
  }

  emit('select', selectedVariant.value, quantity.value)
  emit('close')
}
</script>

<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fade-in"
    @click.self="emit('close')"
  >
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-200 animate-scale-up flex flex-col max-h-[90vh]">
      <!-- Modal Header -->
      <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
        <div class="flex items-center gap-3">
          <div class="h-12 w-12 rounded-xl bg-white border border-slate-200 overflow-hidden flex items-center justify-center shrink-0 shadow-sm">
            <img
              v-if="resolveImageUrl(product?.image)"
              :src="resolveImageUrl(product.image)!"
              :alt="product?.name"
              class="w-full h-full object-cover"
            />
            <Package v-else class="h-6 w-6 text-slate-400" />
          </div>
          <div>
            <h3 class="font-bold text-sm sm:text-base text-slate-900 line-clamp-1">{{ product?.name }}</h3>
            <p class="text-xs text-slate-500 font-mono">{{ product?.sku }}</p>
          </div>
        </div>

        <button
          @click="emit('close')"
          class="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-200/60 rounded-xl transition-colors"
        >
          <X class="h-5 w-5" />
        </button>
      </div>

      <!-- Modal Body / Variants List -->
      <div class="p-4 sm:p-5 overflow-y-auto space-y-4 flex-1">
        <div>
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">
            Select Variant / Option
          </label>
          <div class="grid grid-cols-1 gap-2">
            <button
              v-for="v in variants"
              :key="v.id"
              type="button"
              @click="selectedVariantId = String(v.id)"
              class="w-full text-left p-3 rounded-xl border transition-all flex items-center justify-between gap-3 group"
              :class="[
                selectedVariantId === String(v.id)
                  ? 'border-blue-600 bg-blue-50/50 shadow-sm ring-1 ring-blue-600'
                  : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50',
                (v.stock ?? v.available_quantity ?? 0) <= 0 ? 'opacity-60 bg-slate-50/40' : ''
              ]"
            >
              <div class="flex items-center gap-3 min-w-0">
                <div
                  class="h-5 w-5 rounded-full border flex items-center justify-center transition-colors shrink-0"
                  :class="selectedVariantId === String(v.id) ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-300 bg-white'"
                >
                  <Check v-if="selectedVariantId === String(v.id)" class="h-3 w-3 stroke-[3]" />
                </div>
                <div class="min-w-0">
                  <div class="font-semibold text-xs sm:text-sm text-slate-900 truncate">{{ v.name }}</div>
                  <div class="text-[11px] text-slate-400 font-mono">{{ v.sku }}</div>
                </div>
              </div>

              <div class="text-right shrink-0">
                <div class="font-bold text-xs sm:text-sm text-slate-900">
                  ${{ (typeof v.selling_price === 'number' ? v.selling_price / 100 : (Number(v.selling_price || product.price) || 0)).toFixed(2) }}
                </div>
                <span
                  class="text-[10px] font-semibold px-2 py-0.5 rounded-full"
                  :class="(v.stock ?? v.available_quantity ?? 0) > 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'"
                >
                  {{ (v.stock ?? v.available_quantity ?? 0) > 0 ? `${v.stock ?? v.available_quantity} in store` : 'Out of stock' }}
                </span>
              </div>
            </button>
          </div>
        </div>

        <!-- Quantity Picker -->
        <div v-if="selectedVariant && (selectedVariant.stock ?? selectedVariant.available_quantity ?? 0) > 0" class="pt-2 border-t border-slate-100 flex items-center justify-between">
          <span class="text-xs font-semibold text-slate-700">Quantity</span>
          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="quantity = Math.max(1, quantity - 1)"
              class="h-8 w-8 rounded-lg border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-slate-100 font-bold"
            >
              -
            </button>
            <span class="w-8 text-center text-sm font-bold font-mono">{{ quantity }}</span>
            <button
              type="button"
              @click="quantity = Math.min(selectedVariant?.stock ?? 999, quantity + 1)"
              class="h-8 w-8 rounded-lg border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-slate-100 font-bold"
            >
              +
            </button>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-2 shrink-0">
        <UiButton
          variant="outline"
          size="sm"
          @click="emit('close')"
        >
          Cancel
        </UiButton>
        <UiButton
          variant="primary"
          size="sm"
          :disabled="!selectedVariant || (selectedVariant.stock ?? selectedVariant.available_quantity ?? 0) <= 0"
          @click="handleConfirm"
          class="gap-1.5"
        >
          <ShoppingCart class="w-4 h-4" />
          Add to Order
        </UiButton>
      </div>
    </div>
  </div>
</template>
