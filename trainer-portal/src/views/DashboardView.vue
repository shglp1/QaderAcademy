<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-900">{{ $t('dashboard.title') }}</h1>
      <p class="mt-2 text-gray-600">{{ $t('dashboard.welcome', { name: authStore.user?.name }) }}</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
              </svg>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">{{ $t('dashboard.stats.totalCourses') }}</dt>
                <dd class="text-lg font-semibold text-gray-900">{{ stats.totalCourses }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">{{ $t('dashboard.stats.totalStudents') }}</dt>
                <dd class="text-lg font-semibold text-gray-900">{{ stats.totalStudents }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">{{ $t('dashboard.stats.totalEarnings') }}</dt>
                <dd class="text-lg font-semibold text-gray-900">${{ stats.totalEarnings }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
              </svg>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">{{ $t('dashboard.stats.pendingGrading') }}</dt>
                <dd class="text-lg font-semibold text-gray-900">{{ stats.pendingGrading }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white shadow rounded-lg mb-8">
      <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-medium text-gray-900">Quick Actions</h2>
      </div>
      <div class="p-6">
        <div class="flex flex-wrap gap-4">
          <router-link to="/courses/create" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
            {{ $t('nav.createCourse') }}
          </router-link>
          <router-link to="/grading" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            {{ $t('nav.grading') }}
            <span v-if="stats.pendingGrading > 0" class="ml-2 bg-red-500 text-white text-xs rounded-full px-2 py-0.5">{{ stats.pendingGrading }}</span>
          </router-link>
          <router-link to="/earnings" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            {{ $t('nav.earnings') }}
          </router-link>
        </div>
      </div>
    </div>

    <!-- Recent Courses -->
    <div class="bg-white shadow rounded-lg">
      <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
        <h2 class="text-lg font-medium text-gray-900">{{ $t('nav.courses') }}</h2>
        <router-link to="/courses" class="text-sm text-indigo-600 hover:text-indigo-500">View All</router-link>
      </div>
      <div class="p-6">
        <div v-if="loading" class="text-center py-8">
          <p class="text-gray-500">{{ $t('common.loading') }}</p>
        </div>
        <div v-else-if="courses.length === 0" class="text-center py-8">
          <p class="text-gray-500">{{ $t('common.noData') }}</p>
          <router-link to="/courses/create" class="mt-4 inline-block text-indigo-600 hover:text-indigo-500">{{ $t('nav.createCourse') }}</router-link>
        </div>
        <div v-else class="space-y-4">
          <div v-for="course in courses" :key="course.id" class="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50">
            <div>
              <h3 class="font-medium text-gray-900">{{ course.title }}</h3>
              <p class="text-sm text-gray-500">{{ course.enrollments_count || 0 }} students enrolled</p>
            </div>
            <div class="flex items-center space-x-2">
              <span :class="statusClass(course.status)" class="px-2 py-1 text-xs rounded-full">
                {{ course.status }}
              </span>
              <router-link :to="`/courses/${course.id}/edit`" class="text-indigo-600 hover:text-indigo-500 text-sm">Edit</router-link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

const authStore = useAuthStore()
const stats = ref({
  totalCourses: 0,
  totalStudents: 0,
  totalEarnings: 0,
  pendingGrading: 0
})
const courses = ref([])
const loading = ref(true)

const statusClass = (status) => {
  const classes = {
    draft: 'bg-gray-100 text-gray-800',
    pending: 'bg-yellow-100 text-yellow-800',
    published: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const fetchDashboardData = async () => {
  try {
    const [coursesRes, earningsRes, gradingRes] = await Promise.all([
      axios.get('/api/trainer/courses'),
      axios.get('/api/trainer/earnings'),
      axios.get('/api/trainer/grading-queue')
    ])

    courses.value = coursesRes.data.courses?.data || []
    stats.value.totalCourses = courses.value.length
    
    if (earningsRes.data.summary) {
      stats.value.totalEarnings = earningsRes.data.summary.total_earned || 0
    }
    
    stats.value.pendingGrading = gradingRes.data.total_pending || 0
  } catch (error) {
    console.error('Failed to fetch dashboard data:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchDashboardData()
})
</script>
