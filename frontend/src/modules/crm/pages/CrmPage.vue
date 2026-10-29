<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { crmApi } from '@/api/crm'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiModal from '@/components/ui/UiModal.vue'
import { Plus } from '@lucide/vue'
import { useToast } from '@/composables/useToast'

const { t } = useI18n()
const queryClient = useQueryClient()
const toast = useToast()
const isModalOpen = ref(false)
const form = ref({ name: '', email: '', phone: '', company: '', status: 'lead' as 'lead' | 'customer' })

const { data: contacts, isLoading } = useQuery({
  queryKey: ['crm', 'contacts'],
  queryFn: () => crmApi.getContacts().then(r => r.data.data),
})

const createMutation = useMutation({
  mutationFn: () => crmApi.createContact(form.value),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['crm', 'contacts'] })
    isModalOpen.value = false
    form.value = { name: '', email: '', phone: '', company: '', status: 'lead' }
    toast.success(t('crm.contacts.messages.created'))
  },
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">{{ t('crm.common.title') }}</h1>
        <p class="text-sm text-slate-500">{{ t('crm.common.subtitle') }}</p>
      </div>
      <UiButton @click="isModalOpen = true">
        <Plus class="w-4 h-4 mr-2" /> {{ t('crm.contacts.addContact') }}
      </UiButton>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">{{ t('crm.contacts.modal.fullName') }}</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">{{ t('crm.contacts.modal.companyName') }}</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">{{ t('crm.contacts.modal.email') }}</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">{{ t('crm.contacts.table.status') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="isLoading"><td colspan="4" class="px-4 py-8 text-center text-slate-500">{{ t('crm.common.loading') }}</td></tr>
          <tr v-else-if="!(contacts || []).length"><td colspan="4" class="px-4 py-8 text-center text-slate-500">{{ t('crm.contacts.table.emptySearch') }}</td></tr>
          <tr v-for="c in contacts" :key="c.id" class="hover:bg-slate-50">
            <td class="px-4 py-3 font-medium">{{ c.name }}</td>
            <td class="px-4 py-3 text-sm">{{ c.company || '—' }}</td>
            <td class="px-4 py-3 text-sm">{{ c.email || '—' }}</td>
            <td class="px-4 py-3 text-sm capitalize">{{ t(`crm.contacts.statuses.${c.status}`, c.status) }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <UiModal v-model="isModalOpen" :title="t('crm.contacts.modal.createTitle')">
      <div class="space-y-4">
        <UiInput v-model="form.name" :label="t('crm.contacts.modal.fullName')" required />
        <UiInput v-model="form.company" :label="t('crm.contacts.modal.companyName')" />
        <UiInput v-model="form.email" :label="t('crm.contacts.modal.email')" type="email" />
        <UiInput v-model="form.phone" :label="t('crm.contacts.modal.phone')" />
        <UiSelect
          v-model="form.status"
          :label="t('crm.contacts.modal.relationshipStatus')"
          :options="[{ label: t('crm.contacts.statuses.lead'), value: 'lead' }, { label: t('crm.contacts.statuses.customer'), value: 'customer' }]"
        />
        <div class="flex justify-end gap-2">
          <UiButton variant="outline" @click="isModalOpen = false">{{ t('crm.common.cancel') }}</UiButton>
          <UiButton :loading="createMutation.isPending.value" @click="createMutation.mutate()">{{ t('crm.common.save') }}</UiButton>
        </div>
      </div>
    </UiModal>
  </div>
</template>
