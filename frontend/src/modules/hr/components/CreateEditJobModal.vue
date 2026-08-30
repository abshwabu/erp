<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { hrApi } from '@/api/hr'
import { recruitmentApi } from '@/api/recruitment'
import type { JobPosting, FormQuestionSchema, FormFieldType } from '@/types/recruitment'
import UiModal from '@/components/ui/UiModal.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import { useToast } from '@/composables/useToast'
import {
  Briefcase,
  Layers,
  Plus,
  Trash2,
  CheckCircle2,
  FileText,
  Upload,
  Image,
  List,
  CheckSquare,
  CircleDot,
  Calendar,
  Sparkles,
  Eye,
  GripVertical,
} from '@lucide/vue'

const props = defineProps<{
  modelValue: boolean
  job?: JobPosting | null
}>()

const emit = defineEmits(['update:modelValue', 'saved'])
const queryClient = useQueryClient()
const toast = useToast()

const activeTab = ref<'details' | 'form_builder' | 'preview'>('details')

// Queries for Depts and Positions
const { data: departments } = useQuery({
  queryKey: ['hr', 'departments'],
  queryFn: () => hrApi.getDepartments().then((res) => res.data),
})

const { data: positions } = useQuery({
  queryKey: ['hr', 'positions'],
  queryFn: () => hrApi.getPositions().then((res) => res.data),
})

const defaultQuestions: FormQuestionSchema[] = [
  {
    id: 'q_years_exp',
    label: 'Total Years of Relevant Experience',
    name: 'years_of_experience',
    type: 'select',
    required: true,
    options: ['Less than 1 year', '1 - 3 years', '3 - 5 years', '5 - 8 years', '8+ years'],
    placeholder: 'Select experience range',
  },
  {
    id: 'q_notice_period',
    label: 'Notice Period / Earliest Start Date',
    name: 'notice_period',
    type: 'radio',
    required: true,
    options: ['Immediately available', '1 to 2 weeks', '1 month', '2+ months'],
  },
  {
    id: 'q_skills',
    label: 'Core Skills & Competencies you excel in',
    name: 'core_skills',
    type: 'checkbox',
    required: false,
    options: ['Project Leadership', 'Full Stack Architecture', 'Client Communication', 'Data Analysis', 'DevOps & Cloud'],
  },
  {
    id: 'q_portfolio_link',
    label: 'Portfolio / GitHub / LinkedIn URL',
    name: 'portfolio_link',
    type: 'text',
    placeholder: 'https://...',
    required: false,
  },
]

const form = ref({
  title: '',
  department_id: '',
  position_id: '',
  location: 'Addis Ababa / Hybrid',
  employment_type: 'full-time',
  experience_level: 'mid',
  min_salary: '',
  max_salary: '',
  salary_currency: 'USD',
  deadline: '',
  status: 'published' as const,
  description: '',
  requirements: '',
  benefits: '',
  custom_form_schema: [] as FormQuestionSchema[],
})

const isEditing = computed(() => !!props.job?.id)

watch(
  () => props.job,
  (j) => {
    if (j) {
      form.value = {
        title: j.title || '',
        department_id: j.department_id || '',
        position_id: j.position_id || '',
        location: j.location || 'Addis Ababa / Hybrid',
        employment_type: j.employment_type || 'full-time',
        experience_level: j.experience_level || 'mid',
        min_salary: j.min_salary != null ? String(j.min_salary) : '',
        max_salary: j.max_salary != null ? String(j.max_salary) : '',
        salary_currency: j.salary_currency || 'USD',
        deadline: j.deadline ? String(j.deadline).slice(0, 10) : '',
        status: (j.status as any) || 'published',
        description: j.description || '',
        requirements: j.requirements || '',
        benefits: j.benefits || '',
        custom_form_schema: Array.isArray(j.custom_form_schema) && j.custom_form_schema.length > 0
          ? JSON.parse(JSON.stringify(j.custom_form_schema))
          : JSON.parse(JSON.stringify(defaultQuestions)),
      }
    } else {
      form.value = {
        title: '',
        department_id: '',
        position_id: '',
        location: 'Addis Ababa / Hybrid',
        employment_type: 'full-time',
        experience_level: 'mid',
        min_salary: '',
        max_salary: '',
        salary_currency: 'USD',
        deadline: '',
        status: 'published',
        description: '',
        requirements: '',
        benefits: '',
        custom_form_schema: JSON.parse(JSON.stringify(defaultQuestions)),
      }
    }
    activeTab.value = 'details'
  },
  { immediate: true }
)

