<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          {{ $t('auth.login') }}
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          QaderAcademy Trainer Portal
        </p>
      </div>
      <form class="mt-8 space-y-6" @submit.prevent="handleLogin">
        <div v-if="error" class="rounded-md bg-red-50 p-4">
          <p class="text-sm text-red-800">{{ error }}</p>
        </div>
        
        <div>
          <label for="email" class="block text-sm font-medium text-gray-700">
            {{ $t('auth.email') }}
          </label>
          <input
            id="email"
            v-model="email"
            type="email"
            required
            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            :placeholder="$t('auth.email')"
          />
        </div>
        
        <div>
          <label for="password" class="block text-sm font-medium text-gray-700">
            {{ $t('auth.password') }}
          </label>
          <input
            id="password"
            v-model="password"
            type="password"
            required
            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            :placeholder="$t('auth.password')"
          />
        </div>

        <div class="flex items-center">
          <input
            id="remember-me"
            v-model="rememberMe"
            type="checkbox"
            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
          />
          <label for="remember-me" class="ml-2 block text-sm text-gray-900">
            {{ $t('auth.rememberMe') }}
          </label>
        </div>

        <div>
          <button
            type="submit"
            :disabled="loading"
            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
          >
            <span v-if="loading">{{ $t('common.loading') }}</span>
            <span v-else>{{ $t('auth.signIn') }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useI18n } from 'vue-i18n'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const { t } = useI18n()

const email = ref('')
const password = ref('')
const rememberMe = ref(false)
const loading = ref(false)
const error = ref('')

const handleLogin = async () => {
  loading.value = true
  error.value = ''

  const result = await authStore.login(email.value, password.value)

  if (result.success) {
    await authStore.fetchUser()
    const redirect = route.query.redirect || '/'
    router.push(redirect)
  } else {
    error.value = result.error || t('auth.invalidCredentials')
  }

  loading.value = false
}
</script>
