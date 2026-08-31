<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { settingsApi, type TenantSettings } from '@/api/settings'
import { authApi } from '@/api/auth'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import { useToast } from '@/composables/useToast'
import {
  Building2,
  Globe,
  Receipt,
  Lock,
  Shield,
  CheckCircle,
  Eye,
  EyeOff,
  KeyRound,
  FileText,
  DollarSign,
  Calendar,
  Layers,
  Sparkles,
  Phone,
  Mail,
  MapPin,
  Save,
} from '@lucide/vue'

const toast = useToast()
const activeTab = ref<'company' | 'localization' | 'invoicing' | 'security'>('company')
const loading = ref(false)

const settingsForm = ref<TenantSettings>({
  display_name: '',
  company_email: '',
  company_phone: '',
  company_address: '',
  tax_id: '',
  website: '',
  logo_url: '',
  timezone: 'UTC',
  currency: 'USD',
  currency_symbol: '$',
  date_format: 'YYYY-MM-DD',
  fiscal_year_start: 'January',
  default_tax_rate: 0,
  invoice_prefix: 'INV-',
  quote_prefix: 'QTE-',
  po_prefix: 'PO-',
  default_payment_terms: 'Net 30',
  auto_inventory_sync: true,
})

// Password change state
const passwordLoading = ref(false)
const showCurrentPassword = ref(false)
const showNewPassword = ref(false)
const passwordForm = ref({
  current_password: '',
  password: '',
  password_confirmation: '',
})
const passwordError = ref('')
const passwordSuccess = ref('')

onMounted(async () => {
  try {
    const res = await settingsApi.get()
    if (res.data?.data) {
      settingsForm.value = { ...settingsForm.value, ...res.data.data }
    }
  } catch (err) {
    console.error('Failed to load settings', err)
  }
})

async function saveSettings() {
  loading.value = true
  try {
    const res = await settingsApi.update(settingsForm.value)
    if (res.data?.data) {
      settingsForm.value = { ...settingsForm.value, ...res.data.data }
    }
    toast.success('System settings saved successfully')
  } catch (e: any) {
    toast.error(e?.response?.data?.message || 'Failed to save settings')
  } finally {
    loading.value = false
  }
}

async function changePassword() {
  passwordError.value = ''
  passwordSuccess.value = ''

  if (!passwordForm.value.current_password) {
    passwordError.value = 'Please enter your current password.'
    return
  }

  if (passwordForm.value.password.length < 8) {
    passwordError.value = 'New password must be at least 8 characters long.'
    return
  }

  if (passwordForm.value.password !== passwordForm.value.password_confirmation) {
    passwordError.value = 'New passwords do not match.'
    return
  }

  passwordLoading.value = true
  try {
    const res = await authApi.changePassword({
      current_password: passwordForm.value.current_password,
      password: passwordForm.value.password,
      password_confirmation: passwordForm.value.password_confirmation,
    })
    passwordSuccess.value = res.message || 'Password changed successfully.'
    passwordForm.value = {
      current_password: '',
      password: '',
      password_confirmation: '',
    }
    toast.success('Password changed successfully')
  } catch (e: any) {
    passwordError.value = e?.response?.data?.message || e?.response?.data?.errors?.current_password?.[0] || 'Failed to change password.'
  } finally {
    passwordLoading.value = false
  }
}
</script>

