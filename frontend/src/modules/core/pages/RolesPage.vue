<script setup lang="ts">
import { computed, ref, markRaw } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { rolesApi, usersApi, type Role, type AppUser } from '@/api/roles'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiStat from '@/components/ui/UiStat.vue'
import UiSpinner from '@/components/ui/UiSpinner.vue'
import {
  Users,
  Shield,
  Plus,
  Pencil,
  Trash2,
  Search,
  CheckCircle2,
  XCircle,
  KeyRound,
  UserCheck,
  UserX,
  Lock,
  Mail,
  Edit2,
  CheckSquare,
  Square,
  Layers,
} from '@lucide/vue'
import { useToast } from '@/composables/useToast'

const queryClient = useQueryClient()
const toast = useToast()

const activeTab = ref<'users' | 'roles'>('users')

// --- Users State ---
const userSearchQuery = ref('')
const selectedRoleFilter = ref<string>('all')
const isUserModalOpen = ref(false)
const isDeleteUserModalOpen = ref(false)
const editingUser = ref<AppUser | null>(null)
const userToDelete = ref<AppUser | null>(null)

const userForm = ref({
  name: '',
  email: '',
  password: '',
  roles: [] as string[],
  is_active: true,
})

// --- Roles State ---
const isRoleModalOpen = ref(false)
const isDeleteRoleModalOpen = ref(false)
const editingRole = ref<Role | null>(null)
const roleToDelete = ref<Role | null>(null)
const roleName = ref('')
const selectedPermissions = ref<string[]>([])

// --- Queries ---
const { data: users, isLoading: isLoadingUsers } = useQuery({
  queryKey: ['core', 'users'],
  queryFn: () => usersApi.list().then((res) => res.data.data),
})

const { data: roles, isLoading: isLoadingRoles } = useQuery({
  queryKey: ['core', 'roles'],
  queryFn: () => rolesApi.list().then((res) => res.data.data),
})

const { data: serverPermissions } = useQuery({
  queryKey: ['core', 'permissions'],
  queryFn: () => rolesApi.permissions().then((res) => res.data.data).catch(() => [] as string[]),
})

// --- Computed Stats & Filters ---
const userStats = computed(() => {
  const list = users.value || []
  const active = list.filter((u) => u.is_active).length
  const suspended = list.filter((u) => !u.is_active).length
  const totalRoles = roles.value?.length || 0

  return [
    {
      label: 'Team Members',
      value: list.length,
      icon: markRaw(Users),
    },
    {
      label: 'Active Accounts',
      value: active,
      icon: markRaw(UserCheck),
    },
    {
      label: 'Suspended Accounts',
      value: suspended,
      icon: markRaw(UserX),
    },
    {
      label: 'Defined Roles',
      value: totalRoles,
      icon: markRaw(Shield),
    },
  ]
})

const filteredUsers = computed(() => {
  let list = users.value || []
  if (selectedRoleFilter.value !== 'all') {
    list = list.filter((u) => u.roles.includes(selectedRoleFilter.value))
  }
  if (userSearchQuery.value) {
    const q = userSearchQuery.value.toLowerCase()
    list = list.filter(
      (u) =>
        u.name.toLowerCase().includes(q) ||
        u.email.toLowerCase().includes(q) ||
        u.roles.some((r) => r.toLowerCase().includes(q))
    )
  }
  return list
})

const allPermissions = computed(() => {
  const set = new Set<string>()
  ;(serverPermissions.value || []).forEach((p) => set.add(p))
  ;(roles.value || []).forEach((role) => role.permissions.forEach((p) => set.add(p)))
  return Array.from(set).sort()
})

const groupedPermissions = computed(() => {
  const groups: Record<string, string[]> = {}
  allPermissions.value.forEach((p) => {
    const prefix = p.split('.')[0]?.toUpperCase() || 'OTHER'
    if (!groups[prefix]) {
      groups[prefix] = []
    }
    groups[prefix].push(p)
  })
  return groups
})

// --- User Mutations ---
const saveUserMutation = useMutation({
  mutationFn: (payload: any) => {
    if (editingUser.value) {
      return usersApi.update(editingUser.value.id, payload)
    }
    return usersApi.create(payload)
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['core', 'users'] })
    isUserModalOpen.value = false
    toast.success(editingUser.value ? 'User account updated' : 'New user created')
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || 'Failed to save user')
  },
})

