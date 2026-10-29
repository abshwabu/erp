<script setup lang="ts">
import { ref, computed, markRaw } from 'vue'
import { useI18n } from 'vue-i18n'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { crmApi } from '@/api/crm'
import type { LeadForm, LeadFormType, LeadFormQuestion } from '@/types/crm'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiStat from '@/components/ui/UiStat.vue'
import UiSpinner from '@/components/ui/UiSpinner.vue'
import { useToast } from '@/composables/useToast'
import {
  Sparkles,
  Layers,
  Plus,
  Search,
  Copy,
  Code,
  ExternalLink,
  Eye,
  CheckCircle2,
  TrendingUp,
  Share2,
  Trash2,
  Edit,
  Globe,
  Radio,
  FileText,
  List,
  CircleDot,
  CheckSquare,
  Calendar,
  DollarSign,
  Tv,
} from '@lucide/vue'

const { t } = useI18n()
const queryClient = useQueryClient()
const toast = useToast()

const searchQuery = ref('')
const selectedSource = ref<string>('all')
const selectedType = ref<string>('all')
const isModalOpen = ref(false)
const isEmbedModalOpen = ref(false)
const editingForm = ref<LeadForm | null>(null)
const embedTargetForm = ref<LeadForm | null>(null)

const activeTab = ref<'details' | 'questions' | 'completion'>('details')

const defaultQuestions: LeadFormQuestion[] = [
  {
    id: 'q_service_interest',
    label: 'What service or solution are you interested in?',
    name: 'service_interest',
    type: 'select',
    required: true,
    options: ['Enterprise Software Consultation', 'Cloud & Digital Transformation', 'Dedicated Engineering Support', 'General Inquiry'],
    placeholder: 'Select a category',
  },
  {
    id: 'q_project_budget',
    label: 'What is your estimated project budget?',
    name: 'estimated_budget',
    type: 'radio',
    required: true,
    options: ['Under $5,000', '$5,000 - $20,000', '$20,000 - $50,000', '$50,000+'],
  },
  {
    id: 'q_timeframe',
    label: 'Target Implementation Timeline',
    name: 'timeline',
    type: 'radio',
    required: false,
    options: ['Immediate (Within 2 weeks)', '1 to 3 months', 'Exploring options / 3+ months'],
  },
]

const formState = ref({
  title: '',
  source: 'social_media',
  form_type: 'wizard' as LeadFormType,
  headline: '',
  description: '',
  default_priority: 'medium' as const,
  default_estimated_value: '',
  thank_you_title: 'Thank You for Reaching Out!',
  thank_you_message: 'Your inquiry has been logged. Our specialists will review your requirements and get back to you within 24 hours.',
  redirect_url: '',
  is_active: true,
  custom_questions: [] as LeadFormQuestion[],
})

// Queries
const { data: leadForms, isLoading } = useQuery({
  queryKey: ['crm', 'lead-forms'],
  queryFn: () => crmApi.getLeadForms().then((r) => r.data.data),
})

const filteredForms = computed(() => {
  let list = leadForms.value || []
  if (selectedSource.value !== 'all') {
    list = list.filter((f) => f.source === selectedSource.value)
  }
  if (selectedType.value !== 'all') {
    list = list.filter((f) => f.form_type === selectedType.value)
  }
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(
      (f) =>
        f.title.toLowerCase().includes(q) ||
        (f.headline || '').toLowerCase().includes(q) ||
        f.source.toLowerCase().includes(q)
    )
  }
  return list
})

// Stats
const stats = computed(() => {
  const list = leadForms.value || []
  const totalSubmissions = list.reduce((sum, f) => sum + Number(f.submissions_count || 0), 0)
  const totalViews = list.reduce((sum, f) => sum + Number(f.views_count || 0), 0)
  const avgConversion = totalViews > 0 ? roundNumber((totalSubmissions / totalViews) * 100, 1) : 0

  return [
    {
      label: t('crm.leadForms.kpis.activeForms'),
      value: list.filter((f) => f.is_active).length,
      icon: markRaw(Layers),
    },
    {
      label: t('crm.leadForms.kpis.totalSubmissions'),
      value: totalSubmissions,
      icon: markRaw(CheckCircle2),
    },
    {
      label: t('crm.leadForms.kpis.totalViews'),
      value: totalViews,
      icon: markRaw(Eye),
    },
    {
      label: t('crm.leadForms.kpis.overallConversion'),
      value: `${avgConversion}%`,
      icon: markRaw(TrendingUp),
    },
  ]
})

