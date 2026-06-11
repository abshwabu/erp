<script setup lang="ts">
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
}

const emit = defineEmits(['update:modelValue'])

defineProps<Props>()

const handleChange = (event: Event) => {
  const target = event.target as HTMLSelectElement
  emit('update:modelValue', target.value)
}
</script>

<template>
  <div class="w-full">
    <label v-if="label" :for="id" class="block text-sm font-medium text-slate-700 mb-1">
      {{ label }} <span v-if="required" class="text-red-500">*</span>
    </label>
    <select
      :id="id"
      :value="modelValue"
      :disabled="disabled"
      :required="required"
      @change="handleChange"
      class="block w-full rounded-md border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm transition-colors"
      :class="[
        error ? 'border-red-300 text-red-900' : 'border-slate-300 text-slate-900',
        disabled ? 'bg-slate-50 text-slate-500 cursor-not-allowed' : 'bg-white',
      ]"
    >
      <slot>
        <option v-for="option in options" :key="option.value" :value="option.value">
          {{ option.label }}
        </option>
      </slot>
    </select>
    <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
    <p v-if="helpText && !error" class="mt-1 text-sm text-slate-500">{{ helpText }}</p>
  </div>
</template>
