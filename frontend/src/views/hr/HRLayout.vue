<script setup lang="ts">
import { useRoute } from 'vue-router'
const route = useRoute()
</script>

<template>
  <div>
    <h3 class="text-2xl font-bold mb-6">Human Resources</h3>
    <router-view :key="route.fullPath" />
  </div>
</template>
