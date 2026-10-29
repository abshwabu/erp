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
import en from '../locales/en'
import am from '../locales/am'

describe('Dashboard and Onboarding Translations Parity', () => {
  it('registers dashboard, onboarding, inventory, warehouse, shops, procurement, manufacturing, and documents in index modules for both en and am', () => {
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
