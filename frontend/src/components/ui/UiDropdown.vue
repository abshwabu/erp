<script setup lang="ts">
import { 
  Menu, 
  MenuButton, 
  MenuItems, 
  MenuItem, 
  TransitionRoot 
} from '@headlessui/vue'
import { ChevronDown } from 'lucide-vue-next'

interface DropdownItem {
  label: string
  action?: () => void
  icon?: any
  to?: string | object
  variant?: 'default' | 'danger'
}

interface Props {
  label?: string
  items?: DropdownItem[]
  align?: 'left' | 'right'
}

withDefaults(defineProps<Props>(), {
  align: 'right',
})
</script>

<template>
  <Menu as="div" class="relative inline-block text-left">
    <div>
      <slot name="trigger">
        <MenuButton class="inline-flex w-full justify-center rounded-md bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm border border-slate-300 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
          {{ label }}
          <ChevronDown class="-mr-1 ml-2 h-5 w-5 text-slate-400" aria-hidden="true" />
        </MenuButton>
      </slot>
    </div>

    <TransitionRoot
      enter="transition ease-out duration-100"
      enter-from="transform opacity-0 scale-95"
      enter-to="transform opacity-100 scale-100"
      leave="transition ease-in duration-75"
      leave-from="transform opacity-100 scale-100"
      leave-to="transform opacity-0 scale-95"
    >
      <MenuItems
        class="absolute z-10 mt-2 w-56 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
        :class="[align === 'right' ? 'right-0' : 'left-0']"
      >
        <div class="py-1">
          <slot>
            <MenuItem v-for="item in items" :key="item.label" v-slot="{ active }">
              <component
                :is="item.to ? 'router-link' : 'button'"
                :to="item.to"
                @click="item.action?.()"
                class="flex w-full items-center px-4 py-2 text-sm"
                :class="[
                  active ? 'bg-slate-100 text-slate-900' : 'text-slate-700',
                  item.variant === 'danger' ? 'text-red-600' : '',
                ]"
              >
                <component :is="item.icon" v-if="item.icon" class="mr-3 h-5 w-5 text-slate-400" aria-hidden="true" />
                {{ item.label }}
              </component>
            </MenuItem>
          </slot>
        </div>
      </MenuItems>
    </TransitionRoot>
  </Menu>
</template>
