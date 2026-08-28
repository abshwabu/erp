<script setup lang="ts">
import { ref, reactive, watch, computed } from 'vue'
import api from '@/api/client'
import { useToast } from '@/composables/useToast'
import {
  Package,
  Truck,
  CheckCircle2,
  XCircle,
  Clock,
  MapPin,
  Hash,
  FileText,
  Loader2,
  X,
} from '@lucide/vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiButton from '@/components/ui/UiButton.vue'

interface EcommerceOrder {
  id: string
  order_number: string
  customer_name: string
  customer_email: string
  total_cents: number
  currency: string
  payment_status: string
  fulfillment_status: string
  tracking_number?: string
  shipping_carrier?: string
  notes?: string
  created_at: string
  channel?: { name: string }
}

interface Props {
  modelValue: boolean
  order: EcommerceOrder | null
}

const props = defineProps<Props>()
const emit = defineEmits(['update:modelValue', 'fulfilled'])

const toast = useToast()
const submitting = ref(false)

const form = reactive({
  fulfillment_status: 'shipped' as string,
  tracking_number: '',
  shipping_carrier: '',
  notes: '',
})

const errors = reactive<Record<string, string>>({})

const statusOptions = [
  { value: 'unfulfilled', label: 'Unfulfilled', icon: Clock, color: 'text-yellow-600', bg: 'bg-yellow-50 border-yellow-200' },
  { value: 'shipped', label: 'Shipped', icon: Truck, color: 'text-blue-600', bg: 'bg-blue-50 border-blue-200' },
  { value: 'fulfilled', label: 'Fulfilled / Delivered', icon: CheckCircle2, color: 'text-emerald-600', bg: 'bg-emerald-50 border-emerald-200' },
  { value: 'cancelled', label: 'Cancelled', icon: XCircle, color: 'text-red-600', bg: 'bg-red-50 border-red-200' },
]

const carrierOptions = [
  'DHL',
  'FedEx',
  'UPS',
  'USPS',
  'Aramex',
  'EMS',
  'Local Courier',
  'Other',
]

// Populate form when order changes
watch(
  () => props.order,
  (order) => {
    if (order) {
      form.fulfillment_status = order.fulfillment_status === 'unfulfilled' ? 'shipped' : order.fulfillment_status
      form.tracking_number = order.tracking_number || ''
      form.shipping_carrier = order.shipping_carrier || ''
      form.notes = order.notes || ''
    }
  },
  { immediate: true }
)

function formatCents(cents: number, currency: string = 'USD') {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currency || 'USD',
  }).format(cents / 100)
}

function validate(): boolean {
  Object.keys(errors).forEach((key) => delete errors[key])
  if (!form.fulfillment_status) errors.fulfillment_status = 'Status is required'
  return Object.keys(errors).length === 0
}

async function handleSubmit() {
  if (!validate() || !props.order) return

  submitting.value = true
  try {
    await api.patch(`/ecommerce/orders/${props.order.id}/fulfill`, {
      fulfillment_status: form.fulfillment_status,
      tracking_number: form.tracking_number || null,
      shipping_carrier: form.shipping_carrier || null,
      notes: form.notes || null,
    })

    const statusLabel = statusOptions.find((s) => s.value === form.fulfillment_status)?.label || form.fulfillment_status
    toast.success(`Order ${props.order.order_number} updated to "${statusLabel}"`)
    emit('fulfilled')
    emit('update:modelValue', false)
  } catch (e: any) {
    if (e?.errors) {
      Object.entries(e.errors).forEach(([key, msgs]: any) => {
        errors[key] = Array.isArray(msgs) ? msgs[0] : msgs
      })
    } else {
      toast.error(e?.response?.data?.message || 'Failed to update fulfillment status')
    }
  } finally {
    submitting.value = false
  }
}

const selectedStatusOption = computed(() => statusOptions.find((s) => s.value === form.fulfillment_status))
</script>

