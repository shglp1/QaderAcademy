<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div v-if="loading" class="text-center py-12">
      <p class="text-gray-600">{{ $t('common.loading') }}</p>
    </div>

    <div v-else-if="course" class="grid md:grid-cols-3 gap-8">
      <!-- Main Content -->
      <div class="md:col-span-2">
        <!-- Intro Video -->
        <div v-if="course.intro_video" class="mb-6">
          <div class="video-container rounded-lg overflow-hidden">
            <video :src="course.intro_video" controls class="w-full"></video>
          </div>
        </div>

        <h1 class="text-3xl font-bold mb-4">{{ course.title }}</h1>
        
        <div class="flex items-center gap-4 mb-6 text-sm text-gray-600">
          <span>👨‍🏫 {{ course.trainer?.name }}</span>
          <span>⭐ {{ course.rating_avg || '0' }} ({{ course.ratings_count || 0 }} reviews)</span>
          <span>⏱️ {{ course.duration }} hours</span>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
          <h2 class="text-xl font-semibold mb-4">{{ $t('courseDetail.description') }}</h2>
          <p class="text-gray-700">{{ course.description }}</p>
        </div>

        <!-- Chapters -->
        <div class="bg-white rounded-lg shadow-sm p-6">
          <h2 class="text-xl font-semibold mb-4">{{ $t('courseDetail.chapters') }}</h2>
          <div class="space-y-4">
            <div v-for="chapter in course.chapters" :key="chapter.id" class="border-b pb-4 last:border-0">
              <h3 class="font-medium mb-2">{{ chapter.title }}</h3>
              <div class="flex items-center gap-4 text-sm text-gray-600">
                <span>🎥 {{ chapter.videos?.length || 0 }} videos</span>
                <span>⏱️ {{ chapter.total_duration }} min</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="md:col-span-1">
        <div class="bg-white rounded-lg shadow-sm p-6 sticky top-4">
          <div class="text-2xl font-bold text-indigo-600 mb-4">${{ course.price }}</div>
          
          <button 
            v-if="!isEnrolled"
            @click="enrollNow" 
            class="w-full bg-indigo-600 text-white py-3 rounded-md font-semibold hover:bg-indigo-700 mb-4"
          >
            {{ $t('courseDetail.enrollNow') }}
          </button>
          
          <router-link 
            v-else-if="enrollment"
            :to="`/learn/${enrollment.id}`"
            class="block w-full bg-green-600 text-white text-center py-3 rounded-md font-semibold hover:bg-green-700 mb-4"
          >
            {{ $t('courseDetail.enrolled') }} - Start Learning
          </router-link>

          <div class="space-y-3 text-sm">
            <div class="flex items-center gap-2">
              <span>✓</span>
              <span>{{ course.chapters?.length || 0 }} chapters</span>
            </div>
            <div class="flex items-center gap-2">
              <span>✓</span>
              <span>Lifetime access</span>
            </div>
            <div class="flex items-center gap-2">
              <span>✓</span>
              <span>Certificate of completion</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

const route = useRoute()
const authStore = useAuthStore()

const course = ref(null)
const loading = ref(true)
const enrollment = ref(null)

const isEnrolled = computed(() => !!enrollment.value && enrollment.value.status === 'active')

const fetchCourse = async () => {
  try {
    const response = await axios.get(`/api/courses/${route.params.id}`)
    course.value = response.data.data || response.data
  } catch (error) {
    console.error('Error fetching course:', error)
  } finally {
    loading.value = false
  }
}

const checkEnrollment = async () => {
  if (!authStore.isAuthenticated) return
  
  try {
    const response = await axios.get('/api/enrollments/me')
    const enrollments = response.data.data || response.data
    enrollment.value = enrollments.find(e => e.course_id === parseInt(route.params.id))
  } catch (error) {
    console.error('Error checking enrollment:', error)
  }
}

const enrollNow = async () => {
  if (!authStore.isAuthenticated) {
    window.location.href = '/login?redirect=' + encodeURIComponent(window.location.pathname)
    return
  }

  try {
    const response = await axios.post('/api/enrollments', { course_id: course.value.id })
    // Redirect to checkout with MyFatoorah
    window.location.href = `/checkout/${response.data.data.id}`
  } catch (error) {
    console.error('Error creating enrollment:', error)
    alert('Failed to enroll. Please try again.')
  }
}

onMounted(() => {
  fetchCourse()
  checkEnrollment()
})
</script>
