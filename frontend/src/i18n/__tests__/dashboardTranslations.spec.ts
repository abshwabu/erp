import { describe, it, expect } from 'vitest'
import enDashboard from '../locales/en/dashboard'
import amDashboard from '../locales/am/dashboard'
import enOnboarding from '../locales/en/onboarding'
import amOnboarding from '../locales/am/onboarding'
import enInventory from '../locales/en/inventory'
import amInventory from '../locales/am/inventory'
import en from '../locales/en'
import am from '../locales/am'

describe('Dashboard and Onboarding Translations Parity', () => {
  it('registers dashboard, onboarding, and inventory in index modules for both en and am', () => {
    expect(en.dashboard).toBeDefined()
    expect(am.dashboard).toBeDefined()
    expect(en.onboarding).toBeDefined()
    expect(am.onboarding).toBeDefined()
    expect(en.inventory).toBeDefined()
    expect(am.inventory).toBeDefined()
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
