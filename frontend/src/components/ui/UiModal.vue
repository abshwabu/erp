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
    <Dialog as="div" class="relative z-50 font-sans" @close="close">
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs transition-opacity" />
      </TransitionChild>

      <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-3 sm:p-6 text-center">
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
              class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl ring-1 ring-black/5 transition-all w-full my-6 flex flex-col max-h-[90vh]"
              :class="[
                {
                  'sm:max-w-sm': size === 'sm',
                  'sm:max-w-md': size === 'md',
                  'sm:max-w-lg': size === 'lg',
                  'sm:max-w-xl': size === 'xl',
                  'sm:max-w-2xl': size === '2xl',
                  'sm:max-w-5xl': size === 'full',
                },
              ]"
            >
              <!-- Modal Header -->
              <div class="px-6 pt-5 pb-4 border-b border-slate-100 flex items-center justify-between shrink-0 bg-white">
                <DialogTitle v-if="title" as="h3" class="text-lg font-black text-slate-900 tracking-tight">
                  {{ title }}
                </DialogTitle>
                <button
                  type="button"
                  class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors focus:outline-none"
                  @click="close"
                >
                  <span class="sr-only">Close</span>
                  <X class="h-4 w-4" />
                </button>
              </div>

              <!-- Modal Body -->
              <div class="px-6 py-4 overflow-y-auto flex-1 custom-scrollbar">
                <slot />
              </div>

              <!-- Modal Footer -->
              <div v-if="$slots.footer" class="bg-slate-50/80 px-6 py-3.5 border-t border-slate-100 shrink-0">
                <slot name="footer" />
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>
