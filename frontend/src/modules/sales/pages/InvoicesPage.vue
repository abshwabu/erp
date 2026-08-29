<script setup lang="ts">
import { computed, ref } from 'vue'
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { salesApi, type Invoice, type InvoiceStatus, type Customer } from '@/api/sales'
import { inventoryApi } from '@/api/inventory'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiTable from '@/components/ui/UiTable.vue'
import { formatCurrency, formatDate } from '@/utils/format'
import {
  Plus,
  Search,
  FileText,
  Users,
  Trash2,
  Edit2,
  Package,
  Percent,
  Check,
  ChevronDown,
  Sparkles,
  Receipt,
  Mail,
  Phone,
  Eye,
  DollarSign,
  AlertCircle,
} from '@lucide/vue'

const queryClient = useQueryClient()
const activeTab = ref<'invoices' | 'customers'>('invoices')
const searchQuery = ref('')
const isInvoiceModalOpen = ref(false)
const isCustomerModalOpen = ref(false)
const isEditCustomerModalOpen = ref(false)
const isCustomerDetailModalOpen = ref(false)
const isPaymentModalOpen = ref(false)

const selectedInvoice = ref<Invoice | null>(null)
const selectedCustomer = ref<Customer | null>(null)
const editingCustomerId = ref<string | null>(null)
const errorMessage = ref('')

const today = new Date().toISOString().slice(0, 10)
const dueDefault = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10)

interface InvoiceLineItem {
  product_id?: string
  sku?: string
  description: string
  quantity: number
  unit_price: number
  search_query?: string
  is_dropdown_open?: boolean
}

const invoiceForm = ref({
  customer_id: '',
  status: 'sent' as 'draft' | 'sent',
  issue_date: today,
  due_date: dueDefault,
  tax_percent: 0,
  notes: '',
  lines: [
    {
      product_id: '',
      sku: '',
      description: '',
      quantity: 1,
      unit_price: 0,
      search_query: '',
      is_dropdown_open: false,
    },
  ] as InvoiceLineItem[],
})

const customerForm = ref({
  name: '',
  email: '',
  phone: '',
})

const paymentForm = ref({
  amount_dollars: 0,
  method: 'cash',
  reference: '',
})

// Queries
const { data: invoices, isLoading: invoicesLoading } = useQuery({
  queryKey: ['sales', 'invoices'],
  queryFn: () => salesApi.getInvoices().then((res) => res.data.data),
})

const { data: customers, isLoading: customersLoading } = useQuery({
  queryKey: ['sales', 'customers'],
  queryFn: () => salesApi.getCustomers().then((res) => res.data.data),
})

const { data: productsData, isLoading: productsLoading } = useQuery({
  queryKey: ['inventory', 'products-lookup'],
  queryFn: () => inventoryApi.getProducts({ status: 'active' }, 1).then((res) => res.data.data),
})

const allProducts = computed(() => productsData.value || [])

// Filter products based on search term in line item
function getFilteredProducts(query: string) {
  const q = (query || '').toLowerCase().trim()
  if (!q) return allProducts.value.slice(0, 8)
  return allProducts.value
    .filter(
      (p) =>
        p.name.toLowerCase().includes(q) ||
        (p.sku && p.sku.toLowerCase().includes(q)) ||
        (p.category?.name && p.category.name.toLowerCase().includes(q))
    )
    .slice(0, 8)
}

function selectProductForLine(line: InvoiceLineItem, product: any) {
  line.product_id = product.id
  line.sku = product.sku
  line.description = product.name
  line.search_query = product.name
  line.unit_price = Number(((product.selling_price || 0) / 100).toFixed(2))
  line.is_dropdown_open = false
}

// Mutations
const createCustomerMutation = useMutation({
  mutationFn: () =>
    salesApi.createCustomer({
      name: customerForm.value.name,
      email: customerForm.value.email || null,
      phone: customerForm.value.phone || null,
    }),
  onSuccess: (res) => {
    queryClient.invalidateQueries({ queryKey: ['sales', 'customers'] })
    invoiceForm.value.customer_id = res.data.data.id
    isCustomerModalOpen.value = false
    customerForm.value = { name: '', email: '', phone: '' }
    errorMessage.value = ''
  },
  onError: (err: any) => {
    errorMessage.value = err?.response?.data?.message || err?.message || 'Failed to create customer'
  },
})

