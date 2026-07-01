import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useI18nStore = defineStore('i18n', () => {
  const locale = ref(localStorage.getItem('locale') || 'en')

  const isRTL = computed(() => locale.value === 'ar')

  function setLocale(newLocale) {
    locale.value = newLocale
    localStorage.setItem('locale', newLocale)
    document.documentElement.lang = newLocale
    document.documentElement.dir = newLocale === 'ar' ? 'rtl' : 'ltr'
  }

  function toggleLocale() {
    setLocale(locale.value === 'en' ? 'ar' : 'en')
  }

  return {
    locale,
    isRTL,
    setLocale,
    toggleLocale
  }
})
