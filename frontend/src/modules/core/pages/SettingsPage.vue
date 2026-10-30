<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { settingsApi, type TenantSettings } from '@/api/settings'
import { authApi } from '@/api/auth'
import { emailApi, type EmailDiagnostics } from '@/api/email'
import { useAuthStore } from '@/stores/auth'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiSpinner from '@/components/ui/UiSpinner.vue'
import { useToast } from '@/composables/useToast'
import {
  Building2,
  Globe,
  Receipt,
  Lock,
  Shield,
  CheckCircle,
  CheckCircle2,
  AlertCircle,
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
  Send,
  RefreshCw,
  ExternalLink,
} from '@lucide/vue'

const { t } = useI18n()
const toast = useToast()
const authStore = useAuthStore()
const activeTab = ref<'company' | 'localization' | 'invoicing' | 'security' | 'email'>('company')
const loading = ref(false)

// Email diagnostics state
const emailDiagnostics = ref<EmailDiagnostics | null>(null)
const emailLoading = ref(false)
const testEmailRecipient = ref('')
const testEmailName = ref('')
const testEmailSending = ref(false)
const testEmailResult = ref<any>(null)

const mailhogUrl = computed(() => {
  if (emailDiagnostics.value?.mailhog_ui_url) {
    const raw = emailDiagnostics.value.mailhog_ui_url
    if (raw.includes('localhost') || raw.includes('127.0.0.1')) {
      if (typeof window !== 'undefined' && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
        const protocol = window.location.protocol || 'http:'
        return `${protocol}//${window.location.hostname}:8025`
      }
    }
    return raw
  }

  if (typeof window !== 'undefined' && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
    const protocol = window.location.protocol || 'http:'
    return `${protocol}//${window.location.hostname}:8025`
  }

  return 'http://localhost:8025'
})

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
  if (authStore.user?.email) {
    testEmailRecipient.value = authStore.user.email
    testEmailName.value = authStore.user.name || 'Administrator'
  }

  try {
    const res = await settingsApi.get()
    if (res.data?.data) {
      settingsForm.value = { ...settingsForm.value, ...res.data.data }
    }
  } catch (err) {
    console.error('Failed to load settings', err)
  }

  loadEmailDiagnostics()
})

async function loadEmailDiagnostics() {
  emailLoading.value = true
  try {
    const res = await emailApi.getDiagnostics()
    emailDiagnostics.value = res.data
  } catch (err) {
    console.error('Failed to load email diagnostics', err)
  } finally {
    emailLoading.value = false
  }
}

async function sendTestEmail() {
  if (!testEmailRecipient.value) {
    toast.error(t('settings.email.recipientRequired'))
    return
  }

  testEmailSending.value = true
  testEmailResult.value = null

  try {
    const res = await emailApi.sendTest({
      email: testEmailRecipient.value,
      name: testEmailName.value || undefined,
    })
    testEmailResult.value = res.data
    toast.success(res.message || t('settings.email.delivered'))
  } catch (e: any) {
    testEmailResult.value = {
      success: false,
      error: e?.response?.data?.message || t('settings.email.deliveryFailed'),
    }
    toast.error(e?.response?.data?.message || t('settings.email.sendFailed'))
  } finally {
    testEmailSending.value = false
  }
}

async function saveSettings() {
  loading.value = true
  try {
    const res = await settingsApi.update(settingsForm.value)
    if (res.data?.data) {
      settingsForm.value = { ...settingsForm.value, ...res.data.data }
    }
    toast.success(t('settings.notifications.savedSuccess'))
  } catch (e: any) {
    toast.error(e?.response?.data?.message || t('settings.notifications.saveFailed'))
  } finally {
    loading.value = false
  }
}

