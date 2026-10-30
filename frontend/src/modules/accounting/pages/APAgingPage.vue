<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import AgingReportPanel from '../components/AgingReportPanel.vue'
import { accountingApi } from '@/api/accounting'

const { t } = useI18n()
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
    :title="t('accounting.aging.apTitle')"
    :subtitle="t('accounting.aging.apSubtitle')"
    :party-label="t('accounting.aging.supplier')"
    :rows="rows"
    :resolve-name="(row) => row.supplierName ?? ''"
  />
</template>
