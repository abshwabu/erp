<script setup lang="ts">
import AgingReportPanel from '../components/AgingReportPanel.vue'
import { sampleAPAgingRows } from '../data'
</script>

<template>
  <AgingReportPanel
    title="Accounts Payable Aging"
    subtitle="Monitor supplier balances by aging bucket and plan upcoming payment runs."
    party-label="Supplier"
    :rows="sampleAPAgingRows"
    :resolve-name="(row) => row.supplierName ?? ''"
  />
</template>