async function changePassword() {
  passwordError.value = ''
  passwordSuccess.value = ''

  if (!passwordForm.value.current_password) {
    passwordError.value = t('settings.security.validation.currentRequired')
    return
  }

  if (passwordForm.value.password.length < 8) {
    passwordError.value = t('settings.security.validation.minLength')
    return
  }

  if (passwordForm.value.password !== passwordForm.value.password_confirmation) {
    passwordError.value = t('settings.security.validation.mismatch')
    return
  }

  passwordLoading.value = true
  try {
    const res = await authApi.changePassword({
      current_password: passwordForm.value.current_password,
      password: passwordForm.value.password,
      password_confirmation: passwordForm.value.password_confirmation,
    })
    passwordSuccess.value = res.message || t('settings.security.validation.success')
    passwordForm.value = {
      current_password: '',
      password: '',
      password_confirmation: '',
    }
    toast.success(t('settings.security.validation.success'))
  } catch (e: any) {
    passwordError.value = e?.response?.data?.message || e?.response?.data?.errors?.current_password?.[0] || t('settings.security.validation.failed')
  } finally {
    passwordLoading.value = false
  }
}

const currencyOptions = computed(() => [
  { label: t('settings.localization.currencies.usd'), value: 'USD' },
  { label: t('settings.localization.currencies.eur'), value: 'EUR' },
  { label: t('settings.localization.currencies.gbp'), value: 'GBP' },
  { label: t('settings.localization.currencies.aed'), value: 'AED' },
  { label: t('settings.localization.currencies.etb'), value: 'ETB' },
  { label: t('settings.localization.currencies.cad'), value: 'CAD' },
  { label: t('settings.localization.currencies.aud'), value: 'AUD' },
])

const fiscalYearOptions = computed(() => [
  { label: t('settings.localization.months.january'), value: 'January' },
  { label: t('settings.localization.months.april'), value: 'April' },
  { label: t('settings.localization.months.july'), value: 'July' },
  { label: t('settings.localization.months.october'), value: 'October' },
])

const paymentTermsOptions = computed(() => [
  { label: t('settings.invoicing.paymentTerms.dueOnReceipt'), value: 'Due Upon Receipt' },
  { label: t('settings.invoicing.paymentTerms.net15'), value: 'Net 15' },
  { label: t('settings.invoicing.paymentTerms.net30'), value: 'Net 30' },
  { label: t('settings.invoicing.paymentTerms.net60'), value: 'Net 60' },
])
</script>