<template>
  <div class="space-y-6 max-w-5xl">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">System & Enterprise Settings</h1>
        <p class="text-xs sm:text-sm text-slate-500">Configure company details, localization parameters, numbering prefixes, and security credentials.</p>
      </div>
      <div v-if="activeTab !== 'security'">
        <UiButton :loading="loading" @click="saveSettings">
          <Save class="w-4 h-4 mr-1.5" /> Save Changes
        </UiButton>
      </div>
    </div>

    <!-- Tabbed Navigation -->
    <div class="flex items-center gap-1.5 border-b border-slate-200 pb-2 overflow-x-auto">
      <button
        type="button"
        @click="activeTab = 'company'"
        class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-2 whitespace-nowrap"
        :class="activeTab === 'company' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
      >
        <Building2 class="w-3.5 h-3.5" /> Company Profile
      </button>

      <button
        type="button"
        @click="activeTab = 'localization'"
        class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-2 whitespace-nowrap"
        :class="activeTab === 'localization' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
      >
        <Globe class="w-3.5 h-3.5" /> Localization & Finance
      </button>

      <button
        type="button"
        @click="activeTab = 'invoicing'"
        class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-2 whitespace-nowrap"
        :class="activeTab === 'invoicing' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
      >
        <Receipt class="w-3.5 h-3.5" /> Invoicing & Operations
      </button>

      <button
        type="button"
        @click="activeTab = 'security'"
        class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-2 whitespace-nowrap"
        :class="activeTab === 'security' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
      >
        <Lock class="w-3.5 h-3.5" /> Password & Security
      </button>
    </div>

    <!-- 1. Company Profile Tab -->
    <div v-if="activeTab === 'company'" class="bg-white border border-slate-200 rounded-2xl p-6 space-y-6 shadow-xs">
      <div class="space-y-1 border-b border-slate-100 pb-3">
        <h2 class="text-base font-bold text-slate-900">Organization & Legal Identity</h2>
        <p class="text-xs text-slate-500">This business information appears on invoices, receipts, and client quotations.</p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <UiInput
          v-model="settingsForm.display_name"
          label="Company / Trading Name"
          placeholder="e.g. Acme Innovations Corp"
          required
        />
        <UiInput
          v-model="settingsForm.tax_id"
          label="Tax ID / VAT Registration Number"
          placeholder="e.g. US-987654321 / VAT-GB12345"
        />
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <UiInput
          v-model="settingsForm.company_email"
          label="Primary Business Email"
          type="email"
          placeholder="billing@acme.com"
        />
        <UiInput
          v-model="settingsForm.company_phone"
          label="Company Phone Number"
          placeholder="+1 (555) 234-5678"
        />
        <UiInput
          v-model="settingsForm.website"
          label="Official Website URL"
          placeholder="https://www.acme.com"
        />
      </div>

      <div class="space-y-1">
        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Official Headquarter Address</label>
        <textarea
          v-model="settingsForm.company_address"
          rows="3"
          placeholder="Street address, Suite / Floor, City, Postal Code, Country..."
          class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500"
        ></textarea>
      </div>

      <div class="flex justify-end pt-3 border-t border-slate-100">
        <UiButton :loading="loading" @click="saveSettings">Save Company Profile</UiButton>
      </div>
    </div>

    <!-- 2. Localization & Finance Tab -->
    <div v-else-if="activeTab === 'localization'" class="bg-white border border-slate-200 rounded-2xl p-6 space-y-6 shadow-xs">
      <div class="space-y-1 border-b border-slate-100 pb-3">
        <h2 class="text-base font-bold text-slate-900">Localization, Currency & Fiscal Timing</h2>
        <p class="text-xs text-slate-500">Configure your base currency, timezones, and tax defaults across all financial ledgers.</p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <UiSelect
          v-model="settingsForm.currency"
          label="Base Operating Currency"
          :options="[
            { label: 'USD - United States Dollar ($)', value: 'USD' },
            { label: 'EUR - Euro (€)', value: 'EUR' },
            { label: 'GBP - British Pound (£)', value: 'GBP' },
            { label: 'AED - UAE Dirham (د.إ)', value: 'AED' },
            { label: 'ETB - Ethiopian Birr (Br)', value: 'ETB' },
            { label: 'CAD - Canadian Dollar ($)', value: 'CAD' },
            { label: 'AUD - Australian Dollar ($)', value: 'AUD' },
          ]"
        />

        <UiInput
          v-model="settingsForm.currency_symbol"
          label="Currency Symbol"
          placeholder="$"
        />

        <UiInput
          v-model="settingsForm.default_tax_rate"
          label="Default Sales Tax / VAT (%)"
          type="number"
          step="0.1"
          placeholder="15.0"
        />
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <UiSelect
          v-model="settingsForm.timezone"
          label="System Timezone"
          :options="[
            { label: 'UTC (Coordinated Universal Time)', value: 'UTC' },
            { label: 'America/New_York (EST/EDT)', value: 'America/New_York' },
            { label: 'America/Los_Angeles (PST/PDT)', value: 'America/Los_Angeles' },
            { label: 'Europe/London (GMT/BST)', value: 'Europe/London' },
            { label: 'Europe/Paris (CET/CEST)', value: 'Europe/Paris' },
            { label: 'Africa/Addis_Ababa (EAT)', value: 'Africa/Addis_Ababa' },
            { label: 'Asia/Dubai (GST)', value: 'Asia/Dubai' },
            { label: 'Asia/Singapore (SGT)', value: 'Asia/Singapore' },
            { label: 'Asia/Tokyo (JST)', value: 'Asia/Tokyo' },
          ]"
        />

        <UiSelect
          v-model="settingsForm.date_format"
          label="Standard Date Format"
          :options="[
            { label: 'YYYY-MM-DD (2026-08-31)', value: 'YYYY-MM-DD' },
            { label: 'DD/MM/YYYY (31/08/2026)', value: 'DD/MM/YYYY' },
            { label: 'MM/DD/YYYY (08/31/2026)', value: 'MM/DD/YYYY' },
          ]"
        />

        <UiSelect
          v-model="settingsForm.fiscal_year_start"
          label="Fiscal Year Begins"
          :options="[
            { label: 'January (Calendar Year)', value: 'January' },
            { label: 'April', value: 'April' },
            { label: 'July', value: 'July' },
            { label: 'October', value: 'October' },
          ]"
        />
      </div>

      <div class="flex justify-end pt-3 border-t border-slate-100">
        <UiButton :loading="loading" @click="saveSettings">Save Localization Defaults</UiButton>
      </div>
    </div>

    <!-- 3. Invoicing & Operations Tab -->
    <div v-else-if="activeTab === 'invoicing'" class="bg-white border border-slate-200 rounded-2xl p-6 space-y-6 shadow-xs">
      <div class="space-y-1 border-b border-slate-100 pb-3">
        <h2 class="text-base font-bold text-slate-900">Invoicing, Numbering & Operations</h2>
        <p class="text-xs text-slate-500">Configure auto-incrementing document prefixes and payment terms.</p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <UiInput
          v-model="settingsForm.invoice_prefix"
          label="Invoice Number Prefix"
          placeholder="INV-"
        />
        <UiInput
          v-model="settingsForm.quote_prefix"
          label="Quotation Number Prefix"
          placeholder="QTE-"
        />
        <UiInput
          v-model="settingsForm.po_prefix"
          label="Purchase Order Prefix"
          placeholder="PO-"
        />
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <UiSelect
          v-model="settingsForm.default_payment_terms"
          label="Default Payment Terms"
          :options="[
            { label: 'Due Upon Receipt', value: 'Due Upon Receipt' },
            { label: 'Net 15 Days', value: 'Net 15' },
            { label: 'Net 30 Days', value: 'Net 30' },
            { label: 'Net 60 Days', value: 'Net 60' },
          ]"
        />

        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2 flex flex-col justify-center">
          <div class="flex items-center gap-2">
            <input
              id="auto_inventory_checkbox"
              v-model="settingsForm.auto_inventory_sync"
              type="checkbox"
              class="h-4 w-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500 cursor-pointer"
            />
            <label for="auto_inventory_checkbox" class="text-xs text-slate-800 font-bold cursor-pointer">
              Auto-deplete stock upon invoice issuance
            </label>
          </div>
          <p class="text-[11px] text-slate-500 leading-normal pl-6">
            Automatically create outbound inventory movements when sales invoices are confirmed.
          </p>
        </div>
      </div>

      <div class="flex justify-end pt-3 border-t border-slate-100">
        <UiButton :loading="loading" @click="saveSettings">Save Operations Settings</UiButton>
      </div>
    </div>

    <!-- 4. Password & Security Tab -->
    <div v-else-if="activeTab === 'security'" class="space-y-6">
      <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-5 shadow-xs">
        <div class="space-y-1 border-b border-slate-100 pb-3">
          <h2 class="text-base font-bold text-slate-900">Change Account Password</h2>
          <p class="text-xs text-slate-500">Ensure your account uses a strong password with at least 8 characters.</p>
        </div>

        <div v-if="passwordSuccess" class="p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-semibold rounded-xl flex items-center justify-between">
          <span>{{ passwordSuccess }}</span>
          <button type="button" @click="passwordSuccess = ''" class="text-emerald-500 font-bold ml-2">✕</button>
        </div>

        <div v-if="passwordError" class="p-3.5 bg-red-50 border border-red-200 text-red-700 text-xs font-semibold rounded-xl flex items-center justify-between">
          <span>{{ passwordError }}</span>
          <button type="button" @click="passwordError = ''" class="text-red-500 font-bold ml-2">✕</button>
        </div>

        <div class="space-y-4">
          <div class="space-y-1">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Current Password</label>
            <div class="relative flex items-center">
              <input
                v-model="passwordForm.current_password"
                :type="showCurrentPassword ? 'text' : 'password'"
                placeholder="Enter your current password"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-mono text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-600"
              />
              <button
                type="button"
                @click="showCurrentPassword = !showCurrentPassword"
                class="absolute right-3 text-slate-400 hover:text-slate-600 p-1 cursor-pointer"
                tabindex="-1"
              >
                <EyeOff v-if="showCurrentPassword" class="w-4 h-4" />
                <Eye v-else class="w-4 h-4" />
              </button>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1">
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">New Password</label>
              <div class="relative flex items-center">
                <input
                  v-model="passwordForm.password"
                  :type="showNewPassword ? 'text' : 'password'"
                  placeholder="Min. 8 characters"
                  class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-mono text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-600"
                />
                <button
                  type="button"
                  @click="showNewPassword = !showNewPassword"
                  class="absolute right-3 text-slate-400 hover:text-slate-600 p-1 cursor-pointer"
                  tabindex="-1"
                >
                  <EyeOff v-if="showNewPassword" class="w-4 h-4" />
                  <Eye v-else class="w-4 h-4" />
                </button>
              </div>
            </div>

            <div class="space-y-1">
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Confirm New Password</label>
              <input
                v-model="passwordForm.password_confirmation"
                type="password"
                placeholder="Re-type new password"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-mono text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-600"
              />
            </div>
          </div>

          <div class="flex justify-end pt-2">
            <UiButton :loading="passwordLoading" @click="changePassword" :disabled="!passwordForm.current_password || !passwordForm.password">
              Update Password
            </UiButton>
          </div>
        </div>
      </div>

      <!-- Security Status Card -->
      <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs flex items-center justify-between">
        <div class="flex items-center gap-3.5">
          <div class="p-2.5 bg-primary-50 text-primary-600 rounded-xl">
            <Shield class="w-5 h-5" />
          </div>
          <div>
            <h4 class="text-sm font-bold text-slate-900">Multi-Tenant RBAC Session</h4>
            <p class="text-xs text-slate-500">Your session is protected with isolated schema guards and scoped tenant tokens.</p>
          </div>
        </div>
        <UiBadge variant="success" class="font-bold">Active & Secure</UiBadge>
      </div>
    </div>
  </div>
</template>
