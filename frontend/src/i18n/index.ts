import { createI18n } from 'vue-i18n'
import en from './locales/en'
import am from './locales/am'

export type SupportedLocale = 'en' | 'am'

export const SUPPORTED_LOCALES: { code: SupportedLocale; name: string; flag: string }[] = [
  { code: 'en', name: 'English', flag: 'EN' },
  { code: 'am', name: 'አማርኛ', flag: 'አማ' }
]

const savedLocale = (localStorage.getItem('app_locale') as SupportedLocale) || 'en'
const initialLocale: SupportedLocale = SUPPORTED_LOCALES.some(l => l.code === savedLocale) ? savedLocale : 'en'

export const i18n = createI18n({
  legacy: false, // Composition API mode
  locale: initialLocale,
  fallbackLocale: 'en',
  messages: {
    en,
    am
  }
})

export function setAppLocale(locale: SupportedLocale): void {
  if (SUPPORTED_LOCALES.some(l => l.code === locale)) {
    if (typeof (i18n.global.locale as any) === 'object' && 'value' in (i18n.global.locale as any)) {
      (i18n.global.locale as any).value = locale
    } else {
      (i18n.global as any).locale = locale
    }
    localStorage.setItem('app_locale', locale)
    document.documentElement.lang = locale
  }
}

export default i18n
