<script setup lang="ts">
import { ref } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { crmApi } from '@/api/crm'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiModal from '@/components/ui/UiModal.vue'
import { Plus } from '@lucide/vue'
import { useToast } from '@/composables/useToast'

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
    toast.success('Contact created')
  },
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">CRM</h1>
        <p class="text-sm text-slate-500">Leads and customer contacts.</p>
      </div>
      <UiButton @click="isModalOpen = true"><Plus class="w-4 h-4 mr-2" /> New Contact</UiButton>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Name</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Company</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Email</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="isLoading"><td colspan="4" class="px-4 py-8 text-center text-slate-500">Loading…</td></tr>
          <tr v-else-if="!(contacts || []).length"><td colspan="4" class="px-4 py-8 text-center text-slate-500">No contacts yet.</td></tr>
          <tr v-for="c in contacts" :key="c.id" class="hover:bg-slate-50">
            <td class="px-4 py-3 font-medium">{{ c.name }}</td>
            <td class="px-4 py-3 text-sm">{{ c.company || '—' }}</td>
            <td class="px-4 py-3 text-sm">{{ c.email || '—' }}</td>
            <td class="px-4 py-3 text-sm capitalize">{{ c.status }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <UiModal v-model="isModalOpen" title="New Contact">
      <div class="space-y-4">
        <UiInput v-model="form.name" label="Name" required />
        <UiInput v-model="form.company" label="Company" />
        <UiInput v-model="form.email" label="Email" type="email" />
        <UiInput v-model="form.phone" label="Phone" />
        <UiSelect
          v-model="form.status"
          label="Status"
          :options="[{ label: 'Lead', value: 'lead' }, { label: 'Customer', value: 'customer' }]"
        />
        <div class="flex justify-end gap-2">
          <UiButton variant="outline" @click="isModalOpen = false">Cancel</UiButton>
          <UiButton :loading="createMutation.isPending.value" @click="createMutation.mutate()">Save</UiButton>
        </div>
      </div>
    </UiModal>
  </div>
</template>
