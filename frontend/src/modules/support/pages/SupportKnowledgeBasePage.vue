<script setup lang="ts">
import { ref, computed, markRaw } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { supportApi } from '@/api/support'
import type { SupportKnowledgeArticle, ArticleCategory } from '@/types/support'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiStat from '@/components/ui/UiStat.vue'
import UiSpinner from '@/components/ui/UiSpinner.vue'
import { useToast } from '@/composables/useToast'
import {
  BookOpen,
  Plus,
  Search,
  CheckCircle2,
  Eye,
  Trash2,
  Edit,
  Sparkles,
  HelpCircle,
  FileText,
  CreditCard,
  Wrench,
  ShieldCheck,
  Tag,
} from '@lucide/vue'

const queryClient = useQueryClient()
const toast = useToast()

const searchQuery = ref('')
const selectedCategory = ref<string>('all')
const isCreateModalOpen = ref(false)
const isViewModalOpen = ref(false)
const editingArticle = ref<SupportKnowledgeArticle | null>(null)
const viewingArticle = ref<SupportKnowledgeArticle | null>(null)

const articleForm = ref({
  title: '',
  category: 'general' as ArticleCategory,
  summary: '',
  content: '',
  is_published: true,
})

// Queries
const { data: articles, isLoading } = useQuery({
  queryKey: ['support', 'articles'],
  queryFn: () => supportApi.getArticles().then((r) => r.data.data),
})

const categories: Array<{ key: string; label: string; icon: any }> = [
  { key: 'all', label: 'All Articles', icon: markRaw(BookOpen) },
  { key: 'general', label: 'General', icon: markRaw(FileText) },
  { key: 'billing', label: 'Billing & Invoices', icon: markRaw(CreditCard) },
  { key: 'technical', label: 'Technical Guides', icon: markRaw(Wrench) },
  { key: 'account', label: 'Account & Security', icon: markRaw(ShieldCheck) },
  { key: 'faq', label: 'Common FAQs', icon: markRaw(HelpCircle) },
]

const filteredArticles = computed(() => {
  let list = articles.value || []
  if (selectedCategory.value !== 'all') {
    list = list.filter((a) => a.category === selectedCategory.value)
  }
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(
      (a) =>
        a.title.toLowerCase().includes(q) ||
        (a.summary || '').toLowerCase().includes(q) ||
        a.content.toLowerCase().includes(q)
    )
  }
  return list
})

// Stats
const stats = computed(() => {
  const list = articles.value || []
  const published = list.filter((a) => a.is_published).length
  const totalViews = list.reduce((sum, a) => sum + (a.views_count || 0), 0)

  return [
    {
      label: 'Published Articles',
      value: published,
      icon: markRaw(BookOpen),
    },
    {
      label: 'Article Categories',
      value: 5,
      icon: markRaw(Tag),
    },
    {
      label: 'Total Resource Views',
      value: totalViews,
      icon: markRaw(Eye),
    },
    {
      label: 'Resolution Effectiveness',
      value: '94%',
      icon: markRaw(CheckCircle2),
    },
  ]
})

// Mutations
const saveArticleMutation = useMutation({
  mutationFn: (payload: any) => {
    if (editingArticle.value) {
      return supportApi.updateArticle(editingArticle.value.id, payload)
    }
    return supportApi.createArticle(payload)
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['support', 'articles'] })
    isCreateModalOpen.value = false
    toast.success(editingArticle.value ? 'Article updated' : 'Article published')
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || 'Failed to save article')
  },
})

const deleteArticleMutation = useMutation({
  mutationFn: (id: string) => supportApi.deleteArticle(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['support', 'articles'] })
    toast.success('Article removed')
  },
})

const openCreateModal = () => {
  editingArticle.value = null
  articleForm.value = {
    title: '',
    category: 'general',
    summary: '',
    content: '',
    is_published: true,
  }
  isCreateModalOpen.value = true
}

