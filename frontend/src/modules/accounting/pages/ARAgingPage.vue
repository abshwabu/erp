<script setup lang="ts">
import AgingReportPanel from '../components/AgingReportPanel.vue'
import { sampleARAgingRows } from '../data'

</script>

<template>
  <AgingReportPanel
    title="Accounts Receivable Aging"
    subtitle="Track overdue customer balances and follow up with collection reminders."
    party-label="Customer"
    :rows="sampleARAgingRows"
    :resolve-name="(row) => row.customerName ?? ''"
  />
</template>
