<script setup lang="ts">
import { ChevronDown } from '@lucide/vue'

interface Option {
  label: string
  value: string | number
}

interface Props {
  modelValue?: string | number
  label?: string
  options?: Option[]
  error?: string
  helpText?: string
  disabled?: boolean
  required?: boolean
  id?: string
  size?: 'sm' | 'md' | 'lg'
}

const emit = defineEmits(['update:modelValue'])

withDefaults(defineProps<Props>(), {
  size: 'md',
})

const handleChange = (event: Event) => {
  const target = event.target as HTMLSelectElement
  emit('update:modelValue', target.value)
}
</script>

<template>
  <div class="w-full space-y-1.5 font-sans text-left">
    <label v-if="label" :for="id" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
      {{ label }} <span v-if="required" class="text-red-500 font-bold">*</span>
    </label>
    <div class="relative flex items-center">
      <select
        :id="id"
        :value="modelValue"
        :disabled="disabled"
        :required="required"
        @change="handleChange"
        class="block w-full appearance-none rounded-xl border bg-slate-50/60 hover:bg-white focus:bg-white text-slate-900 shadow-2xs focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 font-medium disabled:bg-slate-100/70 disabled:text-slate-400 disabled:cursor-not-allowed transition-all duration-200 pr-10"
        :class="[
          size === 'sm' ? 'py-1.5 pl-2.5 text-xs' : size === 'lg' ? 'py-3 pl-4 text-base' : 'py-2.5 pl-3.5 text-sm',
          error ? 'border-red-300 ring-1 ring-red-500/20 focus:border-red-500 text-red-900' : 'border-slate-200/90',
        ]"
      >
        <slot>
          <option v-for="option in options" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </slot>
      </select>
      <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
        <ChevronDown class="h-4 w-4" />
      </div>
    </div>
    <p v-if="error" class="text-[11px] font-semibold text-red-600 pl-1">{{ error }}</p>
    <p v-if="helpText && !error" class="text-[11px] text-slate-400 pl-1">{{ helpText }}</p>
  </div>
</template>