const updateCustomerMutation = useMutation({
  mutationFn: ({ id, data }: { id: string; data: any }) =>
    salesApi.updateCustomer(id, data),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['sales', 'customers'] })
    queryClient.invalidateQueries({ queryKey: ['sales', 'invoices'] })
    isEditCustomerModalOpen.value = false
    editingCustomerId.value = null
    customerForm.value = { name: '', email: '', phone: '' }
    errorMessage.value = ''
  },
  onError: (err: any) => {
    errorMessage.value = err?.response?.data?.message || err?.message || 'Failed to update customer'
  },
})

const deleteCustomerMutation = useMutation({
  mutationFn: (id: string) => salesApi.deleteCustomer(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['sales', 'customers'] })
    errorMessage.value = ''
  },
  onError: (err: any) => {
    errorMessage.value = err?.response?.data?.message || err?.message || 'Failed to delete customer'
  },
})

const createInvoiceMutation = useMutation({
  mutationFn: () => {
    const taxCents = Math.round(invoiceTaxDollars.value * 100)
    return salesApi.createInvoice({
      customer_id: invoiceForm.value.customer_id,
      status: invoiceForm.value.status,
      issue_date: invoiceForm.value.issue_date,
      due_date: invoiceForm.value.due_date,
      tax_cents: taxCents,
      notes: invoiceForm.value.notes || null,
      lines: invoiceForm.value.lines.map((line) => ({
        description: line.description || line.search_query || 'Item',
        quantity: Number(line.quantity),
        unit_price_cents: Math.round(Number(line.unit_price || 0) * 100),
      })),
    })
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['sales', 'invoices'] })
    queryClient.invalidateQueries({ queryKey: ['sales', 'customers'] })
    queryClient.invalidateQueries({ queryKey: ['accounting'] })
    isInvoiceModalOpen.value = false
    resetInvoiceForm()
    errorMessage.value = ''
  },
  onError: (err: any) => {
    errorMessage.value = err?.response?.data?.message || err?.message || 'Failed to create invoice'
  },
})

const markSentMutation = useMutation({
  mutationFn: (id: string) => salesApi.markSent(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['sales', 'invoices'] })
    queryClient.invalidateQueries({ queryKey: ['sales', 'customers'] })
    queryClient.invalidateQueries({ queryKey: ['accounting'] })
  },
  onError: (err: any) => {
    errorMessage.value = err?.response?.data?.message || err?.message || 'Failed to mark invoice as sent'
  },
})

const recordPaymentMutation = useMutation({
  mutationFn: ({ id, amount_cents }: { id: string; amount_cents: number }) =>
    salesApi.recordPayment(id, {
      amount_cents,
      method: paymentForm.value.method,
      reference: paymentForm.value.reference || null,
    }),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['sales', 'invoices'] })
    queryClient.invalidateQueries({ queryKey: ['sales', 'customers'] })
    queryClient.invalidateQueries({ queryKey: ['accounting'] })
    isPaymentModalOpen.value = false
    selectedInvoice.value = null
    errorMessage.value = ''
  },
  onError: (err: any) => {
    errorMessage.value = err?.response?.data?.message || err?.message || 'Failed to record payment'
  },
})

const customerOptions = computed(() => [
  { label: 'Select customer', value: '' },
  ...(customers.value || []).map((c) => ({ label: c.name, value: c.id })),
])

const filteredInvoices = computed(() => {
  const q = searchQuery.value.toLowerCase().trim()
  if (!invoices.value) return []
  if (!q) return invoices.value
  return invoices.value.filter(
    (inv) =>
      inv.number.toLowerCase().includes(q) ||
      inv.customer?.name?.toLowerCase().includes(q) ||
      inv.status.toLowerCase().includes(q)
  )
})

const filteredCustomers = computed(() => {
  const q = searchQuery.value.toLowerCase().trim()
  if (!customers.value) return []
  if (!q) return customers.value
  return customers.value.filter(
    (c) =>
      c.name.toLowerCase().includes(q) ||
      c.email?.toLowerCase().includes(q) ||
      c.phone?.toLowerCase().includes(q)
  )
})