const fieldTypes: Array<{ label: string; value: FormFieldType; icon: any }> = [
  { label: 'Short Text', value: 'text', icon: FileText },
  { label: 'Long Paragraph', value: 'textarea', icon: FileText },
  { label: 'Numeric Input', value: 'number', icon: FileText },
  { label: 'Dropdown Select', value: 'select', icon: List },
  { label: 'Radio (Single Choice)', value: 'radio', icon: CircleDot },
  { label: 'Checkbox (Multi-Select)', value: 'checkbox', icon: CheckSquare },
  { label: 'Document Upload', value: 'file', icon: Upload },
  { label: 'Photo / Image Upload', value: 'image', icon: Image },
  { label: 'Date Picker', value: 'date', icon: Calendar },
]

const addQuestion = (type: FormFieldType = 'text') => {
  const count = form.value.custom_form_schema.length + 1
  form.value.custom_form_schema.push({
    id: `q_${Date.now()}`,
    label: `Custom Question ${count}`,
    name: `custom_field_${count}`,
    type: type,
    required: false,
    placeholder: '',
    options: ['select', 'radio', 'checkbox'].includes(type) ? ['Option 1', 'Option 2', 'Option 3'] : undefined,
  })
}

const removeQuestion = (index: number) => {
  form.value.custom_form_schema.splice(index, 1)
}

const addOption = (question: FormQuestionSchema) => {
  if (!question.options) question.options = []
  question.options.push(`Option ${question.options.length + 1}`)
}

const removeOption = (question: FormQuestionSchema, optIndex: number) => {
  if (question.options) {
    question.options.splice(optIndex, 1)
  }
}

// Mutations
const saveMutation = useMutation({
  mutationFn: (payload: any) => {
    if (isEditing.value && props.job) {
      return recruitmentApi.updateJob(props.job.id, payload)
    }
    return recruitmentApi.createJob(payload)
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['hr', 'jobs'] })
    toast.success(isEditing.value ? 'Job posting updated successfully' : 'Job opportunity published successfully')
    emit('saved')
    emit('update:modelValue', false)
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || 'Failed to save job opportunity')
  },
})

const handleSave = () => {
  if (!form.value.title) {
    toast.error('Please enter a job title')
    activeTab.value = 'details'
    return
  }
  if (!form.value.description) {
    toast.error('Please provide a job description')
    activeTab.value = 'details'
    return
  }

  const payload: any = {
    ...form.value,
    department_id: form.value.department_id || null,
    position_id: form.value.position_id || null,
    min_salary: form.value.min_salary ? Number(form.value.min_salary) : null,
    max_salary: form.value.max_salary ? Number(form.value.max_salary) : null,
    deadline: form.value.deadline || null,
  }

  saveMutation.mutate(payload)
}

const employmentTypes = [
  { label: 'Full-time', value: 'full-time' },
  { label: 'Part-time', value: 'part-time' },
  { label: 'Contract', value: 'contract' },
  { label: 'Internship', value: 'intern' },
]

const experienceLevels = [
  { label: 'Entry Level', value: 'entry' },
  { label: 'Mid-Level', value: 'mid' },
  { label: 'Senior', value: 'senior' },
  { label: 'Lead / Executive', value: 'lead' },
]

const currencyOptions = [
  { label: 'USD ($)', value: 'USD' },
  { label: 'ETB (Br)', value: 'ETB' },
  { label: 'EUR (€)', value: 'EUR' },
  { label: 'GBP (£)', value: 'GBP' },
  { label: 'CAD ($)', value: 'CAD' },
]
</script>

