<template>
  <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
      <button @click="goBack" class="text-gray-600 hover:text-gray-900 mb-4 flex items-center">
        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        {{ $t('common.back') }}
      </button>
      <h1 class="text-2xl font-bold text-gray-900">{{ $t('grading.grade') }}</h1>
    </div>

    <div v-if="loading" class="text-center py-12">
      <p class="text-gray-500">{{ $t('common.loading') }}</p>
    </div>

    <div v-else class="space-y-6">
      <!-- Student Info -->
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex justify-between items-start">
          <div>
            <h2 class="text-lg font-medium text-gray-900">{{ submission.student?.name }}</h2>
            <p class="text-sm text-gray-500">{{ courseTitle }}</p>
            <p class="text-sm text-gray-500">{{ quizTitle }}</p>
          </div>
          <span class="px-3 py-1 text-sm rounded-full bg-yellow-100 text-yellow-800">{{ submission.status }}</span>
        </div>
      </div>

      <!-- Questions & Answers -->
      <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Answers</h2>
        <div class="space-y-6">
          <div v-for="(question, idx) in questions" :key="idx" class="border-b pb-4 last:border-0">
            <div class="flex justify-between items-start mb-2">
              <h3 class="font-medium text-gray-900">Question {{ idx + 1 }}</h3>
              <span class="text-xs px-2 py-1 rounded bg-gray-100 text-gray-700">{{ question.type }}</span>
            </div>
            <p class="text-gray-700 mb-3">{{ question.text }}</p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="bg-blue-50 p-4 rounded-lg">
                <h4 class="text-sm font-medium text-blue-800 mb-2">{{ $t('grading.studentAnswer') }}</h4>
                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ getStudentAnswer(question) }}</p>
              </div>
              <div class="bg-green-50 p-4 rounded-lg">
                <h4 class="text-sm font-medium text-green-800 mb-2">{{ $t('grading.modelAnswer') }}</h4>
                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ question.model_answer || 'N/A' }}</p>
              </div>
            </div>
            
            <div v-if="question.type === 'mcq'" class="mt-3">
              <p class="text-sm text-gray-600">
                Correct option: <span class="font-medium">{{ question.options?.[question.correct_answer] }}</span>
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Grading Form -->
      <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">{{ $t('grading.submitGrade') }}</h2>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('grading.score') }} (0-100)</label>
            <input v-model.number="score" type="number" min="0" max="100" class="w-32 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('grading.feedback') }}</label>
            <textarea v-model="feedback" rows="4" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2" placeholder="Provide feedback to the student..."></textarea>
          </div>
          <button @click="submitGrade" :disabled="submitting || score === null" class="w-full py-2 px-4 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50">
            {{ submitting ? $t('common.loading') : $t('grading.submitGrade') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const route = useRoute()

const loading = ref(true)
const submitting = ref(false)
const submission = ref({})
const score = ref(null)
const feedback = ref('')

const attemptType = ref(route.query.type || 'quiz')

const courseTitle = computed(() => {
  if (attemptType.value === 'quiz') {
    return submission.value.quiz?.chapter?.course?.title
  }
  return submission.value.final_exam?.course?.title
})

const quizTitle = computed(() => {
  if (attemptType.value === 'quiz') {
    return submission.value.quiz?.title_en
  }
  return submission.value.final_exam?.title_en
})

const questions = computed(() => {
  // This would normally come from the API with the attempt data
  // For now, we'll show what's available in the submission
  return submission.value.answers || []
})

const getStudentAnswer = (question) => {
  // This would be populated from the attempt's answers
  return 'Student answer would appear here'
}

const goBack = () => {
  router.push('/grading')
}

const submitGrade = async () => {
  submitting.value = true
  
  try {
    await axios.post(`/api/trainer/grade/${submission.value.id}`, {
      type: attemptType.value,
      score: score.value,
      feedback: feedback.value
    })
    
    alert('Grade submitted successfully!')
    router.push('/grading')
  } catch (error) {
    console.error('Failed to submit grade:', error)
    alert(error.response?.data?.message || 'Failed to submit grade')
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  try {
    // Fetch the specific attempt details
    // Note: You may need to add a dedicated endpoint for this
    const res = await axios.get('/api/trainer/grading-queue')
    const attempts = attemptType.value === 'quiz' 
      ? res.data.quiz_attempts || []
      : res.data.final_exam_attempts || []
    
    submission.value = attempts.find(a => a.id == route.params.attemptId) || {}
  } catch (error) {
    console.error('Failed to fetch submission:', error)
  } finally {
    loading.value = false
  }
})
</script>