const invoiceColumns = [
  { key: 'number', label: 'Invoice #' },
  { key: 'customer', label: 'Customer' },
  { key: 'issue_date', label: 'Issue Date' },
  { key: 'due_date', label: 'Due Date' },
  { key: 'total', label: 'Total' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: '' },
]

const customerColumns = [
  { key: 'name', label: 'Customer Name' },
  { key: 'contact', label: 'Contact' },
  { key: 'invoices_count', label: 'Invoices', align: 'center' as const },
  { key: 'financials', label: 'Financial Summary' },
  { key: 'actions', label: 'Actions', align: 'right' as const },
]

// Real-time calculations
const invoiceSubtotal = computed(() =>
  invoiceForm.value.lines.reduce(
    (sum, line) => sum + Number(line.quantity || 0) * Number(line.unit_price || 0),
    0
  )
)

const invoiceTaxDollars = computed(() => {
  const rate = Math.max(0, Number(invoiceForm.value.tax_percent || 0))
  return (invoiceSubtotal.value * rate) / 100
})

const invoiceTotal = computed(
  () => invoiceSubtotal.value + invoiceTaxDollars.value
)

function getLineEffectivePriceWithTax(unitPrice: number) {
  const rate = Math.max(0, Number(invoiceForm.value.tax_percent || 0))
  return unitPrice * (1 + rate / 100)
}

const statusVariant = (status: InvoiceStatus) => {
  if (status === 'paid') return 'success'
  if (status === 'sent') return 'info'
  if (status === 'void') return 'danger'
  return 'default'
}

const resetInvoiceForm = () => {
  invoiceForm.value = {
    customer_id: '',
    status: 'sent',
    issue_date: today,
    due_date: dueDefault,
    tax_percent: 0,
    notes: '',
    lines: [
      {
        product_id: '',
        sku: '',
        description: '',
        quantity: 1,
        unit_price: 0,
        search_query: '',
        is_dropdown_open: false,
      },
    ],
  }
}

const openInvoiceModal = (presetCustomerId?: string) => {
  errorMessage.value = ''
  resetInvoiceForm()
  if (presetCustomerId) {
    invoiceForm.value.customer_id = presetCustomerId
  }
  isInvoiceModalOpen.value = true
}

const openCustomerModal = () => {
  errorMessage.value = ''
  customerForm.value = { name: '', email: '', phone: '' }
  isCustomerModalOpen.value = true
}

const openEditCustomerModal = (customer: Customer) => {
  errorMessage.value = ''
  editingCustomerId.value = customer.id
  customerForm.value = {
    name: customer.name,
    email: customer.email || '',
    phone: customer.phone || '',
  }
  isEditCustomerModalOpen.value = true
}

const handleSaveCustomerEdit = () => {
  if (!editingCustomerId.value || !customerForm.value.name) return
  updateCustomerMutation.mutate({
    id: editingCustomerId.value,
    data: {
      name: customerForm.value.name,
      email: customerForm.value.email || null,
      phone: customerForm.value.phone || null,
    },
  })
}

const handleDeleteCustomer = (customer: Customer) => {
  if ((customer.invoices_count ?? 0) > 0) {
    alert(`Cannot delete "${customer.name}" because they have ${customer.invoices_count} invoice(s) on record.`)
    return
  }
  if (!confirm(`Are you sure you want to delete customer "${customer.name}"?`)) return
  deleteCustomerMutation.mutate(customer.id)
}

const viewCustomerInvoices = (customer: Customer) => {
  searchQuery.value = customer.name
  activeTab.value = 'invoices'
}

const addLine = () => {
  invoiceForm.value.lines.push({
    product_id: '',
    sku: '',
    description: '',
    quantity: 1,
    unit_price: 0,
    search_query: '',
    is_dropdown_open: false,
  })
}

const removeLine = (index: number) => {
  if (invoiceForm.value.lines.length === 1) return
  invoiceForm.value.lines.splice(index, 1)
}

const openPaymentModal = (invoice: Invoice) => {
  selectedInvoice.value = invoice
  const outstanding = (invoice.total_cents - invoice.amount_paid_cents) / 100
  paymentForm.value = {
    amount_dollars: outstanding,
    method: 'cash',
    reference: '',
  }
  errorMessage.value = ''
  isPaymentModalOpen.value = true
}