<template>
  <UiModal
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
    title="Update Fulfillment"
    size="lg"
  >
    <div v-if="order" class="space-y-5">
      <!-- Order Summary Banner -->
      <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-2">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-2">
            <Package class="w-4 h-4 text-slate-500" />
            <span class="text-sm font-bold text-slate-900">{{ order.order_number }}</span>
          </div>
          <span class="text-sm font-black text-slate-900">
            {{ formatCents(order.total_cents, order.currency) }}
          </span>
        </div>
        <div class="flex items-center justify-between text-xs text-slate-500">
          <span>{{ order.customer_name }} · {{ order.customer_email }}</span>
          <span class="font-mono">{{ order.channel?.name ?? 'Storefront' }}</span>
        </div>
      </div>

      <!-- Fulfillment Status Cards -->
      <div class="space-y-1.5">
        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
          Fulfillment Status <span class="text-red-500">*</span>
        </label>
        <div class="grid grid-cols-2 gap-2.5">
          <button
            v-for="option in statusOptions"
            :key="option.value"
            type="button"
            @click="form.fulfillment_status = option.value"
            :class="[
              'p-3 rounded-2xl border text-left transition-all relative flex items-start gap-3',
              form.fulfillment_status === option.value
                ? option.bg + ' ring-2 ring-offset-1 shadow-sm'
                : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/50',
            ]"
            :style="form.fulfillment_status === option.value ? `--tw-ring-color: var(--color-${option.value === 'shipped' ? 'blue' : option.value === 'fulfilled' ? 'emerald' : option.value === 'cancelled' ? 'red' : 'yellow'}-300)` : ''"
          >
            <component
              :is="option.icon"
              :class="['w-5 h-5 mt-0.5 shrink-0', form.fulfillment_status === option.value ? option.color : 'text-slate-400']"
            />
            <div>
              <div :class="['text-xs font-bold', form.fulfillment_status === option.value ? 'text-slate-900' : 'text-slate-700']">
                {{ option.label }}
              </div>
            </div>
          </button>
        </div>
        <p v-if="errors.fulfillment_status" class="text-[11px] font-semibold text-red-600 pl-1">{{ errors.fulfillment_status }}</p>
      </div>

      <!-- Shipping Details (shown when shipped or fulfilled) -->
      <div
        v-if="form.fulfillment_status === 'shipped' || form.fulfillment_status === 'fulfilled'"
        class="space-y-4 p-4 rounded-2xl border border-slate-200 bg-slate-50/50"
      >
        <div class="flex items-center space-x-2 text-xs font-bold text-slate-700 uppercase tracking-wider">
          <Truck class="w-4 h-4 text-blue-500" />
          <span>Shipping Details</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <!-- Shipping Carrier -->
          <div class="space-y-1.5">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Carrier</label>
            <select
              v-model="form.shipping_carrier"
              class="block w-full appearance-none rounded-xl border border-slate-200 bg-white text-sm py-2.5 pl-3.5 pr-10 font-medium text-slate-900 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all"
            >
              <option value="">Select carrier…</option>
              <option v-for="carrier in carrierOptions" :key="carrier" :value="carrier">
                {{ carrier }}
              </option>
            </select>
          </div>

          <!-- Tracking Number -->
          <UiInput
            v-model="form.tracking_number"
            label="Tracking Number"
            placeholder="e.g. 1Z999AA10123456784"
          />
        </div>
      </div>

      <!-- Notes -->
      <div class="space-y-1.5">
        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Notes</label>
        <textarea
          v-model="form.notes"
          rows="2"
          class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white text-sm p-3 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all shadow-2xs font-medium text-slate-900 placeholder:text-slate-400"
          placeholder="Optional internal notes about this fulfillment…"
        ></textarea>
      </div>

      <!-- Status Preview -->
      <div v-if="selectedStatusOption" class="flex items-center gap-2 p-3 rounded-xl bg-slate-50 border border-slate-100">
        <component :is="selectedStatusOption.icon" :class="['w-4 h-4', selectedStatusOption.color]" />
        <span class="text-xs text-slate-600">
          Order <span class="font-bold text-slate-900">{{ order.order_number }}</span> will be marked as
          <span class="font-bold" :class="selectedStatusOption.color">{{ selectedStatusOption.label }}</span>
        </span>
      </div>
    </div>

    <template #footer>
      <div class="flex items-center justify-end gap-2.5">
        <UiButton variant="ghost" @click="emit('update:modelValue', false)">Cancel</UiButton>
        <UiButton
          :loading="submitting"
          @click="handleSubmit"
        >
          <Truck class="w-4 h-4 mr-1.5" />
          Update Fulfillment
        </UiButton>
      </div>
    </template>
  </UiModal>
</template>
