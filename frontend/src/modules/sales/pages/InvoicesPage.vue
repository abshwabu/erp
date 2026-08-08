<script setup lang="ts">
import { computed, ref } from 'vue'
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { salesApi, type Invoice, type InvoiceStatus } from '@/api/sales'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiTable from '@/components/ui/UiTable.vue'
import { formatCurrency, formatDate } from '@/utils/format'
import { Plus, Search, FileText, Users, Trash2 } from '@lucide/vue'

const queryClient = useQueryClient()
const activeTab = ref<'invoices' | 'customers'>('invoices')
const searchQuery = ref('')
const isInvoiceModalOpen = ref(false)
const isCustomerModalOpen = ref(false)
const isPaymentModalOpen = ref(false)
const selectedInvoice = ref<Invoice | null>(null)
const errorMessage = ref('')

const today = new Date().toISOString().slice(0, 10)
const dueDefault = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10)

const invoiceForm = ref({
  customer_id: '',
  status: 'sent' as 'draft' | 'sent',
  issue_date: today,
  due_date: dueDefault,
  tax_dollars: 0,
  notes: '',
  lines: [{ description: '', quantity: 1, unit_price: 0 }],
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

const { data: invoices, isLoading: invoicesLoading } = useQuery({
  queryKey: ['sales', 'invoices'],
  queryFn: () => salesApi.getInvoices().then((res) => res.data.data),
})

const { data: customers, isLoading: customersLoading } = useQuery({
  queryKey: ['sales', 'customers'],
  queryFn: () => salesApi.getCustomers().then((res) => res.data.data),
})

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
    errorMessage.value = err?.message || 'Failed to create customer'
  },
})

const createInvoiceMutation = useMutation({
  mutationFn: () =>
    salesApi.createInvoice({
      customer_id: invoiceForm.value.customer_id,
      status: invoiceForm.value.status,
      issue_date: invoiceForm.value.issue_date,
      due_date: invoiceForm.value.due_date,
      tax_cents: Math.round(Number(invoiceForm.value.tax_dollars || 0) * 100),
      notes: invoiceForm.value.notes || null,
      lines: invoiceForm.value.lines.map((line) => ({
        description: line.description,
        quantity: Number(line.quantity),
        unit_price_cents: Math.round(Number(line.unit_price || 0) * 100),
      })),
    }),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['sales', 'invoices'] })
    isInvoiceModalOpen.value = false
    resetInvoiceForm()
    errorMessage.value = ''
  },
  onError: (err: any) => {
    errorMessage.value = err?.message || 'Failed to create invoice'
  },
})

const markSentMutation = useMutation({
  mutationFn: (id: string) => salesApi.markSent(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['sales', 'invoices'] })
  },
  onError: (err: any) => {
    errorMessage.value = err?.message || 'Failed to mark invoice as sent'
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
    isPaymentModalOpen.value = false
    selectedInvoice.value = null
    errorMessage.value = ''
  },
  onError: (err: any) => {
    errorMessage.value = err?.message || 'Failed to record payment'
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
  { key: 'name', label: 'Name' },
  { key: 'email', label: 'Email' },
  { key: 'phone', label: 'Phone' },
]

const invoiceSubtotal = computed(() =>
  invoiceForm.value.lines.reduce(
    (sum, line) => sum + Number(line.quantity || 0) * Number(line.unit_price || 0),
    0
  )
)

const invoiceTotal = computed(
  () => invoiceSubtotal.value + Number(invoiceForm.value.tax_dollars || 0)
)

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
    tax_dollars: 0,
    notes: '',
    lines: [{ description: '', quantity: 1, unit_price: 0 }],
  }
}

const openInvoiceModal = () => {
  errorMessage.value = ''
  resetInvoiceForm()
  isInvoiceModalOpen.value = true
}

const openCustomerModal = () => {
  errorMessage.value = ''
  customerForm.value = { name: '', email: '', phone: '' }
  isCustomerModalOpen.value = true
}

