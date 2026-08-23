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

const { data: serverPermissions } = useQuery({
  queryKey: ['core', 'permissions'],
  queryFn: () => rolesApi.permissions().then(res => res.data.data).catch(() => [] as string[]),
})

const allPermissions = computed(() => {
  const set = new Set<string>()
  ;(serverPermissions.value || []).forEach(p => set.add(p))
  ;(roles.value || []).forEach(role => role.permissions.forEach(p => set.add(p)))
  return Array.from(set).sort()
})

const groupedPermissions = computed(() => {
  const groups: Record<string, string[]> = {}
  allPermissions.value.forEach(p => {
    const prefix = p.split('.')[0]?.toUpperCase() || 'OTHER'
    if (!groups[prefix]) {
      groups[prefix] = []
    }
    groups[prefix].push(p)
  })
  return groups
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
          <div class="flex items-center justify-between mb-2">
            <p class="text-sm font-medium text-slate-700">Permissions ({{ selectedPermissions.length }} selected)</p>
          </div>
          <div class="max-h-72 overflow-y-auto border border-slate-200 rounded-lg p-3 space-y-4">
            <div v-for="(perms, moduleName) in groupedPermissions" :key="moduleName" class="space-y-2">
              <div class="text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100 pb-1">
                {{ moduleName }}
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <label
                  v-for="permission in perms"
                  :key="permission"
                  class="flex items-center gap-2 text-xs text-slate-700 hover:text-slate-900 cursor-pointer"
                >
                  <input
                    type="checkbox"
                    class="rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                    :checked="selectedPermissions.includes(permission)"
                    @change="togglePermission(permission)"
                  />
                  <span class="font-mono">{{ permission }}</span>
                </label>
              </div>
            </div>
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
