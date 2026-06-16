<script setup lang="ts">
import { type PropType } from 'vue'

const props = defineProps({
  node: { type: Object as PropType<any>, required: true }
})

const emit = defineEmits(['click'])
</script>

<template>
  <div class="flex flex-col items-center relative">
    <div 
      @click="emit('click', node.id)"
      class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm hover:border-primary-500 hover:shadow-md transition-all cursor-pointer w-48 text-center z-10"
    >
      <div class="h-12 w-12 rounded-full bg-slate-100 mx-auto mb-3 flex items-center justify-center font-bold text-slate-400 overflow-hidden">
        <img v-if="node.avatar_url" :src="node.avatar_url" class="h-full w-full object-cover" />
        <span v-else>{{ node.first_name[0] }}{{ node.last_name[0] }}</span>
      </div>
      <div class="font-bold text-slate-900 text-sm truncate">{{ node.first_name }} {{ node.last_name }}</div>
      <div class="text-xs text-slate-500 truncate">{{ node.position?.title || 'No Title' }}</div>
      <div class="mt-2 h-1 w-12 bg-primary-500 mx-auto rounded-full"></div>
    </div>

    <div v-if="node.children?.length" class="relative pt-12">
      <div class="absolute top-0 left-1/2 -translate-x-1/2 w-px h-12 bg-slate-200"></div>
      
      <div class="flex gap-12 relative">
        <!-- Horizontal Connector -->
        <div v-if="node.children.length > 1" class="absolute top-0 left-1/2 -translate-x-1/2 h-px bg-slate-200"
          :style="{ width: 'calc(100% - 48px)' }"></div>
        
        <OrgNode 
          v-for="child in node.children" 
          :key="child.id" 
          :node="child" 
          @click="emit('click', $event)"
        />
      </div>
    </div>
  </div>
</template>

<script lang="ts">
export default {
  name: 'OrgNode'
}
</script>
