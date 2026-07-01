<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center px-4">
    <div class="max-w-md w-full bg-white rounded-lg shadow-sm p-8">
      <h1 class="text-2xl font-bold text-center mb-6">{{ $t('nav.login') }}</h1>

      <form @submit.prevent="handleLogin">
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('auth.email') }}</label>
          <input 
            v-model="email" 
            type="email" 
            required
            class="w-full border rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          />
        </div>

        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('auth.password') }}</label>
          <input 
            v-model="password" 
            type="password" 
            required
            class="w-full border rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          />
        </div>

        <button 
          type="submit" 
          :disabled="loading"
          class="w-full bg-indigo-600 text-white py-3 rounded-md font-semibold hover:bg-indigo-700 disabled:opacity-50"
        >
          {{ loading ? $t('common.loading') : $t('auth.loginButton') }}
        </button>
      </form>

      <p class="mt-4 text-center text-sm text-gray-600">
        {{ $t('auth.noAccount') }}
        <router-link to="/register" class="text-indigo-600 hover:text-indigo-800 font-medium">
          {{ $t('nav.register') }}
        </router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const loading = ref(false)

const handleLogin = async () => {
  loading.value = true
  try {
    await authStore.login(email.value, password.value)
    
    const redirect = route.query.redirect || '/profile'
    router.push(redirect)
  } catch (error) {
    alert(error.response?.data?.message || 'Login failed. Please try again.')
  } finally {
    loading.value = false
  }
}
</script>
