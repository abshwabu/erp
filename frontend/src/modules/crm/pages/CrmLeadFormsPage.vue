<script setup lang="ts">
import { ref, computed, markRaw } from 'vue'
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
      label: 'Active Lead Forms',
      value: list.filter((f) => f.is_active).length,
      icon: markRaw(Layers),
    },
    {
      label: 'Total Inbound Submissions',
      value: totalSubmissions,
      icon: markRaw(CheckCircle2),
    },
    {
      label: 'Total Page Views',
      value: totalViews,
      icon: markRaw(Eye),
    },
    {
      label: 'Overall Conversion Rate',
      value: `${avgConversion}%`,
      icon: markRaw(TrendingUp),
    },
  ]
})

function roundNumber(num: number, dec: number) {
  return Number(Math.round(Number(num + 'e' + dec)) + 'e-' + dec)
}

// Question types palette
const questionTypes = [
  { label: 'Short Text', value: 'text', icon: FileText },
  { label: 'Long Paragraph', value: 'textarea', icon: FileText },
  { label: 'Numeric Value', value: 'number', icon: FileText },
  { label: 'Dropdown Select', value: 'select', icon: List },
  { label: 'Single Choice (Radio)', value: 'radio', icon: CircleDot },
  { label: 'Multi-Select (Checkbox)', value: 'checkbox', icon: CheckSquare },
  { label: 'Date', value: 'date', icon: Calendar },
]

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
    toast.success(editingForm.value ? 'Lead form updated' : 'New lead capture form created')
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || 'Failed to save lead form')
  },
})

const deleteMutation = useMutation({
  mutationFn: (id: string) => crmApi.deleteLeadForm(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['crm', 'lead-forms'] })
    toast.success('Lead form deleted')
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
    toast.error('Please provide a form title')
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
  toast.success('Public lead form link copied to clipboard!')
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
    toast.success('HTML embed snippet copied to clipboard!')
  }
}

