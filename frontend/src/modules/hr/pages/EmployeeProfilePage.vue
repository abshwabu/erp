<script setup lang="ts">
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import { useQuery } from '@tanstack/vue-query'
import { hrApi } from '@/api/hr'
import UiBadge from '@/components/ui/UiBadge.vue'

const route = useRoute()
const employeeId = route.params.id as string

const { data: employee, isLoading } = useQuery({
  queryKey: ['hr', 'employees', employeeId],
  queryFn: () => hrApi.getEmployee(employeeId).then(res => res.data)
})

const activeTab = ref('profile')
const tabs = ['profile', 'leave', 'attendance', 'documents']
</script>

<template>
  <div v-if="isLoading">Loading...</div>
  <div v-else-if="employee" class="space-y-6">
    <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200">
      <h1 class="text-2xl font-bold text-slate-900">{{ employee.first_name }} {{ employee.last_name }}</h1>
      <p class="text-slate-500">{{ employee.position?.title }}</p>
    </div>

    <div class="flex border-b border-slate-200">
      <button v-for="tab in tabs" :key="tab" @click="activeTab = tab"
        :class="['px-4 py-2 capitalize', activeTab === tab ? 'border-b-2 border-primary-600 font-medium' : 'text-slate-500']">
        {{ tab }}
      </button>
    </div>

    <div v-if="activeTab === 'profile'">
      <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200">
        <h2 class="font-semibold mb-4">Personal Information</h2>
        <div class="grid grid-cols-2 gap-4">
          <div>Email: {{ employee.email }}</div>
          <div>Phone: {{ employee.phone }}</div>
        </div>
      </div>
    </div>
    <!-- Other tabs placeholders -->
    <div v-else class="p-6 bg-slate-50 rounded-lg text-slate-500">
      {{ activeTab }} content goes here.
    </div>
  </div>
</template>
