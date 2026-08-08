<script setup lang="ts">
import { computed, ref } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { rolesApi, type Role } from '@/api/roles'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiModal from '@/components/ui/UiModal.vue'
import { Plus, Shield, Pencil } from '@lucide/vue'
import { useToast } from '@/composables/useToast'

const queryClient = useQueryClient()
const toast = useToast()

const isModalOpen = ref(false)
const editingRole = ref<Role | null>(null)
const roleName = ref('')
const selectedPermissions = ref<string[]>([])

const { data: roles, isLoading } = useQuery({
  queryKey: ['core', 'roles'],
  queryFn: () => rolesApi.list().then(res => res.data.data),
})

const allPermissions = computed(() => {
  const set = new Set<string>()
  ;(roles.value || []).forEach(role => role.permissions.forEach(p => set.add(p)))
  // Always include common module permissions even if not yet assigned
  ;[
    'core.roles.view', 'core.roles.create', 'core.roles.edit', 'core.roles.delete',
    'inventory.products.view', 'inventory.products.create', 'inventory.stock.view',
    'pos.sessions.open', 'pos.sessions.close',
    'hr.employees.view', 'hr.leave.view', 'hr.attendance.view',
    'accounting.journals.view', 'accounting.reports.view',
    'sales.invoices.create', 'warehouse.receive', 'warehouse.pick',
    'procurement.purchase_orders.view', 'procurement.purchase_orders.create', 'procurement.suppliers.manage',
    'payroll.runs.view', 'payroll.runs.process', 'payroll.payslips.view',
    'crm.contacts.view', 'crm.contacts.manage',
    'core.settings.view', 'core.settings.edit', 'accounting.reports.view',
  ].forEach(p => set.add(p))
  return Array.from(set).sort()
})

const saveMutation = useMutation({
  mutationFn: async () => {
    if (editingRole.value) {
      await rolesApi.update(editingRole.value.id, roleName.value)
      await rolesApi.syncPermissions(editingRole.value.id, selectedPermissions.value)
    } else {
      const created = await rolesApi.create(roleName.value)
      const id = created.data.data?.id ?? created.data.id
      if (id && selectedPermissions.value.length) {
        await rolesApi.syncPermissions(id, selectedPermissions.value)
      }
    }
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['core', 'roles'] })
    isModalOpen.value = false
    toast.success(editingRole.value ? 'Role updated' : 'Role created')
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || 'Failed to save role')
  },
})

function openCreate() {
  editingRole.value = null
  roleName.value = ''
  selectedPermissions.value = []
  isModalOpen.value = true
}

function openEdit(role: Role) {
  editingRole.value = role
  roleName.value = role.name
  selectedPermissions.value = [...role.permissions]
  isModalOpen.value = true
}

function togglePermission(permission: string) {
  if (selectedPermissions.value.includes(permission)) {
    selectedPermissions.value = selectedPermissions.value.filter(p => p !== permission)
  } else {
    selectedPermissions.value = [...selectedPermissions.value, permission]
  }
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
      <div>
        <h2 class="text-xl font-semibold text-slate-900">Users & Roles</h2>
        <p class="text-sm text-slate-500 mt-1">Manage roles and their permissions.</p>
      </div>
      <UiButton @click="openCreate">
        <Plus class="w-4 h-4 mr-2" />
        New Role
      </UiButton>
    </div>

    <div v-if="isLoading" class="text-sm text-slate-500">Loading roles…</div>

    <div v-else class="bg-white border border-slate-200 rounded-xl overflow-hidden">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Role</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Permissions</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="role in roles" :key="role.id" class="hover:bg-slate-50/80">
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <Shield class="w-4 h-4 text-slate-400" />
                <span class="font-medium text-slate-900">{{ role.name }}</span>
              </div>
            </td>
            <td class="px-4 py-3 text-sm text-slate-600">
              {{ role.permissions.length }} permission{{ role.permissions.length === 1 ? '' : 's' }}
            </td>
            <td class="px-4 py-3 text-right">
              <UiButton variant="outline" size="sm" @click="openEdit(role)">
                <Pencil class="w-3.5 h-3.5 mr-1" />
                Edit
              </UiButton>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <UiModal v-model="isModalOpen" :title="editingRole ? 'Edit Role' : 'Create Role'" @close="isModalOpen = false">
      <div class="space-y-4">
        <UiInput v-model="roleName" label="Role Name" placeholder="e.g. Cashier" />
        <div>
          <p class="text-sm font-medium text-slate-700 mb-2">Permissions</p>
          <div class="max-h-64 overflow-y-auto border border-slate-200 rounded-lg p-3 space-y-2">
            <label
              v-for="permission in allPermissions"
              :key="permission"
              class="flex items-center gap-2 text-sm text-slate-700"
            >
              <input
                type="checkbox"
                class="rounded border-slate-300"
                :checked="selectedPermissions.includes(permission)"
                @change="togglePermission(permission)"
              />
              <span class="font-mono text-xs">{{ permission }}</span>
            </label>
          </div>
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <UiButton variant="outline" @click="isModalOpen = false">Cancel</UiButton>
          <UiButton :loading="saveMutation.isPending.value" @click="saveMutation.mutate()">
            Save
          </UiButton>
        </div>
      </div>
    </UiModal>
  </div>
</template>
