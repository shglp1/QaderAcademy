<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8 flex justify-between items-center">
      <h1 class="text-2xl font-bold text-gray-900">{{ $t('nav.courses') }}</h1>
      <router-link to="/courses/create" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
        {{ $t('nav.createCourse') }}
      </router-link>
    </div>

    <div v-if="loading" class="text-center py-12">
      <p class="text-gray-500">{{ $t('common.loading') }}</p>
    </div>

    <div v-else-if="courses.length === 0" class="text-center py-12 bg-white rounded-lg shadow">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">No courses yet</h3>
      <p class="mt-1 text-sm text-gray-500">Get started by creating your first course.</p>
      <div class="mt-6">
        <router-link to="/courses/create" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
          {{ $t('nav.createCourse') }}
        </router-link>
      </div>
    </div>

    <div v-else class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
      <div v-for="course in courses" :key="course.id" class="bg-white shadow rounded-lg overflow-hidden">
        <div class="p-6">
          <div class="flex justify-between items-start mb-4">
            <h3 class="text-lg font-medium text-gray-900">{{ course.title }}</h3>
            <span :class="statusClass(course.status)" class="px-2 py-1 text-xs rounded-full">
              {{ course.status }}
            </span>
          </div>
          <p class="text-sm text-gray-500 mb-4">{{ truncate(course.description, 100) }}</p>
          <div class="flex justify-between items-center text-sm text-gray-500 mb-4">
            <span>{{ course.enrollments_count || 0 }} students</span>
            <span>${{ course.price }}</span>
          </div>
          <div class="flex gap-2">
            <router-link :to="`/courses/${course.id}/edit`" class="flex-1 text-center px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 text-sm">
              Edit
            </router-link>
            <button @click="deleteCourse(course.id)" class="px-4 py-2 border border-red-300 rounded-md text-red-700 hover:bg-red-50 text-sm">
              Delete
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

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

const truncate = (text, length) => {
  if (!text) return ''
  return text.length > length ? text.substring(0, length) + '...' : text
}

const fetchCourses = async () => {
  try {
    const res = await axios.get('/api/trainer/courses')
    courses.value = res.data.courses?.data || []
  } catch (error) {
    console.error('Failed to fetch courses:', error)
  } finally {
    loading.value = false
  }
}

const deleteCourse = async (id) => {
  if (!confirm('Are you sure you want to delete this course?')) return
  
  try {
    await axios.delete(`/api/trainer/courses/${id}`)
    courses.value = courses.value.filter(c => c.id !== id)
  } catch (error) {
    console.error('Failed to delete course:', error)
    alert('Failed to delete course')
  }
}

onMounted(fetchCourses)
</script>