const toggleUserStatusMutation = useMutation({
  mutationFn: (id: string) => usersApi.toggleStatus(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['core', 'users'] })
    toast.success('User status updated')
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || 'Failed to toggle status')
  },
})

const deleteUserMutation = useMutation({
  mutationFn: (id: string) => usersApi.delete(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['core', 'users'] })
    isDeleteUserModalOpen.value = false
    userToDelete.value = null
    toast.success('User deleted')
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || 'Failed to delete user')
  },
})

// --- Role Mutations ---
const saveRoleMutation = useMutation({
  mutationFn: async () => {
    if (editingRole.value) {
      await rolesApi.update(editingRole.value.id, roleName.value)
      await rolesApi.syncPermissions(editingRole.value.id, selectedPermissions.value)
    } else {
      const created = await rolesApi.create(roleName.value)
      const id = created.data.data?.id ?? (created.data as any).id
      if (id && selectedPermissions.value.length) {
        await rolesApi.syncPermissions(id, selectedPermissions.value)
      }
    }
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['core', 'roles'] })
    isRoleModalOpen.value = false
    toast.success(editingRole.value ? 'Role updated' : 'Role created')
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || 'Failed to save role')
  },
})

const deleteRoleMutation = useMutation({
  mutationFn: (id: number) => rolesApi.destroy(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['core', 'roles'] })
    isDeleteRoleModalOpen.value = false
    roleToDelete.value = null
    toast.success('Role deleted')
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || 'Failed to delete role')
  },
})

// --- Handlers ---
function openCreateUser() {
  editingUser.value = null
  userForm.value = {
    name: '',
    email: '',
    password: '',
    roles: [],
    is_active: true,
  }
  isUserModalOpen.value = true
}

function openEditUser(user: AppUser) {
  editingUser.value = user
  userForm.value = {
    name: user.name,
    email: user.email,
    password: '',
    roles: [...user.roles],
    is_active: user.is_active,
  }
  isUserModalOpen.value = true
}

function openDeleteUserConfirm(user: AppUser) {
  userToDelete.value = user
  isDeleteUserModalOpen.value = true
}

function handleSaveUser() {
  if (!userForm.value.name || !userForm.value.email) {
    toast.error('Name and email are required')
    return
  }
  if (!editingUser.value && !userForm.value.password) {
    toast.error('Password is required for new accounts')
    return
  }

  const payload: any = {
    name: userForm.value.name,
    email: userForm.value.email,
    roles: userForm.value.roles,
    is_active: userForm.value.is_active,
  }
  if (userForm.value.password) {
    payload.password = userForm.value.password
  }

  saveUserMutation.mutate(payload)
}

function openCreateRole() {
  editingRole.value = null
  roleName.value = ''
  selectedPermissions.value = []
  isRoleModalOpen.value = true
}

function openEditRole(role: Role) {
  editingRole.value = role
  roleName.value = role.name
  selectedPermissions.value = [...role.permissions]
  isRoleModalOpen.value = true
}

function openDeleteRoleConfirm(role: Role) {
  roleToDelete.value = role
  isDeleteRoleModalOpen.value = true
}

function togglePermission(permission: string) {
  if (selectedPermissions.value.includes(permission)) {
    selectedPermissions.value = selectedPermissions.value.filter((p) => p !== permission)
  } else {
    selectedPermissions.value = [...selectedPermissions.value, permission]
  }
}

function toggleModulePermissions(moduleName: string) {
  const perms = groupedPermissions.value[moduleName] || []
  const allSelected = perms.every((p) => selectedPermissions.value.includes(p))
  if (allSelected) {
    selectedPermissions.value = selectedPermissions.value.filter((p) => !perms.includes(p))
  } else {
    const combined = new Set([...selectedPermissions.value, ...perms])
    selectedPermissions.value = Array.from(combined)
  }
}

function selectAllPermissions() {
  selectedPermissions.value = [...allPermissions.value]
}

function clearAllPermissions() {
  selectedPermissions.value = []
}
</script>

