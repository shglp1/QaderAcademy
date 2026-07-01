<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold mb-6">{{ $t('nav.courses') }}</h1>

    <!-- Search and Filters -->
    <div class="bg-white p-4 rounded-lg shadow-sm mb-6">
      <div class="grid md:grid-cols-5 gap-4">
        <input 
          v-model="searchQuery"
          type="text" 
          :placeholder="$t('courses.searchPlaceholder')"
          class="md:col-span-2 border rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          @input="fetchCourses"
        />
        <select v-model="filters.category" @change="fetchCourses" class="border rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
          <option value="">{{ $t('courses.filters.all') }}</option>
          <option value="university">{{ $t('courses.categories.university') }}</option>
          <option value="soft_skills">{{ $t('courses.categories.softSkills') }}</option>
          <option value="professional">{{ $t('courses.categories.professional') }}</option>
        </select>
        <select v-model="filters.year" @change="fetchCourses" class="border rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
          <option value="">{{ $t('courses.filters.year') }}</option>
          <option value="1">1</option>
          <option value="2">2</option>
          <option value="3">3</option>
          <option value="4">4</option>
        </select>
        <select v-model="filters.semester" @change="fetchCourses" class="border rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
          <option value="">{{ $t('courses.filters.semester') }}</option>
          <option value="first">{{ $t('courses.filters.first') }}</option>
          <option value="second">{{ $t('courses.filters.second') }}</option>
        </select>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-12">
      <p class="text-gray-600">{{ $t('common.loading') }}</p>
    </div>

    <!-- Course Grid -->
    <div v-else-if="courses.length > 0" class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div v-for="course in courses" :key="course.id" class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow">
        <div class="h-48 bg-gradient-to-r from-indigo-500 to-purple-500 flex items-center justify-center">
          <span class="text-white text-4xl">📚</span>
        </div>
        <div class="p-4">
          <h3 class="font-semibold text-lg mb-2">{{ course.title }}</h3>
          <p class="text-gray-600 text-sm mb-3 line-clamp-2">{{ course.description }}</p>
          <div class="flex justify-between items-center mb-3">
            <span class="text-sm text-gray-500">{{ course.trainer?.name }}</span>
            <div class="flex items-center text-yellow-500">
              <span>⭐</span>
              <span class="ml-1 text-sm">{{ course.rating_avg || '0' }}</span>
            </div>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-indigo-600 font-bold">${{ course.price }}</span>
            <router-link :to="`/courses/${course.id}`" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
              View Details →
            </router-link>
          </div>
        </div>
      </div>
    </div>

    <!-- No Results -->
    <div v-else class="text-center py-12">
      <p class="text-gray-600">{{ $t('courses.noResults') }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const courses = ref([])
const loading = ref(false)
const searchQuery = ref('')
const filters = ref({
  category: '',
  year: '',
  semester: ''
})

const fetchCourses = async () => {
  loading.value = true
  try {
    const params = {}
    if (filters.value.category) params.category = filters.value.category
    if (filters.value.year) params.year = filters.value.year
    if (filters.value.semester) params.semester = filters.value.semester
    if (searchQuery.value) params.search = searchQuery.value

    const response = await axios.get('/api/student/courses', { params })
    courses.value = response.data.data || []
  } catch (error) {
    console.error('Error fetching courses:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchCourses()
})
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