function roundNumber(num: number, dec: number) {
  return Number(Math.round(Number(num + 'e' + dec)) + 'e-' + dec)
}

// Question types palette
const questionTypes = computed(() => [
  { label: t('crm.leadForms.questionTypes.text'), value: 'text', icon: FileText },
  { label: t('crm.leadForms.questionTypes.textarea'), value: 'textarea', icon: FileText },
  { label: t('crm.leadForms.questionTypes.number'), value: 'number', icon: FileText },
  { label: t('crm.leadForms.questionTypes.select'), value: 'select', icon: List },
  { label: t('crm.leadForms.questionTypes.radio'), value: 'radio', icon: CircleDot },
  { label: t('crm.leadForms.questionTypes.checkbox'), value: 'checkbox', icon: CheckSquare },
  { label: t('crm.leadForms.questionTypes.date'), value: 'date', icon: Calendar },
])

const addQuestion = (type: string = 'text') => {
  const count = formState.value.custom_questions.length + 1
  formState.value.custom_questions.push({
    id: `q_${Date.now()}`,
    label: `Question ${count}`,
    name: `field_${count}`,
    type: type as any,
    required: false,
    placeholder: '',
    options: ['select', 'radio', 'checkbox'].includes(type) ? ['Option A', 'Option B', 'Option C'] : undefined,
  })
}

const removeQuestion = (index: number) => {
  formState.value.custom_questions.splice(index, 1)
}

const addOption = (q: LeadFormQuestion) => {
  if (!q.options) q.options = []
  q.options.push(`Option ${q.options.length + 1}`)
}

const removeOption = (q: LeadFormQuestion, idx: number) => {
  if (q.options) {
    q.options.splice(idx, 1)
  }
}

// Mutations
const saveMutation = useMutation({
  mutationFn: (payload: any) => {
    if (editingForm.value) {
      return crmApi.updateLeadForm(editingForm.value.id, payload)
    }
    return crmApi.createLeadForm(payload)
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['crm', 'lead-forms'] })
    isModalOpen.value = false
    toast.success(editingForm.value ? t('crm.leadForms.messages.updated') : t('crm.leadForms.messages.created'))
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || t('crm.leadForms.messages.failedSave'))
  },
})

const deleteMutation = useMutation({
  mutationFn: (id: string) => crmApi.deleteLeadForm(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['crm', 'lead-forms'] })
    toast.success(t('crm.leadForms.messages.deleted'))
  },
})

const openCreateModal = () => {
  editingForm.value = null
  formState.value = {
    title: '',
    source: 'social_media',
    form_type: 'wizard',
    headline: 'Tell us about your project & requirements',
    description: 'Answer a few quick questions to get a customized proposal from our team.',
    default_priority: 'medium',
    default_estimated_value: '',
    thank_you_title: 'Thank You for Reaching Out!',
    thank_you_message: 'Your inquiry has been logged. Our specialists will review your requirements and get back to you within 24 hours.',
    redirect_url: '',
    is_active: true,
    custom_questions: JSON.parse(JSON.stringify(defaultQuestions)),
  }
  activeTab.value = 'details'
  isModalOpen.value = true
}

const openEditModal = (form: LeadForm) => {
  editingForm.value = form
  formState.value = {
    title: form.title,
    source: form.source || 'social_media',
    form_type: form.form_type || 'wizard',
    headline: form.headline || '',
    description: form.description || '',
    default_priority: form.default_priority || 'medium',
    default_estimated_value: form.default_estimated_value != null ? String(form.default_estimated_value) : '',
    thank_you_title: form.thank_you_title || 'Thank You!',
    thank_you_message: form.thank_you_message || '',
    redirect_url: form.redirect_url || '',
    is_active: form.is_active,
    custom_questions: Array.isArray(form.custom_questions) ? JSON.parse(JSON.stringify(form.custom_questions)) : [],
  }
  activeTab.value = 'details'
  isModalOpen.value = true
}