const markPaid = async (invoice: Invoice) => {
  errorMessage.value = ''
  try {
    let current = invoice
    if (current.status === 'draft') {
      const sent = await salesApi.markSent(current.id)
      current = sent.data.data
    }
    if (current.status === 'paid') return
    const outstanding = current.total_cents - current.amount_paid_cents
    if (outstanding <= 0) return
    await salesApi.recordPayment(current.id, {
      amount_cents: outstanding,
      method: 'cash',
      reference: 'Marked paid',
    })
    queryClient.invalidateQueries({ queryKey: ['sales', 'invoices'] })
    queryClient.invalidateQueries({ queryKey: ['sales', 'customers'] })
    queryClient.invalidateQueries({ queryKey: ['accounting'] })
  } catch (err: any) {
    errorMessage.value = err?.message || 'Failed to mark invoice as paid'
  }
}

const submitPayment = () => {
  if (!selectedInvoice.value) return
  recordPaymentMutation.mutate({
    id: selectedInvoice.value.id,
    amount_cents: Math.round(Number(paymentForm.value.amount_dollars || 0) * 100),
  })
}

const cents = (value: number) => formatCurrency(value / 100)
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Sales & Invoicing</h1>
        <p class="text-slate-500">Manage customers, invoices, line items with inventory search, and accounting payments.</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <UiButton variant="outline" @click="openCustomerModal">
          <Users class="h-4 w-4 mr-2" /> New Customer
        </UiButton>
        <UiButton @click="openInvoiceModal()">
          <Plus class="h-4 w-4 mr-2" /> New Invoice
        </UiButton>
      </div>
    </div>

    <div v-if="errorMessage" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 flex items-center justify-between">
      <span>{{ errorMessage }}</span>
      <button type="button" @click="errorMessage = ''" class="text-red-500 hover:text-red-700 font-bold ml-2">✕</button>
    </div>

    <!-- Tabs -->
    <div class="flex gap-2 border-b border-slate-200">
      <button
        class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors flex items-center gap-2"
        :class="activeTab === 'invoices' ? 'border-primary-600 text-primary-700 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700'"
        @click="activeTab = 'invoices'"
      >
        <FileText class="h-4 w-4" />
        <span>Invoices ({{ invoices?.length || 0 }})</span>
      </button>
      <button
        class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors flex items-center gap-2"
        :class="activeTab === 'customers' ? 'border-primary-600 text-primary-700 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700'"
        @click="activeTab = 'customers'"
      >
        <Users class="h-4 w-4" />
        <span>Customers ({{ customers?.length || 0 }})</span>
      </button>
    </div>

    <!-- Search Input -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
      <UiInput v-model="searchQuery" :placeholder="activeTab === 'invoices' ? 'Search invoices by number, customer, status…' : 'Search customers by name, email, phone…'" class="w-full max-w-sm">
        <template #prefix><Search class="h-4 w-4 text-slate-400" /></template>
      </UiInput>
    </div>

    <!-- Invoices Table -->
    <UiTable
      v-if="activeTab === 'invoices'"
      :columns="invoiceColumns"
      :data="filteredInvoices"
      :loading="invoicesLoading"
    >
      <template #cell(number)="{ item }">
        <div class="font-bold text-slate-900 font-mono text-sm">{{ item.number }}</div>
      </template>
      <template #cell(customer)="{ item }">
        <button
          type="button"
          @click="viewCustomerInvoices(item.customer)"
          class="text-left font-medium text-blue-600 hover:underline"
          :title="'View invoices for ' + (item.customer?.name || '')"
        >
          {{ item.customer?.name || '—' }}
        </button>
      </template>
      <template #cell(issue_date)="{ item }">
        <span class="text-slate-600 text-xs font-medium">{{ formatDate(item.issue_date) }}</span>
      </template>
      <template #cell(due_date)="{ item }">
        <span class="text-slate-600 text-xs font-medium">{{ formatDate(item.due_date) }}</span>
      </template>
      <template #cell(total)="{ item }">
        <div class="text-right font-bold text-slate-900 font-mono">{{ cents(item.total_cents) }}</div>
        <div v-if="item.amount_paid_cents > 0" class="text-right text-[11px] text-emerald-600 font-semibold">
          Paid {{ cents(item.amount_paid_cents) }}
        </div>
      </template>
      <template #cell(status)="{ item }">
        <UiBadge :variant="statusVariant(item.status)" class="capitalize font-bold">{{ item.status }}</UiBadge>
      </template>
      <template #cell(actions)="{ item }">
        <div class="flex justify-end gap-1.5">
          <UiButton
            v-if="item.status === 'draft'"
            variant="outline"
            size="sm"
            :loading="markSentMutation.isPending.value"
            @click="markSentMutation.mutate(item.id)"
          >
            Mark Sent
          </UiButton>
          <UiButton
            v-if="item.status === 'sent'"
            variant="outline"
            size="sm"
            @click="openPaymentModal(item)"
          >
            Record Payment
          </UiButton>
          <UiButton
            v-if="item.status === 'draft' || item.status === 'sent'"
            size="sm"
            @click="markPaid(item)"
          >
            Mark Paid
          </UiButton>
        </div>
      </template>
    </UiTable>

    <!-- Customers Table with Actions & Stats -->
    <UiTable
      v-else
      :columns="customerColumns"
      :data="filteredCustomers"
      :loading="customersLoading"
    >
      <template #cell(name)="{ item }">
        <div class="py-1">
          <div class="font-bold text-slate-900 text-sm flex items-center gap-2">
            <span>{{ item.name }}</span>
          </div>
          <span class="text-[11px] text-slate-400 font-mono">ID: {{ item.id.substring(0, 8) }}…</span>
        </div>
      </template>

      <template #cell(contact)="{ item }">
        <div class="space-y-0.5 text-xs text-slate-600">
          <div v-if="item.email" class="flex items-center gap-1.5">
            <Mail class="w-3.5 h-3.5 text-slate-400" />
            <a :href="'mailto:' + item.email" class="hover:text-blue-600 hover:underline">{{ item.email }}</a>
          </div>
          <div v-if="item.phone" class="flex items-center gap-1.5">
            <Phone class="w-3.5 h-3.5 text-slate-400" />
            <span>{{ item.phone }}</span>
          </div>
          <span v-if="!item.email && !item.phone" class="text-slate-400 italic">No contact info</span>
        </div>
      </template>

      <template #cell(invoices_count)="{ item }">
        <button
          type="button"
          @click="viewCustomerInvoices(item)"
          class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700 hover:bg-blue-50 hover:text-blue-600 transition-colors"
        >
          <Receipt class="w-3.5 h-3.5" />
          <span>{{ item.invoices_count ?? 0 }}</span>
        </button>
      </template>

      <template #cell(financials)="{ item }">
        <div class="text-xs space-y-0.5 font-mono">
          <div class="flex items-center justify-between gap-4">
            <span class="text-slate-400">Invoiced:</span>
            <span class="font-bold text-slate-900">{{ cents(item.total_invoiced_cents ?? 0) }}</span>
          </div>
          <div class="flex items-center justify-between gap-4">
            <span class="text-slate-400">Paid:</span>
            <span class="font-semibold text-emerald-600">{{ cents(item.total_paid_cents ?? 0) }}</span>
          </div>
          <div v-if="(item.outstanding_cents ?? 0) > 0" class="flex items-center justify-between gap-4 pt-0.5 border-t border-slate-100">
            <span class="text-amber-600 font-bold">Due:</span>
            <span class="font-bold text-amber-600">{{ cents(item.outstanding_cents ?? 0) }}</span>
          </div>
        </div>
      </template>

      <!-- Customer Action Buttons -->
      <template #cell(actions)="{ item }">
        <div class="flex items-center justify-end gap-1.5">
          <!-- Create Invoice for Customer -->
          <UiButton
            size="sm"
            variant="outline"
            class="text-xs"
            @click="openInvoiceModal(item.id)"
            title="Create Invoice for this customer"
          >
            <Plus class="w-3.5 h-3.5 mr-1 text-primary-600" />
            Invoice
          </UiButton>

          <!-- Edit Customer -->
          <button
            type="button"
            class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-colors"
            @click="openEditCustomerModal(item)"
            title="Edit Customer"
          >
            <Edit2 class="w-4 h-4" />
          </button>

          <!-- Delete Customer -->
          <button
            type="button"
            class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition-colors disabled:opacity-30 disabled:hover:bg-transparent disabled:hover:text-slate-400"
            :disabled="(item.invoices_count ?? 0) > 0"
            @click="handleDeleteCustomer(item)"
            :title="(item.invoices_count ?? 0) > 0 ? 'Cannot delete customer with invoices' : 'Delete Customer'"
          >
            <Trash2 class="w-4 h-4" />
          </button>
        </div>
      </template>
    </UiTable>

    <!-- Create Invoice Modal -->
    <UiModal v-model="isInvoiceModalOpen" title="New Sales Invoice" size="2xl">
      <div class="space-y-5">
        <!-- Customer & Date Fields -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="flex gap-2 items-end">
            <UiSelect
              v-model="invoiceForm.customer_id"
              label="Customer"
              :options="customerOptions"
              placeholder="Select customer"
              class="flex-1"
            />
            <UiButton variant="outline" type="button" @click="openCustomerModal">New</UiButton>
          </div>
          <UiSelect
            v-model="invoiceForm.status"
            label="Status"
            :options="[
              { label: 'Sent (Posted to Accounting)', value: 'sent' },
              { label: 'Draft', value: 'draft' },
            ]"
          />
          <UiInput v-model="invoiceForm.issue_date" type="date" label="Issue Date" />
          <UiInput v-model="invoiceForm.due_date" type="date" label="Due Date" />
        </div>

        <!-- Line Items with Inventory Product Search -->
        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-sm font-bold text-slate-900">Line Items</h3>
              <p class="text-xs text-slate-400">Search products from your inventory catalog or type custom items.</p>
            </div>
            <UiButton variant="outline" size="sm" type="button" @click="addLine">
              <Plus class="w-3.5 h-3.5 mr-1" /> Add Line
            </UiButton>
          </div>

          <div class="space-y-3">
            <div
              v-for="(line, index) in invoiceForm.lines"
              :key="index"
              class="p-3.5 rounded-2xl border border-slate-200/90 bg-slate-50/50 hover:bg-slate-50 space-y-2.5 transition-colors"
            >
              <div class="grid grid-cols-12 gap-3 items-start">
                <!-- Product / Description Combobox -->
                <div class="col-span-12 md:col-span-6 relative">
                  <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                    Product / Description <span class="text-red-500">*</span>
                  </label>
                  <div class="relative">
                    <input
                      type="text"
                      v-model="line.description"
                      placeholder="Type or search inventory products…"
                      @focus="line.is_dropdown_open = true"
                      @input="line.is_dropdown_open = true"
                      class="block w-full rounded-xl border border-slate-200 bg-white text-sm py-2.5 pl-3 pr-8 font-medium text-slate-900 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all placeholder:text-slate-400"
                    />
                    <button
                      type="button"
                      @click="line.is_dropdown_open = !line.is_dropdown_open"
                      class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                    >
                      <ChevronDown class="w-4 h-4" />
                    </button>
                  </div>

                  <!-- Product Search Dropdown -->
                  <div
                    v-if="line.is_dropdown_open"
                    class="absolute left-0 right-0 top-full mt-1.5 z-30 bg-white rounded-xl shadow-xl border border-slate-200 py-1 max-h-56 overflow-y-auto"
                  >
                    <div class="px-3 py-1.5 text-[11px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100 flex items-center justify-between">
                      <span>Inventory Catalog</span>
                      <button
                        type="button"
                        class="text-slate-400 hover:text-slate-600 text-xs"
                        @click.stop="line.is_dropdown_open = false"
                      >
                        ✕
                      </button>
                    </div>

                    <div v-if="productsLoading" class="p-3 text-xs text-slate-400 text-center">
                      Loading products…
                    </div>

                    <template v-else>
                      <button
                        v-for="prod in getFilteredProducts(line.description)"
                        :key="prod.id"
                        type="button"
                        @click="selectProductForLine(line, prod)"
                        class="w-full px-3 py-2 text-left hover:bg-blue-50/80 flex items-center justify-between gap-2 border-b border-slate-50 last:border-0 transition-colors"
                      >
                        <div class="min-w-0">
                          <p class="text-xs font-bold text-slate-900 truncate">{{ prod.name }}</p>
                          <p class="text-[11px] text-slate-400 font-mono">SKU: {{ prod.sku || 'N/A' }}</p>
                        </div>
                        <div class="text-right shrink-0">
                          <span class="text-xs font-black text-slate-900">
                            {{ formatCurrency((prod.selling_price || 0) / 100) }}
                          </span>
                          <p class="text-[10px] text-emerald-600 font-medium">
                            {{ prod.available_quantity ?? prod.quantity_on_hand ?? 0 }} in stock
                          </p>
                        </div>
                      </button>

                      <div
                        v-if="getFilteredProducts(line.description).length === 0"
                        class="p-3 text-xs text-slate-500 text-center"
                      >
                        No inventory matches. Custom item description will be used.
                      </div>
                    </template>
                  </div>

                  <!-- Linked product pill -->
                  <div v-if="line.sku" class="mt-1 flex items-center gap-1.5 text-[11px] text-blue-600">
                    <Package class="w-3 h-3" />
                    <span class="font-mono font-semibold">{{ line.sku }}</span>
                  </div>
                </div>

                <!-- Quantity -->
                <div class="col-span-4 md:col-span-2">
                  <UiInput
                    v-model="line.quantity"
                    type="number"
                    label="Qty"
                    min="0.01"
                    step="1"
                  />
                </div>

                <!-- Unit Price -->
                <div class="col-span-5 md:col-span-3">
                  <UiInput
                    v-model="line.unit_price"
                    type="number"
                    label="Unit Price ($)"
                    step="0.01"
                    min="0"
                  />
                </div>

                <!-- Remove Button -->
                <div class="col-span-3 md:col-span-1 flex justify-end pt-6">
                  <button
                    type="button"
                    class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition-colors disabled:opacity-30"
                    :disabled="invoiceForm.lines.length === 1"
                    @click="removeLine(index)"
                    title="Remove line"
                  >
                    <Trash2 class="h-4 w-4" />
                  </button>
                </div>
              </div>

              <!-- Line Summary -->
              <div class="flex items-center justify-between text-xs text-slate-500 pt-1 border-t border-slate-200/50">
                <span>
                  Line Subtotal: <strong class="text-slate-800">{{ formatCurrency(Number(line.quantity || 0) * Number(line.unit_price || 0)) }}</strong>
                </span>
                <span v-if="Number(invoiceForm.tax_percent || 0) > 0" class="text-slate-600">
                  Unit Price + {{ invoiceForm.tax_percent }}% Tax: <strong class="text-slate-900">{{ formatCurrency(getLineEffectivePriceWithTax(Number(line.unit_price || 0))) }}</strong>
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Tax in Percentage Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 rounded-2xl bg-blue-50/50 border border-blue-100">
          <div class="space-y-2">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
              Tax Rate (%) <span class="text-slate-400 font-normal">Calculated & added to subtotal</span>
            </label>
            <div class="relative">
              <input
                v-model="invoiceForm.tax_percent"
                type="number"
                min="0"
                max="100"
                step="0.1"
                placeholder="0"
                class="block w-full rounded-xl border border-slate-200 bg-white text-sm py-2.5 pl-3 pr-10 font-bold text-slate-900 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all"
              />
              <div class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 font-bold text-sm">
                %
              </div>
            </div>

            <!-- Quick Preset Percentages -->
            <div class="flex items-center gap-1.5 flex-wrap">
              <span class="text-[11px] font-semibold text-slate-500 mr-1">Presets:</span>
              <button
                v-for="rate in [0, 5, 10, 15, 18, 20]"
                :key="rate"
                type="button"
                @click="invoiceForm.tax_percent = rate"
                :class="[
                  'px-2 py-0.5 rounded-lg text-xs font-bold transition-all',
                  Number(invoiceForm.tax_percent) === rate
                    ? 'bg-blue-600 text-white shadow-xs'
                    : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-100',
                ]"
              >
                {{ rate }}%
              </button>
            </div>
          </div>

          <!-- Notes -->
          <div class="space-y-1.5">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Invoice Notes</label>
            <textarea
              v-model="invoiceForm.notes"
              rows="2"
              class="block w-full rounded-xl border border-slate-200 bg-white text-xs p-2.5 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all placeholder:text-slate-400 font-medium text-slate-900"
              placeholder="Payment terms, bank details, or thank you message…"
            ></textarea>
          </div>
        </div>

        <!-- Real-time Financial Breakdown -->
        <div class="rounded-2xl bg-slate-900 text-white p-4 space-y-2 shadow-sm">
          <div class="flex items-center justify-between text-xs text-slate-300">
            <span>Subtotal (Net)</span>
            <span class="font-mono font-bold">{{ formatCurrency(invoiceSubtotal) }}</span>
          </div>

          <div class="flex items-center justify-between text-xs text-slate-300">
            <span>Tax ({{ Number(invoiceForm.tax_percent || 0) }}%)</span>
            <span class="font-mono font-bold text-amber-300">+ {{ formatCurrency(invoiceTaxDollars) }}</span>
          </div>

          <div class="pt-2 border-t border-slate-700/80 flex items-center justify-between text-sm">
            <span class="font-bold">Total Invoice Amount</span>
            <span class="font-black text-lg text-emerald-400 font-mono">{{ formatCurrency(invoiceTotal) }}</span>
          </div>
        </div>

        <!-- Modal Actions -->
        <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
          <UiButton variant="outline" @click="isInvoiceModalOpen = false">Cancel</UiButton>
          <UiButton
            :loading="createInvoiceMutation.isPending.value"
            :disabled="!invoiceForm.customer_id || invoiceForm.lines.some((l) => !l.description && !l.search_query)"
            @click="createInvoiceMutation.mutate()"
          >
            Create Invoice
          </UiButton>
        </div>
      </div>
    </UiModal>

    <!-- Create Customer Modal -->
    <UiModal v-model="isCustomerModalOpen" title="New Customer" size="md">
      <div class="space-y-4">
        <UiInput v-model="customerForm.name" label="Name" placeholder="Acme Corp" />
        <UiInput v-model="customerForm.email" type="email" label="Email" placeholder="billing@acme.com" />
        <UiInput v-model="customerForm.phone" label="Phone" placeholder="+1 555 0100" />
        <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
          <UiButton variant="outline" @click="isCustomerModalOpen = false">Cancel</UiButton>
          <UiButton
            :loading="createCustomerMutation.isPending.value"
            :disabled="!customerForm.name"
            @click="createCustomerMutation.mutate()"
          >
            Save Customer
          </UiButton>
        </div>
      </div>
    </UiModal>

    <!-- Edit Customer Modal -->
    <UiModal v-model="isEditCustomerModalOpen" title="Edit Customer" size="md">
      <div class="space-y-4">
        <UiInput v-model="customerForm.name" label="Name" placeholder="Acme Corp" />
        <UiInput v-model="customerForm.email" type="email" label="Email" placeholder="billing@acme.com" />
        <UiInput v-model="customerForm.phone" label="Phone" placeholder="+1 555 0100" />
        <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
          <UiButton variant="outline" @click="isEditCustomerModalOpen = false">Cancel</UiButton>
          <UiButton
            :loading="updateCustomerMutation.isPending.value"
            :disabled="!customerForm.name"
            @click="handleSaveCustomerEdit"
          >
            Update Customer
          </UiButton>
        </div>
      </div>
    </UiModal>

    <!-- Record Payment Modal -->
    <UiModal v-model="isPaymentModalOpen" title="Record Payment" size="md">
      <div class="space-y-4">
        <p v-if="selectedInvoice" class="text-sm text-slate-500">
          Invoice {{ selectedInvoice.number }} · Outstanding
          {{ cents(selectedInvoice.total_cents - selectedInvoice.amount_paid_cents) }}
        </p>
        <UiInput v-model="paymentForm.amount_dollars" type="number" label="Amount" step="0.01" />
        <UiSelect
          v-model="paymentForm.method"
          label="Method"
          :options="[
            { label: 'Cash', value: 'cash' },
            { label: 'Card', value: 'card' },
            { label: 'Bank Transfer', value: 'bank_transfer' },
            { label: 'Other', value: 'other' },
          ]"
        />
        <UiInput v-model="paymentForm.reference" label="Reference" placeholder="Optional" />
        <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
          <UiButton variant="outline" @click="isPaymentModalOpen = false">Cancel</UiButton>
          <UiButton
            :loading="recordPaymentMutation.isPending.value"
            :disabled="!paymentForm.amount_dollars"
            @click="submitPayment"
          >
            Record Payment
          </UiButton>
        </div>
      </div>
    </UiModal>
  </div>
</template>
