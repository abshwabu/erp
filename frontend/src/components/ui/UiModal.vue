<script setup lang="ts">
import { 
  Dialog, 
  DialogPanel, 
  DialogTitle, 
  TransitionRoot, 
  TransitionChild 
} from '@headlessui/vue'
import { X } from '@lucide/vue'

interface Props {
  modelValue: boolean
  title?: string
  size?: 'sm' | 'md' | 'lg' | 'xl' | '2xl' | 'full'
}

const props = withDefaults(defineProps<Props>(), {
  size: 'md',
})

const emit = defineEmits(['update:modelValue', 'close'])

const close = () => {
  emit('update:modelValue', false)
  emit('close')
}
</script>

<template>
  <TransitionRoot as="template" :show="modelValue">
    <Dialog as="div" class="relative z-50" @close="close">
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity" />
      </TransitionChild>

      <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <DialogPanel
              class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 w-full"
              :class="[
                {
                  'sm:max-w-sm': size === 'sm',
                  'sm:max-w-md': size === 'md',
                  'sm:max-w-lg': size === 'lg',
                  'sm:max-w-xl': size === 'xl',
                  'sm:max-w-2xl': size === '2xl',
                  'sm:max-w-7xl': size === 'full',
                },
              ]"
            >
              <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                <div class="flex items-center justify-between mb-4">
                  <DialogTitle v-if="title" as="h3" class="text-lg font-medium leading-6 text-slate-900">
                    {{ title }}
                  </DialogTitle>
                  <button
                    type="button"
                    class="rounded-md bg-white text-slate-400 hover:text-slate-500 focus:outline-none"
                    @click="close"
                  >
                    <span class="sr-only">Close</span>
                    <X class="h-6 w-6" aria-hidden="true" />
                  </button>
                </div>
                <div>
                  <slot />
                </div>
              </div>
              <div v-if="$slots.footer" class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                <slot name="footer" />
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>
