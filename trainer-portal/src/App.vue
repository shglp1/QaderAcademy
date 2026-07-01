<template>
  <div :dir="currentDir" :class="{ 'rtl': isRTL }" class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <router-link to="/" class="text-xl font-bold text-indigo-600">QaderAcademy Trainer</router-link>
            <div class="hidden md:flex ml-10 space-x-8 rtl:space-x-reverse">
              <router-link to="/" class="text-gray-700 hover:text-indigo-600">{{ $t('nav.dashboard') }}</router-link>
              <router-link to="/courses" class="text-gray-700 hover:text-indigo-600">{{ $t('nav.courses') }}</router-link>
              <router-link to="/grading" class="text-gray-700 hover:text-indigo-600">{{ $t('nav.grading') }}</router-link>
              <router-link to="/earnings" class="text-gray-700 hover:text-indigo-600">{{ $t('nav.earnings') }}</router-link>
              <router-link to="/qa" class="text-gray-700 hover:text-indigo-600">{{ $t('nav.qa') }}</router-link>
            </div>
          </div>
          <div class="flex items-center space-x-4 rtl:space-x-reverse">
            <button @click="toggleLocale" class="text-sm font-medium text-gray-600 hover:text-indigo-600">
              {{ i18nStore.locale === 'en' ? 'العربية' : 'English' }}
            </button>
            <template v-if="!authStore.isAuthenticated">
              <router-link to="/login" class="text-gray-700 hover:text-indigo-600">{{ $t('nav.logout') }}</router-link>
            </template>
            <template v-else>
              <span class="text-gray-700">{{ authStore.user?.name }}</span>
              <button @click="handleLogout" class="text-gray-700 hover:text-indigo-600">{{ $t('nav.logout') }}</button>
            </template>
          </div>
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <main class="py-6 flex-grow">
      <router-view />
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t mt-auto">
      <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <p class="text-center text-gray-500 text-sm">© 2025 QaderAcademy. All rights reserved.</p>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useI18nStore } from '@/stores/i18n'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const i18nStore = useI18nStore()
const router = useRouter()

const isRTL = computed(() => i18nStore.locale === 'ar')
const currentDir = computed(() => isRTL.value ? 'rtl' : 'ltr')

const toggleLocale = () => {
  i18nStore.toggleLocale()
}

onMounted(async () => {
  if (authStore.token && !authStore.user) {
    try {
      await authStore.fetchUser()
    } catch (error) {
      console.error('Failed to restore trainer session:', error)
    }
  }
})

const handleLogout = () => {
  authStore.logout()
  router.push({ name: 'Login' })
}
</script>

<style>
.rtl {
  direction: rtl;
  text-align: right;
}
</style>
