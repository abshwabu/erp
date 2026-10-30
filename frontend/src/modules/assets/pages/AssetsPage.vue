<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  Landmark,
  Plus,
  Calculator,
  Wrench,
  Laptop,
  Truck,
  Building,
  Search,
  Edit2,
  Trash2,
  CheckCircle2,
  AlertCircle,
  RefreshCw,
  Armchair,
} from '@lucide/vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import AssetModal from '../components/AssetModal.vue'
import { assetsApi } from '@/api/assets'
import type { Asset } from '@/types/assets'
import { formatCurrency, formatDate } from '@/utils/format'

const { t } = useI18n()

const assets = ref<Asset[]>([])
const selectedAsset = ref<Asset | null>(null)
const loading = ref(true)
const calculatingSchedule = ref(false)
const savingAsset = ref(false)
const deletingAsset = ref(false)

const searchQuery = ref('')
const selectedCategory = ref('all')
const selectedStatus = ref('all')

const isModalOpen = ref(false)
const editingAsset = ref<Asset | null>(null)

const isDeleteModalOpen = ref(false)
const assetToDelete = ref<Asset | null>(null)

const notification = ref<{ type: 'success' | 'error'; message: string } | null>(null)

const categoryIcons: Record<string, any> = {
  electronics: Laptop,
  vehicle: Truck,
  building: Building,
  equipment: Landmark,
  machinery: Wrench,
  furniture: Armchair,
}

const statusBadgeVariants: Record<string, 'success' | 'warning' | 'danger' | 'info' | 'default'> = {
  active: 'success',
  maintenance: 'warning',
  disposed: 'danger',
  retired: 'default',
}

const showNotification = (type: 'success' | 'error', message: string) => {
  notification.value = { type, message }
  setTimeout(() => {
    if (notification.value?.message === message) {
      notification.value = null
    }
  }, 4000)
}

async function fetchAssets() {
  loading.value = true
  try {
    const res = await assetsApi.getAssets()
    const data = res.data?.data?.data ?? res.data?.data ?? []
    assets.value = data

    // If an asset was previously selected, refresh it
    if (selectedAsset.value) {
      const refreshed = data.find((a: Asset) => a.id === selectedAsset.value?.id)
      if (refreshed) {
        await selectAsset(refreshed)
      } else if (data.length > 0) {
        await selectAsset(data[0])
      } else {
        selectedAsset.value = null
      }
    } else if (data.length > 0) {
      await selectAsset(data[0])
    }
  } catch (e) {
    console.error('Failed to load assets', e)
    showNotification('error', t('assets.notifications.error'))
  } finally {
    loading.value = false
  }
}

async function selectAsset(a: Asset) {
  try {
    const res = await assetsApi.getAsset(a.id)
    selectedAsset.value = res.data?.data ?? res.data
  } catch (e) {
    console.error('Failed to load asset details', e)
    selectedAsset.value = a
  }
}

async function generateSchedule(id: string) {
  calculatingSchedule.value = true
  try {
    const res = await assetsApi.generateDepreciationSchedule(id)
    selectedAsset.value = res.data?.data ?? res.data
    showNotification('success', t('assets.notifications.scheduleCalculated'))
  } catch (e) {
    console.error('Failed to generate depreciation schedule', e)
    showNotification('error', t('assets.notifications.error'))
  } finally {
    calculatingSchedule.value = false
  }
}

function openCreateModal() {
  editingAsset.value = null
  isModalOpen.value = true
}

function openEditModal(asset: Asset) {
  editingAsset.value = asset
  isModalOpen.value = true
}

async function handleSaveAsset(formData: any) {
  savingAsset.value = true
  try {
    if (formData.id) {
      await assetsApi.updateAsset(formData.id, formData)
      showNotification('success', t('assets.notifications.updated'))
    } else {
      await assetsApi.createAsset(formData)
      showNotification('success', t('assets.notifications.created'))
    }
    isModalOpen.value = false
    await fetchAssets()
  } catch (e) {
    console.error('Failed to save asset', e)
    showNotification('error', t('assets.notifications.error'))
  } finally {
    savingAsset.value = false
  }
}

function promptDelete(asset: Asset) {
  assetToDelete.value = asset
  isDeleteModalOpen.value = true
}