<template>
  <UiModal
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
    :title="isEditing ? 'Edit Job Opportunity & Application Form' : 'Post New Job Opportunity'"
    size="xl"
  >
    <div class="space-y-6">
      <!-- Tabs Bar -->
      <div class="flex items-center gap-2 border-b border-slate-200">
        <button
          type="button"
          @click="activeTab = 'details'"
          class="px-4 py-2.5 text-xs font-bold border-b-2 -mb-px transition-colors flex items-center gap-2 cursor-pointer"
          :class="activeTab === 'details' ? 'border-primary-600 text-primary-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
        >
          <Briefcase class="w-4 h-4" />
          <span>1. Job Details & Role</span>
        </button>

        <button
          type="button"
          @click="activeTab = 'form_builder'"
          class="px-4 py-2.5 text-xs font-bold border-b-2 -mb-px transition-colors flex items-center gap-2 cursor-pointer"
          :class="activeTab === 'form_builder' ? 'border-primary-600 text-primary-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
        >
          <Layers class="w-4 h-4" />
          <span>2. Application Form Builder ({{ form.custom_form_schema.length }} Fields)</span>
        </button>

        <button
          type="button"
          @click="activeTab = 'preview'"
          class="px-4 py-2.5 text-xs font-bold border-b-2 -mb-px transition-colors flex items-center gap-2 cursor-pointer"
          :class="activeTab === 'preview' ? 'border-primary-600 text-primary-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
        >
          <Eye class="w-4 h-4" />
          <span>3. Live Candidate Preview</span>
        </button>
      </div>

      <!-- Tab 1: Job Details -->
      <div v-show="activeTab === 'details'" class="space-y-5">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <UiInput v-model="form.title" label="Job Title" placeholder="e.g. Senior Software Engineer" required />
          <UiInput v-model="form.location" label="Location" placeholder="e.g. Addis Ababa / Remote" required />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <UiSelect
            v-model="form.department_id"
            label="Department"
            :options="[{ label: 'None (Unassigned)', value: '' }, ...(departments?.map((d) => ({ label: d.name, value: d.id })) || [])]"
          />
          <UiSelect
            v-model="form.position_id"
            label="Position / Role"
            :options="[{ label: 'None', value: '' }, ...(positions?.map((p) => ({ label: p.title, value: p.id })) || [])]"
          />
          <UiSelect v-model="form.employment_type" label="Employment Type" :options="employmentTypes" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
          <UiSelect v-model="form.experience_level" label="Experience Level" :options="experienceLevels" />
          <UiInput v-model="form.min_salary" label="Min Salary" type="number" placeholder="Optional" />
          <UiInput v-model="form.max_salary" label="Max Salary" type="number" placeholder="Optional" />
          <UiSelect v-model="form.salary_currency" label="Currency" :options="currencyOptions" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UiInput v-model="form.deadline" label="Application Deadline" type="date" />
          <UiSelect
            v-model="form.status"
            label="Posting Status"
            :options="[
              { label: 'Published (Accepting Applications)', value: 'published' },
              { label: 'Draft (Hidden)', value: 'draft' },
              { label: 'Closed (Applications Archived)', value: 'closed' },
            ]"
          />
        </div>

        <div class="space-y-1">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
            Job Description & Responsibilities <span class="text-red-500">*</span>
          </label>
          <textarea
            v-model="form.description"
            rows="4"
            placeholder="Outline role summary, primary duties, team structure, and project mission..."
            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-600"
          ></textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-1">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Requirements & Qualifications</label>
            <textarea
              v-model="form.requirements"
              rows="3"
              placeholder="e.g. • 4+ years Vue/TypeScript • Experience in PostgreSQL..."
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-600"
            ></textarea>
          </div>

          <div class="space-y-1">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Benefits & Perks</label>
            <textarea
              v-model="form.benefits"
              rows="3"
              placeholder="e.g. • Flexible work schedule • Health coverage • Learning stipend..."
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-600"
            ></textarea>
          </div>
        </div>
      </div>

      <!-- Tab 2: Custom Application Form Builder -->
      <div v-show="activeTab === 'form_builder'" class="space-y-5">
        <div class="p-4 bg-primary-50/70 border border-primary-100 rounded-xl flex items-center justify-between">
          <div>
            <h3 class="text-sm font-bold text-primary-900">Custom Dynamic Application Form</h3>
            <p class="text-xs text-primary-700 mt-0.5">
              Standard fields (Full Name, Email, Phone, Cover Letter, Resume, Photo) are collected automatically. Add flexible custom questions below.
            </p>
          </div>
        </div>

        <!-- Add Field Palette -->
        <div class="flex items-center gap-1.5 flex-wrap">
          <span class="text-xs font-bold uppercase tracking-wider text-slate-500 mr-1">Add Question:</span>
          <button
            v-for="ft in fieldTypes"
            :key="ft.value"
            type="button"
            @click="addQuestion(ft.value)"
            class="px-2.5 py-1.5 bg-white border border-slate-200 hover:border-primary-400 hover:bg-primary-50/50 text-slate-700 text-xs font-bold rounded-lg transition-all flex items-center gap-1.5 shadow-2xs cursor-pointer"
          >
            <component :is="ft.icon" class="w-3.5 h-3.5 text-primary-600" />
            <span>+ {{ ft.label }}</span>
          </button>
        </div>

        <!-- Questions List -->
        <div class="space-y-4 max-h-[480px] overflow-y-auto pr-1">
          <div
            v-for="(question, index) in form.custom_form_schema"
            :key="question.id"
            class="p-4 bg-slate-50/80 rounded-2xl border border-slate-200 space-y-3 relative group"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-full bg-slate-200 text-slate-700 font-bold text-xs flex items-center justify-center">
                  {{ index + 1 }}
                </span>
                <UiBadge variant="default" class="text-[10px] uppercase font-bold">
                  {{ question.type }}
                </UiBadge>
              </div>

              <div class="flex items-center gap-3">
                <label class="flex items-center gap-1.5 text-xs text-slate-700 font-semibold cursor-pointer">
                  <input
                    type="checkbox"
                    v-model="question.required"
                    class="h-3.5 w-3.5 rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                  />
                  <span>Mandatory</span>
                </label>
                <button
                  type="button"
                  @click="removeQuestion(index)"
                  class="text-slate-400 hover:text-red-600 p-1 transition-colors cursor-pointer"
                  title="Remove Question"
                >
                  <Trash2 class="w-4 h-4" />
                </button>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <UiInput v-model="question.label" label="Question Label" placeholder="e.g. Years of Experience" size="sm" required />
              <UiInput v-model="question.placeholder" label="Placeholder / Hint" placeholder="e.g. Enter URL" size="sm" />
            </div>

            <!-- Options builder for Select, Radio, Checkbox -->
            <div v-if="['select', 'radio', 'checkbox'].includes(question.type)" class="p-3 bg-white border border-slate-200 rounded-xl space-y-2">
              <div class="flex items-center justify-between">
                <label class="text-xs font-bold uppercase tracking-wider text-slate-600">Choice Options</label>
                <button
                  type="button"
                  @click="addOption(question)"
                  class="text-xs text-primary-600 hover:text-primary-700 font-bold inline-flex items-center gap-1 hover:underline cursor-pointer"
                >
                  <Plus class="w-3 h-3" /> Add Option
                </button>
              </div>

              <div class="space-y-1.5">
                <div
                  v-for="(opt, optIdx) in question.options"
                  :key="optIdx"
                  class="flex items-center gap-2"
                >
                  <span class="text-slate-400 text-xs font-mono">•</span>
                  <input
                    v-model="question.options![optIdx]"
                    class="flex-1 px-2.5 py-1 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500"
                    placeholder="Option label"
                  />
                  <button
                    v-if="(question.options?.length || 0) > 1"
                    type="button"
                    @click="removeOption(question, optIdx)"
                    class="text-slate-400 hover:text-red-500 p-1 cursor-pointer"
                  >
                    ✕
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div v-if="!form.custom_form_schema.length" class="p-8 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200 text-slate-400 text-xs">
            No custom questions added yet. Click one of the "+ Add Question" buttons above to customize your form.
          </div>
        </div>
      </div>

      <!-- Tab 3: Candidate Live Preview -->
      <div v-show="activeTab === 'preview'" class="space-y-5">
        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between text-xs text-slate-600">
          <span class="font-medium">This is how candidates will view and submit your application form publicly.</span>
        </div>

        <div class="p-6 bg-white border border-slate-200 rounded-2xl space-y-6 shadow-xs">
          <div>
            <h2 class="text-xl font-black text-slate-900">{{ form.title || 'Untitled Job Position' }}</h2>
            <div class="flex items-center gap-2 text-xs text-slate-500 mt-1">
              <span>{{ form.location }}</span>
              <span>•</span>
              <span class="capitalize">{{ form.employment_type }}</span>
              <span v-if="form.min_salary || form.max_salary">•</span>
              <span v-if="form.min_salary || form.max_salary" class="font-mono text-emerald-700 font-bold">
                {{ form.salary_currency }} {{ form.min_salary }} - {{ form.max_salary }}
              </span>
            </div>
          </div>

          <p class="text-xs text-slate-600 leading-relaxed">{{ form.description || 'Job description preview...' }}</p>

          <div class="border-t border-slate-100 pt-4 space-y-4">
            <h3 class="text-sm font-bold text-slate-900">Application Form Preview</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <UiInput label="Full Name *" placeholder="Jane Doe" disabled />
              <UiInput label="Email Address *" placeholder="jane@example.com" disabled />
            </div>

            <!-- Custom Form Questions Preview -->
            <div v-for="q in form.custom_form_schema" :key="q.id" class="space-y-1.5">
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                {{ q.label }} <span v-if="q.required" class="text-red-500">*</span>
              </label>

              <!-- Select -->
              <UiSelect
                v-if="q.type === 'select'"
                :options="q.options?.map(o => ({ label: o, value: o })) || []"
                :placeholder="q.placeholder || 'Select option'"
                disabled
              />

              <!-- Radio -->
              <div v-else-if="q.type === 'radio'" class="space-y-1.5 pt-1">
                <div v-for="opt in q.options" :key="opt" class="flex items-center gap-2 text-xs text-slate-700">
                  <input type="radio" :name="q.id" disabled class="text-primary-600" />
                  <span>{{ opt }}</span>
                </div>
              </div>

              <!-- Checkbox -->
              <div v-else-if="q.type === 'checkbox'" class="space-y-1.5 pt-1">
                <div v-for="opt in q.options" :key="opt" class="flex items-center gap-2 text-xs text-slate-700">
                  <input type="checkbox" disabled class="rounded text-primary-600" />
                  <span>{{ opt }}</span>
                </div>
              </div>

              <!-- Textarea -->
              <textarea
                v-else-if="q.type === 'textarea'"
                rows="2"
                :placeholder="q.placeholder || 'Your answer...'"
                disabled
                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs"
              ></textarea>

              <!-- Generic Input -->
              <UiInput
                v-else
                :type="q.type === 'number' ? 'number' : q.type === 'date' ? 'date' : 'text'"
                :placeholder="q.placeholder || 'Your answer'"
                disabled
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center justify-between pt-4 border-t border-slate-100">
        <div class="flex items-center gap-2">
          <UiButton
            v-if="activeTab !== 'details'"
            variant="outline"
            size="sm"
            type="button"
            @click="activeTab = activeTab === 'preview' ? 'form_builder' : 'details'"
          >
            ← Previous Step
          </UiButton>
          <UiButton
            v-if="activeTab !== 'preview'"
            variant="outline"
            size="sm"
            type="button"
            @click="activeTab = activeTab === 'details' ? 'form_builder' : 'preview'"
          >
            Next Step →
          </UiButton>
        </div>

        <div class="flex items-center gap-2">
          <UiButton variant="outline" type="button" @click="emit('update:modelValue', false)">
            Cancel
          </UiButton>
          <UiButton :loading="saveMutation.isPending.value" @click="handleSave">
            {{ isEditing ? 'Save Changes' : 'Publish Opportunity' }}
          </UiButton>
        </div>
      </div>
    </div>
  </UiModal>
</template>