<template>
  <div class="space-y-6 max-w-5xl">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">{{ t('settings.title') }}</h1>
        <p class="text-xs sm:text-sm text-slate-500">{{ t('settings.subtitle') }}</p>
      </div>
      <div v-if="activeTab !== 'security'">
        <UiButton :loading="loading" @click="saveSettings">
          <Save class="w-4 h-4 mr-1.5" /> {{ t('settings.saveChanges') }}
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
        <Building2 class="w-3.5 h-3.5" /> {{ t('settings.tabs.company') }}
      </button>

      <button
        type="button"
        @click="activeTab = 'localization'"
        class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-2 whitespace-nowrap"
        :class="activeTab === 'localization' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
      >
        <Globe class="w-3.5 h-3.5" /> {{ t('settings.tabs.localization') }}
      </button>

      <button
        type="button"
        @click="activeTab = 'invoicing'"
        class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-2 whitespace-nowrap"
        :class="activeTab === 'invoicing' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
      >
        <Receipt class="w-3.5 h-3.5" /> {{ t('settings.tabs.invoicing') }}
      </button>

      <button
        type="button"
        @click="activeTab = 'security'"
        class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-2 whitespace-nowrap"
        :class="activeTab === 'security' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
      >
        <Lock class="w-3.5 h-3.5" /> {{ t('settings.tabs.security') }}
      </button>

      <button
        type="button"
        @click="activeTab = 'email'"
        class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-2 whitespace-nowrap"
        :class="activeTab === 'email' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
      >
        <Mail class="w-3.5 h-3.5" /> {{ t('settings.tabs.email') }}
      </button>
    </div>

    <!-- 1. Company Profile Tab -->
    <div v-if="activeTab === 'company'" class="bg-white border border-slate-200 rounded-2xl p-6 space-y-6 shadow-xs">
      <div class="space-y-1 border-b border-slate-100 pb-3">
        <h2 class="text-base font-bold text-slate-900">{{ t('settings.company.title') }}</h2>
        <p class="text-xs text-slate-500">{{ t('settings.company.subtitle') }}</p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <UiInput
          v-model="settingsForm.display_name"
          :label="t('settings.company.displayNameLabel')"
          :placeholder="t('settings.company.displayNamePlaceholder')"
          required
        />
        <UiInput
          v-model="settingsForm.tax_id"
          :label="t('settings.company.taxIdLabel')"
          :placeholder="t('settings.company.taxIdPlaceholder')"
        />
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <UiInput
          v-model="settingsForm.company_email"
          :label="t('settings.company.emailLabel')"
          type="email"
          :placeholder="t('settings.company.emailPlaceholder')"
        />
        <UiInput
          v-model="settingsForm.company_phone"
          :label="t('settings.company.phoneLabel')"
          :placeholder="t('settings.company.phonePlaceholder')"
        />
        <UiInput
          v-model="settingsForm.website"
          :label="t('settings.company.websiteLabel')"
          :placeholder="t('settings.company.websitePlaceholder')"
        />
      </div>

      <div class="space-y-1">
        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">{{ t('settings.company.addressLabel') }}</label>
        <textarea
          v-model="settingsForm.company_address"
          rows="3"
          :placeholder="t('settings.company.addressPlaceholder')"
          class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500"
        ></textarea>
      </div>

      <div class="flex justify-end pt-3 border-t border-slate-100">
        <UiButton :loading="loading" @click="saveSettings">{{ t('settings.company.saveBtn') }}</UiButton>
      </div>
    </div>

    <!-- 2. Localization & Finance Tab -->
    <div v-else-if="activeTab === 'localization'" class="bg-white border border-slate-200 rounded-2xl p-6 space-y-6 shadow-xs">
      <div class="space-y-1 border-b border-slate-100 pb-3">
        <h2 class="text-base font-bold text-slate-900">{{ t('settings.localization.title') }}</h2>
        <p class="text-xs text-slate-500">{{ t('settings.localization.subtitle') }}</p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <UiSelect
          v-model="settingsForm.currency"
          :label="t('settings.localization.currencyLabel')"
          :options="currencyOptions"
        />

        <UiInput
          v-model="settingsForm.currency_symbol"
          :label="t('settings.localization.currencySymbolLabel')"
          :placeholder="t('settings.localization.currencySymbolPlaceholder')"
        />

        <UiInput
          v-model="settingsForm.default_tax_rate"
          :label="t('settings.localization.taxRateLabel')"
          type="number"
          step="0.1"
          :placeholder="t('settings.localization.taxRatePlaceholder')"
        />
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <UiSelect
          v-model="settingsForm.timezone"
          :label="t('settings.localization.timezoneLabel')"
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
          :label="t('settings.localization.dateFormatLabel')"
          :options="[
            { label: 'YYYY-MM-DD (2026-08-31)', value: 'YYYY-MM-DD' },
            { label: 'DD/MM/YYYY (31/08/2026)', value: 'DD/MM/YYYY' },
            { label: 'MM/DD/YYYY (08/31/2026)', value: 'MM/DD/YYYY' },
          ]"
        />

        <UiSelect
          v-model="settingsForm.fiscal_year_start"
          :label="t('settings.localization.fiscalYearStartLabel')"
          :options="fiscalYearOptions"
        />
      </div>

      <div class="flex justify-end pt-3 border-t border-slate-100">
        <UiButton :loading="loading" @click="saveSettings">{{ t('settings.localization.saveBtn') }}</UiButton>
      </div>
    </div>

    <!-- 3. Invoicing & Operations Tab -->
    <div v-else-if="activeTab === 'invoicing'" class="bg-white border border-slate-200 rounded-2xl p-6 space-y-6 shadow-xs">
      <div class="space-y-1 border-b border-slate-100 pb-3">
        <h2 class="text-base font-bold text-slate-900">{{ t('settings.invoicing.title') }}</h2>
        <p class="text-xs text-slate-500">{{ t('settings.invoicing.subtitle') }}</p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <UiInput
          v-model="settingsForm.invoice_prefix"
          :label="t('settings.invoicing.invoicePrefixLabel')"
          :placeholder="t('settings.invoicing.invoicePrefixPlaceholder')"
        />
        <UiInput
          v-model="settingsForm.quote_prefix"
          :label="t('settings.invoicing.quotePrefixLabel')"
          :placeholder="t('settings.invoicing.quotePrefixPlaceholder')"
        />
        <UiInput
          v-model="settingsForm.po_prefix"
          :label="t('settings.invoicing.poPrefixLabel')"
          :placeholder="t('settings.invoicing.poPrefixPlaceholder')"
        />
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <UiSelect
          v-model="settingsForm.default_payment_terms"
          :label="t('settings.invoicing.paymentTermsLabel')"
          :options="paymentTermsOptions"
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
              {{ t('settings.invoicing.autoInventoryLabel') }}
            </label>
          </div>
          <p class="text-[11px] text-slate-500 leading-normal pl-6">
            {{ t('settings.invoicing.autoInventoryDesc') }}
          </p>
        </div>
      </div>

      <div class="flex justify-end pt-3 border-t border-slate-100">
        <UiButton :loading="loading" @click="saveSettings">{{ t('settings.invoicing.saveBtn') }}</UiButton>
      </div>
    </div>

    <!-- 4. Password & Security Tab -->
    <div v-else-if="activeTab === 'security'" class="space-y-6">
      <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-5 shadow-xs">
        <div class="space-y-1 border-b border-slate-100 pb-3">
          <h2 class="text-base font-bold text-slate-900">{{ t('settings.security.title') }}</h2>
          <p class="text-xs text-slate-500">{{ t('settings.security.subtitle') }}</p>
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
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">{{ t('settings.security.currentPasswordLabel') }}</label>
            <div class="relative flex items-center">
              <input
                v-model="passwordForm.current_password"
                :type="showCurrentPassword ? 'text' : 'password'"
                :placeholder="t('settings.security.currentPasswordPlaceholder')"
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
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">{{ t('settings.security.newPasswordLabel') }}</label>
              <div class="relative flex items-center">
                <input
                  v-model="passwordForm.password"
                  :type="showNewPassword ? 'text' : 'password'"
                  :placeholder="t('settings.security.newPasswordPlaceholder')"
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
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">{{ t('settings.security.confirmPasswordLabel') }}</label>
              <input
                v-model="passwordForm.password_confirmation"
                type="password"
                :placeholder="t('settings.security.confirmPasswordPlaceholder')"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-mono text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-600"
              />
            </div>
          </div>

          <div class="flex justify-end pt-2">
            <UiButton :loading="passwordLoading" @click="changePassword" :disabled="!passwordForm.current_password || !passwordForm.password">
              {{ t('settings.security.updatePasswordBtn') }}
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
            <h4 class="text-sm font-bold text-slate-900">{{ t('settings.security.rbacTitle') }}</h4>
            <p class="text-xs text-slate-500">{{ t('settings.security.rbacDesc') }}</p>
          </div>
        </div>
        <UiBadge variant="success" class="font-bold">{{ t('settings.security.rbacBadge') }}</UiBadge>
      </div>
    </div>

    <!-- 5. Email & Diagnostics Tab -->
    <div v-if="activeTab === 'email'" class="space-y-6">
      <!-- SMTP Health & Server Telemetry -->
      <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-6 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
          <div>
            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
              <Mail class="w-4 h-4 text-indigo-600" />
              {{ t('settings.email.title') }}
            </h2>
            <p class="text-xs text-slate-500">{{ t('settings.email.subtitle') }}</p>
          </div>
          <div class="flex items-center gap-2">
            <UiButton variant="outline" size="sm" :loading="emailLoading" @click="loadEmailDiagnostics">
              <RefreshCw class="w-3.5 h-3.5 mr-1" /> {{ t('settings.email.refreshBtn') }}
            </UiButton>
          </div>
        </div>

        <div v-if="emailLoading && !emailDiagnostics" class="p-8 flex justify-center">
          <UiSpinner size="md" />
        </div>

        <div v-else-if="emailDiagnostics" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-1">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">{{ t('settings.email.statusLabel') }}</span>
            <div class="flex items-center gap-1.5 pt-0.5">
              <span
                class="w-2.5 h-2.5 rounded-full"
                :class="emailDiagnostics.is_connected ? 'bg-emerald-500 animate-pulse' : 'bg-red-500'"
              />
              <span class="font-black text-sm" :class="emailDiagnostics.is_connected ? 'text-emerald-700' : 'text-red-700'">
                {{ emailDiagnostics.is_connected ? t('settings.email.connected') : t('settings.email.failed') }}
              </span>
            </div>
            <span class="text-[11px] text-slate-500 block">
              {{ t('settings.email.latency', { latency: emailDiagnostics.latency_ms }) }}
            </span>
          </div>

          <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-1">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">{{ t('settings.email.driverLabel') }}</span>
            <span class="font-bold text-slate-900 text-sm block uppercase">{{ emailDiagnostics.driver }}</span>
            <span class="text-[11px] text-slate-500 block font-mono">{{ t('settings.email.port', { port: emailDiagnostics.smtp_port }) }}</span>
          </div>

          <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-1">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">{{ t('settings.email.hostLabel') }}</span>
            <span class="font-bold text-slate-900 text-sm block font-mono truncate">{{ emailDiagnostics.smtp_host }}</span>
            <span class="text-[11px] text-slate-500 block">{{ t('settings.email.hostDesc') }}</span>
          </div>

          <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-1">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">{{ t('settings.email.fromLabel') }}</span>
            <span class="font-bold text-slate-900 text-sm block font-mono truncate">{{ emailDiagnostics.from_address }}</span>
            <span class="text-[11px] text-slate-500 block truncate">{{ emailDiagnostics.from_name }}</span>
          </div>
        </div>

        <!-- MailHog Web Inbox Link -->
        <div class="p-4 bg-blue-50/70 border border-blue-200 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-blue-900">
          <div class="flex items-center gap-2.5">
            <Sparkles class="w-5 h-5 text-blue-600 shrink-0" />
            <div>
              <strong class="block text-sm">{{ t('settings.email.mailhogTitle') }}</strong>
              <span>{{ t('settings.email.mailhogDesc') }}</span>
            </div>
          </div>
          <a
            :href="mailhogUrl"
            target="_blank"
            rel="noopener noreferrer"
            class="px-3.5 py-1.5 rounded-xl bg-blue-600 text-white font-bold inline-flex items-center gap-1.5 shrink-0 hover:bg-blue-700 transition-colors shadow-xs"
          >
            {{ t('settings.email.openMailhogBtn') }} <ExternalLink class="w-3.5 h-3.5" />
          </a>
        </div>
      </div>

      <!-- Live Test Email Dispatcher -->
      <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-5 shadow-xs">
        <div class="space-y-1 border-b border-slate-100 pb-3">
          <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
            <Send class="w-4 h-4 text-indigo-600" />
            {{ t('settings.email.testSectionTitle') }}
          </h2>
          <p class="text-xs text-slate-500">{{ t('settings.email.testSectionSubtitle') }}</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UiInput
            v-model="testEmailRecipient"
            :label="t('settings.email.recipientEmailLabel')"
            type="email"
            :placeholder="t('settings.email.recipientEmailPlaceholder')"
            required
          />
          <UiInput
            v-model="testEmailName"
            :label="t('settings.email.recipientNameLabel')"
            :placeholder="t('settings.email.recipientNamePlaceholder')"
          />
        </div>

        <div class="flex items-center justify-between pt-2">
          <div>
            <span v-if="testEmailResult" class="text-xs font-bold flex items-center gap-1.5" :class="testEmailResult.success ? 'text-emerald-600' : 'text-red-600'">
              <CheckCircle2 v-if="testEmailResult.success" class="w-4 h-4" />
              <AlertCircle v-else class="w-4 h-4" />
              {{ testEmailResult.message || (testEmailResult.success ? t('settings.email.delivered') : testEmailResult.error) }}
            </span>
          </div>
          <UiButton
            :loading="testEmailSending"
            @click="sendTestEmail"
            class="bg-indigo-600 hover:bg-indigo-500 text-white font-black"
          >
            <Send class="w-4 h-4 mr-1.5" /> {{ t('settings.email.sendTestBtn') }}
          </UiButton>
        </div>
      </div>
    </div>
  </div>
</template>
