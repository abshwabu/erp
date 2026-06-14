<script setup lang="ts">
const props = defineProps(['modelValue', 'orderData'])
const emit = defineEmits(['update:modelValue'])
</script>

<template>
  <div v-if="modelValue" class="fixed inset-0 bg-black/50 flex items-center justify-center">
    <div class="bg-slate-800 p-6 rounded w-96">
      <h2 class="text-xl font-bold mb-4">Receipt</h2>
      <button @click="emit('update:modelValue', false)" class="w-full bg-slate-700 py-2 rounded">Close</button>
    </div>
  </div>
</template>
