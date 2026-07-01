import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useI18nStore = defineStore('i18n', () => {
  const locale = ref(localStorage.getItem('locale') || 'en')

  const isRTL = computed(() => locale.value === 'ar')

  function setLocale(lang) {
    locale.value = lang
    localStorage.setItem('locale', lang)
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
