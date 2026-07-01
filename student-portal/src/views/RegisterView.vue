<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center px-4 py-8">
    <div class="max-w-md w-full bg-white rounded-lg shadow-sm p-8">
      <h1 class="text-2xl font-bold text-center mb-6">{{ $t('nav.register') }}</h1>

      <form @submit.prevent="handleRegister">
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('auth.name') }}</label>
          <input 
            v-model="name" 
            type="text" 
            required
            class="w-full border rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          />
        </div>

        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('auth.email') }}</label>
          <input 
            v-model="email" 
            type="email" 
            required
            class="w-full border rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          />
        </div>

        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('auth.password') }}</label>
          <input 
            v-model="password" 
            type="password" 
            required
            minlength="8"
            class="w-full border rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          />
        </div>

        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('auth.confirmPassword') }}</label>
          <input 
            v-model="passwordConfirmation" 
            type="password" 
            required
            minlength="8"
            class="w-full border rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          />
        </div>

        <button 
          type="submit" 
          :disabled="loading || passwordsMismatch"
          class="w-full bg-indigo-600 text-white py-3 rounded-md font-semibold hover:bg-indigo-700 disabled:opacity-50"
        >
          {{ loading ? $t('common.loading') : $t('auth.registerButton') }}
        </button>
      </form>

      <p class="mt-4 text-center text-sm text-gray-600">
        {{ $t('auth.hasAccount') }}
        <router-link to="/login" class="text-indigo-600 hover:text-indigo-800 font-medium">
          {{ $t('nav.login') }}
        </router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const loading = ref(false)

const passwordsMismatch = computed(() => 
  password.value !== passwordConfirmation.value
)

const handleRegister = async () => {
  if (passwordsMismatch.value) {
    alert('Passwords do not match')
    return
  }

  loading.value = true
  try {
    await authStore.register({
      name: name.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value
    })
    
    router.push('/profile')
  } catch (error) {
    const errors = error.response?.data?.errors
    if (errors) {
      alert(Object.values(errors).flat().join('\n'))
    } else {
      alert(error.response?.data?.message || 'Registration failed. Please try again.')
    }
  } finally {
    loading.value = false
  }
}
</script>