const getSourceBadge = (source: string) => {
  switch (source) {
    case 'agency': return { label: 'Agency Partner', variant: 'purple' as const }
    case 'social_media': return { label: 'Social Media Campaign', variant: 'info' as const }
    case 'google_ads': return { label: 'Google / Paid Ads', variant: 'warning' as const }
    case 'website': return { label: 'Website Inbound', variant: 'success' as const }
    case 'event': return { label: 'Event / Trade Show', variant: 'default' as const }
    default: return { label: source.replace('_', ' '), variant: 'default' as const }
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Lead Forms & Inbound Channels</h1>
        <p class="text-xs sm:text-sm text-slate-500">
          Build interactive step-by-step lead wizards (for Agency & Social campaigns) and embeddable forms for websites.
        </p>
      </div>
      <UiButton @click="openCreateModal">
        <Plus class="w-4 h-4 mr-2" /> Create Lead Form
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
          {{ sc === 'all' ? `All Channels (${leadForms?.length || 0})` : sc.replace('_', ' ') }}
        </button>
      </div>

      <UiInput
        v-model="searchQuery"
        placeholder="Filter forms..."
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
                  {{ form.form_type === 'wizard' ? 'Step-by-Step Wizard' : 'Embed Form' }}
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
              <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Views</span>
              <p class="font-mono font-bold text-slate-900 mt-0.5">{{ form.views_count }}</p>
            </div>
            <div>
              <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Submissions</span>
              <p class="font-mono font-bold text-primary-700 mt-0.5">{{ form.submissions_count }}</p>
            </div>
            <div>
              <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Conversion</span>
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
              <Copy class="w-3.5 h-3.5" /> Share Form Link
            </button>

            <button
              type="button"
              @click="openEmbedModal(form)"
              class="text-xs font-bold text-indigo-600 hover:text-indigo-700 inline-flex items-center gap-1 bg-indigo-50 hover:bg-indigo-100 px-2.5 py-1.5 rounded-lg transition-colors cursor-pointer"
            >
              <Code class="w-3.5 h-3.5" /> Embed Code
            </button>
          </div>

          <div class="flex items-center justify-between pt-1 text-xs">
            <a
              :href="form.form_type === 'wizard' ? `/leads/wizard/${form.slug || form.id}` : `/leads/form/${form.slug || form.id}`"
              target="_blank"
              class="text-slate-500 hover:text-blue-600 font-semibold inline-flex items-center gap-1 transition-colors"
            >
              <ExternalLink class="w-3.5 h-3.5" /> Test Live Form
            </a>

            <div class="flex items-center gap-1">
              <UiButton variant="ghost" size="sm" @click="openEditModal(form)" title="Edit Form">
                <Edit class="w-3.5 h-3.5 text-slate-600" />
              </UiButton>
              <UiButton
                variant="ghost"
                size="sm"
                class="text-red-500 hover:text-red-700 hover:bg-red-50"
                @click="deleteMutation.mutate(form.id)"
                title="Delete Form"
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
        <h3 class="font-bold text-slate-900 text-base">No lead intake forms found</h3>
        <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">
          Create customized step-by-step lead wizards or website embed forms to automate prospect capture across multiple channels.
        </p>
      </div>
      <UiButton size="sm" @click="openCreateModal">
        <Plus class="w-4 h-4 mr-1.5" /> Create First Lead Form
      </UiButton>
    </div>

    <!-- Create / Edit Form Modal -->
    <UiModal v-model="isModalOpen" :title="editingForm ? 'Edit Lead Intake Form' : 'Create Lead Capture Form'" size="xl">
      <div class="space-y-5">
        <!-- Tabs -->
        <div class="flex items-center gap-2 border-b border-slate-200">
          <button
            type="button"
            @click="activeTab = 'details'"
            class="px-4 py-2.5 text-xs font-bold border-b-2 -mb-px transition-colors flex items-center gap-1.5 cursor-pointer"
            :class="activeTab === 'details' ? 'border-primary-600 text-primary-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
          >
            <span>1. Channel & Form Format</span>
          </button>
          <button
            type="button"
            @click="activeTab = 'questions'"
            class="px-4 py-2.5 text-xs font-bold border-b-2 -mb-px transition-colors flex items-center gap-1.5 cursor-pointer"
            :class="activeTab === 'questions' ? 'border-primary-600 text-primary-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
          >
            <span>2. Dynamic Questions ({{ formState.custom_questions.length }})</span>
          </button>
          <button
            type="button"
            @click="activeTab = 'completion'"
            class="px-4 py-2.5 text-xs font-bold border-b-2 -mb-px transition-colors flex items-center gap-1.5 cursor-pointer"
            :class="activeTab === 'completion' ? 'border-primary-600 text-primary-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
          >
            <span>3. Thank You & Next Steps</span>
          </button>
        </div>

        <!-- Tab 1: Format & Source Setup -->
        <div v-show="activeTab === 'details'" class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <UiInput v-model="formState.title" label="Internal Form Title" placeholder="e.g. Instagram Ad Campaign Intake" required />
            <UiSelect
              v-model="formState.source"
              label="Lead Channel / Source"
              :options="[
                { label: 'Agency Partner Referral', value: 'agency' },
                { label: 'Social Media Campaign (IG, TikTok, FB)', value: 'social_media' },
                { label: 'Google / Paid Ads', value: 'google_ads' },
                { label: 'Website Inbound / Landing Page', value: 'website' },
                { label: 'Event / Trade Show', value: 'event' },
                { label: 'Other', value: 'other' },
              ]"
            />
          </div>

          <!-- Form Type Picker -->
          <div class="space-y-1.5">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Form Experience Type</label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div
                @click="formState.form_type = 'wizard'"
                class="p-4 rounded-2xl border-2 transition-all cursor-pointer space-y-1"
                :class="formState.form_type === 'wizard' ? 'border-primary-600 bg-primary-50/40' : 'border-slate-200 bg-white hover:border-slate-300'"
              >
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                    <Sparkles class="w-4 h-4 text-primary-600" />
                    Interactive Step-by-Step Wizard
                  </span>
                  <input type="radio" :checked="formState.form_type === 'wizard'" class="text-primary-600" />
                </div>
                <p class="text-[11px] text-slate-500 leading-relaxed">
                  Engaging full-screen experience. Questions appear one-by-one with animated progress. Ideal for social media & agencies.
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
                    Standard / Embeddable Form
                  </span>
                  <input type="radio" :checked="formState.form_type === 'classic_embed'" class="text-indigo-600" />
                </div>
                <p class="text-[11px] text-slate-500 leading-relaxed">
                  Classic structured form with copyable iframe embed code. Can be placed directly into external websites & landing pages.
                </p>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <UiInput v-model="formState.headline" label="Candidate Heading / Title" placeholder="Tell us about your project" />
            <UiInput v-model="formState.default_estimated_value" label="Default Est. Value ($)" type="number" placeholder="Optional" />
          </div>

          <div class="space-y-1">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Introductory Subtitle / Instructions</label>
            <textarea
              v-model="formState.description"
              rows="2"
              placeholder="Provide a brief welcome message or instructions..."
              class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500"
            ></textarea>
          </div>
        </div>

        <!-- Tab 2: Custom Question Builder -->
        <div v-show="activeTab === 'questions'" class="space-y-4">
          <div class="flex items-center justify-between p-3.5 bg-slate-50 rounded-xl border border-slate-200">
            <p class="text-xs text-slate-600">
              Basic contact fields (<strong>Full Name, Email, Phone, Company</strong>) are captured automatically. Add customized questions below.
            </p>
          </div>

          <!-- Add Question Palette -->
          <div class="flex items-center gap-1.5 flex-wrap">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500 mr-1">Add Question:</span>
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
                    {{ q.type }}
                  </UiBadge>
                </div>

                <div class="flex items-center gap-3">
                  <label class="flex items-center gap-1.5 text-xs text-slate-700 font-semibold cursor-pointer">
                    <input
                      type="checkbox"
                      v-model="q.required"
                      class="h-3.5 w-3.5 rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                    />
                    <span>Required</span>
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
                <UiInput v-model="q.label" label="Question Prompt" placeholder="e.g. What is your estimated timeline?" size="sm" required />
                <UiInput v-model="q.placeholder" label="Placeholder / Hint" placeholder="Optional" size="sm" />
              </div>

              <!-- Options List for Select/Radio/Checkbox -->
              <div v-if="['select', 'radio', 'checkbox'].includes(q.type)" class="p-3 bg-white border border-slate-200 rounded-xl space-y-2">
                <div class="flex items-center justify-between">
                  <label class="text-xs font-bold uppercase tracking-wider text-slate-600">Choice Options</label>
                  <button
                    type="button"
                    @click="addOption(q)"
                    class="text-xs text-primary-600 hover:text-primary-700 font-bold inline-flex items-center gap-1 hover:underline cursor-pointer"
                  >
                    <Plus class="w-3 h-3" /> Add Option
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
              No custom questions added yet.
            </div>
          </div>
        </div>

        <!-- Tab 3: Thank You & Completion Settings -->
        <div v-show="activeTab === 'completion'" class="space-y-4">
          <UiInput v-model="formState.thank_you_title" label="Thank You Title" placeholder="Thank You for Reaching Out!" />

          <div class="space-y-1">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Thank You Message</label>
            <textarea
              v-model="formState.thank_you_message"
              rows="3"
              placeholder="Your inquiry has been received. Our team will contact you shortly..."
              class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500"
            ></textarea>
          </div>

          <UiInput v-model="formState.redirect_url" label="Auto-Redirect URL (Optional)" placeholder="https://yourwebsite.com/thank-you" />

          <div class="flex items-center gap-2 pt-2">
            <input
              id="form_is_active"
              v-model="formState.is_active"
              type="checkbox"
              class="h-4 w-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500 cursor-pointer"
            />
            <label for="form_is_active" class="text-xs text-slate-700 font-semibold cursor-pointer">
              Form is active and accepting new lead submissions
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
              ← Previous
            </UiButton>
            <UiButton
              v-if="activeTab !== 'completion'"
              variant="outline"
              size="sm"
              type="button"
              @click="activeTab = activeTab === 'details' ? 'questions' : 'completion'"
            >
              Next Step →
            </UiButton>
          </div>

          <div class="flex items-center gap-2">
            <UiButton variant="outline" type="button" @click="isModalOpen = false">Cancel</UiButton>
            <UiButton :loading="saveMutation.isPending.value" @click="handleSave">
              {{ editingForm ? 'Save Changes' : 'Publish Lead Form' }}
            </UiButton>
          </div>
        </div>
      </div>
    </UiModal>

    <!-- Embed Snippet Modal -->
    <UiModal v-model="isEmbedModalOpen" title="Website Embed Code" size="md">
      <div v-if="embedTargetForm" class="space-y-4">
        <p class="text-xs text-slate-600">
          Paste the following HTML code snippet into your external website or landing page to embed this lead capture form seamlessly.
        </p>

        <div class="p-3.5 bg-slate-900 rounded-xl text-slate-100 font-mono text-xs overflow-x-auto select-all">
          <pre>{{ getEmbedCode(embedTargetForm) }}</pre>
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <UiButton variant="outline" size="sm" @click="isEmbedModalOpen = false">Close</UiButton>
          <UiButton size="sm" @click="copyEmbedCode">
            <Copy class="w-3.5 h-3.5 mr-1.5" /> Copy HTML Snippet
          </UiButton>
        </div>
      </div>
    </UiModal>
  </div>
</template>