const addLine = () => {
  invoiceForm.value.lines.push({ description: '', quantity: 1, unit_price: 0 })
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
        <p class="text-slate-500">Create customer invoices and record payments.</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <UiButton variant="outline" @click="openCustomerModal">
          <Users class="h-4 w-4 mr-2" /> New Customer
        </UiButton>
        <UiButton @click="openInvoiceModal">
          <Plus class="h-4 w-4 mr-2" /> New Invoice
        </UiButton>
      </div>
    </div>

    <div v-if="errorMessage" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      {{ errorMessage }}
    </div>

    <div class="flex gap-2 border-b border-slate-200">
      <button
        class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors"
        :class="activeTab === 'invoices' ? 'border-primary-600 text-primary-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
        @click="activeTab = 'invoices'"
      >
        <span class="inline-flex items-center gap-2"><FileText class="h-4 w-4" /> Invoices</span>
      </button>
      <button
        class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors"
        :class="activeTab === 'customers' ? 'border-primary-600 text-primary-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
        @click="activeTab = 'customers'"
      >
        <span class="inline-flex items-center gap-2"><Users class="h-4 w-4" /> Customers</span>
      </button>
    </div>

    <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm">
      <UiInput v-model="searchQuery" :placeholder="activeTab === 'invoices' ? 'Search invoices...' : 'Search customers...'" class="w-full max-w-sm">
        <template #prefix><Search class="h-4 w-4 text-slate-400" /></template>
      </UiInput>
    </div>

    <UiTable
      v-if="activeTab === 'invoices'"
      :columns="invoiceColumns"
      :data="filteredInvoices"
      :loading="invoicesLoading"
    >
      <template #cell(number)="{ item }">
        <div class="font-medium text-slate-900">{{ item.number }}</div>
      </template>
      <template #cell(customer)="{ item }">
        <div class="text-slate-700">{{ item.customer?.name || '—' }}</div>
      </template>
      <template #cell(issue_date)="{ item }">
        <span class="text-slate-600">{{ formatDate(item.issue_date) }}</span>
      </template>
      <template #cell(due_date)="{ item }">
        <span class="text-slate-600">{{ formatDate(item.due_date) }}</span>
      </template>
      <template #cell(total)="{ item }">
        <div class="text-right font-medium text-slate-900">{{ cents(item.total_cents) }}</div>
        <div v-if="item.amount_paid_cents > 0" class="text-right text-xs text-slate-500">
          Paid {{ cents(item.amount_paid_cents) }}
        </div>
      </template>
      <template #cell(status)="{ item }">
        <UiBadge :variant="statusVariant(item.status)">{{ item.status }}</UiBadge>
      </template>
      <template #cell(actions)="{ item }">
        <div class="flex justify-end gap-2">
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

    <UiTable
      v-else
      :columns="customerColumns"
      :data="filteredCustomers"
      :loading="customersLoading"
    >
      <template #cell(name)="{ item }">
        <div class="font-medium text-slate-900">{{ item.name }}</div>
      </template>
      <template #cell(email)="{ item }">
        <span class="text-slate-600">{{ item.email || '—' }}</span>
      </template>
      <template #cell(phone)="{ item }">
        <span class="text-slate-600">{{ item.phone || '—' }}</span>
      </template>
    </UiTable>

    <!-- Create Invoice -->
    <UiModal v-model="isInvoiceModalOpen" title="New Invoice" size="xl">
      <div class="space-y-4">
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
              { label: 'Draft', value: 'draft' },
              { label: 'Sent', value: 'sent' },
            ]"
          />
          <UiInput v-model="invoiceForm.issue_date" type="date" label="Issue Date" />
          <UiInput v-model="invoiceForm.due_date" type="date" label="Due Date" />
        </div>

        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-900">Line items</h3>
            <UiButton variant="outline" size="sm" type="button" @click="addLine">Add Line</UiButton>
          </div>
          <div
            v-for="(line, index) in invoiceForm.lines"
            :key="index"
            class="grid grid-cols-12 gap-2 items-end"
          >
            <div class="col-span-12 md:col-span-5">
              <UiInput v-model="line.description" label="Description" placeholder="Service or product" />
            </div>
            <div class="col-span-4 md:col-span-2">
              <UiInput v-model="line.quantity" type="number" label="Qty" />
            </div>
            <div class="col-span-5 md:col-span-3">
              <UiInput v-model="line.unit_price" type="number" label="Unit Price" step="0.01" />
            </div>
            <div class="col-span-3 md:col-span-2 flex justify-end pb-1">
              <UiButton variant="ghost" size="sm" type="button" class="text-red-500" @click="removeLine(index)">
                <Trash2 class="h-4 w-4" />
              </UiButton>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <UiInput v-model="invoiceForm.tax_dollars" type="number" label="Tax" step="0.01" />
          <UiInput v-model="invoiceForm.notes" label="Notes" placeholder="Optional" class="md:col-span-2" />
        </div>

        <div class="rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm flex justify-between">
          <span class="text-slate-500">Subtotal {{ formatCurrency(invoiceSubtotal) }} · Tax {{ formatCurrency(Number(invoiceForm.tax_dollars || 0)) }}</span>
          <span class="font-semibold text-slate-900">Total {{ formatCurrency(invoiceTotal) }}</span>
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
          <UiButton variant="outline" @click="isInvoiceModalOpen = false">Cancel</UiButton>
          <UiButton
            :loading="createInvoiceMutation.isPending.value"
            :disabled="!invoiceForm.customer_id || invoiceForm.lines.some((l) => !l.description)"
            @click="createInvoiceMutation.mutate()"
          >
            Create Invoice
          </UiButton>
        </div>
      </div>
    </UiModal>

    <!-- Create Customer -->
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

    <!-- Record Payment -->
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