async function confirmDelete() {
  if (!assetToDelete.value) return
  deletingAsset.value = true
  try {
    await assetsApi.deleteAsset(assetToDelete.value.id)
    showNotification('success', t('assets.notifications.deleted'))
    isDeleteModalOpen.value = false
    if (selectedAsset.value?.id === assetToDelete.value.id) {
      selectedAsset.value = null
    }
    await fetchAssets()
  } catch (e) {
    console.error('Failed to delete asset', e)
    showNotification('error', t('assets.notifications.error'))
  } finally {
    deletingAsset.value = false
    assetToDelete.value = null
  }
}

const filteredAssets = computed(() => {
  return assets.value.filter((item) => {
    const matchesCategory = selectedCategory.value === 'all' || item.category === selectedCategory.value
    const matchesStatus = selectedStatus.value === 'all' || item.status === selectedStatus.value
    const query = searchQuery.value.trim().toLowerCase()
    const matchesSearch =
      !query ||
      item.name.toLowerCase().includes(query) ||
      item.asset_tag.toLowerCase().includes(query) ||
      (item.serial_number && item.serial_number.toLowerCase().includes(query))
    return matchesCategory && matchesStatus && matchesSearch
  })
})

const stats = computed(() => {
  const total = assets.value.length
  const totalValueCents = assets.value.reduce((acc, a) => acc + (a.purchase_cost_cents || 0), 0)
  const activeCount = assets.value.filter((a) => a.status === 'active').length
  const maintenanceCount = assets.value.filter((a) => a.status === 'maintenance').length

  return {
    total,
    totalValue: totalValueCents / 100,
    activeCount,
    maintenanceCount,
  }
})

const categoryFilterOptions = computed(() => [
  { label: t('assets.filters.allCategories'), value: 'all' },
  { label: t('assets.categories.equipment'), value: 'equipment' },
  { label: t('assets.categories.machinery'), value: 'machinery' },
  { label: t('assets.categories.vehicle'), value: 'vehicle' },
  { label: t('assets.categories.electronics'), value: 'electronics' },
  { label: t('assets.categories.furniture'), value: 'furniture' },
  { label: t('assets.categories.building'), value: 'building' },
])

const statusFilterOptions = computed(() => [
  { label: t('assets.filters.allStatuses'), value: 'all' },
  { label: t('assets.statuses.active'), value: 'active' },
  { label: t('assets.statuses.maintenance'), value: 'maintenance' },
  { label: t('assets.statuses.disposed'), value: 'disposed' },
  { label: t('assets.statuses.retired'), value: 'retired' },
])

onMounted(fetchAssets)
</script>