<template>
  <div class="space-y-6 max-w-6xl">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Users & Access Control</h1>
        <p class="text-xs sm:text-sm text-slate-500">Manage organization team members, assign access privileges, and configure role-based permissions.</p>
      </div>

      <div class="flex items-center gap-2">
        <UiButton v-if="activeTab === 'users'" @click="openCreateUser">
          <Plus class="w-4 h-4 mr-1.5" /> Add Team Member
        </UiButton>
        <UiButton v-else @click="openCreateRole">
          <Plus class="w-4 h-4 mr-1.5" /> Create Role
        </UiButton>
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <UiStat
        v-for="stat in userStats"
        :key="stat.label"
        :label="stat.label"
        :value="stat.value"
        :icon="stat.icon"
      />
    </div>

    <!-- Tabbed Navigation -->
    <div class="flex items-center gap-1.5 border-b border-slate-200 pb-2">
      <button
        type="button"
        @click="activeTab = 'users'"
        class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-2"
        :class="activeTab === 'users' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
      >
        <Users class="w-3.5 h-3.5" /> Team Members ({{ users?.length || 0 }})
      </button>

      <button
        type="button"
        @click="activeTab = 'roles'"
        class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-2"
        :class="activeTab === 'roles' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
      >
        <Shield class="w-3.5 h-3.5" /> Roles & Permissions ({{ roles?.length || 0 }})
      </button>
    </div>

    <!-- 1. Users Tab -->
    <div v-if="activeTab === 'users'" class="space-y-4">
      <!-- Search & Filters -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-3.5 rounded-2xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 max-w-xl">
          <button
            type="button"
            @click="selectedRoleFilter = 'all'"
            class="px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all cursor-pointer"
            :class="selectedRoleFilter === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
          >
            All Roles
          </button>
          <button
            v-for="r in roles"
            :key="r.id"
            type="button"
            @click="selectedRoleFilter = r.name"
            class="px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all cursor-pointer capitalize"
            :class="selectedRoleFilter === r.name ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
          >
            {{ r.name }}
          </button>
        </div>

        <UiInput
          v-model="userSearchQuery"
          placeholder="Search by name, email..."
          size="sm"
          class="w-full sm:w-64"
        >
          <template #prefix><Search class="w-3.5 h-3.5 text-slate-400" /></template>
        </UiInput>
      </div>

      <!-- Users Table -->
      <div v-if="isLoadingUsers" class="p-16 flex justify-center">
        <UiSpinner size="lg" />
      </div>

      <div v-else class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div v-if="filteredUsers.length" class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-100 text-xs">
            <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
              <tr>
                <th class="px-4 py-3 text-left">Member Profile</th>
                <th class="px-4 py-3 text-left">Assigned Roles</th>
                <th class="px-4 py-3 text-center">Account Status</th>
                <th class="px-4 py-3 text-right">Created Date</th>
                <th class="px-4 py-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-slate-50/70 transition-colors">
                <td class="px-4 py-3">
                  <div class="font-bold text-slate-900 text-sm">{{ user.name }}</div>
                  <div class="text-[11px] text-slate-500 font-mono">{{ user.email }}</div>
                </td>

                <td class="px-4 py-3">
                  <div class="flex flex-wrap gap-1">
                    <span
                      v-for="r in user.roles"
                      :key="r"
                      class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-primary-50 text-primary-700 border border-primary-100 capitalize"
                    >
                      {{ r }}
                    </span>
                    <span v-if="!user.roles?.length" class="text-xs text-slate-400 italic">No roles assigned</span>
                  </div>
                </td>

                <td class="px-4 py-3 text-center">
                  <button
                    type="button"
                    @click="toggleUserStatusMutation.mutate(user.id)"
                    class="cursor-pointer inline-flex items-center gap-1 font-bold text-[10px] px-2 py-0.5 rounded-full transition-all"
                    :class="user.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500 border border-slate-200'"
                    :title="user.is_active ? 'Click to deactivate' : 'Click to activate'"
                  >
                    <span class="w-1.5 h-1.5 rounded-full" :class="user.is_active ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                    {{ user.is_active ? 'Active' : 'Suspended' }}
                  </button>
                </td>

                <td class="px-4 py-3 text-right text-slate-500 font-mono">
                  {{ new Date(user.created_at).toLocaleDateString() }}
                </td>

                <td class="px-4 py-3 text-right space-x-1 whitespace-nowrap">
                  <UiButton variant="ghost" size="sm" @click="openEditUser(user)">
                    <Edit2 class="w-3.5 h-3.5" />
                  </UiButton>
                  <UiButton variant="ghost" size="sm" class="text-red-500 hover:text-red-700 hover:bg-red-50" @click="openDeleteUserConfirm(user)">
                    <Trash2 class="w-3.5 h-3.5" />
                  </UiButton>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="p-12 text-center text-slate-400 text-xs">
          No team members found matching your search.
        </div>
      </div>
    </div>

    <!-- 2. Roles Tab -->
    <div v-else-if="activeTab === 'roles'" class="space-y-4">
      <div v-if="isLoadingRoles" class="p-16 flex justify-center">
        <UiSpinner size="lg" />
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="role in roles"
          :key="role.id"
          class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between space-y-4"
        >
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <Shield class="w-4 h-4 text-primary-600" />
                <h3 class="font-bold text-slate-900 text-base capitalize">{{ role.name }}</h3>
              </div>
              <span class="text-[10px] font-bold bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full">
                {{ role.permissions.length }} permissions
              </span>
            </div>

            <p class="text-xs text-slate-500">
              Grants authorization across specific enterprise modules and APIs.
            </p>

            <div class="flex flex-wrap gap-1 pt-1 max-h-24 overflow-y-auto pr-1">
              <span
                v-for="p in role.permissions.slice(0, 8)"
                :key="p"
                class="text-[9px] font-mono bg-slate-50 text-slate-600 px-1.5 py-0.5 rounded border border-slate-200"
              >
                {{ p }}
              </span>
              <span v-if="role.permissions.length > 8" class="text-[9px] font-bold text-slate-400 self-center">
                +{{ role.permissions.length - 8 }} more
              </span>
            </div>
          </div>

          <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
            <UiButton variant="outline" size="sm" @click="openEditRole(role)">
              <Pencil class="w-3.5 h-3.5 mr-1" /> Configure Permissions
            </UiButton>
            <UiButton variant="ghost" size="sm" class="text-red-500 hover:text-red-700" @click="openDeleteRoleConfirm(role)">
              <Trash2 class="w-3.5 h-3.5" />
            </UiButton>
          </div>
        </div>
      </div>
    </div>

    <!-- Create / Edit User Modal -->
    <UiModal v-model="isUserModalOpen" :title="editingUser ? 'Edit User Profile' : 'Add Team Member'" size="md">
      <div class="space-y-4">
        <UiInput v-model="userForm.name" label="Full Name" placeholder="e.g. Sarah Connor" required />
        <UiInput v-model="userForm.email" label="Email Address" type="email" placeholder="sarah@company.com" required />

        <UiInput
          v-model="userForm.password"
          label="Account Password"
          type="password"
          :placeholder="editingUser ? 'Leave blank to keep existing password' : 'Min. 8 characters'"
          :required="!editingUser"
        />

        <div class="space-y-2">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Assign Roles</label>
          <div class="grid grid-cols-2 gap-2 p-3 bg-slate-50 rounded-xl border border-slate-200 max-h-40 overflow-y-auto">
            <label
              v-for="r in roles"
              :key="r.id"
              class="flex items-center gap-2 text-xs font-semibold text-slate-700 capitalize cursor-pointer hover:text-slate-900"
            >
              <input
                type="checkbox"
                class="rounded border-slate-300 text-primary-600 focus:ring-primary-500 cursor-pointer"
                :value="r.name"
                v-model="userForm.roles"
              />
              <span>{{ r.name }}</span>
            </label>
          </div>
        </div>

        <div class="flex items-center gap-2 pt-1">
          <input
            id="user_active_checkbox"
            v-model="userForm.is_active"
            type="checkbox"
            class="h-4 w-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500 cursor-pointer"
          />
          <label for="user_active_checkbox" class="text-xs text-slate-700 font-semibold cursor-pointer">
            User account is active & authorized to login
          </label>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
          <UiButton variant="outline" type="button" @click="isUserModalOpen = false">Cancel</UiButton>
          <UiButton :loading="saveUserMutation.isPending.value" @click="handleSaveUser">
            {{ editingUser ? 'Save User' : 'Create User' }}
          </UiButton>
        </div>
      </div>
    </UiModal>

    <!-- Create / Edit Role Modal -->
    <UiModal v-model="isRoleModalOpen" :title="editingRole ? `Configure Role: ${editingRole.name}` : 'Create Role'" size="xl">
      <div class="space-y-4">
        <UiInput v-model="roleName" label="Role Name" placeholder="e.g. Operations Manager" required />

        <div>
          <div class="flex items-center justify-between mb-2">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-700">
              Module Permissions ({{ selectedPermissions.length }} of {{ allPermissions.length }} selected)
            </p>
            <div class="flex items-center gap-2 text-xs">
              <button type="button" @click="selectAllPermissions" class="font-bold text-primary-600 hover:text-primary-800 cursor-pointer">
                Select All
              </button>
              <span class="text-slate-300">•</span>
              <button type="button" @click="clearAllPermissions" class="font-bold text-slate-500 hover:text-slate-700 cursor-pointer">
                Clear All
              </button>
            </div>
          </div>

          <div class="max-h-80 overflow-y-auto border border-slate-200 rounded-2xl p-4 space-y-4 bg-slate-50/50">
            <div v-for="(perms, moduleName) in groupedPermissions" :key="moduleName" class="space-y-2 bg-white p-3.5 rounded-xl border border-slate-200">
              <div class="flex items-center justify-between border-b border-slate-100 pb-1.5">
                <span class="text-xs font-black text-slate-800 tracking-wider">
                  {{ moduleName }}
                </span>
                <button
                  type="button"
                  @click="toggleModulePermissions(moduleName)"
                  class="text-[11px] font-bold text-primary-600 hover:text-primary-800 cursor-pointer"
                >
                  Toggle All
                </button>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 pt-1">
                <label
                  v-for="permission in perms"
                  :key="permission"
                  class="flex items-center gap-2 text-xs text-slate-700 hover:text-slate-900 cursor-pointer"
                >
                  <input
                    type="checkbox"
                    class="rounded border-slate-300 text-primary-600 focus:ring-primary-500 cursor-pointer"
                    :checked="selectedPermissions.includes(permission)"
                    @change="togglePermission(permission)"
                  />
                  <span class="font-mono text-[11px] truncate">{{ permission }}</span>
                </label>
              </div>
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
          <UiButton variant="outline" type="button" @click="isRoleModalOpen = false">Cancel</UiButton>
          <UiButton :loading="saveRoleMutation.isPending.value" @click="saveRoleMutation.mutate()">
            {{ editingRole ? 'Save Permissions' : 'Create Role' }}
          </UiButton>
        </div>
      </div>
    </UiModal>

    <!-- Custom Delete User Modal -->
    <UiModal v-model="isDeleteUserModalOpen" title="Delete User Account" size="sm">
      <div v-if="userToDelete" class="space-y-4">
        <div class="flex items-start gap-3.5 p-3.5 bg-red-50 border border-red-200 rounded-2xl">
          <div class="p-2 bg-red-100 text-red-600 rounded-xl shrink-0 mt-0.5">
            <Trash2 class="w-5 h-5" />
          </div>
          <div class="space-y-1">
            <h4 class="text-sm font-bold text-red-950">Confirm User Removal</h4>
            <p class="text-xs text-red-800 leading-relaxed">
              Are you sure you want to remove <strong class="font-bold">{{ userToDelete.name }}</strong> ({{ userToDelete.email }})?
            </p>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
          <UiButton variant="outline" size="sm" type="button" @click="isDeleteUserModalOpen = false">Cancel</UiButton>
          <UiButton
            variant="danger"
            size="sm"
            :loading="deleteUserMutation.isPending.value"
            @click="deleteUserMutation.mutate(userToDelete.id)"
          >
            <Trash2 class="w-3.5 h-3.5 mr-1" /> Delete User
          </UiButton>
        </div>
      </div>
    </UiModal>

    <!-- Custom Delete Role Modal -->
    <UiModal v-model="isDeleteRoleModalOpen" title="Delete Role" size="sm">
      <div v-if="roleToDelete" class="space-y-4">
        <div class="flex items-start gap-3.5 p-3.5 bg-red-50 border border-red-200 rounded-2xl">
          <div class="p-2 bg-red-100 text-red-600 rounded-xl shrink-0 mt-0.5">
            <Trash2 class="w-5 h-5" />
          </div>
          <div class="space-y-1">
            <h4 class="text-sm font-bold text-red-950">Delete Role</h4>
            <p class="text-xs text-red-800 leading-relaxed">
              Are you sure you want to delete the role <strong class="capitalize font-bold">{{ roleToDelete.name }}</strong>?
            </p>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
          <UiButton variant="outline" size="sm" type="button" @click="isDeleteRoleModalOpen = false">Cancel</UiButton>
          <UiButton
            variant="danger"
            size="sm"
            :loading="deleteRoleMutation.isPending.value"
            @click="deleteRoleMutation.mutate(roleToDelete.id)"
          >
            <Trash2 class="w-3.5 h-3.5 mr-1" /> Delete Role
          </UiButton>
        </div>
      </div>
    </UiModal>
  </div>
</template>
