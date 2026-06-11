<script setup lang="ts">
interface Props {
  modelValue?: string | number
  label?: string
  placeholder?: string
  type?: string
  error?: string
  helpText?: string
  disabled?: boolean
  required?: boolean
  id?: string
}

const emit = defineEmits(['update:modelValue'])

withDefaults(defineProps<Props>(), {
  type: 'text',
})

const handleInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  emit('update:modelValue', target.value)
}
</script>

<template>
  <div class="w-full">
    <label v-if="label" :for="id" class="block text-sm font-medium text-slate-700 mb-1">
      {{ label }} <span v-if="required" class="text-red-500">*</span>
    </label>
    <div class="relative flex items-center">
      <div v-if="$slots.prefix" class="absolute left-3 text-slate-400">
        <slot name="prefix" />
      </div>
      <input
        :id="id"
        :type="type"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :required="required"
        @input="handleInput"
        class="block w-full rounded-md border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm disabled:bg-slate-50 disabled:text-slate-500 disabled:cursor-not-allowed transition-colors"
        :class="[
          error ? 'border-red-300 text-red-900 placeholder-red-300' : 'border-slate-300 text-slate-900',
          $slots.prefix ? 'pl-10' : 'pl-3',
          $slots.suffix ? 'pr-10' : 'pr-3',
        ]"
      />
      <div v-if="$slots.suffix" class="absolute right-3 text-slate-400">
        <slot name="suffix" />
      </div>
    </div>
    <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
    <p v-if="helpText && !error" class="mt-1 text-sm text-slate-500">{{ helpText }}</p>
  </div>
</template>
