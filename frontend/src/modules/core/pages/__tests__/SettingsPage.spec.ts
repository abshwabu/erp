import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import en from '@/i18n/locales/en'
import am from '@/i18n/locales/am'
import SettingsPage from '../SettingsPage.vue'

describe('SettingsPage mount test', () => {
  it('renders SettingsPage without throwing errors in en and am', () => {
    // Provide a valid localStorage mock
    const store: Record<string, string> = {}
    const localStorageMock = {
      getItem: (key: string) => store[key] || null,
      setItem: (key: string, value: string) => { store[key] = value },
      removeItem: (key: string) => { delete store[key] },
      clear: () => { for (const k in store) delete store[k] },
    }
    vi.stubGlobal('localStorage', localStorageMock)

    const pinia = createPinia()
    setActivePinia(pinia)
    const i18n = createI18n({
      legacy: false,
      locale: 'en',
      messages: { en, am }
    })

    const wrapper = mount(SettingsPage, {
      global: {
        plugins: [pinia, i18n],
        stubs: {
          RouterLink: true
        }
      }
    })

    expect(wrapper.exists()).toBe(true)
    expect(wrapper.text()).toContain('Settings')

    // Switch to am
    i18n.global.locale.value = 'am'
    const amWrapper = mount(SettingsPage, {
      global: {
        plugins: [pinia, i18n],
        stubs: {
          RouterLink: true
        }
      }
    })
    expect(amWrapper.exists()).toBe(true)
    expect(amWrapper.text()).toContain('ማስተካከያዎች')
  })
})
