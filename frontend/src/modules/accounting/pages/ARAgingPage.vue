<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import AgingReportPanel from '../components/AgingReportPanel.vue'
import { accountingApi } from '@/api/accounting'

const { t } = useI18n()
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
    :title="t('accounting.aging.arTitle')"
    :subtitle="t('accounting.aging.arSubtitle')"
    :party-label="t('accounting.aging.customer')"
    :rows="rows"
    :resolve-name="(row) => row.customerName ?? ''"
  />
</template>