const handleSave = () => {
  if (!formState.value.title) {
    toast.error(t('crm.leadForms.messages.titleRequired'))
    activeTab.value = 'details'
    return
  }
  saveMutation.mutate({
    ...formState.value,
    default_estimated_value: formState.value.default_estimated_value ? Number(formState.value.default_estimated_value) : null,
  })
}

const copyPublicLink = (form: LeadForm) => {
  const path = form.form_type === 'wizard' ? `/leads/wizard/${form.slug || form.id}` : `/leads/form/${form.slug || form.id}`
  const url = `${window.location.origin}${path}`
  navigator.clipboard.writeText(url)
  toast.success(t('crm.leadForms.messages.linkCopied'))
}

const openEmbedModal = (form: LeadForm) => {
  embedTargetForm.value = form
  isEmbedModalOpen.value = true
}

const getEmbedCode = (form: LeadForm) => {
  const url = `${window.location.origin}/embed/lead-form/${form.slug || form.id}`
  return `<iframe\n  src="${url}"\n  width="100%"\n  height="650"\n  frameborder="0"\n  style="border: none; border-radius: 12px; overflow: hidden;"\n></iframe>`
}

const copyEmbedCode = () => {
  if (embedTargetForm.value) {
    navigator.clipboard.writeText(getEmbedCode(embedTargetForm.value))
    toast.success(t('crm.leadForms.messages.embedCopied'))
  }
}

