import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

const API_BASE = '/api'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('token') || null)

  const isAuthenticated = computed(() => !!token.value)
  const isStudent = computed(() => user.value?.role === 'student')

  if (token.value) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
  }

  async function login(email, password) {
    const response = await axios.post(`${API_BASE}/auth/login`, { email, password })
    token.value = response.data.token
    user.value = response.data.user
    
    localStorage.setItem('token', token.value)
    axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
    
    return response.data
  }

  async function register(userData) {
    const response = await axios.post(`${API_BASE}/auth/register`, userData)
    token.value = response.data.token
    user.value = response.data.user
    
    localStorage.setItem('token', token.value)
    axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
    
    return response.data
  }

  async function fetchUser() {
    try {
      const response = await axios.get(`${API_BASE}/auth/me`)
      user.value = response.data
      return response.data
    } catch (error) {
      logout()
      throw error
    }
  }

  function logout() {
    user.value = null
    token.value = null
    localStorage.removeItem('token')
    delete axios.defaults.headers.common['Authorization']
  }

  return {
    user,
    token,
    isAuthenticated,
    isStudent,
    login,
    register,
    fetchUser,
    logout
  }
})
