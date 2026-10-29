import { describe, it, expect } from 'vitest'
import enDashboard from '../locales/en/dashboard'
import amDashboard from '../locales/am/dashboard'
import enOnboarding from '../locales/en/onboarding'
import amOnboarding from '../locales/am/onboarding'
import enInventory from '../locales/en/inventory'
import amInventory from '../locales/am/inventory'
import enWarehouse from '../locales/en/warehouse'
import amWarehouse from '../locales/am/warehouse'
import enShops from '../locales/en/shops'
import amShops from '../locales/am/shops'
import enProcurement from '../locales/en/procurement'
import amProcurement from '../locales/am/procurement'
import enManufacturing from '../locales/en/manufacturing'
import amManufacturing from '../locales/am/manufacturing'
import enDocuments from '../locales/en/documents'
import amDocuments from '../locales/am/documents'
import enSales from '../locales/en/sales'
import amSales from '../locales/am/sales'
import enHr from '../locales/en/hr'
import amHr from '../locales/am/hr'
import enCrm from '../locales/en/crm'
import amCrm from '../locales/am/crm'
import enPayroll from '../locales/en/payroll'
import amPayroll from '../locales/am/payroll'
import en from '../locales/en'
import am from '../locales/am'

describe('Dashboard and Onboarding Translations Parity', () => {
  it('registers dashboard, onboarding, inventory, warehouse, shops, procurement, manufacturing, documents, sales, hr, crm, and payroll in index modules for both en and am', () => {
    expect(en.dashboard).toBeDefined()
    expect(am.dashboard).toBeDefined()
    expect(en.onboarding).toBeDefined()
    expect(am.onboarding).toBeDefined()
    expect(en.inventory).toBeDefined()
    expect(am.inventory).toBeDefined()
    expect(en.warehouse).toBeDefined()
    expect(am.warehouse).toBeDefined()
    expect(en.shops).toBeDefined()
    expect(am.shops).toBeDefined()
    expect(en.procurement).toBeDefined()
    expect(am.procurement).toBeDefined()
    expect(en.manufacturing).toBeDefined()
    expect(am.manufacturing).toBeDefined()
    expect(en.documents).toBeDefined()
    expect(am.documents).toBeDefined()
    expect(en.sales).toBeDefined()
    expect(am.sales).toBeDefined()
    expect(en.hr).toBeDefined()
    expect(am.hr).toBeDefined()
    expect(en.crm).toBeDefined()
    expect(am.crm).toBeDefined()
    expect(en.payroll).toBeDefined()
    expect(am.payroll).toBeDefined()
  })

  it('has identical keys for crm in en and am', () => {
    const enKeys = Object.keys(enCrm).sort()
    const amKeys = Object.keys(amCrm).sort()
    expect(enKeys).toEqual(amKeys)

    expect(Object.keys(enCrm.common).sort()).toEqual(Object.keys(amCrm.common).sort())

    expect(Object.keys(enCrm.dashboard).sort()).toEqual(Object.keys(amCrm.dashboard).sort())
    expect(Object.keys(enCrm.dashboard.kpis).sort()).toEqual(Object.keys(amCrm.dashboard.kpis).sort())
    expect(Object.keys(enCrm.dashboard.stages).sort()).toEqual(Object.keys(amCrm.dashboard.stages).sort())
    expect(Object.keys(enCrm.dashboard.buttons).sort()).toEqual(Object.keys(amCrm.dashboard.buttons).sort())

    expect(Object.keys(enCrm.leads).sort()).toEqual(Object.keys(amCrm.leads).sort())
    expect(Object.keys(enCrm.leads.kpis).sort()).toEqual(Object.keys(amCrm.leads.kpis).sort())
    expect(Object.keys(enCrm.leads.statusFilters).sort()).toEqual(Object.keys(amCrm.leads.statusFilters).sort())
    expect(Object.keys(enCrm.leads.table).sort()).toEqual(Object.keys(amCrm.leads.table).sort())
    expect(Object.keys(enCrm.leads.statuses).sort()).toEqual(Object.keys(amCrm.leads.statuses).sort())
    expect(Object.keys(enCrm.leads.priorities).sort()).toEqual(Object.keys(amCrm.leads.priorities).sort())
    expect(Object.keys(enCrm.leads.sources).sort()).toEqual(Object.keys(amCrm.leads.sources).sort())
    expect(Object.keys(enCrm.leads.detailsModal).sort()).toEqual(Object.keys(amCrm.leads.detailsModal).sort())
    expect(Object.keys(enCrm.leads.formModal).sort()).toEqual(Object.keys(amCrm.leads.formModal).sort())
    expect(Object.keys(enCrm.leads.convertModal).sort()).toEqual(Object.keys(amCrm.leads.convertModal).sort())
    expect(Object.keys(enCrm.leads.messages).sort()).toEqual(Object.keys(amCrm.leads.messages).sort())

    expect(Object.keys(enCrm.deals).sort()).toEqual(Object.keys(amCrm.deals).sort())
    expect(Object.keys(enCrm.deals.kpis).sort()).toEqual(Object.keys(amCrm.deals.kpis).sort())
    expect(Object.keys(enCrm.deals.kanban).sort()).toEqual(Object.keys(amCrm.deals.kanban).sort())
    expect(Object.keys(enCrm.deals.table).sort()).toEqual(Object.keys(amCrm.deals.table).sort())
    expect(Object.keys(enCrm.deals.stages).sort()).toEqual(Object.keys(amCrm.deals.stages).sort())
    expect(Object.keys(enCrm.deals.modal).sort()).toEqual(Object.keys(amCrm.deals.modal).sort())
    expect(Object.keys(enCrm.deals.messages).sort()).toEqual(Object.keys(amCrm.deals.messages).sort())

    expect(Object.keys(enCrm.contacts).sort()).toEqual(Object.keys(amCrm.contacts).sort())
    expect(Object.keys(enCrm.contacts.kpis).sort()).toEqual(Object.keys(amCrm.contacts.kpis).sort())
    expect(Object.keys(enCrm.contacts.statusFilters).sort()).toEqual(Object.keys(amCrm.contacts.statusFilters).sort())
    expect(Object.keys(enCrm.contacts.table).sort()).toEqual(Object.keys(amCrm.contacts.table).sort())
    expect(Object.keys(enCrm.contacts.statuses).sort()).toEqual(Object.keys(amCrm.contacts.statuses).sort())
    expect(Object.keys(enCrm.contacts.modal).sort()).toEqual(Object.keys(amCrm.contacts.modal).sort())
    expect(Object.keys(enCrm.contacts.profileModal).sort()).toEqual(Object.keys(amCrm.contacts.profileModal).sort())
    expect(Object.keys(enCrm.contacts.messages).sort()).toEqual(Object.keys(amCrm.contacts.messages).sort())

    expect(Object.keys(enCrm.activities).sort()).toEqual(Object.keys(amCrm.activities).sort())
    expect(Object.keys(enCrm.activities.kpis).sort()).toEqual(Object.keys(amCrm.activities.kpis).sort())
    expect(Object.keys(enCrm.activities.filters).sort()).toEqual(Object.keys(amCrm.activities.filters).sort())
    expect(Object.keys(enCrm.activities.types).sort()).toEqual(Object.keys(amCrm.activities.types).sort())
    expect(Object.keys(enCrm.activities.priorities).sort()).toEqual(Object.keys(amCrm.activities.priorities).sort())
    expect(Object.keys(enCrm.activities.modal).sort()).toEqual(Object.keys(amCrm.activities.modal).sort())
    expect(Object.keys(enCrm.activities.messages).sort()).toEqual(Object.keys(amCrm.activities.messages).sort())

    expect(Object.keys(enCrm.leadForms).sort()).toEqual(Object.keys(amCrm.leadForms).sort())
    expect(Object.keys(enCrm.leadForms.kpis).sort()).toEqual(Object.keys(amCrm.leadForms.kpis).sort())
    expect(Object.keys(enCrm.leadForms.card).sort()).toEqual(Object.keys(amCrm.leadForms.card).sort())
    expect(Object.keys(enCrm.leadForms.modal).sort()).toEqual(Object.keys(amCrm.leadForms.modal).sort())
    expect(Object.keys(enCrm.leadForms.embedModal).sort()).toEqual(Object.keys(amCrm.leadForms.embedModal).sort())
    expect(Object.keys(enCrm.leadForms.questionTypes).sort()).toEqual(Object.keys(amCrm.leadForms.questionTypes).sort())
    expect(Object.keys(enCrm.leadForms.messages).sort()).toEqual(Object.keys(amCrm.leadForms.messages).sort())

    expect(Object.keys(enCrm.publicLead).sort()).toEqual(Object.keys(amCrm.publicLead).sort())
  })

  it('has identical keys for payroll in en and am', () => {
    const enKeys = Object.keys(enPayroll).sort()
    const amKeys = Object.keys(amPayroll).sort()
    expect(enKeys).toEqual(amKeys)

    expect(Object.keys(enPayroll.header).sort()).toEqual(Object.keys(amPayroll.header).sort())
    expect(Object.keys(enPayroll.kpis).sort()).toEqual(Object.keys(amPayroll.kpis).sort())
    expect(Object.keys(enPayroll.runs).sort()).toEqual(Object.keys(amPayroll.runs).sort())
    expect(Object.keys(enPayroll.runs.filters).sort()).toEqual(Object.keys(amPayroll.runs.filters).sort())
    expect(Object.keys(enPayroll.details).sort()).toEqual(Object.keys(amPayroll.details).sort())
    expect(Object.keys(enPayroll.createModal).sort()).toEqual(Object.keys(amPayroll.createModal).sort())
    expect(Object.keys(enPayroll.createModal.presets).sort()).toEqual(Object.keys(amPayroll.createModal.presets).sort())
    expect(Object.keys(enPayroll.payslipModal).sort()).toEqual(Object.keys(amPayroll.payslipModal).sort())
    expect(Object.keys(enPayroll.messages).sort()).toEqual(Object.keys(amPayroll.messages).sort())
  })

  it('has identical keys for hr in en and am', () => {
    const enKeys = Object.keys(enHr).sort()
    const amKeys = Object.keys(amHr).sort()
    expect(enKeys).toEqual(amKeys)

    expect(Object.keys(enHr.employees).sort()).toEqual(Object.keys(amHr.employees).sort())
    expect(Object.keys(enHr.employees.stats).sort()).toEqual(Object.keys(amHr.employees.stats).sort())
    expect(Object.keys(enHr.employees.columns).sort()).toEqual(Object.keys(amHr.employees.columns).sort())
    expect(Object.keys(enHr.employees.statuses).sort()).toEqual(Object.keys(amHr.employees.statuses).sort())
    expect(Object.keys(enHr.employees.types).sort()).toEqual(Object.keys(amHr.employees.types).sort())

    expect(Object.keys(enHr.employeeModal).sort()).toEqual(Object.keys(amHr.employeeModal).sort())
    expect(Object.keys(enHr.employeeModal.genders).sort()).toEqual(Object.keys(amHr.employeeModal.genders).sort())
    expect(Object.keys(enHr.employeeModal.frequencies).sort()).toEqual(Object.keys(amHr.employeeModal.frequencies).sort())
    expect(Object.keys(enHr.employeeModal.methods).sort()).toEqual(Object.keys(amHr.employeeModal.methods).sort())
    expect(Object.keys(enHr.employeeModal.quickDept).sort()).toEqual(Object.keys(amHr.employeeModal.quickDept).sort())
    expect(Object.keys(enHr.employeeModal.quickPosition).sort()).toEqual(Object.keys(amHr.employeeModal.quickPosition).sort())

    expect(Object.keys(enHr.departments).sort()).toEqual(Object.keys(amHr.departments).sort())
    expect(Object.keys(enHr.departments.stats).sort()).toEqual(Object.keys(amHr.departments.stats).sort())
    expect(Object.keys(enHr.departments.columns).sort()).toEqual(Object.keys(amHr.departments.columns).sort())

    expect(Object.keys(enHr.attendance).sort()).toEqual(Object.keys(amHr.attendance).sort())
    expect(Object.keys(enHr.attendance.kpis).sort()).toEqual(Object.keys(amHr.attendance.kpis).sort())
    expect(Object.keys(enHr.attendance.columns).sort()).toEqual(Object.keys(amHr.attendance.columns).sort())
    expect(Object.keys(enHr.attendance.statuses).sort()).toEqual(Object.keys(amHr.attendance.statuses).sort())
    expect(Object.keys(enHr.attendance.modal).sort()).toEqual(Object.keys(amHr.attendance.modal).sort())

    expect(Object.keys(enHr.leave).sort()).toEqual(Object.keys(amHr.leave).sort())
    expect(Object.keys(enHr.leave.tabs).sort()).toEqual(Object.keys(amHr.leave.tabs).sort())
    expect(Object.keys(enHr.leave.columns).sort()).toEqual(Object.keys(amHr.leave.columns).sort())
    expect(Object.keys(enHr.leave.statuses).sort()).toEqual(Object.keys(amHr.leave.statuses).sort())
    expect(Object.keys(enHr.leave.weekdays).sort()).toEqual(Object.keys(amHr.leave.weekdays).sort())
    expect(Object.keys(enHr.leave.modal).sort()).toEqual(Object.keys(amHr.leave.modal).sort())

    expect(Object.keys(enHr.orgChart).sort()).toEqual(Object.keys(amHr.orgChart).sort())

    expect(Object.keys(enHr.jobs).sort()).toEqual(Object.keys(amHr.jobs).sort())
    expect(Object.keys(enHr.jobs.stats).sort()).toEqual(Object.keys(amHr.jobs.stats).sort())
    expect(Object.keys(enHr.jobs.statusFilters).sort()).toEqual(Object.keys(amHr.jobs.statusFilters).sort())
    expect(Object.keys(enHr.jobs.badges).sort()).toEqual(Object.keys(amHr.jobs.badges).sort())
  })

  it('has identical keys for sales in en and am', () => {
    const enKeys = Object.keys(enSales).sort()
    const amKeys = Object.keys(amSales).sort()
    expect(enKeys).toEqual(amKeys)

    expect(Object.keys(enSales.kpis).sort()).toEqual(Object.keys(amSales.kpis).sort())
    expect(Object.keys(enSales.tabs).sort()).toEqual(Object.keys(amSales.tabs).sort())
    expect(Object.keys(enSales.invoiceTable).sort()).toEqual(Object.keys(amSales.invoiceTable).sort())
    expect(Object.keys(enSales.invoiceTable.columns).sort()).toEqual(Object.keys(amSales.invoiceTable.columns).sort())
    expect(Object.keys(enSales.invoiceTable.actions).sort()).toEqual(Object.keys(amSales.invoiceTable.actions).sort())
    expect(Object.keys(enSales.customerTable).sort()).toEqual(Object.keys(amSales.customerTable).sort())
    expect(Object.keys(enSales.customerTable.columns).sort()).toEqual(Object.keys(amSales.customerTable.columns).sort())
    expect(Object.keys(enSales.customerTable.actions).sort()).toEqual(Object.keys(amSales.customerTable.actions).sort())
    expect(Object.keys(enSales.status).sort()).toEqual(Object.keys(amSales.status).sort())
    expect(Object.keys(enSales.createInvoiceModal).sort()).toEqual(Object.keys(amSales.createInvoiceModal).sort())
    expect(Object.keys(enSales.customerModal).sort()).toEqual(Object.keys(amSales.customerModal).sort())
    expect(Object.keys(enSales.paymentModal).sort()).toEqual(Object.keys(amSales.paymentModal).sort())
    expect(Object.keys(enSales.paymentModal.methods).sort()).toEqual(Object.keys(amSales.paymentModal.methods).sort())
    expect(Object.keys(enSales.messages).sort()).toEqual(Object.keys(amSales.messages).sort())
  })

  it('has identical keys for documents in en and am', () => {
    const enKeys = Object.keys(enDocuments).sort()
    const amKeys = Object.keys(amDocuments).sort()
    expect(enKeys).toEqual(amKeys)

    expect(Object.keys(enDocuments.kpis).sort()).toEqual(Object.keys(amDocuments.kpis).sort())
    expect(Object.keys(enDocuments.folders).sort()).toEqual(Object.keys(amDocuments.folders).sort())
    expect(Object.keys(enDocuments.table).sort()).toEqual(Object.keys(amDocuments.table).sort())
    expect(Object.keys(enDocuments.uploadModal).sort()).toEqual(Object.keys(amDocuments.uploadModal).sort())
    expect(Object.keys(enDocuments.messages).sort()).toEqual(Object.keys(amDocuments.messages).sort())
  })

  it('has identical keys for manufacturing in en and am', () => {
    const enKeys = Object.keys(enManufacturing).sort()
    const amKeys = Object.keys(amManufacturing).sort()
    expect(enKeys).toEqual(amKeys)

    expect(Object.keys(enManufacturing.kpis).sort()).toEqual(Object.keys(amManufacturing.kpis).sort())
    expect(Object.keys(enManufacturing.tabs).sort()).toEqual(Object.keys(amManufacturing.tabs).sort())
    expect(Object.keys(enManufacturing.priorities).sort()).toEqual(Object.keys(amManufacturing.priorities).sort())
    expect(Object.keys(enManufacturing.statuses).sort()).toEqual(Object.keys(amManufacturing.statuses).sort())
    expect(Object.keys(enManufacturing.workOrdersTable).sort()).toEqual(Object.keys(amManufacturing.workOrdersTable).sort())
    expect(Object.keys(enManufacturing.bomsTable).sort()).toEqual(Object.keys(amManufacturing.bomsTable).sort())
    expect(Object.keys(enManufacturing.createWoModal).sort()).toEqual(Object.keys(amManufacturing.createWoModal).sort())
    expect(Object.keys(enManufacturing.createBomModal).sort()).toEqual(Object.keys(amManufacturing.createBomModal).sort())
    expect(Object.keys(enManufacturing.completeWoModal).sort()).toEqual(Object.keys(amManufacturing.completeWoModal).sort())
    expect(Object.keys(enManufacturing.woDetailsModal).sort()).toEqual(Object.keys(amManufacturing.woDetailsModal).sort())
    expect(Object.keys(enManufacturing.woDetailsModal.columns).sort()).toEqual(Object.keys(amManufacturing.woDetailsModal.columns).sort())
    expect(Object.keys(enManufacturing.bomDetailsModal).sort()).toEqual(Object.keys(amManufacturing.bomDetailsModal).sort())
    expect(Object.keys(enManufacturing.bomDetailsModal.columns).sort()).toEqual(Object.keys(amManufacturing.bomDetailsModal.columns).sort())
    expect(Object.keys(enManufacturing.messages).sort()).toEqual(Object.keys(amManufacturing.messages).sort())
  })

  it('has identical keys for procurement in en and am', () => {
    const enKeys = Object.keys(enProcurement).sort()
    const amKeys = Object.keys(amProcurement).sort()
    expect(enKeys).toEqual(amKeys)

    expect(Object.keys(enProcurement.table).sort()).toEqual(Object.keys(amProcurement.table).sort())
    expect(Object.keys(enProcurement.status).sort()).toEqual(Object.keys(amProcurement.status).sort())
    expect(Object.keys(enProcurement.createModal).sort()).toEqual(Object.keys(amProcurement.createModal).sort())
    expect(Object.keys(enProcurement.supplierModal).sort()).toEqual(Object.keys(amProcurement.supplierModal).sort())
    expect(Object.keys(enProcurement.receiveModal).sort()).toEqual(Object.keys(amProcurement.receiveModal).sort())
    expect(Object.keys(enProcurement.detailsModal).sort()).toEqual(Object.keys(amProcurement.detailsModal).sort())
    expect(Object.keys(enProcurement.detailsModal.columns).sort()).toEqual(Object.keys(amProcurement.detailsModal.columns).sort())
    expect(Object.keys(enProcurement.messages).sort()).toEqual(Object.keys(amProcurement.messages).sort())
  })

  it('has identical keys for shops in en and am', () => {
    const enKeys = Object.keys(enShops).sort()
    const amKeys = Object.keys(amShops).sort()
    expect(enKeys).toEqual(amKeys)

    expect(Object.keys(enShops.columns).sort()).toEqual(Object.keys(amShops.columns).sort())
    expect(Object.keys(enShops.stockModes).sort()).toEqual(Object.keys(amShops.stockModes).sort())
    expect(Object.keys(enShops.createModal).sort()).toEqual(Object.keys(amShops.createModal).sort())
    expect(Object.keys(enShops.errors).sort()).toEqual(Object.keys(amShops.errors).sort())
    expect(Object.keys(enShops.detail).sort()).toEqual(Object.keys(amShops.detail).sort())
    expect(Object.keys(enShops.detail.tabs).sort()).toEqual(Object.keys(amShops.detail.tabs).sort())
    expect(Object.keys(enShops.detail.settings).sort()).toEqual(Object.keys(amShops.detail.settings).sort())
    expect(Object.keys(enShops.detail.stock).sort()).toEqual(Object.keys(amShops.detail.stock).sort())
    expect(Object.keys(enShops.detail.stock.columns).sort()).toEqual(Object.keys(amShops.detail.stock.columns).sort())
    expect(Object.keys(enShops.detail.keepers).sort()).toEqual(Object.keys(amShops.detail.keepers).sort())
  })

  it('has identical keys for warehouse in en and am', () => {
    const enKeys = Object.keys(enWarehouse).sort()
    const amKeys = Object.keys(amWarehouse).sort()
    expect(enKeys).toEqual(amKeys)

    for (const key of enKeys) {
      expect((enWarehouse as any)[key]).toBeTruthy()
      expect((amWarehouse as any)[key]).toBeTruthy()
    }
  })

  it('has identical keys for inventory in en and am', () => {
    const enKeys = Object.keys(enInventory).sort()
    const amKeys = Object.keys(amInventory).sort()
    expect(enKeys).toEqual(amKeys)

    for (const key of enKeys) {
      expect((enInventory as any)[key]).toBeTruthy()
      expect((amInventory as any)[key]).toBeTruthy()
    }
  })

  it('has identical keys for dashboard in en and am', () => {
    const enKeys = Object.keys(enDashboard).sort()
    const amKeys = Object.keys(amDashboard).sort()
    expect(enKeys).toEqual(amKeys)

    // Ensure no empty translation strings
    for (const key of enKeys) {
      expect((enDashboard as any)[key]).toBeTruthy()
      expect((amDashboard as any)[key]).toBeTruthy()
    }
  })

  it('has identical top-level keys for onboarding in en and am', () => {
    const enKeys = Object.keys(enOnboarding).sort()
    const amKeys = Object.keys(amOnboarding).sort()
    expect(enKeys).toEqual(amKeys)
  })

  it('has all 7 onboarding steps localized in both en and am', () => {
    const expectedSteps = [
      'company_profile',
      'enable_modules',
      'add_products',
      'launch_pos',
      'team_roles',
      'create_invoice',
      'financial_reports',
    ]

    expect(Object.keys(enOnboarding.steps).sort()).toEqual(expectedSteps.sort())
    expect(Object.keys(amOnboarding.steps).sort()).toEqual(expectedSteps.sort())

    for (const stepKey of expectedSteps) {
      const enStep = (enOnboarding.steps as any)[stepKey]
      const amStep = (amOnboarding.steps as any)[stepKey]

      expect(enStep.title).toBeTruthy()
      expect(amStep.title).toBeTruthy()
      expect(enStep.description).toBeTruthy()
      expect(amStep.description).toBeTruthy()
      expect(enStep.actionLabel).toBeTruthy()
      expect(amStep.actionLabel).toBeTruthy()
    }
  })
})