const getSourceBadge = (source: string) => {
  switch (source) {
    case 'agency': return { label: t('crm.leads.sources.agency'), variant: 'purple' as const }
    case 'social_media': return { label: t('crm.leads.sources.social_media'), variant: 'info' as const }
    case 'google_ads': return { label: t('crm.leads.sources.google_ads'), variant: 'warning' as const }
    case 'website': return { label: t('crm.leads.sources.website'), variant: 'success' as const }
    case 'event': return { label: t('crm.leads.sources.event'), variant: 'default' as const }
    default: return { label: t(`crm.leads.sources.${source}`, source.replace('_', ' ')), variant: 'default' as const }
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">{{ t('crm.leadForms.title') }}</h1>
        <p class="text-xs sm:text-sm text-slate-500">
          {{ t('crm.leadForms.subtitle') }}
        </p>
      </div>
      <UiButton @click="openCreateModal">
        <Plus class="w-4 h-4 mr-2" /> {{ t('crm.leadForms.createForm') }}
      </UiButton>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <UiStat
        v-for="stat in stats"
        :key="stat.label"
        :label="stat.label"
        :value="stat.value"
        :icon="stat.icon"
      />
    </div>

    <!-- Filters & Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
      <div class="flex flex-wrap items-center gap-1.5">
        <button
          v-for="sc in ['all', 'agency', 'social_media', 'google_ads', 'website', 'referral', 'event']"
          :key="sc"
          type="button"
          @click="selectedSource = sc"
          class="px-3 py-1.5 rounded-xl text-xs font-bold capitalize transition-all cursor-pointer"
          :class="selectedSource === sc ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
        >
          {{ sc === 'all' ? `${t('crm.leadForms.filterChannels')} (${leadForms?.length || 0})` : t(`crm.leads.sources.${sc}`, sc.replace('_', ' ')) }}
        </button>
      </div>

      <UiInput
        v-model="searchQuery"
        :placeholder="t('crm.leadForms.filterPlaceholder')"
        size="sm"
        class="w-full sm:w-60"
      >
        <template #prefix><Search class="w-3.5 h-3.5 text-slate-400" /></template>
      </UiInput>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="p-16 flex justify-center">
      <UiSpinner size="lg" />
    </div>

    <!-- Forms Grid -->
    <div v-else-if="filteredForms.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <div
        v-for="form in filteredForms"
        :key="form.id"
        class="bg-white rounded-2xl border border-slate-200 hover:border-slate-300 p-5 shadow-xs flex flex-col justify-between transition-all space-y-4 group"
      >
        <div class="space-y-3">
          <div class="flex items-start justify-between gap-2">
            <div class="space-y-1">
              <div class="flex items-center gap-2">
                <UiBadge :variant="getSourceBadge(form.source).variant" class="text-[10px] font-bold">
                  {{ getSourceBadge(form.source).label }}
                </UiBadge>
                <UiBadge
                  :variant="form.form_type === 'wizard' ? 'purple' : 'info'"
                  class="text-[10px] font-bold uppercase"
                >
                  <Sparkles v-if="form.form_type === 'wizard'" class="w-2.5 h-2.5 mr-1" />
                  {{ form.form_type === 'wizard' ? t('crm.leadForms.card.wizardType') : t('crm.leadForms.card.embedType') }}
                </UiBadge>
              </div>

              <h3 class="font-bold text-slate-900 text-base leading-snug group-hover:text-primary-700 transition-colors">
                {{ form.title }}
              </h3>
            </div>

            <span
              class="w-2.5 h-2.5 rounded-full shrink-0 mt-1"
              :class="form.is_active ? 'bg-emerald-500 shadow-xs' : 'bg-slate-300'"
              :title="form.is_active ? 'Active' : 'Inactive'"
            ></span>
          </div>

          <p v-if="form.headline" class="text-xs text-slate-600 font-medium line-clamp-2">
            {{ form.headline }}
          </p>

          <!-- Performance Metrics -->
          <div class="grid grid-cols-3 gap-2 p-3 bg-slate-50 rounded-xl text-center border border-slate-100 text-xs">
            <div>
              <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ t('crm.leadForms.card.views') }}</span>
              <p class="font-mono font-bold text-slate-900 mt-0.5">{{ form.views_count }}</p>
            </div>
            <div>
              <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ t('crm.leadForms.card.submissions') }}</span>
              <p class="font-mono font-bold text-primary-700 mt-0.5">{{ form.submissions_count }}</p>
            </div>
            <div>
              <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ t('crm.leadForms.card.conversion') }}</span>
              <p class="font-mono font-bold text-emerald-700 mt-0.5">{{ form.conversion_rate }}%</p>
            </div>
          </div>
        </div>

        <!-- Card Actions -->
        <div class="pt-4 border-t border-slate-100 space-y-2.5">
          <div class="flex items-center justify-between text-xs">
            <button
              type="button"
              @click="copyPublicLink(form)"
              class="text-xs font-bold text-slate-700 hover:text-slate-900 inline-flex items-center gap-1.5 bg-slate-100 hover:bg-slate-200 px-2.5 py-1.5 rounded-lg transition-colors cursor-pointer"
            >
              <Copy class="w-3.5 h-3.5" /> {{ t('crm.leadForms.card.shareLink') }}
            </button>

            <button
              type="button"
              @click="openEmbedModal(form)"
              class="text-xs font-bold text-indigo-600 hover:text-indigo-700 inline-flex items-center gap-1 bg-indigo-50 hover:bg-indigo-100 px-2.5 py-1.5 rounded-lg transition-colors cursor-pointer"
            >
              <Code class="w-3.5 h-3.5" /> {{ t('crm.leadForms.card.embedCode') }}
            </button>
          </div>

          <div class="flex items-center justify-between pt-1 text-xs">
            <a
              :href="form.form_type === 'wizard' ? `/leads/wizard/${form.slug || form.id}` : `/leads/form/${form.slug || form.id}`"
              target="_blank"
              class="text-slate-500 hover:text-blue-600 font-semibold inline-flex items-center gap-1 transition-colors"
            >
              <ExternalLink class="w-3.5 h-3.5" /> {{ t('crm.leadForms.card.testLive') }}
            </a>

            <div class="flex items-center gap-1">
              <UiButton variant="ghost" size="sm" @click="openEditModal(form)" :title="t('crm.leadForms.modal.editTitle')">
                <Edit class="w-3.5 h-3.5 text-slate-600" />
              </UiButton>
              <UiButton
                variant="ghost"
                size="sm"
                class="text-red-500 hover:text-red-700 hover:bg-red-50"
                @click="deleteMutation.mutate(form.id)"
                :title="t('crm.common.delete')"
              >
                <Trash2 class="w-3.5 h-3.5" />
              </UiButton>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="bg-white rounded-2xl border border-slate-200 p-16 text-center space-y-4">
      <Sparkles class="w-12 h-12 mx-auto text-slate-300" />
      <div>
        <h3 class="font-bold text-slate-900 text-base">{{ t('crm.leadForms.emptyTitle') }}</h3>
        <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">
          {{ t('crm.leadForms.emptyText') }}
        </p>
      </div>
      <UiButton size="sm" @click="openCreateModal">
        <Plus class="w-4 h-4 mr-1.5" /> {{ t('crm.leadForms.createFirst') }}
      </UiButton>
    </div>

    <!-- Create / Edit Form Modal -->
    <UiModal v-model="isModalOpen" :title="editingForm ? t('crm.leadForms.modal.editTitle') : t('crm.leadForms.modal.createTitle')" size="xl">
      <div class="space-y-5">
        <!-- Tabs -->
        <div class="flex items-center gap-2 border-b border-slate-200">
          <button
            type="button"
            @click="activeTab = 'details'"
            class="px-4 py-2.5 text-xs font-bold border-b-2 -mb-px transition-colors flex items-center gap-1.5 cursor-pointer"
            :class="activeTab === 'details' ? 'border-primary-600 text-primary-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
          >
            <span>{{ t('crm.leadForms.modal.tab1') }}</span>
          </button>
          <button
            type="button"
            @click="activeTab = 'questions'"
            class="px-4 py-2.5 text-xs font-bold border-b-2 -mb-px transition-colors flex items-center gap-1.5 cursor-pointer"
            :class="activeTab === 'questions' ? 'border-primary-600 text-primary-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
          >
            <span>{{ t('crm.leadForms.modal.tab2') }} ({{ formState.custom_questions.length }})</span>
          </button>
          <button
            type="button"
            @click="activeTab = 'completion'"
            class="px-4 py-2.5 text-xs font-bold border-b-2 -mb-px transition-colors flex items-center gap-1.5 cursor-pointer"
            :class="activeTab === 'completion' ? 'border-primary-600 text-primary-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
          >
            <span>{{ t('crm.leadForms.modal.tab3') }}</span>
          </button>
        </div>

        <!-- Tab 1: Format & Source Setup -->
        <div v-show="activeTab === 'details'" class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <UiInput v-model="formState.title" :label="t('crm.leadForms.modal.internalTitle')" :placeholder="t('crm.leadForms.modal.internalPlaceholder')" required />
            <UiSelect
              v-model="formState.source"
              :label="t('crm.leadForms.modal.channelSource')"
              :options="[
                { label: t('crm.leads.sources.agency'), value: 'agency' },
                { label: t('crm.leads.sources.social_media'), value: 'social_media' },
                { label: t('crm.leads.sources.google_ads'), value: 'google_ads' },
                { label: t('crm.leads.sources.website'), value: 'website' },
                { label: t('crm.leads.sources.event'), value: 'event' },
                { label: t('crm.leads.sources.other'), value: 'other' },
              ]"
            />
          </div>

          <!-- Form Type Picker -->
          <div class="space-y-1.5">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">{{ t('crm.leadForms.modal.formType') }}</label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div
                @click="formState.form_type = 'wizard'"
                class="p-4 rounded-2xl border-2 transition-all cursor-pointer space-y-1"
                :class="formState.form_type === 'wizard' ? 'border-primary-600 bg-primary-50/40' : 'border-slate-200 bg-white hover:border-slate-300'"
              >
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                    <Sparkles class="w-4 h-4 text-primary-600" />
                    {{ t('crm.leadForms.modal.wizardTitle') }}
                  </span>
                  <input type="radio" :checked="formState.form_type === 'wizard'" class="text-primary-600" />
                </div>
                <p class="text-[11px] text-slate-500 leading-relaxed">
                  {{ t('crm.leadForms.modal.wizardDesc') }}
                </p>
              </div>

              <div
                @click="formState.form_type = 'classic_embed'"
                class="p-4 rounded-2xl border-2 transition-all cursor-pointer space-y-1"
                :class="formState.form_type === 'classic_embed' ? 'border-indigo-600 bg-indigo-50/40' : 'border-slate-200 bg-white hover:border-slate-300'"
              >
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                    <Code class="w-4 h-4 text-indigo-600" />
                    {{ t('crm.leadForms.modal.embedTitle') }}
                  </span>
                  <input type="radio" :checked="formState.form_type === 'classic_embed'" class="text-indigo-600" />
                </div>
                <p class="text-[11px] text-slate-500 leading-relaxed">
                  {{ t('crm.leadForms.modal.embedDesc') }}
                </p>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <UiInput v-model="formState.headline" :label="t('crm.leadForms.modal.candidateHeading')" :placeholder="t('crm.leadForms.modal.headingPlaceholder')" />
            <UiInput v-model="formState.default_estimated_value" :label="t('crm.leadForms.modal.defaultEstValue')" type="number" placeholder="Optional" />
          </div>

          <div class="space-y-1">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">{{ t('crm.leadForms.modal.introSubtitle') }}</label>
            <textarea
              v-model="formState.description"
              rows="2"
              :placeholder="t('crm.leadForms.modal.introPlaceholder')"
              class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500"
            ></textarea>
          </div>
        </div>

        <!-- Tab 2: Custom Question Builder -->
        <div v-show="activeTab === 'questions'" class="space-y-4">
          <div class="flex items-center justify-between p-3.5 bg-slate-50 rounded-xl border border-slate-200">
            <p class="text-xs text-slate-600">
              {{ t('crm.leadForms.modal.basicFieldsNote') }}
            </p>
          </div>

          <!-- Add Question Palette -->
          <div class="flex items-center gap-1.5 flex-wrap">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500 mr-1">{{ t('crm.leadForms.modal.addQuestionLabel') }}</span>
            <button
              v-for="qt in questionTypes"
              :key="qt.value"
              type="button"
              @click="addQuestion(qt.value)"
              class="px-2.5 py-1.5 bg-white border border-slate-200 hover:border-primary-400 hover:bg-primary-50/50 text-slate-700 text-xs font-bold rounded-lg transition-all flex items-center gap-1.5 shadow-2xs cursor-pointer"
            >
              <component :is="qt.icon" class="w-3.5 h-3.5 text-primary-600" />
              <span>+ {{ qt.label }}</span>
            </button>
          </div>

          <!-- Questions List -->
          <div class="space-y-3.5 max-h-[460px] overflow-y-auto pr-1">
            <div
              v-for="(q, idx) in formState.custom_questions"
              :key="q.id"
              class="p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-3"
            >
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <span class="w-6 h-6 rounded-full bg-slate-200 text-slate-700 font-bold text-xs flex items-center justify-center">
                    {{ idx + 1 }}
                  </span>
                  <UiBadge variant="default" class="text-[10px] uppercase font-bold">
                    {{ t(`crm.leadForms.questionTypes.${q.type}`, q.type) }}
                  </UiBadge>
                </div>

                <div class="flex items-center gap-3">
                  <label class="flex items-center gap-1.5 text-xs text-slate-700 font-semibold cursor-pointer">
                    <input
                      type="checkbox"
                      v-model="q.required"
                      class="h-3.5 w-3.5 rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                    />
                    <span>{{ t('crm.leadForms.modal.requiredCheckbox') }}</span>
                  </label>
                  <button
                    type="button"
                    @click="removeQuestion(idx)"
                    class="text-slate-400 hover:text-red-600 p-1 transition-colors cursor-pointer"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <UiInput v-model="q.label" :label="t('crm.leadForms.modal.questionPrompt')" :placeholder="t('crm.leadForms.modal.promptPlaceholder')" size="sm" required />
                <UiInput v-model="q.placeholder" :label="t('crm.leadForms.modal.hintPlaceholder')" placeholder="Optional" size="sm" />
              </div>

              <!-- Options List for Select/Radio/Checkbox -->
              <div v-if="['select', 'radio', 'checkbox'].includes(q.type)" class="p-3 bg-white border border-slate-200 rounded-xl space-y-2">
                <div class="flex items-center justify-between">
                  <label class="text-xs font-bold uppercase tracking-wider text-slate-600">{{ t('crm.leadForms.modal.choiceOptions') }}</label>
                  <button
                    type="button"
                    @click="addOption(q)"
                    class="text-xs text-primary-600 hover:text-primary-700 font-bold inline-flex items-center gap-1 hover:underline cursor-pointer"
                  >
                    <Plus class="w-3 h-3" /> {{ t('crm.leadForms.modal.addOption') }}
                  </button>
                </div>

                <div class="space-y-1.5">
                  <div
                    v-for="(opt, optIdx) in q.options"
                    :key="optIdx"
                    class="flex items-center gap-2"
                  >
                    <span class="text-slate-400 text-xs font-mono">•</span>
                    <input
                      v-model="q.options![optIdx]"
                      class="flex-1 px-2.5 py-1 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500"
                      placeholder="Option label"
                    />
                    <button
                      v-if="(q.options?.length || 0) > 1"
                      type="button"
                      @click="removeOption(q, optIdx)"
                      class="text-slate-400 hover:text-red-500 p-1 cursor-pointer"
                    >
                      ✕
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="!formState.custom_questions.length" class="p-8 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200 text-slate-400 text-xs">
              {{ t('crm.leadForms.modal.noCustomQuestions') }}
            </div>
          </div>
        </div>

        <!-- Tab 3: Thank You & Completion Settings -->
        <div v-show="activeTab === 'completion'" class="space-y-4">
          <UiInput v-model="formState.thank_you_title" :label="t('crm.leadForms.modal.thankYouTitle')" placeholder="Thank You for Reaching Out!" />

          <div class="space-y-1">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">{{ t('crm.leadForms.modal.thankYouMessage') }}</label>
            <textarea
              v-model="formState.thank_you_message"
              rows="3"
              placeholder="Your inquiry has been received. Our team will contact you shortly..."
              class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500"
            ></textarea>
          </div>

          <UiInput v-model="formState.redirect_url" :label="t('crm.leadForms.modal.redirectUrl')" placeholder="https://yourwebsite.com/thank-you" />

          <div class="flex items-center gap-2 pt-2">
            <input
              id="form_is_active"
              v-model="formState.is_active"
              type="checkbox"
              class="h-4 w-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500 cursor-pointer"
            />
            <label for="form_is_active" class="text-xs text-slate-700 font-semibold cursor-pointer">
              {{ t('crm.leadForms.modal.activeCheckbox') }}
            </label>
          </div>
        </div>

        <!-- Footer Navigation -->
        <div class="flex items-center justify-between pt-4 border-t border-slate-100">
          <div class="flex items-center gap-2">
            <UiButton
              v-if="activeTab !== 'details'"
              variant="outline"
              size="sm"
              type="button"
              @click="activeTab = activeTab === 'completion' ? 'questions' : 'details'"
            >
              {{ t('crm.leadForms.modal.prevBtn') }}
            </UiButton>
            <UiButton
              v-if="activeTab !== 'completion'"
              variant="outline"
              size="sm"
              type="button"
              @click="activeTab = activeTab === 'details' ? 'questions' : 'completion'"
            >
              {{ t('crm.leadForms.modal.nextBtn') }}
            </UiButton>
          </div>

          <div class="flex items-center gap-2">
            <UiButton variant="outline" type="button" @click="isModalOpen = false">{{ t('crm.common.cancel') }}</UiButton>
            <UiButton :loading="saveMutation.isPending.value" @click="handleSave">
              {{ editingForm ? t('crm.leadForms.modal.saveBtn') : t('crm.leadForms.modal.publishBtn') }}
            </UiButton>
          </div>
        </div>
      </div>
    </UiModal>

    <!-- Embed Snippet Modal -->
    <UiModal v-model="isEmbedModalOpen" :title="t('crm.leadForms.embedModal.title')" size="md">
      <div v-if="embedTargetForm" class="space-y-4">
        <p class="text-xs text-slate-600">
          {{ t('crm.leadForms.embedModal.text') }}
        </p>

        <div class="p-3.5 bg-slate-900 rounded-xl text-slate-100 font-mono text-xs overflow-x-auto select-all">
          <pre>{{ getEmbedCode(embedTargetForm) }}</pre>
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <UiButton variant="outline" size="sm" @click="isEmbedModalOpen = false">{{ t('crm.leadForms.embedModal.close') }}</UiButton>
          <UiButton size="sm" @click="copyEmbedCode">
            <Copy class="w-3.5 h-3.5 mr-1.5" /> {{ t('crm.leadForms.embedModal.copyBtn') }}
          </UiButton>
        </div>
      </div>
    </UiModal>
  </div>
</template>
