import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

const API_BASE = '/api'

const setAuthHeader = (token) => {
  if (token) {
    axios.defaults.headers.common.Authorization = `Bearer ${token}`
  } else {
    delete axios.defaults.headers.common.Authorization
  }
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('token'))

  const isAuthenticated = computed(() => !!token.value)
  const isTrainer = computed(() => user.value?.role === 'trainer')

  setAuthHeader(token.value)

  async function login(email, password) {
    try {
      const response = await axios.post(`${API_BASE}/auth/login`, { email, password })
      const data = response.data
      const authToken = data.access_token || data.token

      token.value = authToken
      user.value = data.user ?? null
      
      localStorage.setItem('token', authToken)
      setAuthHeader(authToken)
      
      return { success: true }
    } catch (error) {
      return { success: false, error: error.response?.data?.message || error.message }
    }
  }

  function logout() {
    token.value = null
    user.value = null
    localStorage.removeItem('token')
    setAuthHeader(null)
  }

  async function fetchUser() {
    if (!token.value) return

    try {
      setAuthHeader(token.value)
      const response = await axios.get(`${API_BASE}/auth/me`)
      user.value = response.data.user ?? response.data
    } catch (error) {
      logout()
      throw error
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
