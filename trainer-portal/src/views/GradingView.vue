<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-900">{{ $t('grading.title') }}</h1>
    </div>

    <div v-if="loading" class="text-center py-12">
      <p class="text-gray-500">{{ $t('common.loading') }}</p>
    </div>

    <div v-else-if="submissions.length === 0" class="text-center py-12 bg-white rounded-lg shadow">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $t('grading.noPendingSubmissions') }}</h3>
    </div>

    <div v-else class="bg-white shadow rounded-lg overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('grading.studentName') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('grading.course') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('grading.quiz') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('grading.submittedAt') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('grading.status') }}</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('common.actions') }}</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="submission in submissions" :key="submission.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ submission.student?.name }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ submission.quiz?.chapter?.course?.title || submission.final_exam?.course?.title }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ submission.quiz?.title_en || submission.final_exam?.title_en }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(submission.created_at) }}</td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">{{ submission.status }}</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <router-link :to="`/grading/${submission.id}?type=${submission.type}`" class="text-indigo-600 hover:text-indigo-900">
                {{ $t('grading.viewSubmission') }}
              </router-link>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const submissions = ref([])
const loading = ref(true)

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString()
}

const fetchSubmissions = async () => {
  try {
    const res = await axios.get('/api/trainer/grading-queue')
    const quizAttempts = (res.data.quiz_attempts || []).map(a => ({ ...a, type: 'quiz' }))
    const finalExamAttempts = (res.data.final_exam_attempts || []).map(a => ({ ...a, type: 'final_exam' }))
    submissions.value = [...quizAttempts, ...finalExamAttempts]
  } catch (error) {
    console.error('Failed to fetch grading queue:', error)
  } finally {
    loading.value = false
  }
}

onMounted(fetchSubmissions)
</script>
