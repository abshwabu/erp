<script setup lang="ts">
import { ref, onMounted } from 'vue'
import AgingReportPanel from '../components/AgingReportPanel.vue'
import { accountingApi } from '@/api/accounting'

const rows = ref<any[]>([])

const loadAging = async () => {
  try {
    const res = await accountingApi.getAPAging()
    rows.value = res.data || []
  } catch (err) {
    console.error('Failed to load AP aging:', err)
  }
}

onMounted(() => {
  loadAging()
})
</script>

<template>
  <AgingReportPanel
    title="Accounts Payable Aging"
    subtitle="Monitor supplier balances by aging bucket and plan upcoming payment runs."
    party-label="Supplier"
    :rows="rows"
    :resolve-name="(row) => row.supplierName ?? ''"
  />
</template>