const openEditModal = (a: SupportKnowledgeArticle) => {
  editingArticle.value = a
  articleForm.value = {
    title: a.title,
    category: a.category,
    summary: a.summary || '',
    content: a.content,
    is_published: a.is_published,
  }
  isCreateModalOpen.value = true
}

const viewArticle = async (a: SupportKnowledgeArticle) => {
  try {
    const res = await supportApi.getArticle(a.id)
    viewingArticle.value = res.data.data
  } catch (e) {
    viewingArticle.value = a
  }
  isViewModalOpen.value = true
}

const handleSave = () => {
  if (!articleForm.value.title || !articleForm.value.content) {
    toast.error('Article title and content are required')
    return
  }
  saveArticleMutation.mutate(articleForm.value)
}

const getCategoryBadge = (cat: string) => {
  switch (cat) {
    case 'billing': return { label: 'Billing & Invoicing', variant: 'warning' as const }
    case 'technical': return { label: 'Technical Guide', variant: 'info' as const }
    case 'account': return { label: 'Account & Security', variant: 'purple' as const }
    case 'faq': return { label: 'FAQ', variant: 'success' as const }
    default: return { label: 'General', variant: 'default' as const }
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Knowledge Base & FAQ Library</h1>
        <p class="text-xs sm:text-sm text-slate-500">Standard operating guidelines, troubleshooting documentation, and customer self-service runbooks.</p>
      </div>
      <UiButton @click="openCreateModal">
        <Plus class="w-4 h-4 mr-2" /> Write Article
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

    <!-- Category Tabs & Search -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
      <div class="flex items-center gap-1.5 overflow-x-auto pb-1 max-w-xl">
        <button
          v-for="cat in categories"
          :key="cat.key"
          type="button"
          @click="selectedCategory = cat.key"
          class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer whitespace-nowrap flex items-center gap-1.5"
          :class="selectedCategory === cat.key ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
        >
          <component :is="cat.icon" class="w-3.5 h-3.5" />
          <span>{{ cat.label }}</span>
        </button>
      </div>

      <UiInput
        v-model="searchQuery"
        placeholder="Search articles & FAQs..."
        size="sm"
        class="w-full sm:w-64"
      >
        <template #prefix><Search class="w-3.5 h-3.5 text-slate-400" /></template>
      </UiInput>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="p-16 flex justify-center">
      <UiSpinner size="lg" />
    </div>

    <!-- Articles Grid -->
    <div v-else-if="filteredArticles.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <div
        v-for="article in filteredArticles"
        :key="article.id"
        class="bg-white rounded-2xl border border-slate-200 hover:border-slate-300 p-5 shadow-xs flex flex-col justify-between transition-all space-y-4 cursor-pointer group"
        @click="viewArticle(article)"
      >
        <div class="space-y-2.5">
          <div class="flex items-center justify-between">
            <UiBadge :variant="getCategoryBadge(article.category).variant" class="text-[10px] font-bold">
              {{ getCategoryBadge(article.category).label }}
            </UiBadge>
            <span class="text-[11px] text-slate-400 font-mono flex items-center gap-1">
              <Eye class="w-3 h-3" /> {{ article.views_count }} views
            </span>
          </div>

          <h3 class="font-bold text-slate-900 text-base leading-snug group-hover:text-primary-700 transition-colors">
            {{ article.title }}
          </h3>

          <p v-if="article.summary" class="text-xs text-slate-600 line-clamp-2 leading-relaxed">
            {{ article.summary }}
          </p>
          <p v-else class="text-xs text-slate-400 line-clamp-2 leading-relaxed">
            {{ article.content }}
          </p>
        </div>

        <!-- Footer Meta -->
        <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-400" @click.stop>
          <span class="truncate font-semibold text-slate-600">
            By {{ article.author?.name || 'Support Team' }}
          </span>

          <div class="flex items-center gap-1">
            <UiButton variant="ghost" size="sm" @click="openEditModal(article)">
              <Edit class="w-3.5 h-3.5 text-slate-600" />
            </UiButton>
            <UiButton variant="ghost" size="sm" class="text-red-500 hover:text-red-700 hover:bg-red-50" @click="deleteArticleMutation.mutate(article.id)">
              <Trash2 class="w-3.5 h-3.5" />
            </UiButton>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="bg-white rounded-2xl border border-slate-200 p-16 text-center space-y-4">
      <BookOpen class="w-12 h-12 mx-auto text-slate-300" />
      <div>
        <h3 class="font-bold text-slate-900 text-base">No knowledge base articles found</h3>
        <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">
          Create help guides, FAQs, and technical troubleshooting articles for your team and clients.
        </p>
      </div>
      <UiButton size="sm" @click="openCreateModal">
        <Plus class="w-4 h-4 mr-1.5" /> Write First Article
      </UiButton>
    </div>

    <!-- Create / Edit Article Modal -->
    <UiModal v-model="isCreateModalOpen" :title="editingArticle ? 'Edit Knowledge Article' : 'Publish Knowledge Article'" size="lg">
      <div class="space-y-4">
        <UiInput v-model="articleForm.title" label="Article Title" placeholder="e.g. How to reconcile multi-currency invoices" required />

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UiSelect
            v-model="articleForm.category"
            label="Category"
            :options="[
              { label: 'General Documentation', value: 'general' },
              { label: 'Billing & Invoices', value: 'billing' },
              { label: 'Technical Guide', value: 'technical' },
              { label: 'Account & Security', value: 'account' },
              { label: 'Frequently Asked Questions (FAQ)', value: 'faq' },
            ]"
          />
          <UiInput v-model="articleForm.summary" label="Summary / Preview Snippet" placeholder="Short description for search results..." />
        </div>

        <div class="space-y-1">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Article Content (Markdown Supported)</label>
          <textarea
            v-model="articleForm.content"
            rows="8"
            placeholder="Step 1: Navigate to the billing section...&#10;Step 2: Click reconcile transactions..."
            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500"
            required
          ></textarea>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
          <UiButton variant="outline" type="button" @click="isCreateModalOpen = false">Cancel</UiButton>
          <UiButton :loading="saveArticleMutation.isPending.value" @click="handleSave">
            {{ editingArticle ? 'Save Changes' : 'Publish Article' }}
          </UiButton>
        </div>
      </div>
    </UiModal>

    <!-- View Article Modal -->
    <UiModal v-model="isViewModalOpen" title="Knowledge Base Article" size="lg">
      <div v-if="viewingArticle" class="space-y-4">
        <div class="space-y-1.5 border-b border-slate-100 pb-3">
          <div class="flex items-center gap-2">
            <UiBadge :variant="getCategoryBadge(viewingArticle.category).variant" class="font-bold text-xs">
              {{ getCategoryBadge(viewingArticle.category).label }}
            </UiBadge>
            <span class="text-xs text-slate-400 font-mono">• {{ viewingArticle.views_count }} views</span>
          </div>
          <h2 class="text-xl font-black text-slate-900 leading-snug">{{ viewingArticle.title }}</h2>
          <p v-if="viewingArticle.summary" class="text-xs text-slate-500 italic">{{ viewingArticle.summary }}</p>
        </div>

        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 text-xs text-slate-800 leading-relaxed whitespace-pre-wrap font-sans">
          {{ viewingArticle.content }}
        </div>

        <div class="flex items-center justify-between pt-3 border-t border-slate-100 text-xs text-slate-400">
          <span>Author: <strong class="text-slate-700">{{ viewingArticle.author?.name || 'Support Admin' }}</strong></span>
          <UiButton size="sm" @click="isViewModalOpen = false">Close</UiButton>
        </div>
      </div>
    </UiModal>
  </div>
</template>
