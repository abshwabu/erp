<script setup lang="ts">
import { ref, onMounted } from 'vue'
import AgingReportPanel from '../components/AgingReportPanel.vue'
import { accountingApi } from '@/api/accounting'

const rows = ref<any[]>([])

const loadAging = async () => {
  try {
    const res = await accountingApi.getARAging()
    rows.value = res.data || []
  } catch (err) {
    console.error('Failed to load AR aging:', err)
  }
}

onMounted(() => {
  loadAging()
})
</script>

<template>
  <AgingReportPanel
    title="Accounts Receivable Aging"
    subtitle="Track overdue customer balances and follow up with collection reminders."
    party-label="Customer"
    :rows="rows"
    :resolve-name="(row) => row.customerName ?? ''"
  />
</template>
