import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('token'))

  const isAuthenticated = computed(() => !!token.value)
  const isTrainer = computed(() => user.value?.role === 'trainer')

  async function login(email, password) {
    try {
      const response = await fetch('/api/auth/login', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ email, password })
      })

      const data = await response.json()

      if (!response.ok) {
        throw new Error(data.message || 'Login failed')
      }

      token.value = data.token
      user.value = data.user
      
      localStorage.setItem('token', data.token)
      
      return { success: true }
    } catch (error) {
      return { success: false, error: error.message }
    }
  }

  function logout() {
    token.value = null
    user.value = null
    localStorage.removeItem('token')
  }

  async function fetchUser() {
    if (!token.value) return

    try {
      const response = await fetch('/api/user', {
        headers: {
          'Authorization': `Bearer ${token.value}`,
          'Accept': 'application/json'
        }
      })

      if (response.ok) {
        const data = await response.json()
        user.value = data
      } else {
        logout()
      }
    } catch (error) {
      console.error('Failed to fetch user:', error)
    }
  }

  return {
    user,
    token,
    isAuthenticated,
    isTrainer,
    login,
    logout,
    fetchUser
  }
})
