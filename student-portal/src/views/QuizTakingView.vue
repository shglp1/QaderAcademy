<template>
  <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold mb-6">{{ $t('quiz.results') }}</h1>

    <div v-if="loading" class="text-center py-12">
      <p class="text-gray-600">{{ $t('common.loading') }}</p>
    </div>

    <div v-else-if="quiz" class="bg-white rounded-lg shadow-sm p-6">
      <h2 class="text-xl font-semibold mb-6">{{ quiz.title }}</h2>

      <div class="space-y-6">
        <div v-for="(question, index) in quiz.questions" :key="question.id" class="border-b pb-6 last:border-0">
          <div class="flex items-start gap-4 mb-4">
            <span class="bg-indigo-100 text-indigo-800 w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0">
              {{ index + 1 }}
            </span>
            <div class="flex-grow">
              <p class="font-medium mb-3">{{ question.question_text }}</p>

              <div v-if="question.type === 'mcq'" class="space-y-2">
                <label
                  v-for="option in question.options"
                  :key="option.id"
                  :class="['flex items-center gap-2 p-3 border rounded-md cursor-pointer', getOptionClass(question, option)]"
                >
                  <input
                    type="radio"
                    :name="`q_${question.id}`"
                    :value="option.id"
                    v-model="answers[question.id]"
                    :disabled="submitted"
                    class="accent-indigo-600"
                  />
                  <span>{{ option.text }}</span>
                </label>
              </div>

              <textarea
                v-else
                v-model="answers[question.id]"
                :disabled="submitted"
                placeholder="Type your answer here..."
                class="w-full border rounded-md px-4 py-2 h-32 focus:outline-none focus:ring-2 focus:ring-indigo-500"
              ></textarea>

              <div
                v-if="feedbackByQuestion[question.id]"
                :class="['mt-3 p-3 rounded-md', feedbackByQuestion[question.id].is_correct ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800']"
              >
                <p v-if="feedbackByQuestion[question.id].feedback">
                  <strong>{{ $t('quiz.feedback') }}:</strong> {{ feedbackByQuestion[question.id].feedback }}
                </p>
                <p v-if="feedbackByQuestion[question.id].model_answer">
                  <strong>Correct answer:</strong> {{ feedbackByQuestion[question.id].model_answer }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-if="!submitted" class="mt-6 pt-6 border-t">
        <button
          @click="submitQuiz"
          :disabled="!canSubmit || submitting"
          class="w-full bg-indigo-600 text-white py-3 rounded-md font-semibold hover:bg-indigo-700 disabled:opacity-50"
        >
          {{ submitting ? $t('common.loading') : $t('quiz.submit') }}
        </button>
      </div>

      <div v-if="submitted && score !== null" class="mt-6 pt-6 border-t text-center">
        <div class="text-3xl font-bold mb-2" :class="score >= 50 ? 'text-green-600' : 'text-red-600'">
          {{ score }}%
        </div>
        <p class="text-gray-600">{{ $t('quiz.score') }}</p>
      </div>

      <div v-if="submitted && score === null" class="mt-6 pt-6 border-t text-center text-gray-600">
        Quiz submitted and is awaiting grading.
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

const route = useRoute()
const router = useRouter()

const quiz = ref(null)
const loading = ref(true)
const answers = ref({})
const submitted = ref(false)
const submitting = ref(false)
const score = ref(null)
const feedbackByQuestion = ref({})

const canSubmit = computed(() => {
  if (!quiz.value) return false

  return quiz.value.questions.every((question) => {
    const answer = answers.value[question.id]
    return answer !== undefined && answer !== null && answer !== ''
  })
})

const getOptionClass = (question, option) => {
  if (!submitted.value) return 'hover:bg-gray-50'

  const feedback = feedbackByQuestion.value[question.id]
  if (!feedback) return 'border-gray-200'

  if (option.text === feedback.model_answer) {
    return 'bg-green-50 border-green-300 text-green-800'
  }

  if (option.id === answers.value[question.id] && feedback.model_answer && option.text !== feedback.model_answer) {
    return 'bg-red-100 border-red-500 text-red-800'
  }

  return 'border-gray-200'
}

const fetchQuiz = async () => {
  try {
    const response = await axios.get(`/api/student/quizzes/${route.params.quizId}`)
    quiz.value = response.data.quiz || response.data.data || response.data

    quiz.value.questions.forEach((question) => {
      answers.value[question.id] = question.type === 'mcq' ? null : ''
    })
  } catch (error) {
    console.error('Error fetching quiz:', error)
    alert('Failed to load quiz')
    router.push('/profile')
  } finally {
    loading.value = false
  }
}

const submitQuiz = async () => {
  if (!canSubmit.value) return

  submitting.value = true
  try {
    const formattedAnswers = quiz.value.questions.map((question) => {
      if (question.type === 'mcq') {
        const selectedOption = question.options.find((option) => option.id === answers.value[question.id])
        return {
          question_id: question.id,
          answer: selectedOption?.text || ''
        }
      }

      return {
        question_id: question.id,
        answer: answers.value[question.id]
      }
    })

    const response = await axios.post('/api/student/quiz-attempts', {
      quiz_id: quiz.value.id,
      answers: formattedAnswers
    })

    submitted.value = true
    score.value = response.data.percentage ?? null

    const attemptAnswers = response.data.attempt?.answers || []
    feedbackByQuestion.value = attemptAnswers.reduce((accumulator, answer) => {
      accumulator[answer.question_id] = answer
      return accumulator
    }, {})
  } catch (error) {
    console.error('Error submitting quiz:', error)
    alert('Failed to submit quiz')
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  fetchQuiz()
})
</script>