<template>
  <div class="p-4 sm:p-6 space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="flex items-center space-x-3">
        <div class="p-2.5 bg-primary-50 rounded-2xl border border-primary-100 text-primary-700">
          <Landmark class="w-6 h-6 shrink-0" />
        </div>
        <div>
          <h1 class="text-xl sm:text-2xl font-bold text-slate-900">{{ t('assets.title') }}</h1>
          <p class="text-xs sm:text-sm text-slate-500">{{ t('assets.subtitle') }}</p>
        </div>
      </div>

      <UiButton class="w-full sm:w-auto justify-center" @click="openCreateModal">
        <Plus class="mr-2 h-4 w-4 shrink-0" /> {{ t('assets.newAsset') }}
      </UiButton>
    </div>

    <!-- Notification Toast -->
    <div
      v-if="notification"
      class="flex items-center gap-2.5 rounded-2xl border p-4 text-xs sm:text-sm font-medium transition-all"
      :class="
        notification.type === 'success'
          ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
          : 'border-rose-200 bg-rose-50 text-rose-800'
      "
    >
      <CheckCircle2 v-if="notification.type === 'success'" class="h-4 w-4 shrink-0 text-emerald-600" />
      <AlertCircle v-else class="h-4 w-4 shrink-0 text-rose-600" />
      <span>{{ notification.message }}</span>
    </div>

    <!-- KPI Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
      <div class="rounded-3xl border border-slate-200 bg-white p-4 sm:p-5 shadow-sm">
        <p class="text-xs sm:text-sm font-medium text-slate-500">{{ t('assets.stats.totalAssets') }}</p>
        <p class="mt-1.5 sm:mt-2 text-xl sm:text-2xl font-bold text-slate-900">{{ stats.total }}</p>
      </div>

      <div class="rounded-3xl border border-slate-200 bg-white p-4 sm:p-5 shadow-sm">
        <p class="text-xs sm:text-sm font-medium text-slate-500">{{ t('assets.stats.totalValue') }}</p>
        <p class="mt-1.5 sm:mt-2 text-xl sm:text-2xl font-bold text-slate-900">
          {{ formatCurrency(stats.totalValue) }}
        </p>
      </div>

      <div class="rounded-3xl border border-slate-200 bg-white p-4 sm:p-5 shadow-sm">
        <p class="text-xs sm:text-sm font-medium text-slate-500">{{ t('assets.stats.activeAssets') }}</p>
        <p class="mt-1.5 sm:mt-2 text-xl sm:text-2xl font-bold text-emerald-600">{{ stats.activeCount }}</p>
      </div>

      <div class="rounded-3xl border border-slate-200 bg-white p-4 sm:p-5 shadow-sm">
        <p class="text-xs sm:text-sm font-medium text-slate-500">{{ t('assets.stats.maintenance') }}</p>
        <p class="mt-1.5 sm:mt-2 text-xl sm:text-2xl font-bold text-amber-600">{{ stats.maintenanceCount }}</p>
      </div>
    </div>

    <!-- Filters Bar -->
    <div class="rounded-3xl border border-slate-200 bg-white p-4 sm:p-5 shadow-sm space-y-3">
      <div class="grid gap-3 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
        <div class="lg:col-span-2">
          <UiInput
            v-model="searchQuery"
            :placeholder="t('assets.filters.searchPlaceholder')"
          >
            <template #prefix>
              <Search class="h-4 w-4 text-slate-400" />
            </template>
          </UiInput>
        </div>

        <UiSelect
          v-model="selectedCategory"
          :options="categoryFilterOptions"
        />

        <UiSelect
          v-model="selectedStatus"
          :options="statusFilterOptions"
        />
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-16 text-slate-500 bg-white rounded-3xl border border-slate-200 shadow-sm">
      <RefreshCw class="w-8 h-8 text-primary-500 mx-auto animate-spin mb-3" />
      <p class="font-medium text-slate-700">{{ t('assets.loading') }}</p>
    </div>

    <!-- Empty State -->
    <div
      v-else-if="assets.length === 0"
      class="text-center py-16 bg-white rounded-3xl border border-slate-200 shadow-sm p-6"
    >
      <Landmark class="w-12 h-12 text-slate-300 mx-auto mb-3" />
      <h3 class="text-base font-semibold text-slate-900">{{ t('assets.emptyState.title') }}</h3>
      <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-sm mx-auto">
        {{ t('assets.emptyState.subtitle') }}
      </p>
      <UiButton class="mt-4 justify-center" @click="openCreateModal">
        <Plus class="mr-2 h-4 w-4 shrink-0" /> {{ t('assets.emptyState.action') }}
      </UiButton>
    </div>

    <!-- Main Content Grid -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Asset Cards List -->
      <div class="lg:col-span-1 space-y-3">
        <div
          v-if="filteredAssets.length === 0"
          class="bg-white rounded-3xl border border-slate-200 p-8 text-center text-slate-500 text-sm"
        >
          No assets match your search criteria.
        </div>

        <div
          v-for="item in filteredAssets"
          :key="item.id"
          class="p-4 rounded-3xl border cursor-pointer transition-all hover:shadow-md"
          :class="[
            selectedAsset?.id === item.id
              ? 'bg-primary-50/50 border-primary-500 ring-2 ring-primary-500/20'
              : 'bg-white border-slate-200 hover:border-slate-300'
          ]"
          @click="selectAsset(item)"
        >
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-mono font-semibold text-primary-700 bg-primary-100/60 px-2 py-0.5 rounded-md">
              {{ item.asset_tag }}
            </span>
            <UiBadge :variant="statusBadgeVariants[item.status] || 'default'">
              {{ t(`assets.statuses.${item.status}`) || item.status }}
            </UiBadge>
          </div>

          <div class="flex items-center space-x-2.5">
            <div class="p-1.5 rounded-xl bg-slate-100 text-slate-600 shrink-0">
              <component :is="categoryIcons[item.category] ?? Landmark" class="w-4 h-4" />
            </div>
            <h3 class="text-sm font-semibold text-slate-900 truncate">{{ item.name }}</h3>
          </div>

          <div class="flex items-center justify-between text-xs text-slate-500 mt-3 pt-2.5 border-t border-slate-100">
            <span class="font-medium">{{ t(`assets.categories.${item.category}`) || item.category }}</span>
            <span class="font-bold text-slate-900">{{ formatCurrency((item.purchase_cost_cents || 0) / 100) }}</span>
          </div>
        </div>
      </div>

      <!-- Asset Detail & Depreciation View -->
      <div class="lg:col-span-2">
        <div
          v-if="!selectedAsset"
          class="bg-white rounded-3xl border border-slate-200 p-12 text-center text-slate-400 shadow-sm"
        >
          {{ t('assets.details.selectAssetPrompt') }}
        </div>

        <div v-else class="bg-white rounded-3xl border border-slate-200 p-4 sm:p-6 space-y-6 shadow-sm">
          <!-- Detail Header -->
          <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 pb-4 border-b border-slate-100">
            <div class="space-y-1">
              <div class="flex items-center gap-2">
                <span class="text-xs font-mono font-semibold text-primary-700 bg-primary-100/60 px-2.5 py-0.5 rounded-md">
                  {{ selectedAsset.asset_tag }}
                </span>
                <UiBadge :variant="statusBadgeVariants[selectedAsset.status] || 'default'">
                  {{ t(`assets.statuses.${selectedAsset.status}`) || selectedAsset.status }}
                </UiBadge>
              </div>
              <h2 class="text-lg sm:text-xl font-bold text-slate-900">{{ selectedAsset.name }}</h2>
              <p class="text-xs text-slate-500 font-medium">
                {{ t('assets.details.categoryLabel', { category: t(`assets.categories.${selectedAsset.category}`) || selectedAsset.category }) }}
              </p>
            </div>

            <!-- Action buttons -->
            <div class="flex items-center gap-2">
              <UiButton variant="outline" size="sm" @click="openEditModal(selectedAsset)">
                <Edit2 class="w-3.5 h-3.5 mr-1.5 shrink-0" /> {{ t('assets.editAsset') }}
              </UiButton>
              <UiButton variant="danger" size="sm" @click="promptDelete(selectedAsset)">
                <Trash2 class="w-3.5 h-3.5 mr-1.5 shrink-0" /> {{ t('assets.deleteAsset') }}
              </UiButton>
            </div>
          </div>

          <!-- Key Metrics / Specifications -->
          <div>
            <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-3">
              {{ t('assets.details.specifications') }}
            </h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4 p-4 bg-slate-50 rounded-2xl border border-slate-100">
              <div>
                <span class="text-xs text-slate-500 block">{{ t('assets.details.purchaseCost') }}</span>
                <span class="text-sm font-bold text-slate-900">
                  {{ formatCurrency((selectedAsset.purchase_cost_cents || 0) / 100) }}
                </span>
              </div>
              <div>
                <span class="text-xs text-slate-500 block">{{ t('assets.details.salvageValue') }}</span>
                <span class="text-sm font-semibold text-slate-800">
                  {{ formatCurrency((selectedAsset.salvage_value_cents || 0) / 100) }}
                </span>
              </div>
              <div>
                <span class="text-xs text-slate-500 block">{{ t('assets.details.usefulLife') }}</span>
                <span class="text-sm font-semibold text-slate-800">
                  {{ t('assets.details.usefulLifeYears', { years: selectedAsset.useful_life_years }) }}
                </span>
              </div>
              <div>
                <span class="text-xs text-slate-500 block">{{ t('assets.details.method') }}</span>
                <span class="text-sm font-medium text-slate-800">
                  {{ t(`assets.methods.${selectedAsset.depreciation_method}`) || selectedAsset.depreciation_method }}
                </span>
              </div>
              <div>
                <span class="text-xs text-slate-500 block">{{ t('assets.details.serialNumber') }}</span>
                <span class="text-sm font-medium text-slate-800">
                  {{ selectedAsset.serial_number || t('assets.details.notSpecified') }}
                </span>
              </div>
              <div>
                <span class="text-xs text-slate-500 block">{{ t('assets.details.purchaseDate') }}</span>
                <span class="text-sm font-medium text-slate-800">
                  {{ selectedAsset.purchase_date ? formatDate(selectedAsset.purchase_date) : t('assets.details.notSpecified') }}
                </span>
              </div>
            </div>
          </div>

          <!-- Notes Card -->
          <div v-if="selectedAsset.notes" class="rounded-2xl border border-slate-100 bg-slate-50 p-4 space-y-1">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">
              {{ t('assets.details.notes') }}
            </span>
            <p class="text-xs sm:text-sm text-slate-700 leading-relaxed whitespace-pre-line">
              {{ selectedAsset.notes }}
            </p>
          </div>

          <!-- Depreciation Schedule Section -->
          <div class="space-y-3 pt-2">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
              <div>
                <h3 class="text-sm font-bold text-slate-900">
                  {{ t('assets.details.depreciationSchedule') }}
                </h3>
              </div>
              <UiButton
                variant="outline"
                size="sm"
                :loading="calculatingSchedule"
                @click="generateSchedule(selectedAsset.id)"
              >
                <Calculator class="w-3.5 h-3.5 mr-1.5 shrink-0" />
                {{ calculatingSchedule ? t('assets.details.calculating') : t('assets.details.calculateSchedule') }}
              </UiButton>
            </div>

            <div
              v-if="!selectedAsset.depreciations || selectedAsset.depreciations.length === 0"
              class="text-xs sm:text-sm text-slate-500 py-8 text-center bg-slate-50 rounded-2xl border border-slate-100 px-4"
            >
              {{ t('assets.details.noSchedule') }}
            </div>

            <div v-else class="overflow-x-auto rounded-2xl border border-slate-200">
              <table class="min-w-full divide-y divide-slate-200 text-xs sm:text-sm">
                <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider font-semibold">
                  <tr>
                    <th class="px-4 py-3 text-left whitespace-nowrap">{{ t('assets.details.table.fiscalYear') }}</th>
                    <th class="px-4 py-3 text-right whitespace-nowrap">{{ t('assets.details.table.depreciation') }}</th>
                    <th class="px-4 py-3 text-right whitespace-nowrap">{{ t('assets.details.table.accumulated') }}</th>
                    <th class="px-4 py-3 text-right whitespace-nowrap">{{ t('assets.details.table.bookValue') }}</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                  <tr
                    v-for="d in selectedAsset.depreciations"
                    :key="d.id"
                    class="hover:bg-slate-50 transition-colors"
                  >
                    <td class="px-4 py-3 font-semibold text-slate-900 whitespace-nowrap">{{ d.fiscal_year }}</td>
                    <td class="px-4 py-3 text-right text-slate-700 whitespace-nowrap font-medium">
                      {{ formatCurrency((d.depreciation_amount_cents || 0) / 100) }}
                    </td>
                    <td class="px-4 py-3 text-right text-slate-500 whitespace-nowrap">
                      {{ formatCurrency((d.accumulated_depreciation_cents || 0) / 100) }}
                    </td>
                    <td class="px-4 py-3 text-right font-bold text-slate-900 whitespace-nowrap">
                      {{ formatCurrency((d.book_value_cents || 0) / 100) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create / Edit Modal -->
    <AssetModal
      v-model="isModalOpen"
      :asset="editingAsset"
      :saving="savingAsset"
      @save="handleSaveAsset"
    />

    <!-- Delete Confirmation Modal -->
    <UiModal
      v-model="isDeleteModalOpen"
      :title="t('assets.deleteModal.title')"
      size="md"
    >
      <div class="space-y-3">
        <p class="text-sm text-slate-700">
          {{ t('assets.deleteModal.confirmText', { tag: assetToDelete?.asset_tag, name: assetToDelete?.name }) }}
        </p>
        <p class="text-xs text-rose-600 bg-rose-50 border border-rose-100 p-3 rounded-xl font-medium leading-relaxed">
          {{ t('assets.deleteModal.warning') }}
        </p>
      </div>

      <template #footer>
        <div class="flex items-center justify-end gap-2 w-full">
          <UiButton variant="outline" :disabled="deletingAsset" @click="isDeleteModalOpen = false">
            {{ t('assets.deleteModal.cancel') }}
          </UiButton>
          <UiButton variant="danger" :loading="deletingAsset" @click="confirmDelete">
            {{ t('assets.deleteModal.confirm') }}
          </UiButton>
        </div>
      </template>
    </UiModal>
  </div>
</template>
