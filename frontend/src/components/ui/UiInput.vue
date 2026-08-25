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
  size?: 'sm' | 'md' | 'lg'
}

const emit = defineEmits(['update:modelValue'])

withDefaults(defineProps<Props>(), {
  type: 'text',
  size: 'md',
})

const handleInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.type === 'number') {
    const val = target.value === '' ? '' : Number(target.value)
    emit('update:modelValue', val)
  } else {
    emit('update:modelValue', target.value)
  }
}
</script>

<template>
  <div class="w-full space-y-1.5 font-sans text-left">
    <label v-if="label" :for="id" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
      {{ label }} <span v-if="required" class="text-red-500 font-bold">*</span>
    </label>
    <div class="relative flex items-center group">
      <div v-if="$slots.prefix" class="absolute left-3.5 text-slate-400 pointer-events-none group-focus-within:text-blue-500 transition-colors">
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
        class="block w-full rounded-xl border bg-slate-50/60 hover:bg-white focus:bg-white text-sm font-medium text-slate-900 placeholder:text-slate-400 placeholder:font-normal shadow-2xs focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 disabled:bg-slate-100/70 disabled:text-slate-400 disabled:cursor-not-allowed transition-all duration-200"
        :class="[
          size === 'sm' ? 'py-1.5 text-xs' : size === 'lg' ? 'py-3 text-base' : 'py-2.5 text-sm',
          error ? 'border-red-300 ring-1 ring-red-500/20 focus:border-red-500 focus:ring-red-500/10 text-red-900' : 'border-slate-200/90',
          $slots.prefix ? 'pl-10' : 'pl-3.5',
          $slots.suffix ? 'pr-10' : 'pr-3.5',
        ]"
      />
      <div v-if="$slots.suffix" class="absolute right-3.5 text-slate-400 pointer-events-none group-focus-within:text-blue-500 transition-colors">
        <slot name="suffix" />
      </div>
    </div>
    <p v-if="error" class="text-[11px] font-semibold text-red-600 pl-1">{{ error }}</p>
    <p v-if="helpText && !error" class="text-[11px] text-slate-400 pl-1">{{ helpText }}</p>
  </div>
</template>
